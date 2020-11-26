<?php

use Bundles\DatabaseConnection;

require "../vendor/autoload.php";

require './Address.php';
require './connection.php';

$conn = DatabaseConnection::attempt($db_params);
$address = Address::find($conn, 92);
