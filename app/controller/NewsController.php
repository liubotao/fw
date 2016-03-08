<?php

class NewsController extends Controller {

    public function viewAction() {
        echo "<pre>";
        print_r(DB::table("test")->findByNickname());
        echo "</pre>";
    }


}