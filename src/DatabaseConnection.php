<?php

namespace Bundles;

use PDO;
use PDOException;

class DatabaseConnection
{

    private $db_host;
    private $db_name;
    private $db_user;
    private $db_psw;

    public $conn;

    /**
     * @param array $attributes;
     * 
     */

    public function __construct(array $attributes)
    {
        foreach ($attributes as $prop => $value) {
            $this->{$prop} = $value;
        }
    }

    // get the database connection
    public function getConnection()
    {

        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->db_host . ";dbname=" . $this->db_name, $this->db_user, $this->db_psw);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            //$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            die("Connection error: " . $exception->getMessage());

        }

        return $this->conn;
    }

    /**
     * @param array $attributes;
     * @return \PDO $connection;
     * 
     */

    public static function attempt(array $attributes)
    {
        $self = new self($attributes);
        return $self->getConnection();
    }
}
