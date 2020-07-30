<?php

include_once __DIR__ . "./../api.php";
include_once __DIR__ . "./../utils/vkApiQuery.php";
include_once __DIR__ . "./../utils/notificationSender.php";

class StatAccess extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $res = $this->getUserPairs($data, $userId);
            $this->sendResponse($res);
        } elseif ($method == 'POST' && count($url) == 0) {
            $res = $this->createUserPair($data, $userId);
            $this->sendResponse($res, 201);
        } elseif ($method == 'DELETE' && count($url) == 0) {
            $res = $this->deleteUserPair($data, $userId);
            $this->sendResponse(null, 204);
        } else {
            $this->sendResponse("No such method in 'statAccess' table", 400);
        }
    }

    public function getUserPairs($data, $userId)
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

        return $res;
    }

    private function sendAccessNotif($users, $userId)
    {
        $params["user_ids"] = $userId;
        $userData = vkApiQuery("users.get", $params)["response"][0];
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

    public function createUserPair($data, $userId)
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

        return $res;
    }

    public function deleteUserPair($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["toId"]);
        $params = explode(",", $params["toId"]);
        $query = "DELETE FROM statAccess WHERE fromId = ? AND toId IN " . getPlaceholders(count($params));
        array_unshift($params, $userId);

        // Делаем запрос
        return $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
    }
}

$router = new StatAccess();
