<?php

include_once __DIR__ . "/../api.php";

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
