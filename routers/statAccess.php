<?php

include_once __DIR__ . "./../api.php";

class StatAccess extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->getUserPairs($data, $userId);
        }
        if ($method == 'POST' && count($url) == 0) {
            $this->createUserPair($data, $userId);
        }
        if ($method == 'DELETE' && count($url) == 0) {
            $this->deleteUserPair($data, $userId);
        } else {
            $this->sendResponse("No such method in 'statAccess' table", 400);
        }
    }

    private function getUserPairs($data, $userId)
    {
        // Данные запроса
        $query = "SELECT fromId FROM statAccess WHERE toId = :userId";
        $params = $this->getParams($data, ["userId"]);

        // Проверяем права доступа
        if ($params["userId"] != $userId) {
            $this->sendResponse("You don't have permission to do this", 403);
        }

        // Делаем запрос и форматируем данные
        $res = $this->pdoQuery($query, $params)->fetchAll();
        $res = array_map(function ($row) {
            return (int)$row['fromId'];
        }, $res);

        $this->sendResponse($res);
    }

    private function createUserPair($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["toId", "fromId"]);
        $query = "INSERT INTO statAccess SET " . $this->getSetters($params);

        // Проверяем права доступа
        if ($params["fromId"] != $userId) {
            $this->sendResponse("You don't have permission to do this", 403);
        }

        // Делаем запрос
        $this->pdoQuery($query, $params);
        $this->sendResponse(null, 201);
    }

    private function deleteUserPair($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["toId", "fromId"]);
        $query = "DELETE FROM statAccess WHERE toId = :toId AND fromId = :fromId";

        // Проверяем права доступа
        if ($params["fromId"] != $userId) {
            $this->sendResponse("You don't have permission to do this", 403);
        }

        // Делаем запрос
        $this->pdoQuery($query, $params);
        $this->sendResponse(null, 204);
    }
}

$router = new StatAccess();
