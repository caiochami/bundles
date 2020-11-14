<?php
header("Content-Type: application/json");

use Bundles\Validator;

require "../vendor/autoload.php";

$request = [

    "order_by" => [
        [
            "column" => "Column 1",
            "orientation" => "DESC"
        ],
        [
            "column" => "Column 2",
            "orientation" => "ASC"
        ],
        12,
        233
        

    ]

];

$validator = Validator::make($request, [
    "order_by" => ["nullable", "array"],
    "order_by.*.column" => ["required", "string"],
    "order_by.*.orientation" => ["required", "string", "in:DESC,ASC"],
]);

var_dump($validator->errors());
