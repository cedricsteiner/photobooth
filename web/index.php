<?php
require __DIR__ . '/vendor/autoload.php';

use BenMajor\ImageResize\Image;
use BenMajor\ImageResize\Watermark;

?>
<?php require_once 'config-local.php'; ?>
<?php

initdir($imagepath . '/tmp');
initdir($imagepath . '/original');
initdir($imagepath . '/show');

$btnClass = "btn btn-warning btn-lg";

if(isset($_GET['capture'])) {
    $result = runcmd('capture');
    echo $result;
    exit;
}
if(isset($_GET['prepare'])) {
    foreach(glob($imagepath . '/tmp/*.jpg') as $image) {
        break;
    }
    if($image) {
        $imageName =  date("Y-m-d_H-i-s", time()) . '.jpg';
        $originalImage = $imagepath . '/original/' . $imageName;
        $showImage = $imagepath . '/show/' . $imageName;
        //rename($image, $originalImage);
        copy($image, $originalImage);

        // converting
        if(convert($originalImage, $showImage, $photoSize, $watermarkSize)) {
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
if(isset($_GET['flashdown'])) {
    $result = runcmd('flashdown');
    echo $result;
    exit;
}
function runcmd($cmd) {
    $result = exec('./cmd/' . $cmd . '.sh');
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
    //return '1234567890';
    return $base . $image;
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
            font-size: <?= $fontsize ?>px;
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
            width: <?= $previewSize ?>;
            left: <?= $previewLeft ?>;
            top: <?= $previewTop ?>;
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
            <h1><?= $title ?></h1>
            <p><?= $description ?></p>
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
            let countdown = <?= $countdown ?>;
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
        <a class="<?= $btnClass ?>" href="#" onclick="javascript:qrCode();void(0);">QR-Code</a>
        <div id="qrcode"></div>
    </div>
    <script>
        $( document ).ready(function() {
            setTimeout(flashdown, <?= $flashdown ?> * 1000);
        });

        function qrCode() {
            var qrEl = $('#qrcode');
            if(qrEl.data('generated') != 1) {
                var qrcode = new QRCode(document.getElementById("qrcode"), {
                    text: '<?= webUrl($webUrlBase, $_GET['src']); ?>',
                    width : 300,
                    height : 300,
                    useSVG: true
                });
                qrEl.data('generated', 1);
            }
            qrEl.toggle();
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


</body>
</html>
