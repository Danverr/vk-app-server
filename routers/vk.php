<?php

include_once __DIR__ . "./../utils/getVk.php";
include_once __DIR__ . "./../utils/sendResponse.php";

class Vk
{
    // Роутер
    public function route($method, $url, $data)
    {
        if ($method == 'GET' && count($url) == 1 && $url[0] == 'users' && isset($data['userIds'])) {
            sendResponse($this->getUsers($data['userIds']));
        } else {
            sendResponse("No such method in Vk Api", 400);
        }
    }

    // GET /vk/users/userIds[]
    // Возвращает общие сведения о пользователях
    private function getUsers($userIds)
    {
        $vk = getVk();
        $res = $vk->users()->get(ACCESS_TOKEN, [
          'user_ids' => $userIds,
          'fields' => ['photo_50'],
        ]);

        return $res;
    }
}

$router = new Vk();
