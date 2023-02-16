<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

require __DIR__ . '/vendor/autoload.php';

use BenMajor\ImageResize\Image;
use BenMajor\ImageResize\Watermark;

?>
<?php $config = parse_ini_file('admin/config.ini');  ?>
<?php

initdir(getconfig('imagepath') . '/tmp');
initdir(getconfig('imagepath') . '/original');
initdir(getconfig('imagepath') . '/show');
initdir(getconfig('imagepath') . '/thumb');

$btnClass = "btn btn-warning btn-lg";
$content = '';
if(isset($_GET['capture'])) {
    $result = runcmd('capture');
    //print_r($result);exit;
    $image = getCameraImage();
    $error = true;
    if($image) {
        $content = $result;
        $error = false;
    }
    else {
        $content = $result;
    }
    header('Content-type: application/json');
    echo json_encode(compact('error', 'content'));
    exit;
}
if(isset($_GET['prepare'])) {
    $image = getCameraImage();
    $error = true;
    if($image) {
        $imageName =  date("Y-m-d_H-i-s", time()) . '.jpg';
        $originalImage = getconfig('imagepath') . '/original/' . $imageName;
        $showImage = getconfig('imagepath') . '/show/' . $imageName;
        //rename($image, $originalImage);
        if(copy($image, $originalImage)) {
            unlink($image);
        }

        // converting
        if(convert($originalImage, $showImage, getconfig('photo_size'), getconfig('watermark_size'))) {
            $content = $imageName;
            $error = false;
        }
        else {
            $content = 'Bildbearbeitung-Fehler';
        }
    }
    else {
        $content = 'Kamera-Fehler';
    }

    header('Content-type: application/json');
    echo json_encode(compact('error', 'content'));
    exit;
}
if(isset($_GET['upload'])) {
    $image = $_GET['src'];

    $result = runcmd('bash /home/pi/Dropbox-Uploader/dropbox_uploader.sh -s -f /home/pi/.dropbox_uploader upload /var/www/photobooth/web/images/show/' . $image . ' images/' . $image , true);
    //echo $result;
    $result = runcmd('bash /home/pi/Dropbox-Uploader/dropbox_uploader.sh -s -f /home/pi/.dropbox_uploader share images/' . $image , true);
    echo str_replace('?dl=0', '?raw=1', 'http' . substr($result, strpos($result, "http") + 4));
    exit;
}
if(isset($_GET['flashdown'])) {
    $result = runcmd('flashdown');
    echo $result;
    exit;
}
if(isset($_GET['flashup'])) {
    $result = runcmd('flashup');
    echo $result;
    exit;
}
function runcmd($cmd, $full = false) {
    if($cmd == 'flashup_preview' || $cmd == 'flashup') {
        $brightness = getconfig('flash_brightness');
        if($cmd == 'flashup_preview' ) {
            $brightness = getconfig('flash_brightness_preview');
        } 

        $cmd = 'ola_streaming_client -d ' . $brightness . ',' . getconfig('flash_color_r') . ',' . getconfig('flash_color_g') . ',' . getconfig('flash_color_b') . ' 1';
        $full = true;
    }
    else if ($cmd == 'capture') {
        $cmd = 'cd images/tmp; gphoto2 --capture-image-and-download --set-config capturetarget=1';
        $full = true;
    }

    if($full) {
        $result = shell_exec($cmd . ' 2>&1');
    }
    else {
        $result = shell_exec('sh ./cmd/' . $cmd . '.sh 2>&1');
    }
    if(strpos($result, '***') !== false) {
        $result = substr($result, 0, strpos($result, "For debugging") - 13);
    }

    exec('echo "' . $result . '" >> ./log/cmd.log');
    return $result;
}
function initdir($path) {
    if(!file_exists($path)) {
        return mkdir($path);
    }
}
function convert($oImage, $sImage, $pSize, $wmSize) {

    $image = new Image($oImage);

    $watermark = new Watermark('./app/watermark2.png');
    $watermark->setPosition('bl');
    $watermark->setWidth($wmSize);
    //$watermark->setMargin(100 );

    $image->addWatermark( $watermark );
    $image->resizeWidth($pSize);
    $image->output('./images/show/');
    // thumb
    $thumb = new Image( $sImage);
    $thumb->resizeWidth(1200);
    $thumb->output('./images/thumb/');
    return true;
}
function webUrl($base, $image) {
    return $base . $image;
}
function getconfig($attribute) {
    global $config;
    return $config[$attribute];
}
function getCameraImage() {
    foreach(glob(getconfig('imagepath') . '/tmp/*.[jJ][pP][gG]') as $image) {
        break;
    }
    return $image;
}
?>
<!doctype html>
<html lang="de">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="app/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <title>Foto-Box</title>
    <style>
        @font-face {
            font-family: 'Boogaloo';
            font-style: normal;
            font-weight: 400;
            src: url(./app/Boogaloo-Regular.ttf) format("truetype");
        }
        html {
            font-size: <?= getconfig('font_size'); ?>px;
            height: 100%;
        }
        body {
            font-family: 'Boogaloo', sans-serif;
            background: black;
            color: white;
            height: 100%;
        }
        video {
        -webkit-transform: scaleX(-1);
        transform: scaleX(-1);
        }
        #camPreview {
            position: fixed;
            object-fit: cover;
            width: <?= getconfig('preview_size'); ?>;
            height: <?= getconfig('preview_size'); ?>;
            left: <?= getconfig('preview_left'); ?>;
            top: <?= getconfig('preview_top'); ?>;
        }
        .bottom-bar {
            position: fixed;
            bottom: 0.8em;
        }
        #qrcode {
            display: none;
            position: fixed;
            right: 0.8em;
            bottom: 0.8em;
            border: 0.2em white solid;
        }
        #home {
            position: absolute;
            bottom: 0.5em;
            right: 0.5em;
        }
        #photo {

            widht: 100%;
            height: 100%;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
    </style>
