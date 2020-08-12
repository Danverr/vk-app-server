<?php

include_once __DIR__ . "/../api.php";

class Banlist extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $res = $this->isBanned($data, $userId);
            $this->sendResponse($res);
        } else {
            $this->sendResponse("No such method in 'banlist' table", 400);
        }
    }

    public function isBanned($data, $userId)
    {
        // Данные запроса
        $query = "SELECT * FROM banlist WHERE userId = :userId";
        $params["userId"] = $userId;

        // Делаем запрос
        $res =  $this->pdoQuery($query, $params);

        if (count($res) == 0) {
            $res = ["isBanned" => false];
        } else {
            $res = $res[0];
            unset($res["userId"]);
            $res["isBanned"] = true;
        }

        return $res;
    }
}

$router = new Banlist();
