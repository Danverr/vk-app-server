<?php

include_once __DIR__ . "./getVk.php";

function checkSign($url)
{
    // Получаем query-параметры из URL
    $query_params = [];
    parse_str(parse_url($url, PHP_URL_QUERY), $query_params);

    $sign_params = [];
    foreach ($query_params as $name => $value) {
        // Получаем только vk параметры из query
        if (strpos($name, 'vk_') !== 0) {
            continue;
        }

        $sign_params[$name] = $value;
    }

    // Сортируем массив по ключам
    ksort($sign_params);

    // Формируем строку вида "param_name1=value&param_name2=value"
    $sign_params_query = http_build_query($sign_params);

    // Получаем хеш-код от строки, используя защищеный ключ приложения. Генерация на основе метода HMAC.
    $sign = rtrim(strtr(base64_encode(hash_hmac('sha256', $sign_params_query, CLIENT_SECRET, true)), '+/', '-_'), '=');

    // Сравниваем полученную подпись со значением параметра 'sign'
    $status = $sign === $query_params['sign'];

    return $status;
}
