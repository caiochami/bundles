<?php

/**
 * Model 
 * 
 * @author Caio Chami
 * @since 2020-09-03
 */

namespace Bundles;

use \PDO;

use Carbon\Carbon;

use Bundles\Collection;

use \Exception;

class Model
{

    /**
     * @var PDO $conn
     */

    protected static $conn;

    /**
     * @var array $with
     */

    protected static $with = [];

    protected static $where = [];

    protected static $orWhere = [];

    protected static $whereBetween = [];

    protected static $whereIn = [];

    protected static $orderBy = [];

    protected static $withCount = null;

    protected static $limit = null;

    const ALLOWED_MYSQL_FUNCTIONS = ["NOW()", "CURRENT_DATE()", "CURRENT_TIMESTAMP()"];

    public function __construct($connection = null)
    {

        if ($connection) {
            self::$conn = $connection;
        }
    }

    public static function getSql($conditions = "", $sort = "", $limit = "")
    {
        $sql =
            "SELECT 
        " . static::$columns . "
        FROM 
        " . static::$tableName . "
        " . static::$joins . "
        " . self::getConditions() . "
        {$conditions} {$sort} {$limit};";

        return $sql;
    }

    public static function setConnection(PDO $conn)
    {
        self::$conn = $conn;
        self::clearParams();
    }

    public static function all(PDO $conn, $conditions = "")
    {
        self::setConnection($conn);
        return new Collection(self::fetch($conditions));
    }

    //define which database connection to use
    public static function use(PDO $conn)
    {
        self::setConnection($conn);
        return new static;
    }

    //sets table alias to column if it was not specified
    private static function formatColumn(string $column): string
    {
        if (in_array($column, self::ALLOWED_MYSQL_FUNCTIONS)) {
            return $column;
        }

        $tableAlias = self::getTableAlias();

        $formattedTableAlias = "";

        if ($tableAlias) {
            $formattedTableAlias = self::getTableAlias() . '.';
        }

        $exploded = explode('.', $column);
        return count($exploded) > 1 ? $column : $formattedTableAlias . $column;
    }

    private static function formatValue($value)
    {
        if (!in_array($value, self::ALLOWED_MYSQL_FUNCTIONS)) {
            if (gettype($value) === "string") {
                $value = "'" . $value . "'";
            } else {
                $value = (int) $value;
            }
        }

        return $value;
    }

    public static function table(string $tableName)
    {
        static::$tableName = $tableName;
        return new static;
    }

    public static function select(array $columns)
    {
        static::$columns = implode(', ', $columns);
        return new static;
    }

    public static function where(string $column, $value)
    {
        $totalArgs = func_num_args();
        $args = func_get_args();

        $column = self::formatColumn($column);

        $operator = "=";

        //sets default operator to equals if middle argument is omitted

        if ($totalArgs > 2) {
            $operator = $args[1];
            $value = $args[2];
        }

        self::$where[] = $column . ' ' . $operator . ' ' . self::formatValue($value);

        return new static;
    }

    public static function orWhere(string $column, string $value)
    {

        $totalArgs = func_num_args();
        $args = func_get_args();

        $column = self::formatColumn($column);

        $operator = "=";

        if ($totalArgs > 2) {
            $operator = $args[1];
            $value = $args[2];
        }

        self::$orWhere[] = $column . ' ' . $operator . ' ' . self::formatValue($value);

        return new static;
    }

    public static function whereIn(string $column, array $values)
    {

        $wrappedValues = [];

        $length = count($values);

        foreach ($values as $key => $value) {
            $wrappedValues[] = self::formatValue($value);
        }

        self::$whereIn[] = self::formatColumn($column) . " IN (" . implode(",", $wrappedValues) . ")";
        return new static;
    }

    private static function columns()
    {

        if (static::$columns === "*") {
            $sql = "SHOW COLUMNS FROM " . self::getTableName() . "; ";
            $stmt = self::$conn->prepare($sql);
            $stmt->execute();
            $data = [];
            while ($resource = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $resource["Field"];
            }
            $stmt->closeCursor();
            $unformattedColumns = $data;
        } else {
            $unformattedColumns = explode(",", static::$columns ?? "");
        }


        $columns = [];

        foreach ($unformattedColumns as $unformattedColumn) {
            $unformattedColumn = str_replace([" as ", " AS "], ["AS", "AS"], $unformattedColumn);
            $formattedColumn = explode("AS", $unformattedColumn);
            $column = trim($formattedColumn[0]);
            $dismemberedColumn = explode(".", $column);
            $fieldName = $dismemberedColumn[1] ?? $dismemberedColumn[0];

            $columns[$fieldName] = [
                "field_name" =>  $fieldName,
                "column" => $column,
                "alias" => $formattedColumn[1] ?? null
            ];
        }

        return Collection::create($columns);
    }

