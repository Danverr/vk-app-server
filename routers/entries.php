<?php

include_once __DIR__ . "./../api.php";
include_once __DIR__ . "./../utils/formatters.php";

class Entries extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->getEntries($data, $userId);
        } elseif ($method == 'GET' && count($url) == 1 && $url[0] == "all") {
            $this->getAllEntries($data, $userId);
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
        // Данные запроса
        $data = $this->getParams($data, ["users"], ["day", "month"]);
        $users = explode(",", $data["users"]);
        $params = $users;
        $query = "SELECT * FROM entries WHERE (isPublic = 1 AND userId IN " . getPlaceholders(count($params));

        // Проверяем права доступа
        $this->checkAccess($userId, $params);

        // Модифицируем запрос
        if (array_search($userId, $params) !== false) {
            $query .= " OR userId = ?";
            $params[] = $userId;
        }

        $query .= ")";

        if (!is_null($data["day"])) {
            $query .= " AND date >= ? AND date < DATE_ADD(?, INTERVAL 1 DAY)";
            $params[] = $data["day"];
            $params[] = $data["day"];
        } elseif (!is_null($data["month"])) {
            $query .= " AND date >= ? AND date < DATE_ADD(?, INTERVAL 1 MONTH)";
            $data["month"] .= "-01";
            $params[] = $data["month"];
            $params[] = $data["month"];
        }

        $query .= " ORDER BY date DESC";

        // Делаем запрос и форматируем данные
        $entries = $this->pdoQuery($query, $params, ["NO_COLON"]);
        $res = [];

        foreach ($users as $user) {
            $res[$user] = [];
        }

        foreach ($entries as $entry) {
            $id = $entry["userId"];
            unset($entry["userId"]);
            $res[$id][] = $entry;
        }

        $this->sendResponse($res);
    }

    private function getAllEntries($data, $userId)
    {
        // Формируем список юзеров к которым есть доступ
        $access = $this->getAccess($userId);

        // Данные запроса
        $matchUsers = count($access) ? "OR (userId IN (" . implode(", ", $access) . ") AND isPublic = 1)" : "";
        $subQuery = "SELECT entryId FROM entries WHERE userId = :userId $matchUsers ORDER BY date DESC, entryId LIMIT :skip, :count";
        $query = "SELECT * FROM ($subQuery) ids INNER JOIN entries using(entryId) ORDER BY date DESC, entryId";
        $params = $this->getParams($data, ["skip", "count"]);

        // Делаем запрос
        $STH = $this->DBH->prepare($query);
        $STH->setFetchMode(PDO::FETCH_ASSOC);

        $STH->bindValue(':skip', $params["skip"], PDO::PARAM_INT);
        $STH->bindValue(':count', $params["count"], PDO::PARAM_INT);
        $STH->bindValue(':userId', $userId);

        try {
            $STH->execute();
        } catch (Exception $e) {
            $this->sendResponse($e->getMessage(), 400);
        }

        $res = $STH->fetchAll();
        $this->sendResponse($res);
    }

    private function getStats($data, $userId)
    {
        // Данные запроса
        $data = $this->getParams($data, ["users"], ["startDate"]);
        $users = explode(",", $data["users"]);
        $params = $users;
        $query = "SELECT entryId, userId, mood, stress, anxiety, date FROM entries WHERE userId IN " . getPlaceholders(count($params));

        // Проверяем права доступа
        $this->checkAccess($userId, $params);

        // Модифицируем запрос
        if (!is_null($data["startDate"])) {
            $query .= " AND date >= ?";
            $params[] = $data["startDate"];
        }

        $query .= " ORDER BY date DESC";

        // Делаем запрос и форматируем данные
        $stats = $this->pdoQuery($query, $params, ["NO_COLON"]);
        $res = [];

        foreach ($users as $user) {
            $res[$user] = [];
        }

        foreach ($stats as $stat) {
            $id = $stat["userId"];
            unset($stat["userId"]);
            $res[$id][] = $stat;
        }

        $this->sendResponse($res);
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
            $query .= "INSERT INTO entries SET " . getSetters($entry, true) . ";";

            foreach ($entry as $key => $value) {
                $params[] = $value;

                if ($key == "mood" || $key == "stress" || $key == "anxiety" || $key == "isPublic") {
                    $from = 1;
                    $to = 5;

                    if ($key == "isPublic") {
                        $from = 0;
                        $to = 1;
                    }

                    if (!($value >= $from && $value <= $to)) {
                        $this->sendResponse("$key param must be from $from to $to inclusive in #" . ($i + 1) . " entry", 400);
                    }
                }
            }
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
        $this->sendResponse($this->DBH->lastInsertId(), 201);
    }

    private function updateEntry($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["entryId"], ["mood", "stress", "anxiety", "isPublic", "title", "note"]);
        $query = "UPDATE entries SET " . getSetters($params) . " WHERE entryId = :entryId";

        // Проверяем права доступа
        $checkQuery = "SELECT userId FROM entries WHERE entryId = :entryId";
        $res = $this->pdoQuery($checkQuery, ["entryId" => $params["entryId"]]);

        if (count($res) && $res[0]["userId"] != $userId) {
            $this->sendResponse("You don't have permission to do this", 403);
        }

        // Проверяем правильность данных
        $stats = ["mood", "stress", "anxiety", "isPublic"];

        foreach ($stats as $stat) {
            $from = 1;
            $to = 5;

            if ($stat == "isPublic") {
                $from = 0;
                $to = 1;
            }

            if (!is_null($params[$stat]) && !($params[$stat] >= $from && $params[$stat] <= $to)) {
                $this->sendResponse("$stat param must be from $from to $to inclusive", 400);
            }
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
