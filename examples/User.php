<?php

use Bundles\Model;

class User extends Model
{
    protected static $tableName = "users";

    protected static $columns = "*";//"id,email,password,name,age,gender,created_at,updated_at";

    protected static $joins = "";

    protected static $key = "id";

    public $address;

    public function address()
    {
        $this->address = Address::use(self::$conn)->where("id", $this->address_id)->retrieve()->first();
        return $this->address;
    }
}
