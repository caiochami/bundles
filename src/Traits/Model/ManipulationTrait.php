<?php

namespace Bundles\Traits\Model;

use PDO;

trait ManipulationTrait
{


    public static function create(PDO $conn, array $data, $options = [])
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

        return self::execute(
            function ($stmt, $options) {

                if ($stmt->execute($options['values'])) {
                    $id = self::lastInsertedId();
                    $stmt->closeCursor();
                    return self::find($options['connection'], $id);
                }

                return false;
            },
            array_merge($options, [
                'sql' => $sql,
                'values' => $values
            ])
        );
    }

    public static function createOrUpdate(PDO $conn, array $data)
    {
        $class = self::class;

        self::setConnection($conn);

        $data = self::filterData($data);

        $updateParams = [];
        $insertColumns = [];
        $insertValues = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== $class::$key) {
                $updateParams[] = "{$key} = ?";
                $insertColumns[] = $key;
                $insertValues[] = "?";

                $values[] = addSlashes($value);
            }
        };

        $params = [];

        foreach ($data as $key => $value) {
            if ($key !== $class::$key) $params[] = "{$key} = {$value}";
        };

        $sql =
            "INSERT INTO " . self::getTableName() . " (" . implode(",", $insertColumns) . ")
        VALUES ( " . implode(",", $insertValues) . " ) ON DUPLICATE KEY UPDATE " . implode(",", $updateParams);

        $stmt = $class::$conn->prepare($sql);

        if ($stmt->execute(array_merge($values, $values))) {
            $id = $class::lastInsertedId();
            return $class::find($class::$conn, $id);
        }

        return false;
    }

    public static function update(PDO $conn, int $id, array $data, array $options = [])
    {
        $class = self::class;
        self::setConnection($conn);

        $data = self::filterData($data);
        $params = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== $class::$key) {
                $params[] = "{$key} = ?";
                $values[] = $value;
            }
        };

        //var_dump($id, $values);
        $sql = "UPDATE " . self::getTableName() . " SET " . implode(", ", $params) . " WHERE  " . $class::getKeyName() . " = " . $id;

        return $class::execute(function ($stmt, $options) {
            if ($stmt->execute($options['values'])) {
                return self::find($options['connection'], $options['id']);
            }

            return false;
        }, array_merge($options, [
            'values' => $values,
            'sql' => $sql,
            'id' => $id
        ]));
    }

    public static function destroy(PDO $conn, int $id, array $options = [])
    {
        self::setConnection($conn);

        $sql =
            "DELETE FROM  
        " . self::getTableName() . "
        WHERE " . self::getKeyName() . " = ?;";

        return self::execute(function ($stmt, $options) {

            $stmt->bindParam('1', $options['id'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        }, array_merge($options, ['sql' => $sql, 'id' => $id]));
    }

    public function delete()
    {
        $class = self::class;
        return self::destroy($class::$conn, $this->id);
    }

    public function save(array $options = [])
    {
        $class = self::class;

        $params = [];

        foreach (get_object_vars($this) as $key => $value) {
            $params[$key] = $value;
        }

        $connection = $class::$conn;

        if (isset($this->id)) {
            $newInstance = self::update($connection, $this->id, $params, $options);
        } else {
            $newInstance = self::create($connection, $params, $options);
        }

        if ($newInstance) {
            $this->copycat($newInstance);
        }

        return $newInstance;
    }

    private static function execute(\Closure $closure, array $options = [])
    {
        $class = self::class;
        $connection = $class::$conn;

        $stmt = $connection->prepare($options['sql']);

        $execution = $closure($stmt, array_merge($options, ['connection' => $connection]));

        if (isset($options['debug']) && boolval($options['debug'])) {
            var_dump($stmt->errorInfo());
        }

        if (isset($options['show_query']) && boolval($options['show_query'])) {
            var_dump($options['sql']);
        }

        return $execution;
    }
}
