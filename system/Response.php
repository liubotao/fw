<?php

class Response {

    public static function redirect($url, $statusCode = 303) {
        header('Location:' . $url, true, $statusCode);
        die();
    }

}