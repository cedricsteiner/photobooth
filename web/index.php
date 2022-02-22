<?php
require __DIR__ . '/vendor/autoload.php';

use BenMajor\ImageResize\Image;
use BenMajor\ImageResize\Watermark;

?>
<?php $config = parse_ini_file('admin/config.ini');  ?>
<?php

initdir(getconfig('imagepath') . '/tmp');
initdir(getconfig('imagepath') . '/original');
initdir(getconfig('imagepath') . '/show');

$btnClass = "btn btn-warning btn-lg";

if(isset($_GET['capture'])) {
    $result = runcmd('capture');
    echo $result;
    exit;
}
if(isset($_GET['prepare'])) {
    foreach(glob(getconfig('imagepath') . '/tmp/*.jpg') as $image) {
        break;
    }
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
            echo '/images/show/' . $imageName;
        }
        else {
            echo 'Fehler bei der Bildbearbeitung';
        }
    }
    else {
        echo 'Kein Bild gefunden';
    }
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
function runcmd($cmd, $full = false) {
    if($full) {
        $result = exec($cmd . ' 2>&1');
    }
    else {
        $result = exec('sh ./cmd/' . $cmd . '.sh 2>&1');
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
    return true;
}
function webUrl($base, $image) {
    return $base . $image;
}
function getconfig($attribute) {
    global $config;
    return $config[$attribute];
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
        #camPreview {
            height: 100%;
            position: fixed;
            width: <?= getconfig('preview_size'); ?>;
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
            <a class="<?= $btnClass ?>" href="?take">Zur Kamera-Vorschau</a>
        </div>
    </div>
<?php elseif(isset($_GET['take'])) : ?>
    <?php
    $result = runcmd('flashup');
    //echo $result;
    ?><div style="height:100%" class="d-flex align-items-center">
    <div class="container text-center">
        Vorschau ladet...</div></div>
    <video autoplay="true" id="camPreview"></video>
        <div class="container bottom-bar">
            <a class="<?= $btnClass ?>" href="#" onclick="javascript:take();void(0)" id="countdown">Foto aufnehmen</a>
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
        function take() {
            let countdown = <?= getconfig('countdown'); ?>;
            var counter = countdown;
            var countdownEl = $('#countdown');
            countdownEl.text(counter);
            var interval = setInterval(function() {
                counter--;
                countdownEl.text(counter);
                if (counter == 0) {
                    clearInterval(interval);
                    // take photo
                    countdownEl.text('Cheeeese!');
                    capture();
                }
            }, 1000);
        }
        function capture() {
            $.ajax({
                url: "?capture",
            }).done(function(data) {
                console.log(data);
                prepare();
            });
            //location.href = '?photo';
        }
        function prepare() {
            $('#countdown').text('Verarbeitung...');
            $.ajax({
                url: "?prepare",
            }).done(function(data) {
                console.log(data);
                //$('#countdown').text('Done! ' + data);
                //$('#photo').attr('src', data);
                location.href = '?photo&src=' + data;
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
    </script>

<?php elseif(isset($_GET['photo'])) :  ?>
    <img id="photo" src="<?= $_GET['src'] ?>" width="100%" />
    <div class="container bottom-bar">
        <a class="<?= $btnClass ?>" href="?take">Neues Foto</a>&nbsp;
        <a id="qrbutton" class="<?= $btnClass ?>" href="#" onclick="javascript:qrToggle();void(0);">QR-Code</a>
        <div id="qrcode"></div>
    </div>
    <script>
        $( document ).ready(function() {
            setTimeout(flashdown, <?= getconfig('flashdown'); ?> * 1000);
            
            $('#qrbutton').hide();
            upload();
        });

        function qrCode(link) {
            var qrEl = $('#qrcode');
            if(qrEl.data('generated') != 1) {
                var qrcode = new QRCode(document.getElementById("qrcode"), {
                    text: link,
                    /*text: '<?= webUrl(getconfig('web_url_base'), $_GET['src']); ?>',*/
                    width : 300,
                    height : 300,
                    useSVG: true
                });
                qrEl.data('generated', 1);
                $('#qrbutton').show();
            }
        }

        function qrToggle() {
            $('#qrcode').toggle();
        }

        function upload() {
            $.ajax({
                url: "?upload&src=<?= basename($_GET['src']) ?>",
            }).done(function(data) {
                console.log(data);
                qrCode(data);
            });
            //location.href = '?photo';
        }

        function flashdown() {
            $.ajax({
                url: "?flashdown",
            }).done(function(data) {
                console.log(data);
            });
        }
    </script>
<?php endif; ?>

<a id="home" href="/" class="btn">-</a>
</body>
</html>
