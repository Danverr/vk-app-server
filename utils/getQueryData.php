<?php

// Получение данных из тела запроса
function getQueryData($method)
{
    // GET или POST: данные возвращаем как есть
    if ($method === 'GET') {
        return $_GET;
    } elseif ($method === 'POST') {
        return $_POST;
    } else {
        // PUT, PATCH или DELETE
        $data = [];
        $exploded = explode('&', file_get_contents('php://input'));

        foreach ($exploded as $pair) {
            $item = explode('=', $pair);
            if (count($item) == 2) {
                $data[urldecode($item[0])] = urldecode($item[1]);
            }
        }

        return $data;
    }
}
