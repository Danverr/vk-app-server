<?php

class AppData
{
    const CLIENT_SECRET = 'vrikjcw4PJpIvKWswil8';
    const ACCESS_TOKEN = '174b0977174b0977174b09772a173a41301174b174b097749d406ac389fd14cd082862a';
    const APP_ID = "7424071";
    const APP_URL = "https://vk.com/app" . self::APP_ID;

    const HOST = "127.0.0.1";
    const USERNAME = "root";
    const PASSWORD = "";
    const DATABASE = "vkapp-mood";

    const HTTP_CODE_NAMES = [
      "200" => "OK", // Ответ на успешные GET, PUT, PATCH или DELETE. Этот код также используется для POST, который не приводит к созданию.
      "201" => "Created", // Этот код состояния является ответом на POST, который приводит к созданию.
      "204" => "No Content", // Нет содержимого. Это ответ на успешный запрос, который не будет возвращать тело (например, запрос DELETE)
      "400" => "Bad Request", // Указывает, что запрос искажен, например, если тело не может быть проанализировано
      "401" => "Unauthorized", // Указаны или недействительны данные аутентификации
      "403" => "Forbidden", // Когда аутентификация прошла успешно, но аутентифицированный пользователь не имеет доступа к ресурсу
      "404" => "Not found", // Если запрашивается несуществующий ресурс
      "429" => "Too Many Requests", // Запрос отклоняется из-за ограничения скорости
      "500" => "Internal Server Error" // Любая внутренняя ошибка сервера, которая не входит в рамки остальных ошибок класса
    ];
}
