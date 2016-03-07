<?php

class Config {

    private static $config = array();

    private static $environment;

    public static function init() {
        self::$environment = strtolower(ENVIRONMENT);
    }

    public static function get($key, $default = null) {
        $keys = explode(".", $key);
        $file = $keys[0];

        if (!isset(self::$config[$file])) {
            $config = self::loadConfigFile($file);
            if (!$config) {
                return $default;
            }
            self::$config[$file] = $config;
        }

        $newConfig = self::$config[$file];
        if (strpos($key, ".") === false) {
            return $newConfig;
        }

        array_shift($keys);
        foreach ($keys as $name) {
            if (!isset($newConfig[$name])) {
                return $default;
            }
            $newConfig = $newConfig[$name];
        }

        return $newConfig;
    }

    public static function env() {
        return self::$environment;
    }

    private static function loadConfigFile($file) {
        $file = APP_PATH . "config" . DS . self::$environment . DS . $file . ".php";
        if (file_exists($file)) {
            include $file;
        }

        if (!isset($config)) {
            return false;
        }

        return $config;
    }
}