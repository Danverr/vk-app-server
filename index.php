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
    include_once __DIR__ . "/logs/logger.php";
    include_once __DIR__ . '/getQueryData.php';

    // Разбираем url
    $url = (isset($_GET['q'])) ? $_GET['q'] : '';
    $url = rtrim($url, '/');
    $url = explode('/', $url);

    $version = $url[0];
    $table = $url[1];
    $url = array_slice($url, 2);

    if (!include_once $version . '/api.php') {
        http_response_code(400);
        $error = "400 Bad Request: Wrong API version";
        echo $error;
        throw new Exception($error);
    }

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

    // Проверяем юзера на бан
    if ($table != "users") {
        $isBanned = $api->pdoQuery("SELECT isBanned FROM users WHERE userId = :userId", ["userId" => $userId]);

        if (count($isBanned) == 1 && $isBanned[0]["isBanned"] != null) {
            $api->sendResponse("You are banned", 403);
        }
    }

    // Подключаем роутер и запускаем главную функцию
    if (!include_once $version . '/routers/' . $table . '.php') {
        $api->sendResponse("Invalid table name", 404);
    }

    $router = null;

    if ($table == "complaints") {
        $router = new Complaints();
    } elseif ($table == "entries") {
        $router = new Entries();
    } elseif ($table == "logs") {
        $router = new Logs();
    } elseif ($table == "statAccess") {
        $router = new StatAccess();
    } elseif ($table == "users") {
        $router = new Users();
    } elseif ($table == "vkApi") {
        $router = new VkApi();
    } elseif ($table == "banlist") {
        $router = new Banlist();
    } elseif ($table == "notifications") {
        $router = new Notifications();
    }

    $router->route($method, $url, $data, $userId);
} catch (Exception $error) {
    logError($error, $userId, $_GET['q'], $data);
}
