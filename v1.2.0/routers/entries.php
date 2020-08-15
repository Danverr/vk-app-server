<?php

include_once __DIR__ . "/../api.php";
include_once __DIR__ . "/../utils/vkApiQuery.php";
include_once __DIR__ . "/../utils/notificationSender.php";

class Entries extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $res = $this->getEntries($data, $userId);
            $this->sendResponse($res);
        } elseif ($method == 'POST' && count($url) == 0) {
            $res = $this->createEntries($data, $userId);
            $this->sendResponse($this->DBH->lastInsertId(), 201);
        } elseif ($method == 'PUT' && count($url) == 0) {
            $res = $this->updateEntry($data, $userId);
            $this->sendResponse(null, 204);
        } elseif ($method == 'DELETE' && count($url) == 0) {
            $res = $this->deleteEntry($data, $userId);
            $this->sendResponse(null, 204);
        } else {
            $this->sendResponse("No such method in 'entries' table", 400);
        }
    }

    public function getEntries($data, $userId)
    {
        $data = $this->getParams($data, [], ["users", "afterDate", "beforeDate", "beforeId","count"]);
        $data["users"] = $this->getUsers($userId, $data["users"]);
        $params = [];

        // Формируем перечень юзеров, записи которых нам нужны
        $matchUsers = "";

        if (count($data["users"])) {
            $matchUsers = "(userId IN " . getPlaceholders(count($data["users"])) . ")";
            $params = array_merge($params, $data["users"]);
        }

        // После какой даты брать записи
        $afterDate = "";

        if (!is_null($data["afterDate"])) {
            $afterDate = "AND date >= ?";
            $params[] = $data["afterDate"];
        }

        // До какой даты и id брать записи
        $beforeDate = "";

        if (!is_null($data["beforeDate"])) {
            $beforeDate = "AND date < ?";
            $params[] = $data["beforeDate"];

            if (!is_null($data["beforeId"])) {
                $beforeDate = "AND (date < ? OR date = ? AND entryId < ?)";
                $params[] = $data["beforeDate"];
                $params[] = $data["beforeId"];
            }
        } elseif (!is_null($data["beforeId"])) {
            $this->sendResponse("You cant use 'beforeId' param without 'beforeDate'");
        }

        // Определяем кол-во записей
        $count = !is_null($data["count"]) ? ("LIMIT " . (int)$data["count"]) : "";

        // Порядок записей
        $order = "ORDER BY date DESC, entryId DESC";

        // Формируем запросы
        $subQuery = "SELECT entryId FROM entries WHERE $matchUsers $afterDate $beforeDate $order $count";
        $query = "SELECT * FROM ($subQuery) ids INNER JOIN entries using(entryId) $order";

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["NO_COLON"]);

        // Форматируем данные
        for ($i = 0; $i < count($res); $i++) {
            if ($res[$i]["isPublic"] == 0 && $res[$i]["userId"] != $userId) {
                $res[$i]["title"] = "";
                $res[$i]["note"] = "";
            }
        }

        return $res;
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
        $params["user_ids"] = $userId;
        $params["name_case"] = "gen";

        $userData = vkApiQuery("users.get", $params)[0];
        $userName = $userData["first_name"] . " " . $userData["last_name"];

        $message = "Кажется, у $userName сейчас не лучшие дни. Поддержи друга в трудную минуту!";

        $subQuery1 = "SELECT toId FROM statAccess WHERE fromId = $userId";
        $subQuery2 = "SELECT userId FROM users WHERE lowStatsNotif = 1";
        $query = "SELECT userId FROM ($subQuery1) ids INNER JOIN ($subQuery2) notif ON notif.userId = ids.toId";

        $users = $this->pdoQuery($query);
        $users = array_map(function ($row) {
            return $row["userId"];
        }, $users);

        $sender = new NotificationSender();
        $sender->send($users, $message);
    }

    public function createEntries($data, $userId)
    {
        // Данные запроса
        $data = $this->getParams($data, ["entries", "isImport"]);
        $entries = json_decode($data["entries"], true);
        $query = "";
        $params = [];
        $isImport = $data["isImport"] == "true";
        $shouldSendNotif = false;
        $optionalKeys = ["title", "note", "isPublic"];

        include_once "users.php";
        $usersRouter = new Users();

        if ($isImport) {
            $importAttempts = $usersRouter->getUserData([], $userId);
            $importAttempts = (int)$importAttempts["importAttempts"];
            
            if ($importAttempts > 0) {
                $optionalKeys[] = "date";
            } else {
                $this->sendResponse("No import attempts left", 400);
            }
        }

        // Модифицируем запрос и проверяем данные
        for ($i=0; $i < count($entries); $i++) {
            $entry = $this->getParams($entries[$i], ["mood", "stress", "anxiety"], $optionalKeys);
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
        $res =  $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
        
        if ($isImport) {
            $usersRouter->setImportAttempts($importAttempts - 1, $userId);
        }

        return $res;
    }

    public function updateEntry($data, $userId)
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

        // Делаем запрос
        return $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
    }

    public function deleteEntry($data, $userId)
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
        return $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
    }
}
