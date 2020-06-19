<?php

include_once __DIR__ . "./../api.php";

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

    private function getAccess($userId)
    {
        $query = "SELECT * FROM statAccess WHERE toId = :toId";

        $res = $this->pdoQuery($query, ["toId" => $userId]);
        $res = array_map(function ($row) {
            return (int)$row["fromId"];
        }, $res);

        return $res;
    }

    private function getEntries($data, $userId)
    {
        // Данные запроса
        $query = "SELECT * FROM entries WHERE userId = :userId";
        $params = $this->getParams($data, ["userId"], ["day", "month"]);

        // Проверяем права доступа
        if ($userId != $params["userId"]) {
            $access = $this->getAccess($userId);

            if (array_search($params["userId"], $access) === false) {
                $this->sendResponse("You don't have permission to do this", 403);
            } else {
                $query .= " AND isPublic = 1";
            }
        }

        // Модифицируем запрос
        if (!is_null($params["day"])) {
            $query .= " AND date >= :day AND date < DATE_ADD(:day, INTERVAL 1 DAY)";
        } elseif (!is_null($params["month"])) {
            $params["month"] .= "-01";
            $query .= " AND date >= :month AND date < DATE_ADD(:month, INTERVAL 1 MONTH)";
        }

        $query .= " ORDER BY date DESC";

        // Делаем запрос
        $res = $this->pdoQuery($query, $params);
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
        $query = "SELECT entryId, mood, stress, anxiety, date FROM entries WHERE userId = :userId";
        $params = $this->getParams($data, ["userId"], ["startDate"]);

        // Проверяем права доступа
        if ($userId != $params["userId"]) {
            $access = $this->getAccess($userId);

            if (array_search($params["userId"], $access) === false) {
                $this->sendResponse("You don't have permission to do this", 403);
            }
        }

        // Модифицируем запрос
        if (!is_null($params["startDate"])) {
            $query .= " AND date >= :startDate";
        }

        $query .= " ORDER BY date DESC";

        // Делаем запрос и форматируем данные
        $res = $this->pdoQuery($query, $params);
        $this->sendResponse($res);
    }

    private function createEntries($data, $userId)
    {
        // Данные запроса
        $data = $this->getParams($data, ["csv"]);
        $csv = str_getcsv($data["csv"]);
        $query = "INSERT INTO entries (userId, mood, stress, anxiety, title, note, isPublic, date) VALUES";
        $params = [];
        $cols = ["mood", "stress", "anxiety", "title", "note", "isPublic", "date"];

        if (count($csv) % count($cols) != 0) {
            $this->sendResponse("There must be " . count($cols) . " values on each row, but " . count($csv) % count($cols)  . " extra found", 400);
        }

        for ($i = 0; $i < count($csv); $i += count($cols)) {
            // Модифицируем запрос
            $query .= ($i ? ", " : " ") . "(?, ";
            $params[] = $userId; // userId

            for ($j=0; $j < count($cols); $j++) {
                $query .= ($j == count($cols) - 1 ? "?)" : "?, ");

                if ($j <= 2) { // mood, stress, anxiety
                    $params[] = (int)$csv[$i + $j];

                    if (end($params) < 1 || end($params) > 5) {
                        $this->sendResponse($cols[$j] . " param (#" . ($j + 1) . ") must be from 1 to 5 inclusive in row " . ($i / count($cols) + 1), 400);
                    }
                } elseif ($j == 5) { // isPublic
                    $params[] = (int)$csv[$i + $j];

                    if (end($params) < 0 || end($params) > 1) {
                        $this->sendResponse($cols[$j] . " param (#" . ($j + 1) . ") must be from 0 to 1 inclusive in row " . ($i / count($cols) + 1), 400);
                    }
                } else {
                    $params[] = $csv[$i + $j];
                }
            }
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT", "NO_COLON"]);
        $this->sendResponse([
          "rowsAdded" => $res,
          "lastId" => $this->DBH->lastInsertId(),
        ], 201);
    }

    private function updateEntry($data, $userId)
    {
        // Данные запроса
        $params = $this->getParams($data, ["entryId"], ["mood", "stress", "anxiety", "isPublic", "title", "note"]);
        $query = "UPDATE entries SET " . $this->getSetters($params) . " WHERE entryId = :entryId";

        // Проверяем права доступа
        $checkQuery = "SELECT userId FROM entries WHERE entryId = :entryId";
        $res = $this->pdoQuery($checkQuery, ["entryId" => $params["entryId"]]);

        if (count($res) && $res[0]["userId"] != $userId) {
            $this->sendResponse("You don't have permission to do this", 403);
        }

        // Проверяем правильность данных
        if ($params["mood"] < 1 || $params["mood"] > 5) {
            $this->sendResponse("'mood' param must be from 1 to 5 inclusive", 400);
        }
        if ($params["stress"] < 1 || $params["stress"] > 5) {
            $this->sendResponse("'stress' param must be from 1 to 5 inclusive", 400);
        }
        if ($params["anxiety"] < 1 || $params["anxiety"] > 5) {
            $this->sendResponse("'anxiety' param must be from 1 to 5 inclusive", 400);
        }
        if ($params["isPublic"] < 0 || $params["isPublic"] > 1) {
            $this->sendResponse("'isPublic' param must be from 0 to 1 inclusive", 400);
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
