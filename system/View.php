<?php

class View {

    protected $vars = array();

    protected $viewname;

    public $output = '';

    public function assign($key, $data = null) {
        if (is_array($key)) {
            $this->vars = array_merge($this->vars, $key);
        } else {
            $this->vars[$key] = $data;
        }
        return $this;
    }

    public function display($viewname = null, $vars = null) {
        if (empty($viewname)) {
            $viewname = $this->viewname;
        }
        echo $this->fetch($viewname, $vars);
    }

    function fetch($viewname = null, $vars = null) {
        if (empty($viewname)) {
            $viewname = $this->viewname;
        }

        $filename = APP_PATH . DS . "view" . DS . "{$viewname}.php";
        if (file_exists($filename)) {
            if (!is_array($vars)) {
                $vars = $this->vars;
            }
            $this->assign($vars);
            $output = $this->parse($filename);
        } else {
            throw new Exception("模板文件不存在,请检查模板" . $filename);
        }
        $this->output = $output;
        return $output;
    }

    public function parse($filename) {
        ob_start();
        $this->_include($filename);
        $content = ob_get_clean();
        return $content;
    }

    protected function _include($filename) {
        $this->extname = pathinfo($filename, PATHINFO_EXTENSION);
        extract($this->vars, EXTR_OVERWRITE);
        include $filename;
    }

}