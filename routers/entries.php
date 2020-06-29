<?php

include_once __DIR__ . "./../api.php";
include_once __DIR__ . "./../utils/formatters.php";

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
        $data = $this->getParams($data, [], ["users", "day", "month", "skip", "count"]);
        $data["users"] = $this->getUsers($userId, $data["users"]);
        $params = [];

        // Формируем перечень юзеров, записи которых нам нужны
        $matchUsers = "";

        if (count($data["users"])) {
            $matchUsers .= "userId IN (" . getPlaceholders(count($data["users"])) . ") AND isPublic = 1";
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

        // Определяем лимит записей и сдвиг
        $limit = !is_null($data["count"]) ? ("LIMIT " . (int)$data["count"]) : "";
        $offset = !is_null($data["skip"]) ? ("OFFSET " . (int)$data["skip"]) : "";

        if ($offset != "" && $limit == "") {
            $this->sendResponse("You cant use 'skip' param without 'count'");
        }

        // Порядок записей
        $order = "ORDER BY date DESC, entryId ASC";

        // Формируем запросы
        $subQuery = "SELECT entryId FROM entries WHERE $matchUsers $timeInterval $order $limit $offset";
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
        $query = "SELECT entryId, userId, mood, stress, anxiety, date FROM entries WHERE userId IN (" . getPlaceholders(count($params)) . ")";

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
                $date = new DateTime($value, new DateTimeZone("UTC"));

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

    private function createEntries($data, $userId)
    {
        // Данные запроса
        $data = $this->getParams($data, ["entries"]);
        $entries = json_decode($data["entries"], true);
        $query = "";
        $params = [];

        // Модифицируем запрос и проверяем данные
        for ($i=0; $i < count($entries); $i++) {
            $entry = $this->getParams($entries[$i], ["mood", "stress", "anxiety"], ["title", "note", "isPublic", "date"]);
            $entry["userId"] = $userId;
            $entry = $this->formatEntry($entry);

            foreach ($entry as $key => $value) {
                $params[] = $value;
            }

            $query .= "INSERT INTO entries SET " . getSetters($entry, true) . ";";
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
        $this->sendResponse($this->DBH->lastInsertId(), 201);
    }

    private function updateEntry($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["entryId"], ["mood", "stress", "anxiety", "isPublic", "title", "note"]);
        $params = $this->formatEntry($params);

        $query = "UPDATE entries SET " . getSetters($params) . " WHERE entryId = :entryId";

        // Проверяем права доступа
        $checkQuery = "SELECT userId FROM entries WHERE entryId = :entryId";
        $res = $this->pdoQuery($checkQuery, ["entryId" => $params["entryId"]]);

        if (count($res) && $res[0]["userId"] != $userId) {
            $this->sendResponse("You don't have permission to do this", 403);
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse($res);
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
        $this->sendResponse($res);
    }
}

$router = new Entries();