    private static function isParamAColumn(string $param): bool
    {
        $isColumn = self::columns()->find(function ($value) use ($param) {
            return $value["column"] === $param;
        });

        return !is_null($isColumn);
    }

    public static function whereBetween(string $column, string $startsAt, string $endsAt)
    {
        if (!self::isParamAColumn($startsAt)) {
            $startsAt = "'{$startsAt}'";
            $endsAt = "'{$endsAt}'";
        }

        self::$whereBetween[] = self::formatColumn($column) . " BETWEEN {$startsAt} AND {$endsAt}";
        return new static;
    }

    public static function orderBy(string $column, string $direction = "ASC")
    {
        self::$orderBy[] = self::formatColumn($column) . " " . $direction;
        return new static;
    }

    private static function getConditions()
    {
        $WHERE_LENGTH = count(self::$where);
        $WHERE = $WHERE_LENGTH ? " AND " . implode(' AND ', self::$where) : "";

        $OR_WHERE_LENGTH = count(self::$orWhere);
        $OR_WHERE = $OR_WHERE_LENGTH ? " AND " . implode(' AND ', self::$orWhere) : "";

        $WHERE_BETWEEN_LENGTH = count(self::$whereBetween);
        $WHERE_BETWEEN = $WHERE_BETWEEN_LENGTH ? " AND " . implode(' AND ', self::$whereBetween) : "";

        $WHERE_IN_LENGTH = count(self::$whereIn);
        $WHERE_IN = $WHERE_IN_LENGTH ? " AND " . implode(' AND ', self::$whereIn) : "";

        $CHECK_IF_HAS_OPTIONS = array_reduce([
            $WHERE_LENGTH,
            $WHERE_BETWEEN_LENGTH,
            $OR_WHERE_LENGTH,
            $WHERE_IN_LENGTH,
        ], function ($haystack, $length) {
            return $haystack + $length;
        });

        $OPTIONS = $CHECK_IF_HAS_OPTIONS ? ("WHERE 1=1 " . $WHERE . $OR_WHERE . $WHERE_IN . $WHERE_BETWEEN) : "";

        $ORDER_BY = count(self::$orderBy) ? "ORDER BY " . implode(",", self::$orderBy) : "";

        $LIMIT = self::$limit ?? "";

        return $OPTIONS . " " . $ORDER_BY . " " . $LIMIT;
    }

    public static function limit($value)
    {
        self::$limit = $value;

        if (gettype($value) === "integer") {
            self::$limit = "LIMIT " . $value;
        }

        return new static;
    }

    public static function withCount($column = null)
    {
        $column = $column ? self::formatColumn($column) : static::$key;
        self::$withCount = $column;
        static::$columns .= " , COUNT( " . $column . " ) AS with_count ";
        return new static;
    }

    public static function retrieve($options = ['debug' => false])
    {
        if (!self::$conn) {
            throw new Exception('Connection was not set');
        }

        $sql = self::getSql();

        $stmt = self::$conn->prepare($sql);
        $stmt->execute();

        $debug = $options['debug'] ?? false;

        if ($debug) {
            var_dump($stmt->errorInfo());
        }

        $collection = [];

        if ($stmt->execute() && $stmt->rowCount()) {

            while ($resource = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $self = self::setProperties(new static(self::$conn), $resource);

                array_push($collection, $self);
            }

            $stmt->closeCursor();
        }


        self::clearParams();

        return new Collection($collection);
    }

    public static function with(array $relationships)
    {

        self::$with = $relationships;

        return new static(self::$conn);
    }

    public static function find(PDO $conn, int $id, $options = [])
    {
        self::setConnection($conn);
        $id = htmlspecialchars(strip_tags($id));

        $collection = self::fetch('WHERE ' . static::$key . ' = ' . $id, '', 'LIMIT 0,1', $options);
        return count($collection) ? $collection[0] : null;
    }

    private static function setProperties($instance, array $data)
    {
        if (defined("CONNECTION_ID")) {
            $instance->connection_id = intval(CONNECTION_ID);
        }

        if (defined("CONNECTION_NAME")) {
            $instance->connection_name = CONNECTION_NAME;
        }

        foreach ($data as $key => $value) {
            if (\preg_match("/\_id/i", $key) || $key === "id") {
                $value = intval($data[$key]);
                $instance->{$key} = $value > 0 ? $value : null;
            } else {
                $instance->{$key} = $data[$key];
            }
        }


        $relationships = self::$with;

        foreach ($relationships as $relationship) {
            if (method_exists($instance, $relationship)) {

                $instance->{$relationship}();
            }
        }

        return $instance;
    }

    private static function clearParams()
    {
        //self::$with = [];

        self::$where = [];

        self::$orWhere = [];

        self::$whereBetween = [];

        self::$whereIn = [];

        self::$orderBy = [];

        self::$withCount = null;

        self::$limit = "";
    }

