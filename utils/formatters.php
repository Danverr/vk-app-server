<?php

function getSetters($params, $isUnnamed = false)
{
    $query = "";

    foreach ($params as $param => $value) {
        $query .= $param . " = " . ($isUnnamed ? "?" : ":" . $param) . ", ";
    }

    $query = rtrim($query, ", ");
    return $query;
}

function getPlaceholders($count)
{
    if ($count == 0) {
        return "";
    } else {
        return str_repeat('?, ', $count - 1) . "?";
    }
}
