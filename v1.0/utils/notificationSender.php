<?php

include_once __DIR__ . "/../api.php";
include_once __DIR__ . "/../../logs/logger.php";

class NotificationSender extends API
{
    public function send($users, $message)
    {
        $BATCH_SIZE = 100;
        $batches = ceil(count($users) / $BATCH_SIZE);
        $formattedMessage = str_replace(" ", "+", $message);
        $response = [];

        for ($i=0; $i < $batches; $i++) {
            $curUsers = array_slice($users, $i * $BATCH_SIZE, $BATCH_SIZE);
            $curUsers = implode(",", $curUsers);

            $random_id = self::APP_ID . time() . $i;

            $url = "https://api.vk.com/method/notifications.sendMessage?v=5.120";
            $url .= "&user_ids=$curUsers";
            $url .= "&random_id=$random_id";
            $url .= "&message=$formattedMessage";
            $url .= "&access_token=" . self::ACCESS_TOKEN;

            $curResponse = file_get_contents($url);
            $curResponse = json_decode($curResponse, true)["response"];
            $response = array_merge($response, $curResponse);
        }

        logNotif($response, $message);
        return $response;
    }
}
