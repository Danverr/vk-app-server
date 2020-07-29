<?php

include_once __DIR__ . "/../api.php";
include_once __DIR__ . "/../routers/vkApi.php";

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

            $curResponse = vkApiQuery($method, $params)["response"];
            $response = array_merge($response, $curResponse);
        }

        $this->log($response, $message);
    }

    public function log($response, $message)
    {
        $FILE_PATH = __DIR__ . "/../logs/notifications.log";
        $file = fopen($FILE_PATH, 'a');

        $total = count($response);
        $success = 0;
        $errors = [];
        $haveErrors = false;

        foreach ($response as $res) {
            if ($res["status"]) {
                $success++;
            } else {
                $haveErrors = true;
                $errors[$res["error"]["code"]][] = $res["user_id"];
            }
        }

        if ($haveErrors) {
            $text = "\n[" . date(DateTime::RFC1123) . "]\n";
            $text .= "Notifications sent: $total\n";
            $text .= "Message: $message\n";

            if ($total > 0) {
                $text .= "Successfully: $success, ≈" . round($success * 100 / $total) . "%\n";

                foreach ($errors as $code => $users) {
                    $count = count($users);
                    $text .= "Failed with code №$code: $count, ≈" . round($count * 100 / $total) . "%\n";
                    $text .= "Code №$code users: " . implode(", ", $users);
                }
            }

            fwrite($file, $text);
        }

        fclose($file);
    }
}
