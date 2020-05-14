<?php

include_once __DIR__ . "./../api.php";

class Entries extends API
{
    public function route($method, $url, $data)
    {
        if ($method == 'GET' && count($url) == 0) {
            $this->getEntries($data);
        } elseif ($method == 'POST' && count($url) == 0) {
            $this->createEntry($data);
        } elseif ($method == 'PUT' && count($url) == 0) {
            $this->updateEntry($data);
        } elseif ($method == 'DELETE' && count($url) == 0) {
            $this->deleteEntry($data);
        } else {
            $this->sendResponse("No such method in 'entries' table", 400);
        }
    }

    private function getEntries($data)
    {
        // Данные запроса
        $query = "SELECT * FROM entries WHERE userId = :userId";
        $params = $this->getParams($data, ["userId"], ["date"]);

        // Модифицируем запрос
        if (!is_null($params["date"])) {
            $query .= " AND date BETWEEN :date AND :date_end";
            $params['date_end'] = $data["date"] . " 23:59:59";
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params)->fetchAll();
        $this->sendResponse($res);
    }

    private function createEntry($data)
    {
        // Данные запроса
        $params = $this->getParams($data, ["userId", "mood", "stress", "anxiety", "isPublic"], ["title", "note"]);
        $query = "INSERT INTO entries SET " . $this->getSetters($params);

        // Делаем запрос
        $this->pdoQuery($query, $params);
        $this->sendResponse(null, 201);
    }

    private function updateEntry($data)
    {
        // Данные запроса
        $params = $this->getParams($data, ["entryId"], ["mood", "stress", "anxiety", "isPublic", "title", "note"]);
        $query = "UPDATE entries SET " . $this->getSetters($params) . " WHERE entryId = :entryId";

        // Делаем запрос
        $this->pdoQuery($query, $params);
        $this->sendResponse(null, 204);
    }

    private function deleteEntry($data)
    {
        // Данные запроса
        $query = "DELETE FROM entries WHERE entryId = :entryId";
        $params = $this->getParams($data, ["entryId"]);

        // Делаем запрос
        $this->pdoQuery($query, $params);
        $this->sendResponse(null, 204);
    }
}

$router = new Entries();
