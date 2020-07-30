<?php

include_once __DIR__ . "/../api.php";
include_once __DIR__ . "./notificationSender.php";

$MESSAGE = "Как прошел твой день? Сделай об этом запись в дневнике!";

$api = new API();

$time = new DateTime("now", new DateTimeZone("UTC"));
$time = $time->format("H:i:00");

$query = "SELECT userId FROM notifications WHERE createEntry='$time'";

$users = $api->pdoQuery($query);
$users = array_map(function ($row) {
    return $row["userId"];
}, $users);

$sender = new NotificationSender();
$res = $sender->send($users, $MESSAGE);
