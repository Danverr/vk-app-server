<?php

function getDBH()
{
    $host = "remotemysql.com";
    $username = "4xv3ZUe5kc";
    $password = "ooRAVaHThw";
    $database = "4xv3ZUe5kc";
    $DBH = null;

    try {
        $DBH = new PDO("mysql:host=" . $host . ";dbname=" . $database, $username, $password);
    } catch (PDOException $exception) {
        echo "ERR: " . $exception->getMessage() . "<br>";
    }

    return $DBH;
}
