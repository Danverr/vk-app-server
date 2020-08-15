<?php

include_once __DIR__ . "/utils/formatters.php";
include_once __DIR__ . "/../appData.php";

class API extends AppData
{
    protected $DBH;

    public function __construct()
    {
        try {
            $this->DBH = new PDO("mysql:host=" . self::HOST . ";dbname=" . self::DATABASE, self::USERNAME, self::PASSWORD);
            $this->DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->DBH->exec("SET NAMES utf8mb4");
        } catch (PDOException $exception) {
            $this->sendResponse($exception->getMessage(), 500);
        }
    }

    public function getAccess($userId)
    {
        $query = "SELECT * FROM statAccess WHERE toId = :toId";

        $res = $this->pdoQuery($query, ["toId" => $userId]);
        $res = array_map(function ($row) {
            return (int)$row["fromId"];
        }, $res);

        return $res;
    }

    public function checkAccess($userId, $users)
    {
        $access = $this->getAccess($userId);
        $access[] = $userId;

        if (array_intersect($users, $access) != $users) {
            $this->sendResponse("You don't have permission to do this", 403);
        }
    }

    public function getUsers($userId, $users)
    {
        if (is_null($users)) { // Формируем список юзеров к которым есть доступ
            $users = $this->getAccess($userId);
            $users[] = $userId;
        } else { // Если параметр не пустой, проверяем доступ к тем, что указаны
            $users = explode(",", $users);
            $this->checkAccess($userId, $users);
        }

        return $users;
    }

    public function pdoQuery($query, $params = [], $options = [])
    {
        $NO_COLON = array_search("NO_COLON", $options) !== false;
        $RETURN_ROW_COUNT = array_search("RETURN_ROW_COUNT", $options) !== false;

        $STH = $this->DBH->prepare($query);
        $STH->setFetchMode(PDO::FETCH_ASSOC);
        $newParams = [];

        foreach ($params as $param => $value) {
            $newParams[($NO_COLON ? "" : ":") . $param] = $value;
        }

        try {
            $STH->execute($newParams);
        } catch (Exception $e) {
            $this->sendResponse($e->getMessage(), 400);
        }

        if ($RETURN_ROW_COUNT) {
            return $STH->rowCount();
        } else {
            return $STH->fetchAll();
        }
    }

    public function sendResponse($responce = null, $code = 200)
    {
        http_response_code($code);

        if ($responce !== null) {
            if ($code >= 300) {
                $title = $code . " " . self::HTTP_CODE_NAMES[strval($code)];

                if (!is_null($responce)) {
                    $title .= ": ";
                }

                $responce = $title . $responce;
            }

            echo json_encode($responce, JSON_NUMERIC_CHECK);
        }

        if ($code >= 300) {
            throw new Exception($responce);
        }

        exit(0);
    }

    public function getParams($data, $required, $optional = [])
    {
        $res = [];

        foreach ($required as $param) {
            if (!isset($data[$param])) {
                $this->sendResponse("'$param' required parameter is not found", 400);
            } elseif (strlen($data[$param]) == 0) {
                $this->sendResponse("'$param' parameter is empty", 400);
            } else {
                $res[$param] = $data[$param];
            }
        }

        foreach ($optional as $param) {
            if (isset($data[$param])) {
                $res[$param] = $data[$param];
            }
        }

        return $res;
    }

    public function checkSign($url)
    {
        // Получаем query-параметры из URL
        $query_params = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $query_params);

        $sign_params = [];
        foreach ($query_params as $name => $value) {
            // Получаем только vk параметры из query
            if (strpos($name, 'vk_') !== 0) {
                continue;
            }

            $sign_params[$name] = $value;
        }

        // Сортируем массив по ключам
        ksort($sign_params);

        // Формируем строку вида "param_name1=value&param_name2=value"
        $sign_params_query = http_build_query($sign_params);

        // Получаем хеш-код от строки, используя защищеный ключ приложения. Генерация на основе метода HMAC.
        $sign = rtrim(strtr(base64_encode(hash_hmac('sha256', $sign_params_query, self::CLIENT_SECRET, true)), '+/', '-_'), '=');

        // Сравниваем полученную подпись со значением параметра 'sign'
        $status = $sign === $query_params['sign'];

        return $status ? $sign_params["vk_user_id"] : null;
    }
}
