<?php

include_once __DIR__ . "./../api.php";
include_once __DIR__ . "./../utils/formatters.php";

class EntryNotifications extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->isUserSubscribed($data, $userId);
        } elseif ($method == 'POST' && count($url) == 0) {
            $this->subscribeUser($data, $userId);
        } elseif ($method == 'PUT' && count($url) == 0) {
            $this->updateSubscribtion($data, $userId);
        } elseif ($method == 'DELETE' && count($url) == 0) {
            $this->deleteUser($data, $userId);
        } else {
            $this->sendResponse("No such method in 'entryNotifications' table", 400);
        }
    }

    private function isUserSubscribed($data, $userId)
    {
        // Данные запроса
        $query = "SELECT time FROM entryNotifications WHERE userId=:userId";
        $params["userId"] = $userId;

        // Делаем запрос
        $res = $this->pdoQuery($query, $params);

        if (count($res)) {
            $res = $res[0]["time"];
        } else {
            $res = null;
        }

        $this->sendResponse($res);
    }

    private function subscribeUser($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["time"]);
        $params["userId"] = $userId;
        $query = "INSERT INTO entryNotifications SET " . getSetters($params);

        // Форматируем данные
        $params["time"] .= ":00";

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse($res, 201);
    }

    private function updateSubscribtion($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["time"]);
        $params["userId"] = $userId;
        $query = "UPDATE entryNotifications SET time=:time WHERE userId=:userId";

        // Форматируем данные
        $params["time"] .= ":00";

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse(null, 204);
    }

    private function deleteUser($data, $userId)
    {
        // Данные запроса
        $query = "DELETE FROM entryNotifications WHERE userId=:userId";
        $params["userId"] = $userId;

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse(null, 204);
    }
}

$router = new EntryNotifications();
