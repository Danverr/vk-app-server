<?php

include_once __DIR__ . "./../api.php";

class Vk extends API
{
    public function route($method, $url, $data)
    {
        if ($method == 'GET' && count($url) == 1 && $url[0] == 'users') {
            $this->getUsers($data);
        } else {
            $this->sendResponse("No such method in Vk Api", 400);
        }
    }

    private function getUsers($data)
    {
        // Данные запроса
        $params = $this->getParams($data, ["user_ids"]);
        $params['fields'] = ['photo_50','photo_100'];

        // Делаем запрос
        try {
            $res = $this->vk->users()->get(self::ACCESS_TOKEN, $params);
        } catch (Exception $e) {
            $this->sendResponse($e->getMessage(), 400);
        }

        $this->sendResponse($res);
    }
}

$router = new Vk();
