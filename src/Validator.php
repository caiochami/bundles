<?php

/**
 * @author Caio Chami
 * @since 2020-10-19
 *
 * Subject: "Request validator"
 *
 */

namespace Bundles;

use Bundles\Rule;
use Bundles\Collection;

use \Exception;

class Validator extends Rule
{
    /**
     * @var \PDO $conn
     */

    protected static $conn;

    /**
     * @var \Bundles\Collection $request
     */

    protected static $request;

    /**
     * @var array $customMessages
     */

    protected static $customMessages = [];

    /**
     * @var \stdClass $validation
     */

    protected static $validation;

    /**
     * @var array $rules
     */

    private static $rules = [];

    /**
     * @var array $errors
     */

    private static $errors = [];

    /**
     * @var array $validated
     */

    private static $validated = [];

    /**
     * @var bool $success
     */

    private $success = false;

    //creates a self instance with validation results
    public static function make(array $request, array $rules, \PDO $connection = null, array $customMessages = []): self
    {
        self::$conn = $connection;
        self::$customMessages = $customMessages;
        self::$request = Collection::create($request, true);
        self::$validation = new \stdClass();
        self::$rules = $rules;

        foreach ($rules as $field => $ruleSet) {
            self::$validation->fieldName = $field;
            self::$validation->ruleSet = $ruleSet;
            self::$validation->isNested = false;
            $dismemberedFields = self::dismemberField($field);
            self::handleFieldSet($dismemberedFields, $ruleSet);
        }

        return new self;
    }

    private static function handleFieldSet(array $fieldSet, array $ruleSet)
    {
        $request = self::$request;


        $length = count($fieldSet);
        $path = [];
        $position = 0;



        foreach ($fieldSet as $key => $index) {

            $position++;
            $path[] = $index;
            $parentPath = array_slice($path, 0, $position - 1);

            

            self::$validation->fieldIndex = $index;
            self::$validation->parentPath = $parentPath;



            if ($index === "*" && in_array("*", $fieldSet)) {

                self::$validation->isNested = true;

                if (!self::checkIfParentIsFilled()) {
                    break;
                }

           

                $parentValue = $request->getValueByPath($parentPath);
                if (gettype($parentValue) === "array") {
                    $remainingPath = array_slice($fieldSet, $position);
                    foreach ($parentValue as $key => $el) {

                        self::handleFieldSet(array_merge($parentPath, [$key], $remainingPath), $ruleSet);
                    }
                }
                return;
            } else {
                self::$validation->currentPath = $path;
                
                $currentValue = $request->getValueByPath($path);

                if ($position === $length) {
                    self::exec($ruleSet, implode('.', $path), $currentValue);
                }
            }
        }
    }

    private static function checkIfParentIsFilled()
    {
        $path = self::$validation->parentPath;
        $parentFieldName = implode(".", $path);
        $parentValue = self::$request->getValueByPath($path);
        $parentRules = self::$rules[$parentFieldName];

        $isNullable = in_array("nullable", $parentRules) && is_null($parentValue);

        return !$isNullable && gettype($parentValue) === "array" && count($parentValue) > 0;
    }

    //run validation based on ruleset and field name provided
    private static function exec(array $ruleSet, string $field, $value): void
    {

        foreach ($ruleSet as $rule) {
            $rule = self::dismemberRule($rule);
            self::$validation->currentField = $field;
            self::$validation->currentRule = $rule->name;

            if (!method_exists(__CLASS__, $rule->name)) {
                throw new \Exception('Rule "' . $rule->name . '" does not exists');
            }

            //if the rule set has nullable rule, it will ignore other rules if the value is null
            if ($rule->name === "nullable" && is_null($value)) {
                $verified = true;
                break;
            } elseif ($rule->name === "required_if") {

                if (count($rule->params) !== 2) {
                    throw new Exception("Rule required_if expects 2 params");
                }

                if ($rule->params[0] !== $rule->params[1]) {
                    $verified = true;
                    break;
                }

                $rule->params = [
                    self::$validation->comparingFieldName ?? $rule->params[0],
                    $rule->params[1]
                ];
            } elseif ($rule->name === "required_with") {

                if (count($rule->params) !== 1) {
                    throw new Exception("Rule required_with expects 1 param and it must be referenced to another field name");
                }

                if (!$rule->params[0]) {
                    break;
                }

                $rule->params = [
                    self::$validation->comparingFieldName,
                    $rule->params[0]
                ];
            } elseif ($rule->name === "confirmed") {

                $confirmationFieldValue = $rule->params[0] ?? null;

                if (!$confirmationFieldValue) {
                    $confirmationFieldName =  self::$validation->fieldIndex . "_confirmation";
                    $path = array_merge(self::$validation->parentPath, [$confirmationFieldName]);
                    $confirmationFieldValue = self::$request->getValueByPath($path) ?? [];
                }

                $rule->params[0] = $confirmationFieldValue;
            } elseif ($rule->name === "exists" || $rule->name === "unique") {

                $rule->params = [
                    $rule->params[0],
                    self::$validation->comparingFieldName ?? $rule->params[1],
                    self::getConnection(),
                    $rule->params[2] ?? null,
                    $rule->params[3] ?? null,
                ];
            }

            $verified = self::{$rule->name}($value, $rule->params);


            if (!$verified) {

                self::$errors[$field][] = self::getErrorMessage($rule->name, array_merge([$field], $rule->params), self::$customMessages);
            }

            self::$validated[$field] = $value;
        }
    }

    private static function getConnection()
    {
        $connection = static::$conn;

        if (!$connection) {
            throw new Exception('Connection was not set');
        }

        return $connection;
    }

    private static function dismemberField(string $field): array
    {
        $exploded = explode('.', $field);
        return $exploded;
    }

    private static function formatRule(string $rule): array
    {
        $exploded = explode(':', $rule, 2);
        $name = $exploded[0];
        $params = $exploded[1] ?? null;

        return [
            "name" => $name,
            "params" => $params
        ];
    }

    private static function dismemberRule(string $rule): \stdClass
    {
        $formattedRule = self::formatRule($rule);
        self::$validation->comparingFieldName = null;
        $path = self::$validation->parentPath;
        $request = self::$request;

        if ($formattedRule["params"]) {
            $formattedRule["params"] = array_map(
                function ($param) use ($path, $request) {

                    $path = self::$validation->parentPath;

                    $value = $request->getValueByPath(array_merge($path, [$param]));
                    if ($value) {
                        self::$validation->comparingFieldName = $param;
                        return $value;
                    }
                    return $param;
                },
                explode(",", $formattedRule["params"])
            );
        }

        $rule = new \stdClass();
        $rule->name = $formattedRule["name"];
        $rule->params = $formattedRule["params"] ?? [];

        return $rule;
    }

    //retrieves all errors
    public function errors(): array
    {
        return  $failed = array_filter(self::$errors, function ($error) {
            return count($error);
        });
    }

    //retrieves all validated data
    public function validated(): array
    {
        return self::$validated;
    }

    //checks if validation has failed
    public function fails(): bool
    {
        $this->success = false;

        //error counter
        $hasErrors =
            Collection::create(self::$errors)
            ->count();

        //returns error
        if ($hasErrors > 0) {
            $this->success = true;
        }

        return $this->success;
    }
}
