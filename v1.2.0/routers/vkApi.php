<?php

include_once __DIR__ . "/../api.php";
include_once __DIR__ . "/../utils/vkApiQuery.php";

class VkApi extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 1 && $url[0] == "users.get") {
            $res = $this->getUsers($data, $userId);
            $this->sendResponse($res);
        }else {
            $this->sendResponse("No such method in 'vkApi' table", 400);
        }
    }

    public function getUsers($data, $userId)
    {
        $data = $this->getParams($data, ["users"]);
        $params = [
            "user_ids" => $data["users"],
            "fields" => "photo_50, photo_100, sex"
        ];

        return vkApiQuery("users.get", $params);
    }
}