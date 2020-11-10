<?php

/**
 * @author Caio Chami
 * @since 2020-10-19
 *
 * This class handle all incoming http request data
 *
 */

namespace Bundles;

use Bundles\Validator;

use Bundles\Collection;

use \PDO;

class Request
{
    /**
     * @var \PDO $conn
     */

    protected static $conn;

    public function __construct(PDO $conn = null)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['errors']);
        unset($_SESSION['input']);

        $data =
            json_decode(file_get_contents('php://input'), true) ??
            json_decode(json_encode($_REQUEST), true) ??
            [];

        self::$conn = $conn;

        $content = Collection::create((array) $data, true)->get();

        foreach ($content as $prop => $value) {
            $this->{$prop} = $value;
        }
    }

    public function has($fieldName)
    {
        return isset($this->{$fieldName});
    }

    public function filled($fieldName)
    {
        return $this->has($fieldName) &&
            ((!is_null($this->{$fieldName}) && !empty($this->{$fieldName})) || (gettype($fieldName) === "array" && count($this->{$fieldName}) > 0));
    }

    public function merge(array $array): void
    {
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function isJsonable()
    {
        return isset($_SERVER['HTTP_CONTENT_TYPE']) && preg_match("/application\/json/i", $_SERVER['HTTP_CONTENT_TYPE']);
    }

    public function only(array $indexes): array
    {
        $arr = [];

        $content = $this->all();

        foreach ($content as $key => $value) {
            if (in_array($key, $indexes)) {
                $arr[$key] = $content[$key];
            }
        }

        return $arr;
    }

    public function all()
    {
        return json_decode(json_encode($this), true);;
    }

    public function validate(array $rules, array $customMessages = []): array
    {
        $validator = Validator::make($this->all(), $rules, self::$conn, $customMessages);

        if ($validator->fails()) {
            http_response_code(422);

            $response = [
                'message' => 'Erros encontrados',
                'errors' => $validator->errors()
            ];

            if ($this->isJsonable()) {
                die(json_encode($response));
            } else {
                $_SESSION['input'] = $this->all();
                $_SESSION['errors'] = $validator->errors();
                if (isset($_SERVER['HTTP_REFERER'])) {
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                }
                die(print_r($response));
            }
        }

        return $validator->validated();
    }
}
