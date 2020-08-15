<?php

include_once __DIR__ . "/../api.php";

function vkApiQuery($method, $params)
{
    $api = new API();
    $url = "https://api.vk.com/method/$method?";

    $params["lang"] = "ru";
    $params["v"] = "5.120";

    if (is_null($params["access_token"])) {
        $params["access_token"] = API::ACCESS_TOKEN;
    }

    $url .= http_build_query($params);
    $res = json_decode(file_get_contents($url), true);

    if (!is_null($res["response"])) {
        $res = $res["response"];
    } else {
        $api->sendResponse($res["error"]);
    }

    return $res;
}
