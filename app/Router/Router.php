<?php

namespace app\Router;

use app\Services\MakeRoutId;

class Router
{
    /**
     * @var array<mixed>
     */
    private static array $directoryList = [];

    /**
     * @param string $uri
     * @param string $class
     * @param string $method
     * @param bool $form_data
     * @param bool $files
     * @return void
     */
    public static function post(string $uri, string $class, string $method, bool $form_data = false, bool $files = false): void
    {
        self::$directoryList[] = [
            "uri" => $uri,
            "class" => $class,
            "method" => $method,
            "post" => true,
            "form_data" => $form_data,
            "files" => $files,
        ];
    }

    /**
     * @param string $uri
     * @param string $class
     * @param string $method
     * @param bool $argument
     * @return void
     */
    public static function get(string $uri, string $class, string $method, bool $argument = false): void
    {
        self::$directoryList[] = [
            "uri" => $uri,
            "class" => $class,
            "method" => $method,
            "get" => true,
            "argument" => $argument,
        ];
    }

    /**
     * @param string $uri
     * @param string $class
     * @param string $method
     * @return void
     */
    public static function delete(string $uri, string $class, string $method): void
    {
        self::$directoryList[] = [
            "uri" => $uri,
            "class" => $class,
            "method" => $method,
            "delete" => true,
        ];
    }

    /**
     * @param string $uri
     * @param string $class
     * @param string $method
     * @param bool $argument
     * @return void
     */
    public static function put(string $uri, string $class, string $method, bool $argument = false): void
    {
        self::$directoryList[] = [
            "uri" => $uri,
            "class" => $class,
            "method" => $method,
            "put" => true,
            "argument" => $argument,
        ];
    }

    /**
     * @return string|null
     */
    public static function enable(): string|null
    {
        $url = $_GET["q"] ?? 'url';
        $url = rtrim($url, '/');
        foreach (self::$directoryList as $item) {
            $routerURI = array_diff(explode('/', $item["uri"]), array(''));
            $currentURL = explode('/', $url);
            if ($item["uri"] === '/' . $url || (preg_match("/{.*}/", $item["uri"]) && $routerURI[1] === $currentURL[0])) {
                if (isset($item["get"]) && $item["get"] === true && $_SERVER['REQUEST_METHOD'] === 'GET') {
                    if (isset($item["argument"]) && $item["argument"] === true) {
                        return MakeRoutId::createRout($item);
                    }
                    $method = $item["method"];
                    $class = new $item["class"]();
                    return $class->$method();
                }
                if (isset($item["delete"]) && $item["delete"] === true && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
                    return MakeRoutId::createRout($item);
                }
                if (isset($item["put"]) && $item["put"] === true && $_SERVER['REQUEST_METHOD'] === 'PUT') {
                    if (isset($item["argument"]) && $item["argument"] === true) {
                        return MakeRoutId::createRout($item);
                    }
                    $method = $item["method"];
                    $class = new $item["class"]();
                    return $class->$method();
                }
                if (isset($item["post"]) && $item["post"] === true && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $method = $item["method"];
                    $class = new $item["class"]();
                    if (isset($item['form_data']) && isset($item['files'])) {
                        return $class->$method($_POST, $_FILES);
                    } elseif (isset($item['form_data']) && !isset($item['files'])) {
                        return $class->$method($_POST);
                    } elseif (isset($item['files']) && !isset($item['form_data'])) {
                        return $class->$method($_FILES);
                    } else {
                        return $class->$method();
                    }
                }
            }
        }
        return null;
    }
}
