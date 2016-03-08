<?php

class Logger {

    public static $logType;
    public static $file;

    const LOG_TYPE_FILE   = 0;
    const LOG_TYPE_SIMPLE = 1;
    const LOG_TYPE_SYSLOG = 2;

    public static $logString = array(LOG_DEBUG   => 'DEBUG',
                                     LOG_INFO    => 'INFO',
                                     LOG_ERR     => 'ERROR',
                                     LOG_WARNING => 'WARN',
                                     LOG_NOTICE  => 'NOTICE');

    public static function init() {
        $config = Config::get("application.logger");
        self::$logType = isset($config['logType']) ? $config['logType'] : self::LOG_TYPE_SIMPLE;
        self::$file    = isset($config['file']) ? $config['file'] : APP_PATH.DS."logs".DS."app.log";
    }

    public static function setLogType($logType) {
        self::$logType = $logType;
    }
    public static function debug($msg) {
        self::output(LOG_DEBUG, $msg);
    }

    public static function info($msg) {
        self::output(LOG_INFO, $msg);
    }

    public static function error($msg) {
        self::output(LOG_ERR, $msg);
    }

    public static function warn($msg) {
        self::output(LOG_WARNING, $msg);
    }

    public static function notice($msg) {
        self::output(LOG_NOTICE, $msg);
    }

    public static function setFile($file) {
        self::$file = $file;
    }

    private static function output($logType, $msg) {
        switch (self::$logType) {
            case self::LOG_TYPE_FILE:
                $msg = date("Y-m-d H:i:s")."|".self::$logString[$logType]."|".$msg;
                $dir = dirname(self::$file);
                is_dir($dir) || mkdir($dir, 0755, true);
                return file_put_contents(self::$file, $msg . "\n", FILE_APPEND | LOCK_EX);
            case self::LOG_TYPE_SYSLOG:
                openlog(APP_NAME, LOG_PID | LOG_PERROR, LOG_LOCAL6);
                return syslog($logType, $msg);
            case self::LOG_TYPE_SIMPLE:
                 default:
                 $msg = date("Y-m-d H:i:s")."|".self::$logString[$logType]."|".$msg;
                 echo "[Logger]".$msg."<br/>";
                 return;
        }
    }
}
