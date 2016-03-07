<?php

class Performance {

    private $suffix = "xhprof";

    public function start() {
        if (!extension_loaded('xhprof')) {
            return;
        }

        if (isset($_GET['debug']) && $_GET['debug'] == "ok") {
            xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
            Application::instance()->register_shutdown_function(array($this, "shutdown"));
        }
    }

    public function shutdown() {
        $xhprof_data = xhprof_disable();
        $xhprof_data = serialize($xhprof_data);
        $dir = ini_get("xhprof.output_dir");
        if (empty($dir)) {
            $dir = "/tmp";
        }

        $run_id = date("mdHis") . substr(uniqid(rand()), -6);
        $type = APP_NAME;
        $file = "$run_id.$type." . $this->suffix;
        $file_name = $dir . "/" . $file;
        $file = new File();
        $file->create($file_name, $xhprof_data);
    }
}