<?php

include_once __DIR__ . "./../api.php";

class Notifications extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->getNotifications($data, $userId);
        } elseif ($method == 'PUT' && count($url) == 0) {
            $this->updateNotifications($data, $userId);
        } else {
            $this->sendResponse("No such method in 'notifications' table", 400);
        }
    }

    private function getNotifications($data, $userId)
    {
        // Данные запроса
        $query = "SELECT createEntry, lowStats, accessGiven FROM notifications WHERE userId=:userId";
        $params["userId"] = $userId;

        // Делаем запрос
        $res = $this->pdoQuery($query, $params);

        if (count($res)) {
            $this->sendResponse($res[0]);
        } else {
            $params = [
                "userId" => $userId,
                "createEntry" => null,
                "lowStats" => 0,
                "accessGiven" => 0,
            ];

            $query = "INSERT INTO notifications SET " . getSetters($params);
            $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);

            unset($params["userId"]);
            $this->sendResponse($params);
        }
    }

    private function updateNotifications($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, [], ["lowStats", "accessGiven"]);
        $query = "UPDATE notifications SET ";

        // Форматируем данные
        foreach ($params as $key => $value) {
            $params[$key] = min((int)$params[$key], 1);
            $params[$key] = max((int)$params[$key], 0);
        }

        if (!is_null($data["createEntry"])) {
            if ($data["createEntry"] == "null") { // null
                $query .= "createEntry = null" . (count($params) ? ", " : " ");
            } else { // time
                $nums = explode(":", $data["createEntry"]);

                if (count($nums) == 2 && strlen($nums[0]) == 2 && strlen($nums[1]) == 2 &&
                (int)$nums[0] <= 23 && (int)$nums[0] >= 0 &&
                (int)$nums[1] <= 59 && (int)$nums[1] >= 0 && (int)$nums[1] % 10 == 0) {
                    $params["createEntry"] = $data["createEntry"] . ":00";
                } else {
                    $this->sendResponse("Incorrect  time format in 'createEntry' param", 400);
                }
            }
        }

        // Проверяем наличие записи
        $res = $this->pdoQuery("SELECT * FROM notifications WHERE userId=:userId", [
          "userId" => $userId,
        ]);

        // Делаем запрос
        if (count($res) == 0) {
            $params["userId"] = $userId;
            $query = "INSERT INTO notifications SET " . getSetters($params);
        } else {
            $query .= getSetters($params) . " WHERE userId=:userId";
            $params["userId"] = $userId;
        }

        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse(null, 204);
    }
}

$router = new Notifications();
