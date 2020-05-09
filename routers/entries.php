<?php

include_once __DIR__ . "./../utils/getDBH.php";
include_once __DIR__ . "./../utils/sendResponse.php";

class Entries
{
    // Роутер
    public function route($method, $url, $data)
    {
        if ($method == 'GET' && count($url) == 0 && isset($data['userId']) && isset($data['date'])) {
            sendResponse($this->getDatedEntries($data['userId'], $data['date']));
        } elseif ($method == 'GET' && count($url) == 0 && isset($data['userId'])) {
            sendResponse($this->getAllEntries($data['userId']));
        } else {
            sendResponse("No such method in 'entries' table", 400);
        }
    }

    // GET /entries/?userId
    // Возвращает все записи пользователя
    private function getAllEntries($userId)
    {
        $DBH = getDBH();
        $STH = $DBH->prepare("SELECT * FROM entries WHERE userId = :userId");
        $STH->setFetchMode(PDO::FETCH_ASSOC);
        $STH->execute([':userId' => $userId]);

        $res = $STH->fetchAll();
        return $res;
    }

    // GET /entries/?userId&date
    // Возвращает все записи пользователя с указанной датой
    private function getDatedEntries($userId, $date)
    {
        $DBH = getDBH();
        $STH = $DBH->prepare("SELECT * FROM entries WHERE userId = :userId AND date BETWEEN :date_start AND :date_end");
        $STH->setFetchMode(PDO::FETCH_ASSOC);
        $STH->execute([':userId' => $userId, ':date_start' => $date, ':date_end' => $date . " 23:59:59"]);

        $res = $STH->fetchAll();
        return $res;
    }
}

$router = new Entries();
