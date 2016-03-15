<?php

include SYS_EXT_PATH . DS . 'phpqrcode/phpqrcode.php';

ini_set("display_errors", 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

class Gd {

    public function createQRWithLogo($text, $url) {

        $srcImg = imagecreatefrompng('logo.png');
        $srcWidth = imagesx($srcImg);
        $srcHeight = imagesy($srcImg);

        $newImg = imagecreatetruecolor(49, 49);
        imagesavealpha($newImg, true);
        $color = imagecolorallocatealpha($newImg, 255, 255, 255, 0);
        imagefill($newImg, 0, 0, $color);
        imagecopyresampled($newImg, $srcImg, 4, 4, 0, 0, 41, 41, $srcWidth, $srcHeight);

        define('IMAGE_WIDTH', 179);
        define('IMAGE_HEIGHT', 179);
        $errorCorrectionLevel = 'H';
        $matrixPointSize = 6;
        $QRTempName = md5($url) . ".png";
        QRcode::png($url, $QRTempName, $errorCorrectionLevel, $matrixPointSize, 0);
        $QR = imagecreatefromstring(file_get_contents($QRTempName));
        $BG = imagecreatefromjpeg("bg@3.jpg");
        $BG_width = imagesx($BG);
        $BG_height = imagesy($BG);
        $logo = $newImg;
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        imagecopyresampled($QR, $logo, 65, 65, 0, 0, 49, 49, $logo_width, $logo_height);
        $finishImg = imagecreatetruecolor(640, 220);
        imagesavealpha($finishImg, true);
        $color = imagecolorallocatealpha($finishImg, 255, 255, 255, 0);
        imagefill($finishImg, 0, 0, $color);
        imagecopyresampled($finishImg, $BG, 47, 0, 0, 0, 546, 220, $BG_width, $BG_height);
        imagecopyresampled($finishImg, $QR, 393, 17, 0, 0, $QR_width, $QR_height, $QR_width, $QR_height);
        $color = imagecolorallocatealpha($finishImg, 26, 0, 0, 0);
        $fontFile = "/Users/liubotao/Desktop/font/SourceHanSans-Medium.otf";
        $fontSize = 26;
        $string = trim($text);
        $temp = array("color" => array(26, 0, 0), "fontSize" => $fontSize, "width" => 258, "left" => 90, "top" => 30, "height" => 32);
        $box = imagettfbbox($fontSize, 0, $fontFile, $string);
        $width = abs($box[4] - $box[0]);
        if ($width <= 258) {
            imagettftext($finishImg, 26, 0, 140, 80, $color, $fontFile, $string);
        } else {
            $this->drawMultiText($finishImg, $temp, $string, $fontFile);
        }

        $saveName = "s" . $QRTempName;
        imagepng($finishImg, $saveName, 9);
        echo "<img src = '/" . $saveName . "'/>";
        die();

    }

    function drawMultiText($res, $pos, $str, $fontFile) {
        $top = $pos["top"];
        $fontSize = $pos["fontSize"];
        $width = $pos["width"];
        $margin_lift = $pos["left"];
        $height = $pos["height"];
        $temp_string = "";
        $tp = 0;

        $font_color = imagecolorallocate($res, $pos["color"][0], $pos["color"][1], $pos["color"][2]);
        $position = 0;
        for ($i = 0; $i < mb_strlen($str, 'UTF-8'); $i++) {
            $box = imagettfbbox($fontSize, 0, $fontFile, $temp_string);
            $stringLength = $box[2] - $box[0];
            $tempText = mb_substr($str, $i, 1);
            $temp = imagettfbbox($fontSize, 0, $fontFile, $tempText);
            if ($stringLength + $temp[2] - $temp[0] < $width) {
                $temp_string .= mb_substr($str, $i, 1, 'UTF-8');
                if ($i == (mb_strlen($str, 'UTF-8') - 1)) {
                    $top += $height;
                    $tp++;
                    imagettftext($res, $fontSize, 0, $margin_lift, $top, $font_color, $fontFile, $temp_string);
                }
            } else {
                $beforeString = mb_substr($str, $i - 1, 1, 'UTF-8');
                if (!preg_match('/[' . chr(0xa1) . '-' . chr(0xff) . ']/i', $beforeString) && $beforeString != " ") {
                    for ($j = $i; $j >= 0; $j--) {
                        $beforeString = mb_substr($str, $j, 1, 'UTF-8');
                        if ($beforeString == " ") {
                            $temp_string = mb_substr($temp_string, 0, $j + 1 - $position, 'UTF-8');
                            $i = $j + 1;
                            $position = $i;
                            break;
                        }
                    }
                }
                $texts = mb_substr($str, $i, 1, 'UTF-8');
                $isSymbol = preg_match("/[\\\\pP]/u", $texts) ? true : false;
                if ($isSymbol) {
                    $temp_string .= $texts;
                    $f = mb_substr($str, $i + 1, 1, 'UTF-8');
                    $fh = preg_match("/[\\\\pP]/u", $f) ? true : false;
                    if ($fh) {
                        $temp_string .= $f;
                        $i++;
                    }
                } else {
                    $i--;
                }

                $tmp_str_len = mb_strlen($temp_string, 'UTF-8');
                $s = mb_substr($temp_string, $tmp_str_len - 1, 1, 'UTF-8');
                if ($this->isSymbol($s)) {
                    $temp_string = rtrim($temp_string, $s);
                    $i--;
                }
                $top += $height;
                $tp++;
                imagettftext($res, $fontSize, 0, $margin_lift, $top, $font_color, $fontFile, $temp_string);
                $temp_string = "";
            }
        }
        return $tp * $height;
    }

    private function isSymbol($str) {
        $isSymbol = array("\\\"", "“", "'", "<", "《",);
        return in_array($str, $isSymbol);
    }

}