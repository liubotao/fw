<?php


class Controller {

    protected static $view;

    public function get_interceptor_index_name() {
        return get_class($this);
    }

    public function get_user_id() {
        return Application::instance()->get_request()->get_user_id();
    }

    public function get_user_info() {
        return Application::instance()->get_request()->get_user_info();
    }

    public function get_attribute($key) {
        return Application::instance()->get_request()->get_attribute($key);
    }

    public function post_int($key) {
        return Application::instance()->get_request()->post_int($key);
    }

    public function post_string($key, $default = null, $is_encode = true) {
        return Application::instance()->get_request()->post_string($key, $default = null, $is_encode = true);
    }

    public function get_int($key) {
        return Application::instance()->get_request()->get_int($key);
    }

    public function get_string($key, $default = null, $is_encode = true) {
        return Application::instance()->get_request()->get_string($key, $default = null, $is_encode = true);
    }

    public function redirect($url) {
        Response::redirect($url);
    }

    protected function view() {
        static $view;
        if (!$view) {
            $view = new View();
        }
        return $view;
    }

    public function assign($name, $value) {
        return $this->view()->assign($name, $value);
    }

    public function display($tpl = null) {
        if (empty($tpl)) {
            $tpl = $this->defaultTemplate();
        }
        $this->view()->display($tpl);
    }

    protected function defaultTemplate() {
        $current_controller = Application::instance()->get_current_controller();
        $current_action = Application::instance()->get_current_action();

        $tpl = str_replace('_', DS, strtolower(substr($current_controller, 0, -10)))
                . DS
                . substr($current_action, 0, -6);

        return $tpl;
    }

}