<?php

include_once __DIR__ . "/../api.php";
include_once __DIR__ . "/../utils/vkApiQuery.php";

class VkApi extends API
{
    public function route($method, $url, $data, $userId)
    {
        if ($method == 'GET' && count($url) == 0) {
            $res = $this->get($data, $userId);
            $this->sendResponse($res);
        } else {
            $this->sendResponse("No such method in 'vkApi' table", 400);
        }
    }

    public function get($data, $userId)
    {
        $data = $this->getParams($data, ["method", "params"]);
        $method = $data["method"];
        $params = json_decode($data["params"], true);
        return vkApiQuery($method, $params);
    }
}

$router = new VkApi();
