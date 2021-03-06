<?php

error_reporting(-1);
ini_set("display_errors", "On");

require "../vendor/autoload.php";

include "./Address.php";
include "./User.php";
include "./connection.php";

use Bundles\DatabaseConnection;
use Bundles\Request;

$connection = DatabaseConnection::attempt($db_params);

$request = new Request($connection);

$request->validate([
  "id" => ["required_if:action,update", "string", "exists:users,id"],
  "name" => ["required", "string", "minimum:3", "maximum:255"],
  "email" => ["required", "email"],
  "gender" => ["required", "string", "in:male,female"],
  "age" => ["required", "string"],
  "birthday" => ["required", "date_format:Y-m-d", "before:2020-11-07"],
  "password" => ["required", "string", "confirmed"],
  "action" => ["required", "string", "in:store,update"],
  "city" => ["required", "string", "minimum:5", "unique:addresses,city"]
],
["required" => "The field %s is required"]);



$address = Address::createOrUpdate($connection, ["city" => $request->city]);
$request->merge(["address_id" => $address->id]);

if ($request->action === "update") {
  $user = User::update($connection, (int) $request->id, $request->all());
  $message = urlencode("User updated successfully");
} else {
  $user = User::create($connection, $request->all());
  $message = urlencode("User created successfully");
}

header("Location: index.php?message=" . $message);
