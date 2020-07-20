<?php

include_once __DIR__ . "./../api.php";

class StatNotifications extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->isUserSubscribed($data, $userId);
        } elseif ($method == 'POST' && count($url) == 0) {
            $this->subscribeUser($data, $userId);
        } elseif ($method == 'DELETE' && count($url) == 0) {
            $this->deleteUser($data, $userId);
        } else {
            $this->sendResponse("No such method in 'statNotifications' table", 400);
        }
    }

    private function isUserSubscribed($data, $userId)
    {
        // Данные запроса
        $query = "SELECT * FROM statNotifications WHERE userId=:userId";
        $params["userId"] = $userId;

        // Делаем запрос
        $res = $this->pdoQuery($query, $params);
        $res = count($res) == 1;
        $this->sendResponse($res);
    }

    private function subscribeUser($data, $userId)
    {
        // Данные запроса
        $query = "INSERT INTO statNotifications SET userId=:userId";
        $params["userId"] = $userId;

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse($res, 201);
    }

    private function deleteUser($data, $userId)
    {
        // Данные запроса
        $query = "DELETE FROM statNotifications WHERE userId=:userId";
        $params["userId"] = $userId;

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse(null, 204);
    }
}

$router = new StatNotifications();
