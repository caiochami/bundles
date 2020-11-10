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
Its behavior changes when no "Content-Type: application/json" is present in the headers. It will redirect to the previous page with the errors and the request data attached to the \$\_SESSION variable.

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

The validation will be like this:

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
],
[
    "required" => "The field %s is required"
]);

```

## The Model Class

Let's assume we have a users table with an id and a name column.
The model class have all the methods you need to create, update, delete and query entries.
Check out the following code:

```php

 require "vendor/autoload.php";

 use Bundles\Model;
 use Bundles\DatabaseConnection as Database;

 class User extends Model
 {

    protected static $tableName = "users";

    protected static $columns = "id,name,address.city";

    protected static $joins = "INNER JOIN addresses address ON address.id = users.address_id";

    protected static $key = "id";

 }

 $connection = Database::connect([
    "db_host" =>  "HOST",
    "db_name" =>  "NAME",
    "db_user" =>  "USER",
    "db_psw" =>  "PASSWORD"
])

 //creating a user
 $user = new User($connection);
 $user->name = "Foo";
 $user->save();

 //or

 $user = User::create(["name"=> "Foo"]);

 //finding and updating the user

 $user = User::find($connection, 1); 
 //or 
 $user = User::use($connection)->where("id", 1)->first();

 $user->name = "Bar";
 $user->save();

 //or

 User::update($connection, 1, [
     "name" => "Bar"
 ]);

 // deleting a user

 User::destroy($connection, 1);

 $user = User::find($connection, 1);
 $user->delete();

 //querying all users

 User::all($connection);

 //or more specific

 User::use($connection)
 ->where("name", "LIKE", "%bar%")
 ->whereBetween("birthday", "1993-01-01", "2020-01-01"),
 ->orWhere("address.city", "California")
 ->limit(15)
 ->retrieve()
 ->get();

//for debugging you can pass an array just like this
User::find($connection, 1, ["debug" => true]);

//or 

User::where("birthday", ">", "1972-11-11")
->orderBy(["name", "DESC"])
->retrieve(["debug" => true])
->get(); 

```

For more examples, check out the examples/ folder
