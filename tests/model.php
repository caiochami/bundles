<?php
error_reporting(-1);
ini_set("display_errors", "On");
use Bundles\DatabaseConnection;
use Bundles\Model;

require "../vendor/autoload.php";

class User extends Model
{
    protected static $tableName = "users";
    protected static $columns = "*";
    protected static $joins = "";
    protected static $key = "id";
}

$connection = DatabaseConnection::attempt([
    "db_host" =>  "localhost",
    "db_name" =>  "testingdb",
    "db_user" =>  "root",
    "db_psw" =>  "root"
]);

$users = User::all($connection)->get();

?>

<pre>
<?php var_dump($users, ["debug" => true]); ?>
</pre>
