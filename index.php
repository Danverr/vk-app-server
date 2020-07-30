<?php

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

// Заголовки для CORS
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, X-VK-SIGN');

// Определяем метод запроса
$method = $_SERVER['REQUEST_METHOD'];

// Preflighted Access Control Request
if ($method == "OPTIONS") {
    header("Content-Length: 0");
    header("Content-Type: text/plain");
    exit(0);
}

try {
    // Разбираем url
    $url = (isset($_GET['q'])) ? $_GET['q'] : '';
    $url = rtrim($url, '/');
    $url = explode('/', $url);

    $version = $url[0];
    $table = $url[1];
    $url = array_slice($url, 2);

    include_once $version . '/api.php';
    include_once $version . '/utils/getQueryData.php';
    include_once $version . '/utils/logError.php';

    $api = new API();

    // Получаем данные из тела запроса
    $data = getQueryData($method);
    if (isset($data['q'])) {
        unset($data['q']);
    }

    // Проверяем подпись и в случае неудачи формируем ответ
    $userId = $api->checkSign($_SERVER['HTTP_X_VK_SIGN']);
    if (is_null($userId)) {
        $api->sendResponse("Wrong VK Sign", 401);
    }

    // Подключаем роутер и запускаем главную функцию
    if (!include_once $version . '/routers/' . $table . '.php') {
        $api->sendResponse("Invalid table name", 404);
    }

    $router->route($method, $url, $data, $userId);
} catch (Exception $error) {
    logError($error, $userId);
}
