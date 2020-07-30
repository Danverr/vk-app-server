<?php

include_once __DIR__ . "./../api.php";

function vkApiQuery($method, $params)
{
    $url = "https://api.vk.com/method/$method?";

    $params["lang"] = "ru";
    $params["v"] = "5.120";
    $params["access_token"] = API::ACCESS_TOKEN;

    $url .= http_build_query($params);
    return json_decode(file_get_contents($url), true);
}
