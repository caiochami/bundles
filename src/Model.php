<?php

/**
 * Model 
 * 
 * @author Caio Chami
 * @since 2020-09-03
 */

namespace Bundles;

use \PDO;

use Bundles\Collection;
use Bundles\Traits\Model\HelperTrait;
use Bundles\Traits\Model\ManipulationTrait;
use \Exception;

class Model
{
    use ManipulationTrait, HelperTrait;

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

    protected static $limit = null;

    protected static $customJoins = [];

    private static $tableName = null;

    private static $columns = null;

    private static $joins = null;

    private static $key = null;

    private static $groupBy = [];

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
        " . self::renderSelect() . "
        FROM 
        " . self::$tableName . "
        " . self::renderJoins() . "
        " . self::renderConditions() . "
        " . self::renderGroupBy() . "
        " . self::renderOrderBy() . "
        " . self::renderLimit() . "
        {$conditions} {$sort} {$limit};";

        return $sql;
    }

   

    public static function setConnection(PDO $conn)
    {
        self::checkIfStaticPropertiesExists();
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

    

    public static function table(string $tableName)
    {
        self::$tableName = $tableName;
        return new static;
    }

    

    public static function join()
    {
        $args = func_get_args();

        $join = self::formatJoinArgs($args);

        self::$customJoins[] = "INNER JOIN " . $join;

        return new static;
    }

    public static function leftJoin()
    {
        $args = func_get_args();

        $join = self::formatJoinArgs($args);

        self::$customJoins[] = "LEFT JOIN " . $join;

        return new static;
    }

    public static function rightJoin()
    {
        $args = func_get_args();

        $join = self::formatJoinArgs($args);

        self::$customJoins[] = "RIGHT JOIN " . $join;

        return new static;
    }

    private static function renderJoins()
    {
        if (count(self::$customJoins)) {
            return implode(" ", self::$customJoins);
        }
        return self::$joins;
    }

    private static function renderSelect()
    {
        return self::$columns;
    }

    public static function select(array $columnsSet): self
    {
        $columns = [];

        foreach ($columnsSet as $set) {

            if (gettype($set) === "array" && count($set) === 1) {
                foreach ($set as $column => $alias) {
                    $columns[] = $column . " " . $alias;
                }
            } else {
                $columns[] = $set;
            }
        }

        self::$columns = implode(', ', $columns);
        return new static;
    }

    public static function groupBy(array $groupBy): self
    {
        self::$groupBy = $groupBy;
        return new static;
    }

    private static function renderGroupBy()
    {
        return count(self::$groupBy) ? " GROUP BY " . implode(",", self::$groupBy) : "";
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

    private static function tableColumns(): array
    {
        $sql = "SHOW COLUMNS FROM " . self::getTableName() . "; ";
        $stmt = self::$conn->prepare($sql);
        $stmt->execute();
        $data = [];
        while ($resource = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $resource["Field"];
        }
        $stmt->closeCursor();

        return $data;
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

    private static function renderConditions()
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

        return $OPTIONS;
    }

    private static function renderLimit()
    {
        return self::$limit ?? "";
    }

    private static function renderOrderBy()
    {
        return count(self::$orderBy) ? "ORDER BY " . implode(",", self::$orderBy) : "";
    }

    public static function limit($value)
    {
        self::$limit = $value;


        if (gettype($value) === "integer") {
            self::$limit = "LIMIT " . $value;
        }

        return new static;
    }

    public static function retrieve($options = ['debug' => false, 'show_query' => false])
    {
        return new Collection(self::fetch("", "", "", $options));
    }

    public static function with(array $relationships)
    {
        self::$with = $relationships;

        return new static(self::$conn);
    }

    public static function find(...$args)
    {
        $conn = $GLOBALS['connection'] ?? null;
        $options = [];
        $id = null;
        $length = count($args);

        if ($length > 3 || $length < 1) {
            throw new Exception("The find method requires at least one parameter.");
        }

        foreach ($args as $arg) {
            if ($arg instanceof PDO) {
                $conn = $arg;
            }
            if (gettype($arg) === "integer" || gettype($arg) === "string") {
                $id = intval($arg);
            }

            if (gettype($arg) === "array") {
                $options = $arg;
            }
        }

        if ($id === null) {
            return null;
        }

        self::setConnection($conn);
        
        $id = htmlspecialchars(strip_tags($id));

        $collection = self::fetch('WHERE ' . static::$key . ' = ' . $id, '', 'LIMIT 0,1', $options);
        
        return count($collection) ? $collection[0] : null;
    }


      

    private static function fetch($conditions = "", $sort = "", $limit = "", $options = ['debug' => false, 'show_query' => false])
    {
        if (!self::$conn) {
            throw new Exception('Connection was not set');
        }

        $sql = self::getSql($conditions, $sort, $limit);

        return self::execute(function ($stmt, $options) {

            $collection = [];

            if ($stmt->execute() && $stmt->rowCount()) {
                while ($resource = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $self = self::setProperties(new static($options['connection']), $resource);
                    array_push($collection, $self);
                }

                $stmt->closeCursor();
            }

            return $collection;
        }, array_merge($options, ['sql' => $sql]));
    }

    private static function lastInsertedId()
    {
        $sql = "SELECT MAX(" . self::getKeyName() . ") AS last_inserted_id FROM " . self::getTableName() . "; ";

        return self::execute(function ($stmt) {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $row['last_inserted_id'];
        }, ['sql' => $sql]);
    }
    

    private function copycat(self $instance): void
    {
        foreach (get_object_vars($instance) as $key => $value) {
            $this->{$key} = $value;
        }
    }

    
}
