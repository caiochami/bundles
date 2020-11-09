<?php

use Bundles\Model;

class Address extends Model
{
    protected static $tableName = "addresses address";

    protected static $columns = "id, city";//"id,email,password,name,age,gender,created_at,updated_at";

    protected static $joins = "";

    protected static $key = "id";

    public $users;

    public function users()
    {
        $this->users = User::use(self::$conn)->where("address_id",  $this->id);
        return $this->users;
    }
}