    private static function fetch($conditions = "", $sort = "", $limit = "", $options = ['debug' => false])
    {
        // select all query

        $sql = self::getSql($conditions, $sort, $limit);

        $stmt = self::$conn->prepare($sql);
        $stmt->execute();

        $collection = [];

        if ($stmt->execute() && $stmt->rowCount()) {
            while ($resource = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $self = self::setProperties(new static(self::$conn), $resource);
                array_push($collection, $self);
            }

            $stmt->closeCursor();
        }

        $debug = boolVal($options['debug'] ?? false);

        if ($debug) {
            var_dump($stmt->errorInfo());
        }

        return $collection;
    }

    public static function createOrUpdate(PDO $conn, array $data)
    {
        self::setConnection($conn);

        $data = self::filterData($data);

        $updateParams = [];
        $insertColumns = [];
        $insertValues = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== static::$key) {
                $updateParams[] = "{$key} = ?";
                $insertColumns[] = $key;
                $insertValues[] = "?";

                $values[] = addSlashes($value);
            }
        };

        $params = [];

        foreach ($data as $key => $value) {
            if ($key !== static::$key) $params[] = "{$key} = {$value}";
        };

        $sql =
            "INSERT INTO " . static::getTableName() . " (" . implode(",", $insertColumns) . ")
        VALUES ( " . implode(",", $insertValues) . " ) ON DUPLICATE KEY UPDATE " . implode(",", $updateParams);

        $stmt = self::$conn->prepare($sql);

        if ($stmt->execute(array_merge($values, $values))) {
            $id = self::lastInsertedId();
            return self::find(self::$conn, $id);
        }

        var_dump($stmt->errorInfo());

        return false;
    }

    public static function update(PDO $conn, int $id, array $data)
    {
        self::setConnection($conn);

        $data = self::filterData($data);



        $params = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== static::$key) {
                $params[] = "{$key} = ?";
                $values[] = $value;
            }
        };

        $sql = "UPDATE " . self::getTableName() . " SET " . implode(", ", $params) . " WHERE  " . self::getKeyName() . " = " . $id;
        $stmt = self::$conn->prepare($sql);

        if ($stmt->execute($values)) {
            return true;
        }

        return false;
    }

    protected static function getKeyName()
    {
        $arr =  explode('.', static::$key);
        if (count($arr) > 1) {
            return $arr[1];
        }

        return $arr[0];
    }

    protected static function getTableName()
    {
        return explode(' ', static::$tableName)[0];
    }

    protected static function getTableAlias()
    {
        $tableNameArr = explode(' ', static::$tableName);
        return count($tableNameArr) > 1 ? $tableNameArr[1] : null;
    }

    private static function filterData(array $data): array
    {
        $params = [];

        $columns = self::columns()->get();

        $keys = array_keys($data);

        foreach ($columns as $column) {
            $fieldName = $column["field_name"];

            if (in_array($fieldName, $keys)) {
                $value = $data[$fieldName];

                /*  if (\gettype($value) === "string" && !in_array($value, self::ALLOWED_MYSQL_FUNCTIONS)) {
                    $value = "'" . addslashes(strip_tags($value)) .  "'";
                } */

                $params[$fieldName] = $value;
            }
        }

        return $params;
    }

    public static function create(PDO $conn, array $data)
    {
        self::setConnection($conn);

        $params = self::filterData($data);

        $values =  array_values($params);

        $columns = array_keys($params);

        $tableName = self::getTableName();

        $sql = "INSERT INTO " . $tableName . " (" . implode(",", $columns) . ")";

        $bindValues = array_map(function () {
            return "?";
        }, $values);

        $sql .= "VALUES (" . implode(",", $bindValues) . ")";

        $connection = self::$conn;

        $stmt = $connection->prepare($sql);

        if ($stmt->execute($values)) {
            $id = self::lastInsertedId();
            $stmt->closeCursor();
            return self::find($connection, $id);
        }

        return false;
    }

    private static function lastInsertedId()
    {
        $sql = "SELECT MAX(" . static::$key . ") AS last_inserted_id FROM " . static::$tableName . "; ";

        $stmt = self::$conn->prepare($sql);

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $row['last_inserted_id'];
    }

    public static function destroy(PDO $conn, int $id)
    {

        self::setConnection($conn);

        $sql =
            "DELETE FROM  
        " . static::getTableName() . "
        WHERE " . static::getKeyName() . " = ?;";

        $stmt = self::$conn->prepare($sql);

        $stmt->bindParam('1', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function delete()
    {
        return self::destroy(self::$conn, $this->id);
    }

    public function save()
    {
        $array = [];

        foreach ($this->fields as $field) {
            $array[$field] = $this->{$field};
        }

        $connection = self::$conn;

        if ($this->id) {
            return self::update($connection, $this->id, $array);
        } else {
            return self::create($connection, $array);
        }
    }

    public function presentedTimestamp($column, $format = "d/m/Y H:i:s")
    {
        return $this->{$column} ? Carbon::parse($this->{$column})->format($format) : null;
    }
}
