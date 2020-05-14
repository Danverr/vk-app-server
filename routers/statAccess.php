<?php

include_once __DIR__ . "./../api.php";

class StatAccess extends API
{
    public function route($method, $url, $data)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->getUserPairs($data);
        }
        if ($method == 'POST' && count($url) == 0) {
            $this->createUserPair($data);
        }
        if ($method == 'DELETE' && count($url) == 0) {
            $this->deleteUserPair($data);
        } else {
            $this->sendResponse("No such method in 'statAccess' table", 400);
        }
    }

    private function getUserPairs($data)
    {
        // Данные запроса
        $query = "SELECT fromId FROM statAccess WHERE toId = :userId";
        $params = $this->getParams($data, ["userId"]);

        // Делаем запрос
        $res = $this->pdoQuery($query, $params)->fetchAll();
        $res = array_map(function ($row) {
            return $row['fromId'];
        }, $res);

        $this->sendResponse($res);
    }

    private function createUserPair($data)
    {
        // Данные запроса
        $params = $this->getParams($data, ["toId", "fromId"]);
        $query = "INSERT INTO statAccess SET " . $this->getSetters($params);

        // Делаем запрос
        $this->pdoQuery($query, $params);
        $this->sendResponse(null, 201);
    }

    private function deleteUserPair($data)
    {
        // Данные запроса
        $params = $this->getParams($data, ["toId", "fromId"]);
        $query = "DELETE FROM statAccess WHERE toId = :toId AND fromId = :fromId";

        // Делаем запрос
        $this->pdoQuery($query, $params);
        $this->sendResponse(null, 204);
    }
}

$router = new StatAccess();
