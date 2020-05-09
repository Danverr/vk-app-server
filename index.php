<?php

include_once './utils/getQueryData.php';
include_once './utils/checkSign.php';
include_once './utils/sendResponse.php';

error_reporting(E_ERROR | E_PARSE);

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

// Получаем данные из тела запроса
$data = getQueryData($method);
if (isset($data['q'])) {
    unset($data['q']);
}

// Разбираем url
$url = (isset($_GET['q'])) ? $_GET['q'] : '';
$url = rtrim($url, '/');
$url = explode('/', $url);

$table = $url[0];
$url = array_slice($url, 1);

// Проверяем подпись и в случае неудачи формируем ответ
if (!checkSign($_SERVER['HTTP_X_VK_SIGN'])) {
    sendResponse("Wrong VK Sign", 401);
}

// Подключаем роутер и запускаем главную функцию
if (!include_once 'routers/' . $table . '.php') {
    sendResponse("Invalid table name", 404);
}

$router->route($method, $url, $data);
