<?php

class ClassLoader {

    public static $loadFiles = array();

    public static function autoLoad($className) {
        if (isset(self::$systemClasses[$className])) {
            if (class_exists($className) || interface_exists($className)) {
                return;
            }
            $file = self::$systemClasses[$className];
            $fileName = SYS_PATH . $file;
            include $fileName;
            self::$loadFiles[] = $fileName;
        } else if (strlen($className) > 10 && substr($className, -10) == "Controller") {
            self::loadClass($className, "controller");
        } else {
            self::loadClass($className);
        }
    }

    public static function loadClass($className, $prefix = "") {
        if (class_exists($className) || interface_exists($className)) {
            return true;
        }

        $fileName = $prefix . DS . $className;
        if (in_array($fileName, self::$loadFiles)) {
            return true;
        }

        $paths = explode("_", $fileName);
        $count = count($paths) - 1;
        $path = "";
        for ($i = 0; $i < $count; $i++) {
            $path .= strtolower($paths[$i]) . DS;
        }
        $class = $paths[$count];
        $file = $path . $class . ".php";
        global $GLOBAL_LOAD_PATH;
        foreach ($GLOBAL_LOAD_PATH as $dir) {
            $path = $dir . $file;
            if (file_exists($path)) {
                require $path;
                self::$loadFiles[] = $fileName;
                return true;
            }
        }
        return false;
    }

    private static $systemClasses = array(
            'Application' => "Application.php",
            'Config'      => 'Config.php',
            'Controller'  => 'Controller.php',
            'DB'          => 'DB.php',
            'DB_Table'    => "db/Table.php",
            'File'        => 'File.php',
            'Logger'      => 'Logger.php',
            'Page'        => 'Page.php',
            'Request'     => 'Request.php',
            'Response'    => 'Response.php',
            'Rest'        => 'Rest.php',
            'Router'      => 'Router.php',
            'View'        => 'View.php',
    );


}

spl_autoload_register(array('ClassLoader', 'autoLoad'));