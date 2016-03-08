<?php

class Table_Test extends DB_Table {

    protected $table = "cms_article";

    public function __construct() {
        parent::__construct();
    }

    public function findByNickname() {
       $result = $this->select("*")->where('id', 1)->get();
        var_dump(DB::getQueryLog());
        return $result;

    }

}