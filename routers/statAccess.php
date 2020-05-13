<?php

include_once __DIR__ . "./../api.php";

class StatAccess extends API
{
    public function route($method, $url, $data)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->getAccessibleUsers($data);
        } else {
            $this->sendResponse("No such method in 'statAccess' table", 400);
        }
    }

    private function getAccessibleUsers($data)
    {
        // Данные запроса
        $query = "SELECT fromId FROM statAccess WHERE toId = :userId";
        $params = $this->getParams($data, ["userId"]);

        // Делаем запрос
        $res = $this->pdoQuery($query, $params);
        $res = array_map(function ($row) {
            return $row['fromId'];
        }, $res);

        $this->sendResponse($res);
    }
}

$router = new StatAccess();
