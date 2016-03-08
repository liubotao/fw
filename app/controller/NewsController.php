<?php

class NewsController extends Controller {

    public function viewAction() {
        DB::table('Test')->findByNickname();
    }


}