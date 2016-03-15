<?php

class NewsController extends Controller {

    public function viewAction() {
        $result = DB::table("test")->insert(array( "image" => "test", 'title'=>"疯狂动物城5",));
        var_dump($result);
    }


}