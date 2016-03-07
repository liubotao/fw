<?php

// 请求第三方服务客户端

class Rest {

    protected $server_url;
    protected $curl_opt = array('CONNECTTIMEOUT' => 3, "TIMEOUT" => 3);
    protected $args;
    protected $result;
    protected $header_code_received;
    protected $content_type_received;
    protected $header_size_received;
    protected $content_size_received;
    protected $request_warn_time = 5000;

    const HTML = 'text/html';
    const XML = 'application/xml';
    const JSON = 'application/json';
    const JS = 'application/javascript';
    const CSS = 'text/css';
    const RSS = 'application/rss+xml';
    const YAML = 'text/yaml';
    const ATOM = 'application/atom+xml';
    const PDF = 'application/pdf';
    const TEXT = 'text/plain';
    const PNG = 'image/png';
    const JPG = 'image/jpeg';
    const GIF = 'image/gif';
    const CSV = 'text/csv';

    public function  __construct($server_url = null) {
        if ($server_url != null) {
            $this->server_url = $server_url;
        }

        $this->curl_opt['RETURNTRANSFER'] = true; //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        $this->curl_opt['HEADER'] = false;
        $this->curl_opt['FRESH_CONNECT'] = true; //强制获取一个新的连接，替代缓存中的连接。
    }

    public function connect($server_url = null) {
        if ($server_url == null) {
            return $this->server_url;
        }
        if (strrpos($server_url, 'http://') !== 0 && strrpos($server_url, 'https://') !== 0) {
            $this->server_url = 'http://' . $server_url;
        }
        return $this;
    }

    public static function checkUrlExist($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // set to HEAD request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // don't output the response
        curl_exec($ch);
        $valid = curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200;
        curl_close($ch);
        return $valid;
    }

    public static function retrieveHeaderCode($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // set to HEAD request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // don't output the response
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }

    public function timeout($sec = null) {
        if ($sec === null) {
            return $this->curl_opt['TIMEOUT'];
        } else {
            $this->curl_opt['TIMEOUT'] = $sec;
        }
        return $this;
    }

    public function data($data = null) {
        if ($data == null) {
            return $this->args;
        }
        if (is_string($data)) {
            $this->args = $data;
        } else {
            $this->args = http_build_query($data);
        }
        return $this;
    }

    public function options($optArr = null) {
        if ($optArr == null) {
            return $this->curl_opt;
        }
        $this->curl_opt = array_merge($this->curl_opt, $optArr);
        return $this;
    }

    public function requestWarnTime($time = null) {
        if ($time == null) {
            return $this->request_warn_time;
        }
        $this->request_warn_time = $time;
        return $this;
    }

    public function header($headerArr = null) {
        if ($headerArr == null) {
            return $this->curl_opt['HTTPHEADER'];
        }

        foreach ($headerArr as $k => $v) {
            $this->curl_opt['HTTPHEADER'][] = $k . ': ' . $v;
        }

        return $this;
    }

    public function accept($type = null) {
        if ($type === null) {
            if (isset($this->curl_opt['HTTPHEADER']) && $this->curl_opt['HTTPHEADER'][0]) {
                return str_replace('Accept: ', '', $this->curl_opt['HTTPHEADER'][0]);
            } else {
                return;
            }
        }
        $this->curl_opt['HTTPHEADER'][] = "Accept: $type";
        return $this;
    }

    public function contentType($type = null) {
        if ($type == null) {
            if (isset($this->curl_opt['HTTPHEADER']) && $this->curl_opt['HTTPHEADER'][0]) {
                return str_replace('Content-Type: ', '', $this->curl_opt['HTTPHEADER'][0]);
            } else {
                return;
            }
        }
        $this->curl_opt['HTTPHEADER'][] = "Content-Type: $type";
        return $this;
    }

    public function get() {
        $start_time = microtime(true);

        if ($this->args != null) {
            $serverurl = $this->server_url . '?' . $this->args;
        } else {
            $serverurl = $this->server_url;
        }

        $ch = curl_init($serverurl);

        $arr = array();
        foreach ($this->curl_opt as $k => $v) {
            $arr[constant('CURLOPT_' . strtoupper($k))] = $v;
        }

        $arr[CURLOPT_HTTPGET] = true;

        curl_setopt_array($ch, $arr);

        $this->result = curl_exec($ch);

        if (curl_errno($ch)) {
            if (DEBUG == true) {
                throw new Exception("curl get [" . $serverurl . "] failed, Curl error code: " . curl_error($ch));
            } else {
                Log::error('curl get [' . $serverurl . '] failed, Curl error code: ' . curl_errno($ch));
            }
        }

        $this->header_code_received = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->content_type_received = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->header_size_received = intval(curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $this->content_size_received = intval(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));

        if (!$this->isSuccess()) {
            if (DEBUG == true) {
                throw new Exception("curl get [" . $serverurl . "] failed, Http error code: " . $this->header_code_received);
            } else {
                Log::error('curl get [' . $serverurl . '] failed, Http error code:' . $this->header_code_received);
            }
        }

        curl_close($ch);
        $end_time = microtime(true);
        $executeTime = round(($end_time - $start_time) * 1000);
        if ($executeTime >= $this->request_warn_time) {
            Log::warn('curl get [' . $serverurl . '] time more than query warn time, request use :' . $executeTime . "ms, request result: " . $this->result);
        }

        return $this;
    }

