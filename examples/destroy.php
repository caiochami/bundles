<?php

error_reporting(-1);
ini_set("display_errors", "On");

require "../vendor/autoload.php";

include "./User.php";
include "./connection.php";

use Bundles\DatabaseConnection;
use Bundles\Request;

$connection = DatabaseConnection::attempt($db_params);
$request = new Request($connection);

$request->validate([
  "id" => ["required", "string", "exists:users,id"],
]);

$user = User::find($connection, $request->id);
$user->delete();

$message = urlencode("User deleted successfully");
header("Location: index.php?message=". $message);
