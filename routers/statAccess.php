<?php

include_once __DIR__ . "./../api.php";

class StatAccess extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->getUserPairs($data, $userId);
        } elseif ($method == 'POST' && count($url) == 0) {
            $this->createUserPair($data, $userId);
        } elseif ($method == 'DELETE' && count($url) == 0) {
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

        $query .= " ORDER BY date DESC";

        // Делаем запрос
        $res = $this->pdoQuery($query, $params);
        $res = array_map(function ($row) use ($data) {
            return [
              "id" => $row[$data["type"]],
              "date" => $row["date"],
            ];
        }, $res);

        $this->sendResponse($res);
    }

    private function sendAccessNotif($users, $userId)
    {
        $url = "https://api.vk.com/method/users.get?v=5.120&lang=ru";
        $url .= "&user_ids=" . $userId;
        $url .= "&access_token=" . self::ACCESS_TOKEN;

        $userData = file_get_contents($url);
        $userData = json_decode($userData, true)["response"][0];
        $userName = $userData["first_name"] . " " . $userData["last_name"];

        $message = "$userName дал вам доступ к своей статистике";

        $query = "SELECT userId FROM notifications WHERE accessGiven = 1 AND userId IN " . getPlaceholders(count($users));

        $users = $this->pdoQuery($query, $users, ["NO_COLON"]);
        $users = array_map(function ($row) {
            return $row["userId"];
        }, $users);

        $sender = new NotificationSender();
        $sender->send($users, $message);
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

            $query .= getPlaceholders(2) . ", ";
            $params[] = $userId;
            $params[] = $friend;
        }

        $query = rtrim($query, ", ");

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
        $this->sendAccessNotif($friends, $userId);
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
        $this->sendResponse(null, 204);
    }
}

$router = new StatAccess();
