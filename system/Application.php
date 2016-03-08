<?php

class Application {

    private static $instance;
    private $request;
    private $response;
    private $router;

    private $controllers = array();
    private $current_controller;
    private $current_action;
    private $shutdown_functions = array();

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new Application();
        }
        return self::$instance;
    }

    private function __construct() {
        if (get_magic_quotes_gpc()) {
            $in = array(& $_GET, & $_POST, & $_COOKIE, & $_REQUEST);
            while (list ($k, $v) = each($in)) {
                foreach ($v as $key => $val) {
                    if (!is_array($val)) {
                        $in[$k][$key] = stripslashes($val);
                        continue;
                    }
                    $in[] = & $in[$k][$key];
                }
            }
            unset($in);
        }

        if (DEBUG == true) {
            ini_set("display_errors", 1);
            error_reporting(E_ALL);
        } else {
            ini_set("display_errors", 0);
            set_exception_handler(array($this, 'handler_exception'));
            set_error_handler(array($this, 'handle_error'));
        }

        Config::init();
        Logger::init();
        register_shutdown_function(array($this, "shutdown"));
    }

    public function register_shutdown_function($function) {
        $this->shutdown_functions[] = $function;
    }

    public function handler_exception($exception) {
        $trace = $this->format_trace($exception->getTrace());
        $trace = "Exception '" . get_class($exception) . "' with message '"
                . $exception->getMessage() . "' in "
                . $exception->getFile() . ":"
                . $exception->getLine() . " Stack trace: " . $trace;

        Log::error($trace);
        exit(1);
    }

    public function handle_error($errno, $errstr) {
        if ($errno & error_reporting()) {
            $level_names = array(E_ERROR => 'E_ERROR',
                    E_WARNING => 'E_WARNING',
                    E_PARSE => 'E_PARSE',
                    E_NOTICE => 'E_NOTICE',
                    E_CORE_ERROR => 'E_CORE_ERROR',
                    E_CORE_WARNING => 'E_CORE_WARNING',
                    E_COMPILE_ERROR => 'E_COMPILE_ERROR',
                    E_COMPILE_WARNING => 'E_COMPILE_WARNING',
                    E_USER_ERROR => 'E_USER_ERROR',
                    E_USER_WARNING => 'E_USER_WARNING',
                    E_USER_NOTICE => 'E_USER_NOTICE');
            $levels = array();
            $value = $errno;
            if (($value & E_ALL) == E_ALL) {
                $levels[] = 'E_ALL';
                $value &= ~E_ALL;
            }
            foreach ($level_names as $level => $name) {
                if (($value & $level) == $level) $levels[] = $name;
            }

            $trace = debug_backtrace(false);
            $trace = $this->format_trace($trace);

            Log::error("Error " . implode(' | ', $levels) . " " . $errstr . ", TRACE: " . $trace);
            exit(1);
        }
    }

    public function format_trace($trace) {
        if (!is_array($trace)) {
            return;
        }

        $error_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $http_refer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $trace_str = "Request Url : {$http_host}{$error_url} Refer : {$http_refer} ";
        foreach ($trace as $key => $val) {
            $trace_str .= "#{$key} " . @$val['file'] . " (" . @$val['line'] . ") : ";
            if (isset($val['class'])) {
                $trace_str .= "{$val['class']}{$val['type']}";
            }

            $trace_str .= "{$val['function']}(";
            if (is_array(@$val['args'])) {
                foreach ($val['args'] as $v) {
                    $_v = preg_replace('#[\r\n \t]+#', ' ', print_r($v, true));
                    $_v = substr($_v, 0, 100);
                    $trace_str .= $_v . ",";
                }
                $trace_str = rtrim($trace_str, ',');
            }
            $trace_str .= ") ";
        }
        return $trace_str;
    }

    protected function get_controller($class) {
        if (isset($this->controllers[$class])) {
            return $this->controllers[$class];
        }

        $ret = ClassLoader::loadClass($class, "controller");
        if (!$ret) {
            $this->throw404();
        }

        $controller = new $class();
        $this->controllers[$class] = $controller;
        return $controller;
    }

    public function get_current_controller() {
        return $this->current_controller;
    }

    public function get_current_action() {
        return $this->current_action;
    }

    public function run() {
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router();
        $this->dispatch();
    }

    private function dispatch() {
        $rule = $this->router->mapping();
        $controller = $this->get_controller($rule['controller']);
        $this->current_controller = $rule['controller'];
        $this->current_action = $rule['action'];

        if (method_exists($controller, $rule['action'])) {
            call_user_func(array($controller, $rule['action']));
        } else {
            $this->throw404();
        }
    }

    public function throw404() {
        if (file_exists(APP_PATH . "view/404.php")) {
            include(APP_PATH . "view/404.php");
        } else {
            header("HTTP/1.1 404 Not Found");
        }
        exit();
    }

    public function get_request() {
        return $this->request;
    }

    public function get_response() {
        return $this->response;
    }

    public function shutdown() {
        restore_exception_handler();
        restore_error_handler();
        if (is_array($this->shutdown_functions)) {
            $functions = array_reverse($this->shutdown_functions);
            foreach ($functions as $function) {
                call_user_func($function);
            }
        }
    }
}