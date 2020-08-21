<?php

include_once __DIR__ . "/../api.php";

class Users extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $res = $this->getUserData($data, $userId);
            $this->sendResponse($res);
        } elseif ($method == 'PUT' && count($url) == 0) {
            $res = $this->updateUserData($data, $userId);
            $this->sendResponse(null, 204);
        } else {
            $this->sendResponse("No such method in 'users' table", 400);
        }
    }

    public function getUserData($data, $userId)
    {
        // Данные запроса
        $query = "SELECT * FROM users WHERE userId=:userId";
        $params = ["userId" => $userId];

        // Делаем запрос
        $res = $this->pdoQuery($query, $params);

        if (count($res) == 0) {
            $this->pdoQuery(
                "INSERT INTO users SET userId = :userId",
                ["userId" => $userId],
                ["RETURN_ROW_COUNT"]
            );

            $res = $this->pdoQuery($query, $params);
        }

        $res = $res[0];
        unset($res["userId"]);
        return $res;
    }

    public function updateUserData($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, [], ["lowStatsNotif", "accessGivenNotif"]);
        $query = "INSERT INTO users SET";
        $setters = "";

        // Форматируем данные
        foreach ($params as $key => $value) {
            $params[$key] = min((int)$params[$key], 1);
            $params[$key] = max((int)$params[$key], 0);
        }

        // Добавляем кастомный параметр
        if (!is_null($data["createEntryNotif"])) {
            if ($data["createEntryNotif"] == "null") { // null
                $setters .= "createEntryNotif = null" . (count($params) ? ", " : " ");
            } else { // time
                $nums = explode(":", $data["createEntryNotif"]);

                if (count($nums) == 2 && strlen($nums[0]) == 2 && strlen($nums[1]) == 2 &&
                (int)$nums[0] <= 23 && (int)$nums[0] >= 0 &&
                (int)$nums[1] <= 59 && (int)$nums[1] >= 0 && (int)$nums[1] % 10 == 0) {
                    $params["createEntryNotif"] = $data["createEntryNotif"] . ":00";
                } else {
                    $this->sendResponse("Incorrect time format in 'createEntryNotif' param", 400);
                }
            }
        }

        // Делаем запрос
        $params["userId"] = $userId;
        $setters .= " " . getSetters($params);
        $query .= " $setters ON DUPLICATE KEY UPDATE $setters";

        return $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
    }

    public function setImportAttempts($attempts, $userId)
    {
        // Данные запроса
        $params = ["importAttempts" => $attempts, "userId" => $userId];
        $setters = getSetters($params);
        $query = "INSERT INTO users SET $setters ON DUPLICATE KEY UPDATE $setters";

        // Делаем запрос
        return $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
    }
}
