<?php

include_once __DIR__ . "./notificationSender.php";
include_once __DIR__ . "./../api.php";

$MESSAGE = "Как прошел твой день? Сделай об этом запись в дневнике!";
$INTERVAL = 10;
$FORMAT = "H:i:00";

$api = new API();

$time = new DateTime("now", new DateTimeZone("UTC"));
$time = $time->format($FORMAT);

$query = "SELECT userId FROM entryNotifications WHERE time='$time'";

$users = $api->pdoQuery($query);
$users = array_map(function ($row) {
    return $row["userId"];
}, $users);

$sender = new NotificationSender();
$res = $sender->send($users, $MESSAGE);
$sender->log($res, $MESSAGE);
