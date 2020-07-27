<?php

include_once __DIR__ . "./../api.php";
include_once __DIR__ . "./../utils/formatters.php";

class Complaints extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'POST' && count($url) == 0) {
            $this->createComplaint($data, $userId);
        } else {
            $this->sendResponse("No such method in 'complaints' table", 400);
        }
    }

    public function getComplaint($entryId)
    {
        $query = "SELECT * FROM complaints WHERE entryId=:entryId";
        $params = ["entryId" => $entryId];

        $res = $this->pdoQuery($query, $params);

        if (count($res)) {
            $res = $res[0];
        } else {
            $res = null;
        }

        return $res;
    }

    private function createComplaint($data, $userId)
    {
        // Данные запроса
        $data = $this->getParams($data, ["entryId"]);
        $complaint = $this->getComplaint($data["entryId"]);

        if (is_null($complaint)) {
            $params = [
              "entryId" => $data["entryId"],
              "users" => $userId,
              "count" => 1
            ];

            $query = "INSERT INTO complaints SET " . getSetters($params);
        } else {
            $params = $complaint;
            $users = explode(",", $complaint["users"]);

            if (array_search($userId, $users) === false) {
                $params["users"] .= ",$userId";
                $params["count"] += 1;
            }

            $query = "UPDATE complaints SET " . getSetters($params);
        }

        // Делаем запрос
        $res = $this->pdoQuery($query, $params, ["RETURN_ROW_COUNT"]);
        $this->sendResponse(null, 204);
    }
}

$router = new Complaints();
