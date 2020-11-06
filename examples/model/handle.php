<?php
require "../../vendor/autoload.php";

include "./connection.php";

use Bundles\DatabaseConnection;
use Bundles\Request;

$request = new Request;

$request->validate([
  "name" => ["required", "string", "minimum:3", "maximum:255"],
  "email" => ["required", "email"],
  "gender" => ["required", "string", "in:male,female"],
  "age" => ["required", "string"],
  "password" => ["required", "string", "confirmed"]
]);

$connection = DatabaseConnection::attempt($db_params);
