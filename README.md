[Гайд по установке](https://vk.com/away.php?utf=1&to=http%3A%2F%2Fvk.com%2F%40bless_mt19937-gaid-po-ustanovke-servera)

# Содержание
* [statAccess](#statAccess)
* [entries](#entries)
* [VK API](#vkApi)

---

# <a name="statAccess"></a> statAccess

## GET /statAccess/
Возвращает id пользователей, к которым есть доступ
### Параметры
* **userId** *(обязательно)* - VK ID пользователя
### Ответ
Массив строк с id пользователей
### Пример
```json
[
    "270029019",
    "505643430"
]
```

# <a name="entries"></a> entries

## GET /entries/
Возвращает записи пользователя
### Параметры
* **userId** *(обязательно)* - VK ID пользователя
* **date** *(опционально)* - Дата в формате YYYY-MM-DD
### Ответ
Массив объектов с полями:
* **entryId** - ID записи
* **userId** - ID пользователя
* **mood** - Настроение от 1 до 5
* **stress** - Стресс от 1 до 5
* **anxiety** - Тревожность от 1 до 5
* **title** - Заголовок записи
* **note** - Текст записи
* **isPublic** - Доступно ли друзьям
* **date** - Дата в формате YYYY-MM-DD HH:MM:SS
### Пример
```json
[
    {
        "entryId": "1",
        "userId": "331480448",
        "mood": "1",
        "stress": "2",
        "anxiety": "3",
        "title": "asaasdar",
        "note": "asdafasfsahsifjvksaor",
        "isPublic": "0",
        "date": "2020-05-05 00:00:00"
    }
]
```

# <a name="vkApi"></a> VK API

## GET /vk/users/
 Возвращает общие сведения о пользователях
### Параметры
* **userIds[]** *(обязательно)* - Массив VK ID пользователей
### Ответ
Массив объектов с полями:
* **id** - ID пользователя
* **first_name** - Имя
* **last_name** - Фамилия
* **is_closed** - Cкрыт ли профиль пользователя настройками приватности.
* **can_access_closed** - Может ли текущий пользователь видеть профиль при is_closed = 1 (например, он есть в друзьях)
* **photo_50** - URL 50x50 px фотографии пользователя
### Пример
```json
[
    {
        "id": 331480448,
        "first_name": "Даниил",
        "last_name": "Маряхин",
        "is_closed": false,
        "can_access_closed": true,
        "photo_50": "https://sun9-44.userapi.com/c857416/v857416969/1a5de7/WRpCWJyzQ8A.jpg?ava=1"
    }
]
```
