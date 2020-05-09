<?php

include_once __DIR__ . "./../utils/getDBH.php";
include_once __DIR__ . "./../utils/sendResponse.php";

class StatAccess
{
    // Роутер
    public function route($method, $url, $data)
    {
        if ($method == 'GET' && count($url) == 0 && isset($data['userId'])) {
            sendResponse($this->getAccessibleUsers($data['userId']));
        } else {
            sendResponse("No such method in 'statAccess' table", 400);
        }
    }

    // GET /statAccess/?userId
    // Возвращает id пользователей к которым есть доступ
    private function getAccessibleUsers($userId)
    {
        $DBH = getDBH();
        $STH = $DBH->prepare("SELECT fromId FROM statAccess WHERE toId = :userId");
        $STH->execute([':userId' => $userId]);

        $res = $STH->fetchAll();
        $res = array_map(function ($row) {
            return $row['fromId'];
        }, $res);

        return $res;
    }
}

$router = new StatAccess();
