<?php

include_once __DIR__ . "./../vendor/autoload.php";
use VK\Client\VKApiClient;
use VK\Client\Enums\VKLanguage;

define("ACCESS_TOKEN", '174b0977174b0977174b09772a173a41301174b174b097749d406ac389fd14cd082862a');
define("CLIENT_SECRET", 'vrikjcw4PJpIvKWswil8');

function getVk()
{
    return new VKApiClient(5.103, VKLanguage::RUSSIAN);
}