</head>
<body>
<script src="app/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
<script src="app/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="app/qrcode.min.js" crossorigin="anonymous"></script>
<?php if(empty($_GET)) : ?>
    <?php $result = runcmd('flashdown'); ?>
    <div style="height:100%" class="d-flex align-items-center">
        <div class="container text-center">
            <h1><?= getconfig('title'); ?></h1>
            <p><?= getconfig('description'); ?></p>
            <a class="<?= $btnClass ?>" href="?take"><?= getconfig('text_to_preview') ?></a>
        </div>
    </div>
<?php elseif(isset($_GET['take'])) : ?>
    <meta http-equiv="refresh" content="<?= getconfig('refresh_take'); ?>; URL=/">
    <?php
    $result = runcmd('flashup_preview');
    ?><div style="height:100%" class="d-flex align-items-center">
        <div id="preview-wait" class="container text-center"><?= getconfig('text_loading_preview') ?></div>
        <div id="prepare-wait" style="font-size: 200%; display: none;" class="container text-center"><?= getconfig('text_wait'); ?></div>
    </div>
    <video autoplay="true" id="camPreview"></video>
        <div class="container bottom-bar">
        <?php if($_GET['error']) : ?>
            <div class="error"><?= getconfig('text_error') ?></div>
            <div style="font-size:50%"><?= $_GET['error']; ?></div>
            <?php endif; ?>

            <a class="<?= $btnClass ?>" href="#" onclick="javascript:take();$('.error').hide();void(0)" id="countdown"><?= getconfig('text_take_photo') ?></a>
        </div>
    <script>
        $( document ).ready(function() {
            var video = document.querySelector("#camPreview");

            if (navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function (stream) {
                        video.srcObject = stream;
                    })
                    .catch(function (err0r) {
                        console.log("Fehler bei der Kamera-Vorschau!");
                    });
            }
        });
    </script>

<?php elseif(isset($_GET['photo'])) :  ?>
    <meta http-equiv="refresh" content="<?= getconfig('refresh_photo'); ?>; URL=/">
    <div id="photo" style="background-image:url('/images/thumb/<?= $_GET['src'] ?>');" ></div>
    <div class="container bottom-bar">
        <a class="<?= $btnClass ?>" href="?take">Neues Foto</a>&nbsp;
        <a id="qrbutton" class="<?= $btnClass ?>" href="#" onclick="javascript:qrToggle();void(0);">QR-Code</a>
        <div id="qrcode"></div>
    </div>
    <script>
        $( document ).ready(function() {
            setTimeout(flashdown, <?= getconfig('flashdown'); ?> * 1000);
            
            //$('#qrbutton').hide();
            //upload();
            qrCode('dummy');
        });
    </script>
<?php endif; ?>

<a id="home" href="/" class="btn">-</a>
<script>
    function take() {
        let countdown = <?= getconfig('countdown'); ?>;
        var counter = countdown;
        var countdownEl = $('#countdown');
        countdownEl.text(counter);
        var interval = setInterval(function() {
            counter--;
            countdownEl.text(counter);
            if (counter <= 0) {
                clearInterval(interval);
                // take photo
                countdownEl.text('<?= getconfig('text_cheese') ?>');
                capture();
            }
        }, 1000);
    }
    function capture() {
        flashup();
        $.ajax({
            url: "?capture",
        }).done(function(data) {
            console.log(data);
            if(data.error) {
                console.log('ERROR', data.content);
                location.href = '?take&error=' + data.content;
            }
            else {
                prepare();
            }
        });
    }
    function prepare() {
        $('#countdown').text('<?= getconfig('text_processing') ?>');
        $('#camPreview').hide();
        $('#preview-wait').hide();
        $('#prepare-wait').show();
        $.ajax({
            url: "?prepare",
        }).done(function(data) {
            console.log(data);
            if(data.error) {
                console.log('ERROR', data.content);
                location.href = '?take&error=' + data.content;
            }
            else {
                location.href = '?photo&src=' + data.content;
            }
        });
    }
    function flashdown() {
        $.ajax({
            url: "?flashdown",
        }).done(function(data) {
            console.log(data);
        });
        //location.href = '?photo';
    }
    function qrCode(link) {
        var qrEl = $('#qrcode');
        if(qrEl.data('generated') != 1) {
            var qrcode = new QRCode(document.getElementById("qrcode"), {
                /*text: link,*/
                text: '<?= webUrl(getconfig('web_url_base'), $_GET['src']); ?>',
                width : 300,
                height : 300,
                useSVG: true
            });
            qrEl.data('generated', 1);
            $('#qrbutton').show();
            console.log('qr', '<?= webUrl(getconfig('web_url_base'), $_GET['src']); ?>');
        }
    }

    function qrToggle() {
        $('#qrcode').toggle();
    }

    function upload() {
        $.ajax({
            url: "?upload&src=<?= $_GET['src']; ?>",
        }).done(function(data) {
            console.log(data);
            qrCode(data);
        });
        //location.href = '?photo';
    }

    function flashup() {
        $.ajax({
            url: "?flashup",
        }).done(function(data) {
            console.log(data);
        });
    }

    function flashdown() {
        $.ajax({
            url: "?flashdown",
        }).done(function(data) {
            console.log(data);
        });
    }
</script>
</body>
</html>
