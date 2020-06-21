<?php

class API
{
    protected const CLIENT_SECRET = 'vrikjcw4PJpIvKWswil8';
    protected const ACCESS_TOKEN = '174b0977174b0977174b09772a173a41301174b174b097749d406ac389fd14cd082862a';
    private const HTTP_CODE_NAMES = [
      "200" => "OK", // Ответ на успешные GET, PUT, PATCH или DELETE. Этот код также используется для POST, который не приводит к созданию.
      "201" => "Created", // Этот код состояния является ответом на POST, который приводит к созданию.
      "204" => "No Content", // Нет содержимого. Это ответ на успешный запрос, который не будет возвращать тело (например, запрос DELETE)
      "400" => "Bad Request", // Указывает, что запрос искажен, например, если тело не может быть проанализировано
      "401" => "Unauthorized", // Указаны или недействительны данные аутентификации
      "403" => "Forbidden", // Когда аутентификация прошла успешно, но аутентифицированный пользователь не имеет доступа к ресурсу
      "404" => "Not found", // Если запрашивается несуществующий ресурс
      "429" => "Too Many Requests", // Запрос отклоняется из-за ограничения скорости
      "500" => "Internal Server Error" // Любая внутренняя ошибка сервера, которая не входит в рамки остальных ошибок класса
    ];

    private const HOST = "remotemysql.com";
    private const USERNAME = "4xv3ZUe5kc";
    private const PASSWORD = "ooRAVaHThw";
    private const DATABASE = "4xv3ZUe5kc";
    protected $DBH;

    public function __construct()
    {
        try {
            $this->DBH = new PDO("mysql:host=" . self::HOST . ";dbname=" . self::DATABASE, self::USERNAME, self::PASSWORD);
            $this->DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            $this->sendResponse($exception->getMessage(), 500);
        }
    }

    protected function getAccess($userId)
    {
        $query = "SELECT * FROM statAccess WHERE toId = :toId";

        $res = $this->pdoQuery($query, ["toId" => $userId]);
        $res = array_map(function ($row) {
            return (int)$row["fromId"];
        }, $res);

        return $res;
    }

    protected function checkAccess($userId, $users)
    {
        $access = $this->getAccess($userId);

        foreach ($users as $user) {
            if ($user != $userId && array_search($user, $access) === false) {
                $this->sendResponse("You don't have permission to do this", 403);
            }
        }
    }

    protected function pdoQuery($query, $params = [], $options = [])
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

        if ($responce != null && $code >= 400) {
            $title = $code . " " . self::HTTP_CODE_NAMES[strval($code)];

            if (!is_null($responce)) {
                $title .= ": ";
            }

            $responce = $title . $responce;
        }

        echo json_encode($responce, JSON_NUMERIC_CHECK);
        exit(0);
    }

    protected function getParams($data, $required, $optional = [])
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
                if (strlen($data[$param]) == 0) {
                    $this->sendResponse("'$param' parameter is empty", 400);
                }

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

$api = new API();
