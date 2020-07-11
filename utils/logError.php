<?php

function logError($error, $userId)
{
    $LOG_PATH = __DIR__ . "/../logs/all.log";
    $file = fopen($LOG_PATH, 'a');

    $text .= "\n";
    $text .= "[" . date(DateTime::RFC1123) . "]\n";
    $text .= "IP: " . $_SERVER["REMOTE_ADDR"] . "\n";
    $text .= "VK ID: " . $userId . "\n";
    $text .= "User agent: " . $_SERVER["HTTP_USER_AGENT"] . "\n";
    $text .= $error . "\n";

    fwrite($file, $text);
    fclose($file);
}