    public function post() {
        $start_time = microtime(true);

        $ch = curl_init($this->server_url);
        $arr = array();
        foreach ($this->curl_opt as $k => $v) {
            $arr[constant('CURLOPT_' . strtoupper($k))] = $v;
        }

        $arr[CURLOPT_POST] = true;
        $arr[CURLOPT_POSTFIELDS] = $this->args;

        curl_setopt_array($ch, $arr);
        $this->result = curl_exec($ch);

        if (curl_errno($ch)) {
            if (DEBUG == true) {
                throw new Exception("curl post [" . $this->server_url . "] failed, args :" . $this->args . ", Curl error code: " . curl_error($ch));
            } else {
                Log::error('curl post [' . $this->server_url . '] failed, args: ' . $this->args . ', Curl error code: ' . curl_errno($ch));
            }
        }

        $this->header_code_received = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->content_type_received = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->header_size_received = intval(curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $this->content_size_received = intval(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));

        if (!$this->isSuccess()) {
            if (DEBUG == true) {
                throw new Exception("curl post [" . $this->server_url . "] failed, args: " . $this->args . ", Http error code: " . $this->header_code_received);
            } else {
                Log::error('curl post [' . $this->server_url . '] failed, args: ' . $this->args . ' , Http error code:' . $this->header_code_received);
            }
        }

        curl_close($ch);

        $end_time = microtime(true);
        $executeTime = round(($end_time - $start_time) * 1000);
        if ($executeTime >= $this->request_warn_time) {
            Log::warn('curl post [' . $this->server_url . '] args : ' . $this->args . ' time more than query warn time, request use :' . $executeTime . "ms, request result: " . $this->result);
        }

        return $this;
    }

    public function put() {
        $start_time = microtime(true);
        $ch = curl_init($this->server_url);

        $arr = array();
        foreach ($this->curl_opt as $k => $v) {
            $arr[constant('CURLOPT_' . strtoupper($k))] = $v;
        }

        $arr[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $arr[CURLOPT_POSTFIELDS] = $this->args;
        curl_setopt_array($ch, $arr);
        $this->result = curl_exec($ch);


        if (curl_errno($ch)) {
            if (DEBUG == true) {
                throw new Exception("curl put [ " . $this->server_url . "] failed, args :" . $this->args . ", Curl error code: " . curl_error($ch));
            } else {
                Log::error('curl put [' . $this->server_url . '] failed, args :' . $this->args . ', Curl error code: ' . curl_errno($ch));
            }
        }

        $this->header_code_received = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->content_type_received = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->header_size_received = intval(curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $this->content_size_received = intval(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));

        if (!$this->isSuccess()) {
            if (DEBUG == true) {
                throw new Exception("curl put [ " . $this->server_url . "] failed, args:" . $this->args . ", Http error code: " . $this->header_code_received);
            } else {
                Log::error('curl put [ ' . $this->server_url . '] failed, args: ' . $this->args . ', Http error code:' . $this->header_code_received);
            }
        }
        curl_close($ch);

        $end_time = microtime(true);
        $executeTime = round(($end_time - $start_time) * 1000);
        if ($executeTime >= $this->request_warn_time) {
            Log::warn('curl put [' . $this->server_url . '] args ' . $this->args . ' time more than query warn time, request use :' . $executeTime . "ms, request result: " . $this->result);
        }
        return $this;
    }

    public function delete() {
        $start_time = microtime(true);
        $ch = curl_init($this->server_url);

        $arr = array();
        foreach ($this->curl_opt as $k => $v) {
            $arr[constant('CURLOPT_' . strtoupper($k))] = $v;
        }

        $arr[CURLOPT_CUSTOMREQUEST] = 'DELETE';

        curl_setopt_array($ch, $arr);

        $this->result = curl_exec($ch);

        if (curl_errno($ch)) {
            if (DEBUG == true) {
                throw new Exception("curl delete [ " . $this->server_url . "] failed, args: " . $this->args . ", Curl error code: " . curl_error($ch));
            } else {
                Log::error('curl delete [ ' . $this->server_url . '] failed, args: ' . $this->args . ', Curl error code: ' . curl_errno($ch));
            }
        }

        $this->header_code_received = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->content_type_received = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->header_size_received = intval(curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $this->content_size_received = intval(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));

        if (!$this->isSuccess()) {
            if (DEBUG == true) {
                throw new Exception("curl delete [ " . $this->server_url . "] failed, args: " . $this->args . ", Http error code: " . $this->header_code_received);
            } else {
                Log::error('curl delete [ ' . $this->server_url . '] failed, args: ' . $this->args . ', Http error code:' . $this->header_code_received);
            }
        }

        curl_close($ch);

        $end_time = microtime(true);
        $executeTime = round(($end_time - $start_time) * 1000);
        if ($executeTime >= $this->request_warn_time) {
            Log::warn('curl delete [' . $this->server_url . '] args ' . $this->args . ' time more than query warn time, request use :' . $executeTime . "ms, request result: " . $this->result);
        }
        return $this;
    }

    public function getResult() {
        return $this->result;
    }

    public function isSuccess() {
        return ($this->header_code_received >= 200 && $this->header_code_received < 300);
    }

    public function resultCode() {
        return $this->header_code_received;
    }

    public function getJsonResult() {
        return json_decode($this->result, true);
    }

}