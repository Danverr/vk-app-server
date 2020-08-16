<?php

function logError($error, $userId, $version)
{
    $LOG_PATH = __DIR__ . "/routers.log";
    $file = fopen($LOG_PATH, 'a');

    $text = "\n[" . date(DateTime::RFC1123) . "]\n";
    $text .= "API Version: $version\n";
    $text .= "IP: " . $_SERVER["REMOTE_ADDR"] . "\n";
    $text .= "VK ID: $userId\n";
    $text .= "User agent: " . $_SERVER["HTTP_USER_AGENT"] . "\n";
    $text .= $error . "\n";

    fwrite($file, $text);
    fclose($file);
}

function logNotif($response, $message)
{
    $FILE_PATH = __DIR__ . "/notifications.log";
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
                $text .= "Code №$code users: " . implode(", ", $users) . "\n";
            }
        }

        fwrite($file, $text);
    }

    fclose($file);
}
