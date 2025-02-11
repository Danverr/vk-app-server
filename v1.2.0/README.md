# Mood API v1.2.0
* **statAccess**
   * [GET /statAccess/](#get-stataccess)
   * [POST /statAccess/](#post-stataccess)
   * [DELETE /statAccess/](#delete-stataccess)
* **entries**
   * [GET /entries/](#get-entries)
   * [POST /entries/](#post-entries)
   * [PUT /entries/](#put-entries)    
   * [DELETE /entries/](#delete-entries)
* **users**
   * [GET /users/](#get-users)
   * [PUT /users/](#put-users)
* **complaints**
   * [POST /complaints/](#post-complaints)
* **vkApi**
   * [GET /vkApi/users.get](#get-vkapiusersget)
* **logs**
   * [POST /logs/](#post-logs)

## GET /statAccess/
Возвращает id пользователей, к которым есть доступ или которым дан доступ
#### Параметры
*  ```type``` *(обязательно)* - Если указано значение ```fromId```, запрос вернет VK ID пользователей, которые дали юзеру доступ. Если указано значение ```toId```, запрос вернет VK ID пользователей, которым юзер дал доступ на чтение своей статистики.
#### Ответ
Массив объектов - id юзера и дата создания пары доступа. Объекты отсортированы в порядке убывания даты.
#### Пример
```json
[
    {
        "id": 230944995,
        "date": "2020-07-22 13:39:29"
    },
    {
        "id": 368889050,
        "date": "2020-07-21 10:30:20"
    },
    {
        "id": 505643430,
        "date": "2020-07-16 19:54:00"
    }
]
```

## POST /statAccess/
Создает новую пару доступа.
#### Параметры
*  ```users``` *(обязательно)* - Массив JSON объектов с полями:
   *  ```id``` *(обязательно)* - VK ID пользователя
   *  ```sign``` *(обязательно)* - Подпись, полученная с помощью метода [friends.areFriends](https://vk.com/dev/friends.areFriends)
#### Ответ
В случае успеха вернет кол-во созданных пар

## DELETE /statAccess/
Удаляет пару доступа.
#### Параметры
*  ```toId``` *(обязательно)* - VK ID пользователей, котрым нужно запретить доступ, через запятую
#### Ответ
В случае успеха вернет пустой ответ

## GET /entries/
Возвращает записи пользователей в порядке убывания даты. Если даты равны - по убыванию ID.
#### Параметры
* ```users``` - VK ID пользователей через запятую. Если не указано, вернутся все доступные посты, т.е. свои и друзей.
* ```afterDate``` - Дата по UTC в формате YYYY-MM-DD HH:MM:SS (можно не полностью), после которой (включительно) надо выбрать строки, т.е. ```date >= afterDate```
* ```beforeDate``` - Дата по UTC в формате YYYY-MM-DD HH:MM:SS (можно не полностью), до которой (не включительно) надо выбрать строки, т.е. ```date < beforeDate```
* ```beforeId``` - Используется для фильтрации записей с ```date = beforeDate```. Вернутся записи со строго меньшим ```entryId```. Нельзя использовать без ```beforeDate```
* ```count``` - Сколько записей вернуть
#### Ответ
Объект с полями в виде VK ID юзеров. Ключ поля - массив объектов с параметрами:
* ```entryId``` - ID записи
* ```userId``` - VK ID автора записи
* ```mood``` - Настроение от 1 до 5
* ```stress``` - Стресс от 1 до 5
* ```anxiety``` - Тревожность от 1 до 5
* ```title``` - Заголовок записи, максимум 64 символа
* ```note``` - Текст записи, максимум 2048 символов
* ```isPublic``` - Доступно ли друзьям, 1 или 0. Если недоступно, поля ```note``` и ```title``` будут пустыми
* ```date``` - Дата по UTC в формате YYYY-MM-DD HH:MM:SS
#### Пример
```json
[
    {
        "entryId": 1066,
        "userId": 331480448,
        "mood": 2,
        "stress": 3,
        "anxiety": 4,
        "title": "fir",
        "note": "",
        "isPublic": 0,
        "date": "2020-06-19 12:34:56"
    },
    {
        "entryId": 904,
        "userId": 505643430,
        "mood": 5,
        "stress": 1,
        "anxiety": 1,
        "title": "День норм",
        "note": "",
        "isPublic": 1,
        "date": "2020-06-16 12:05:24"
    }
]
```

## POST /entries/
Создает новую запись пользователя и возвращает ID последней добавленной записи
#### Параметры
* ```isImport``` *(обязательно)* - Импортируются ли записи. Если параметр равен строке ```"true"```, то записи считаются импортированными
* ```entries``` *(обязательно)* - JSON объект с записями. В каждом объекте должны быть следующие параметры:
    * ```mood``` *(обязательно)* - Настоение пользователя от 1 до 5
    * ```stress``` *(обязательно)* - Стресс пользователя от 1 до 5
    * ```anxiety``` *(обязательно)* - Тревожность пользователя от 1 до 5
    * ```isPublic``` - Доступна ли запись друзьям, 1 или 0. По умолчанию 0.
    * ```title``` - Заголовок записи, строка с максимальной длиной в 64 символа. По умолчанию пустая строка.
    * ```note``` - Текст записи, строка с максимальной длиной в 2048 символов. По умолчанию пустая строка.
    * ```date``` - Дата по UTC в формате YYYY-MM-DD HH:MM:SS. По умолчанию текущее время. Учитывается только при импорте.
#### Примечание
* Все параметры кроме ```title``` и ```note``` имеют автоисправление. Т.е. настроение меньше единицы будет заменено на 1, а больше пятерки - на 5. Аналогично с ```isPublic```. Если дата больше текущей по UTC, то она заменяется на нее.
* Попытки импорта уменьшаются автоматически
#### Пример
```json
[
  {
      "mood": 5,
      "stress": -5,
      "anxiety": 5,
      "note": "bla bla bla"
  },
  {
      "mood": 1,
      "stress": 100,
      "anxiety": 1,
      "title": "title",
      "isPublic": 100,
      "date": "3333-06-20",
  }
]
```
#### Ответ
В случае успеха вернет ID последней добавленной записи

## PUT /entries/
Редактирует запись пользователя
#### Параметры
* ```entryId``` *(обязательно)* - ID записи
* ```mood``` - Настоение пользователя от 1 до 5
* ```stress``` - Стресс пользователя от 1 до 5
* ```anxiety``` - Тревожность пользователя от 1 до 5
* ```isPublic``` - Доступна ли запись друзьям, 1 или 0
* ```title``` - Заголовок записи, строка с максимальной длиной в 64 символа
* ```note``` - Текст записи, строка с максимальной длиной а 2048 символов
#### Примечание
* Все параметры кроме ```title``` и ```note``` имеют автоисправление. Т.е. настроение меньше единицы будет заменено на 1, а больше пятерки - на 5. Аналогично с ```isPublic```.
#### Ответ
В случае успеха вернет пустой ответ

## DELETE /entries/
Удаляет запись пользователя
#### Параметры
* ```entryId``` *(обязательно)* - ID записи
#### Ответ
В случае успеха вернет пустой ответ

## GET /users/
Возвращает данные юзера
#### Параметры
Нет
#### Ответ
В случае успеха вернет объект с полями:
* ```isBanned``` - Если юзер забанен, вернется причина бана в виде строки. Если нет - ```null```.
* ```importAttempts``` - Оставшиеся попытки импорта записей.
* ```createEntryNotif``` - Время по UTC в формате HH:MM:SS или ```null``` в зависимости от того, подписан юзер на напоминания о создании записи или нет. Если возвращается время, минуты всегда кратны 10.
* ```lowStatsNotif``` - Подписан ли юзер на уведомления о низком здоровье друзей. 1 или 0.
* ```accessGivenNotif``` - Подписан ли юзер на уведомления о получении доступа. 1 или 0.
#### Пример
```json
{
    "isBanned": null,
    "importAttempts": 3,
    "createEntryNotif": "09:40:00",
    "lowStatsNotif": 1,
    "accessGivenNotif": 0
}
```

## PUT /users/
Меняет данные пользователя
#### Параметры
* ```createEntryNotif``` - Время по UTC в формате HH:MM или строка ```"null"``` в зависимости от того, подписан юзер на напоминания о создании записи или нет. Минуты должны быть всегда кратны 10.
* ```lowStatsNotif``` - Подписан ли юзер на уведомления о низком здоровье друзей. 1 или 0.
* ```accessGivenNotif``` - Подписан ли юзер на уведомления о получении доступа. 1 или 0.
#### Ответ
В случае успеха вернет пустой ответ

## POST /complaints/
Создает жалобу пользователя на запись. Повторные жалобы не учитываются
#### Параметры
* ```entryId``` *(обязательно)* - ID записи
#### Ответ
В случае успеха вернет пустой ответ

## GET /vkApi/users.get
Выполняет запрос к методу users.get VK API с токеном сервиса. Параметры: ```photo_50, photo_100, sex```
#### Параметры
* ```users``` *(обязательно)* - Аналогичен параметру [user_ids](https://vk.com/dev/users.get) из документации к VK API
#### Ответ
В случае успеха вернет ответ VK API (содержимое поля response или поля error).

## POST /logs/
Создает отчет об ошибке
#### Параметры
* ```userAgent``` *(обязательно)* - User Agent пользователя
* ```error``` *(обязательно)* - Полный текст ошибки, включая Stack Trace
#### Ответ
В случае успеха вернет пустой ответ
