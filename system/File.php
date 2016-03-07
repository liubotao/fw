<?php

class File {

    public $chmod;

    public function __construct($chmod = null) {
        if ($chmod !== null) {
            $this->chmod = $chmod;
        } else {
            $this->chmod = 0777;
        }
    }

    public function create($path, $content = null, $writeFileMode = 'w+') {
        if (!empty($content)) {
            if (strpos($path, '/') !== false || strpos($path, '\\') !== false) {
                $path = str_replace('\\', '/', $path);
                $filename = $path;
                $path = explode('/', $path);
                array_splice($path, sizeof($path) - 1);

                $path = implode('/', $path);
                if ($path[strlen($path) - 1] != '/') {
                    $path .= '/';
                }
            } else {
                $filename = $path;
            }

            if ($filename != $path && !file_exists($path)) {
                mkdir($path, $this->chmod, true);
            }
            $fp = fopen($filename, $writeFileMode);
            $rs = fwrite($fp, $content);
            fclose($fp);
            return ($rs > 0);
        } else {
            if (!file_exists($path)) {
                return mkdir($path, $this->chmod, true);
            } else {
                return true;
            }
        }
    }
}