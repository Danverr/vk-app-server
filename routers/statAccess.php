<?php

include_once __DIR__ . "./../api.php";
include_once __DIR__ . "./../utils/formatters.php";

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
        $query = "SELECT * FROM statAccess WHERE";
        $params["userId"] = $userId;

        // Проверяем параметры
        if ($data["type"] == "toId") {
            $query .= " fromId = :userId";
        } elseif ($data["type"] == "fromId") {
            $query .= " toId = :userId";
        } else {
            $this->sendResponse("'type' property must equal only 'toId' or 'fromId'", 400);
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params);
        $res = array_map(function ($row) use ($data) {
            return $row[$data["type"]];
        }, $res);

        $this->sendResponse($res);
    }

    private function createUserPair($data, $userId)
    {
        // Данные запроса
        $friends = $this->getParams($data, ["toId"]);
        $friends = explode(",", $friends["toId"]);
        $query = "INSERT INTO statAccess (fromId, toId) VALUES ";
        $params = [];

        foreach ($friends as $friend) {
            if ($friend == $userId) {
                $this->sendResponse("You cant add access to yourself", 400);
            }

            $query .= "(?, ?), ";
            $params[] = $userId;
            $params[] = $friend;
        }

        $query = rtrim($query, ", ");

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
        $this->sendResponse($res, 201);
    }

    private function deleteUserPair($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["toId"]);
        $params = explode(",", $params["toId"]);
        $query = "DELETE FROM statAccess WHERE fromId = ? AND toId IN " . getPlaceholders(count($params));
        array_unshift($params, $userId);

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
        $this->sendResponse($res);
    }
}

$router = new StatAccess();
