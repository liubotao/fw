<?php


class Request {

    protected $params = array();

    protected $attributes = array();

    private $user_id;

    private $user_uuid;

    private $user_info;

    private $user_name;

    private $user_email;

    private $user_account;

    private $is_login = false;

    protected $filter_get_rule = "<[^>]*?=[^>]*?&#[^>]*?>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b[^>]*?>|^\\+\\/v(8|9)|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";

    protected $filter_post_rule = "<[^>]*?=[^>]*?&#[^>]*?>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b[^>]*?>|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";

    protected $filter_cookie_rule = "\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";


    public function get_attributes() {
        return $this->attributes;
    }

    public function set_attribute($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function get_attribute($key, $default = null, $is_encode = true) {
        if (isset($this->attributes[$key])) {
            if ($is_encode) {
                return h($this->attributes[$key]);
            } else {
                return $this->attributes[$key];
            }
        } else {
            return $default;
        }
    }

    public function remve_attribute($key) {
        unset($this->attributes[$key]);
    }

    public function set_param($key, $value) {
        $this->attributes[$key] = $value;
    }


    public function get_int($key, $default = 0) {
        if (!$key || !isset($_GET[$key])) {
            return $default;
        }

        return intval($_GET[$key]);
    }

    public function get_string($key, $default = null, $is_encode = true) {
        if (!$key || !isset($_GET[$key])) {
            return $default;
        }
        $this->filter_attack('GET', $key, $_GET[$key], $this->filter_get_rule);
        if ($is_encode) {
            return $this->h($_GET[$key]);
        } else {
            return $_GET[$key];
        }
    }

    public function post_int($key) {
        if (!$key || !isset($_POST[$key])) {
            return 0;
        }
        return intval($_POST[$key]);
    }

    public function post_string($key, $default = null, $is_encode = true) {
        if (!$key || !isset($_POST[$key])) {
            return $default;
        }
        $this->filter_attack('POST', $key, $_POST[$key], $this->filter_post_rule);
        if ($is_encode) {
            return $this->h($_POST[$key]);
        } else {
            return $_POST[$key];
        }
    }

    public function filter_attack($method, $key, $value, $rule) {
        if (preg_match("/" . $rule . "/is", $value) == 1) {
            $this->log_attack($method, $key, $value);
            exit('Do not Acctack, Tks!!');
        }

        if (preg_match("/" . $rule . "/is", $key) == 1) {
            $this->log_attack($method, $key, $value);
            exit('Do not Acctack, Tks!!');
        }
    }

    protected function log_attack($method, $key, $value) {
        $time = strftime("%Y-%m-%d %H:%M:%S");
        $log = "ip:" . $_SERVER["REMOTE_ADDR"] . " time:{$time} page:" . $_SERVER["PHP_SELF"] . " method:{$method} rkey:{$key} rdata:{$value} user_agent:" . $_SERVER['HTTP_USER_AGENT'] . " request_url:" . $_SERVER["REQUEST_URI"];
        Log::warn($log);
    }

    public function get_server_port() {
        return $_SERVER['SERVER_PORT'];
    }

    public function get_url_referrer() {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    public function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    public function get_userHostAddress() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        return $ip;
    }

    public function set_user_email($user_email) {
        $this->user_email = $user_email;
    }

    public function get_user_email() {
        return $this->user_email;
    }

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }

    public function get_user_id() {
        return $this->user_id;
    }

    public function get_user_uuid() {
        return $this->user_uuid;
    }

    public function set_user_uuid($user_uuid) {
        $this->user_uuid = $user_uuid;
    }

    public function set_user_account($user_account) {
        $this->user_account = $user_account;
    }

    public function get_user_account() {
        return $this->user_account;
    }

    public function set_user_name($user_name) {
        $this->user_name = $user_name;
    }

    public function get_user_name() {
        return $this->user_name;
    }

    public function set_user_info($user_info) {
        $this->user_info = $user_info;
    }

    public function get_user_info() {
        return $this->user_info;
    }

    public function set_is_login($is_login) {
        $this->is_login = $is_login;
    }

    public function get_is_login() {
        return $this->is_login;
    }

    public function h($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'utf-8');
    }
}
