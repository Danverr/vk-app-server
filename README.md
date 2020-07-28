# Содержание
* **statAccess**
   * [GET /statAccess/](#get-stataccess)
   * [POST /statAccess/](#post-stataccess)
   * [DELETE /statAccess/](#delete-stataccess)
* **entries**
   * [GET /entries/](#get-entries)
   * [POST /entries/](#post-entries)
   * [PUT /entries/](#put-entries)    
   * [DELETE /entries/](#delete-entries)
* **notifications**
   * [GET /notifications/](#get-notifications)
   * [PUT /notifications/](#put-notifications)    
* **complaints**
   * [POST /complaints/](#post-complaints)

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
*  ```toId``` *(обязательно)* - VK ID пользователей, котрым нужно дать доступ, через запятую
#### Ответ
В случае успеха вернет кол-во созданных пар

## DELETE /statAccess/
Удаляет пару доступа.
#### Параметры
*  ```toId``` *(обязательно)* - VK ID пользователей, котрым нужно запретить доступ, через запятую
#### Ответ
В случае успеха вернет пустой ответ

## GET /entries/
Возвращает записи пользователей в порядке убывания ```entryId``` (в порядке создания, т.е. чем больше ```entryId```, тем позже создана запись).
#### Параметры
* ```users``` - VK ID пользователей через запятую. Если не указано, вернутся все доступные посты, т.е. свои и друзей.
* ```day``` - День по UTC в формате YYYY-MM-DD
* ```month``` - Месяц по UTC в формате YYYY-MM
* ```lastId``` - ID записи, до которой надо выбрать строки, т.е. вернутся поля с ```entryId < lastId```
* ```lastDate``` - Дата по UTC в формате YYYY-MM-DD HH:MM:SS, до которой надо выбрать строки, т.е. вернутся поля с ```date < lastDate```
* ```count``` - Сколько записей вернуть
#### Примечание
* Если указаны параметры  ```day``` и  ```month``` одновременно, то учитываться будет только  ```day```
#### Ответ
Объект с полями в виде VK ID юзеров. Ключ поля - массив объектов с параметрами:
* ```entryId``` - ID записи
* ```userId``` - VK ID автора записи
* ```mood``` - Настроение от 1 до 5
* ```stress``` - Стресс от 1 до 5
* ```anxiety``` - Тревожность от 1 до 5
* ```title``` - Заголовок записи, максимум 64 символа
* ```note``` - Текст записи, максимум 2048 символов
* ```isPublic``` - Доступно ли друзьям, 1 или 0
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
* ```entries``` *(обязательно)* - JSON объект с записями. В каждом объекте должны быть следующие параметры:
    * ```mood``` *(обязательно)* - Настоение пользователя от 1 до 5
    * ```stress``` *(обязательно)* - Стресс пользователя от 1 до 5
    * ```anxiety``` *(обязательно)* - Тревожность пользователя от 1 до 5
    * ```isPublic``` - Доступна ли запись друзьям, 1 или 0. По умолчанию 0.
    * ```title``` - Заголовок записи, строка с максимальной длиной в 64 символа. По умолчанию пустая строка.
    * ```note``` - Текст записи, строка с максимальной длиной в 2048 символов. По умолчанию пустая строка.
    * ```date``` - Дата по UTC в формате YYYY-MM-DD HH:MM:SS. По умолчанию текущее время.
#### Примечание
* Все параметры кроме ```title``` и ```note``` имеют автоисправление. Т.е. настроение меньше единицы будет заменено на 1, а больше пятерки - на 5. Аналогично с ```isPublic```. Если дата больше текущей по UTC, то она заменяется на нее.
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

## GET /notifications/
Показывает подписки пользователя на уведомления
#### Параметры
Нет
#### Ответ
В случае успеха вернет объект с полями:
* ```createEntry``` - Время по UTC в формате HH:MM:SS или ```null``` в зависимости от того, подписан юзер на напоминания о создании записи или нет. Если возвращается время, минуты всегда кратны 10.
* ```lowStats``` - Подписан ли юзер на уведомления о низком здоровье друзей. 1 или 0.
* ```accessGiven``` - Подписан ли юзер на уведомления о получении доступа. 1 или 0.
#### Пример
```json
{
    "createEntry": "10:10:00",
    "lowStats": 1,
    "accessGiven": 0
}
```

## PUT /notifications/
Меняет подписки пользователя на уведомления
#### Параметры
Аналогичны ответу в ```GET /notifications/```. Все параметры необязательны.
#### Ответ
В случае успеха вернет пустой ответ

## POST /complaints/
Создает жалобу пользователя на запись. Повторные жалобы не учитываются
#### Параметры
* ```entryId``` *(обязательно)* - ID записи
#### Ответ
В случае успеха вернет пустой ответ
