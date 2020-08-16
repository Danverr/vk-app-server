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
        } elseif ($method == 'GET' && count($url) == 1 && $url[0] == "token") {
            $res = $this->getToken($data, $userId);
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

    public function getToken($data, $userId)
    {
        $data = $this->getParams($data, ["code"]);
        $url = "https://oauth.vk.com/access_token?";

        $params["client_id"] = API::APP_ID;
        $params["client_secret"] = API::CLIENT_SECRET;
        $params["redirect_uri"] = API::APP_URL;
        $params["code"] = $data["code"];

        $url .= http_build_query($params);
        $res = file_get_contents($url, false, stream_context_create(['http' => ['ignore_errors' => true]]));
        $res = json_decode($res, true);    

        if(!is_null($res["access_token"])){
            return $res["access_token"];
        }else{
            $this->sendResponse($res["error_description"], 400);
        }
    }
}