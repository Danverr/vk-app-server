<?php

include_once __DIR__ . "/../api.php";
include_once __DIR__ . "/../routers/vkApi.php";
include_once __DIR__ . "/../../logs/logger.php";

class NotificationSender extends API
{
    public function send($users, $message)
    {
        $BATCH_SIZE = 100;
        $batches = ceil(count($users) / $BATCH_SIZE);
        $response = [];

        for ($i=0; $i < $batches; $i++) {
            $curUsers = array_slice($users, $i * $BATCH_SIZE, $BATCH_SIZE);
            $curUsers = implode(",", $curUsers);

            $method = "notifications.sendMessage";
            $params["user_ids"] = $curUsers;
            $params["random_id"] = self::APP_ID . time() . $i;
            $params["message"] = $message;

            $curResponse = vkApiQuery($method, $params);
            $response = array_merge($response, $curResponse);
        }

        logNotif($response, $message);
        return $response;
    }
}
