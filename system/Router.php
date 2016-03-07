<?php

class Router {

    private $mappings;

    public function mapping() {
        $request = Application::instance()->get_request();

        if (empty($this->mappings)) {
            $mappings = Config::get('router.mappings');
        } else {
            $mappings = $this->mappings;
        }

        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, 'index.php/') === 0) {
            $uri = substr($uri, strlen('index.php/'));
        }

        if ($pos = strpos($uri, '/?')) {
            $uri = substr($uri, 0, $pos);
        } else if ($pos = strpos($uri, '?')) {
            $tmp = explode('?', $uri);
            $uri = $tmp[0];
        }

        if ($uri !== '/') {
            $end = strlen($uri) - 1;
            while ($end > 0 && $uri[$end] === '/') {
                $end--;
            }
            $uri = substr($uri, 0, $end + 1);
        }

        if ($uri[0] === '/') {
            $uri = substr($uri, 1);
        }

        if ($mappings) {
            foreach ($mappings as $regex => $rule) {
                if (!preg_match($regex, $uri, $matches)) {
                    continue;
                }
                if (isset($rule['maps']) && is_array($rule['maps'])) {
                    foreach ($rule['maps'] as $pos => $key) {
                        if (isset($matches[$pos]) && $matches[$pos] != '') {
                            $request->set_attribute($key, $matches[$pos]);
                        }
                    }
                }
                return $rule;
            }
        }
        return $this->auto_mapping($uri);
    }

    protected function auto_mapping($uri) {
        $application = Application::instance();
        $request = $application->get_request();

        $default_rule = array();
        $default_rule['controller'] = Config::get('router.default_controller');
        $default_rule['action'] = Config::get('router.default_action');

        $rule = array();
        $matches = explode('/', $uri);
        if ($matches) {
            if ($controller = current($matches)) {
                if (preg_match("/^V[0-9]{0,4}$/i", $controller)) {
                    $controller = $controller . '_' . ucfirst(next($matches));
                }
                $rule['controller'] = ucfirst($controller) . 'Controller';
            }

            if ($action = next($matches)) {
                $rule['action'] = $action . 'Action';
            }

            while (false !== ($next = next($matches))) {
                $request->set_param($next, urldecode(next($matches)));
            }
        }
        return array_merge($default_rule, $rule);
    }

}