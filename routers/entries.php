<?php

include_once __DIR__ . "./../api.php";
include_once __DIR__ . "./../utils/formatters.php";
include_once __DIR__ . "./../utils/notificationSender.php";

class Entries extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->getEntries($data, $userId);
        } elseif ($method == 'GET' && count($url) == 1 && $url[0] == "stats") {
            $this->getStats($data, $userId);
        } elseif ($method == 'POST' && count($url) == 0) {
            $this->createEntries($data, $userId);
        } elseif ($method == 'PUT' && count($url) == 0) {
            $this->updateEntry($data, $userId);
        } elseif ($method == 'DELETE' && count($url) == 0) {
            $this->deleteEntry($data, $userId);
        } else {
            $this->sendResponse("No such method in 'entries' table", 400);
        }
    }

    private function getEntries($data, $userId)
    {
        $data = $this->getParams($data, [], ["users", "day", "month", "lastId", "lastDate","count"]);
        $data["users"] = $this->getUsers($userId, $data["users"]);
        $params = [];

        // Формируем перечень юзеров, записи которых нам нужны
        $matchUsers = "";

        if (count($data["users"])) {
            $matchUsers .= "userId IN " . getPlaceholders(count($data["users"])) . " AND isPublic = 1";
            $params = array_merge($params, $data["users"]);

            if (array_search($userId, $data["users"]) !== false) {
                $matchUsers .= " OR userId = ?";
                $params[] = $userId;
            }

            $matchUsers = "($matchUsers)";
        }

        // Определяем временной промежуток
        $timeInterval = "";

        if (!is_null($data["day"])) {
            $timeInterval .= "AND date >= ? AND date < DATE_ADD(?, INTERVAL 1 DAY)";
            $params[] = $data["day"];
            $params[] = $data["day"];
        } elseif (!is_null($data["month"])) {
            $timeInterval .= "AND date >= ? AND date < DATE_ADD(?, INTERVAL 1 MONTH)";
            $data["month"] .= "-01";
            $params[] = $data["month"];
            $params[] = $data["month"];
        }

        // После какого ID брать записи
        $lastId = "";

        if (!is_null($data["lastId"])) {
            $lastId = "AND entryId < ?";
            $params[] = $data["lastId"];
        }

        // После какой даты брать записи
        $lastDate = "";

        if (!is_null($data["lastDate"])) {
            $lastDate = "AND date < ?";
            $params[] = $data["lastDate"];
        }

        // Определяем кол-во записей
        $limit = !is_null($data["count"]) ? ("LIMIT " . (int)$data["count"]) : "";

        // Порядок записей
        $order = "ORDER BY date DESC, entryId ASC";

        // Формируем запросы
        $subQuery = "SELECT entryId FROM entries WHERE $matchUsers $timeInterval $lastId $lastDate $order $limit";
        $query = "SELECT * FROM ($subQuery) ids INNER JOIN entries using(entryId) $order";

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["NO_COLON"]);
        $this->sendResponse($res);
    }

    private function getStats($data, $userId)
    {
        // Данные запроса
        $data = $this->getParams($data, [], ["users", "startDate"]);
        $data["users"] = $this->getUsers($userId, $data["users"]);
        $params = $data["users"];

        $order = "ORDER BY date DESC";
        $query = "SELECT entryId, userId, mood, stress, anxiety, date FROM entries WHERE userId IN " . getPlaceholders(count($params));

        // Модифицируем запрос
        if (!is_null($data["startDate"])) {
            $query .= " AND date >= ?";
            $params[] = $data["startDate"];
        }

        $query .= " ORDER BY date DESC";

        // Делаем запрос и форматируем данные
        $stats = $this->pdoQuery($query, $params, ["NO_COLON"]);
        $res = [];

        foreach ($data["users"] as $user) {
            $res[$user] = [];
        }

        foreach ($stats as $stat) {
            $id = $stat["userId"];
            unset($stat["userId"]);
            $res[$id][] = $stat;
        }

        $this->sendResponse($res);
    }

    private function formatEntry($entry)
    {
        $params = ["entryId", "userId", "mood", "stress", "anxiety", "title", "note", "isPublic", "date"];
        $newEntry = [];

        foreach ($entry as $key => $value) {
            if ($key == "mood" || $key == "stress" || $key == "anxiety" || $key == "isPublic") {
                $mn = 1;
                $mx = 5;

                if ($key == "isPublic") {
                    $mn = 0;
                    $mx = 1;
                }

                $value = max($value, $mn);
                $value = min($value, $mx);
            } elseif ($key == "date") {
                $now = new DateTime("now", new DateTimeZone("UTC"));

                try {
                    $date = new DateTime($value, new DateTimeZone("UTC"));
                } catch (Exception $error) {
                    $this->sendResponse("Incorrect date", 400);
                }

                if ($date > $now) {
                    $value = $now->format('Y-m-d H:i:s');
                }
            }

            if (array_search($key, $params) !== false) {
                $newEntry[$key] = $value;
            }
        }

        return $newEntry;
    }

    private function isLowHealthEntry($entry)
    {
        $LIMIT = 2;
        $time = new DateTime("now", new DateTimeZone("UTC"));
        $time->sub(new DateInterval('P2D'));
        $time->setTime(0, 0, 0);

        $date = new DateTime($entry["date"], new DateTimeZone("UTC"));

        $mn = 5;
        $mn = min($mn, $entry["mood"]);
        $mn = min($mn, 6 - $entry["anxiety"]);
        $mn = min($mn, 6 - $entry["stress"]);

        return $date >= $time && $mn <= $LIMIT;
    }

    private function sendLowHealthNotif($userId)
    {
        $url = "https://api.vk.com/method/users.get?v=5.120&lang=ru";
        $url .= "&user_ids=" . $userId;
        $url .= "&name_case=gen";
        $url .= "&access_token=" . self::ACCESS_TOKEN;

        $userData = file_get_contents($url);
        $userData = json_decode($userData, true)["response"][0];
        $userName = $userData["first_name"] . " " . $userData["last_name"];

        $message = "Кажется, у $userName сейчас не лучшие дни. Поддержи друга в трудную минуту!";

        $subQuery = "SELECT toId FROM statAccess WHERE fromId=$userId";
        $query = "SELECT userId FROM ($subQuery) ids INNER JOIN statNotifications notif ON notif.userId = ids.toId";

        $users = $this->pdoQuery($query);
        $users = array_map(function ($row) {
            return $row["userId"];
        }, $users);

        $sender = new NotificationSender();
        $sender->send($users, $message);
    }

    private function createEntries($data, $userId)
    {
        // Данные запроса
        $data = $this->getParams($data, ["entries"]);
        $entries = json_decode($data["entries"], true);
        $query = "";
        $params = [];
        $shouldSendNotif = false;

        // Модифицируем запрос и проверяем данные
        for ($i=0; $i < count($entries); $i++) {
            $entry = $this->getParams($entries[$i], ["mood", "stress", "anxiety"], ["title", "note", "isPublic", "date"]);
            $entry["userId"] = $userId;
            $entry = $this->formatEntry($entry);

            if ($this->isLowHealthEntry($entry)) {
                $shouldSendNotif = true;
            }

            foreach ($entry as $key => $value) {
                $params[] = $value;
            }

            $query .= "INSERT INTO entries SET " . getSetters($entry, true) . ";";
        }

        // Отправляем уведомление о низком здоровье
        if ($shouldSendNotif) {
            $this->sendLowHealthNotif($userId);
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
        $this->sendResponse($this->DBH->lastInsertId(), 201);
    }

    private function updateEntry($data, $userId)
    {
        // Данные запроса
        $optionalParamsName = ["mood", "stress", "anxiety", "isPublic", "title", "note"];
        $params = $this->getParams($data, ["entryId"], $optionalParamsName);
        $params = $this->formatEntry($params);

        $query = "UPDATE entries SET " . getSetters($params) . " WHERE entryId = :entryId";

        // Проверяем права доступа
        $checkQuery = "SELECT * FROM entries WHERE entryId = :entryId";
        $entry = $this->pdoQuery($checkQuery, ["entryId" => $params["entryId"]]);

        if (count($entry) == 0) {
            $this->sendResponse("No such entry with entryId = " . $params["entryId"], 400);
        } else {
            $entry = $entry[0];
        }

        if ($entry["userId"] != $userId) {
            $this->sendResponse("You don't have permission to do this", 403);
        }

        foreach ($optionalParamsName as $param) {
            if (!is_null($params[$param])) {
                $entry[$param] = $params[$param];
            }
        }

        if ($this->isLowHealthEntry($entry)) {
            $this->sendLowHealthNotif($userId);
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse(null, 204);
    }

    private function deleteEntry($data, $userId)
    {
        // Данные запроса
        $query = "DELETE FROM entries WHERE entryId = :entryId";
        $params = $this->getParams($data, ["entryId"]);

        // Проверяем права доступа
        $checkQuery = "SELECT userId FROM entries WHERE entryId = :entryId";
        $res = $this->pdoQuery($checkQuery, ["entryId" => $params["entryId"]]);

        if (count($res) && $res[0]["userId"] != $userId) {
            $this->sendResponse("You don't have permission to do this", 403);
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse(null, 204);
    }
}

$router = new Entries();
