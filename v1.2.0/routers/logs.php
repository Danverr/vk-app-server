<?php

include_once __DIR__ . "/../api.php";

class Logs extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'POST' && count($url) == 0) {
            $res = $this->log($data, $userId);
            $this->sendResponse(null, 201);
        } else {
            $this->sendResponse("No such method in 'logs' table", 400);
        }
    }

    public function log($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["userAgent", "error"]);
        $params["userId"] = $userId;
        
        $params["date"] = new DateTime("now", new DateTimeZone("Europe/Moscow"));
        $params["date"] = $params["date"]->format("Y-m-d H:i:s");

        $query = "INSERT INTO logs SET " . getSetters($params);

        // Делаем запрос
        return $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
    }
}