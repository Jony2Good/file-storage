<?php

namespace app\Services;

class MakeRoutId
{
    /**
     * @param array<string> $item
     * @return mixed
     * @throws \Exception
     */
    public static function createRout(array $item): mixed
    {
        $reqUri = preg_replace("/(^\/)|(\/$)/", "", $_REQUEST['q']);
        $urlPaths = parse_url($reqUri, PHP_URL_PATH);
        if(!isset($reqUri) || !isset($urlPaths) || !$urlPaths) {
            throw new \Exception('Bad rout');
        }
        $param = explode('/', $urlPaths);
        $reqUri = explode("/", $reqUri);

        $id = null;
        $str = null;

        $address = end($reqUri);

        if ((int)$address > 0) {
            $id = $address;
        } else {
            $str = $address;
        }

        $method = $item["method"];
        $class = new $item["class"]();

        if (isset($param[2]) && isset($param[3])) {
            $fileId = $param[2];
            $user_id = $param[3];
            return $class->$method($fileId, $user_id);
        } else {
            if (isset($id)) {
                return $class->$method($id);
            } else {
                return $class->$method($str);
            }
        }
    }
}
