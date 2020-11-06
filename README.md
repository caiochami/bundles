# bundles
A laravel-like toolbox for non-laravel projects


## Installation

[composer] composer require caiochami/bundles

## Usage

require the composer-autoloader.

```php
require "vendor/autoload.php";
```

## Request example

The Request class works similarly to the laravel validation.
If errors are found, it will display the information in json format.
Its behavior changes when no "Content-Type: application/json" is present in headers. It will redirect to the previous page with the errors and the request data attached to the $_SESSION variable.

1. Instantiate the class Request. Passing in a PDO instance is required when using the rule "exists".
2. Specify the rules
3. (Optional) Specify custom messages

Let's say we have a request with the following data.

var postData = [
    "name" : "John Doe",
    "password" : "12345678",
    "password_confirmation" : "1234568",
    "id" : null,
    "patients": [
        {
            "name": "Foo",
            "age": 2
        },
        {
            "name": "Bar",
            "age": 10
        }
    ]
];

the validation will be like this:

```php

header("Content-Type: application/json;");

use Bundles\Request;

$request = new Request;

//all empty string variables are converted to null automatically
$request->validate([
    "name" => ["required","string", "minimum:3", "maximum:100"],
    "password" => ["required", "string", "confirmed"],
    "id" => ["nullable", "integer"],
    "patients" => ["required", "array", "gte:1"],
    "patients.*.name" => ["required", "string"],
    "patients.*.age" => ["required", "integer"]
]);









