# Содержание
* [Установка](#установка)
* [Взаимодействие с API](#взаимодействие-с-api)
* [Коды возврата](#коды-возврата)
* [Методы API](#методы-api)
   * statAccess
       * [GET /statAccess/](#get-stataccess)
       * [POST /statAccess/](#post-stataccess)
       * [DELETE /statAccess/](#delete-stataccess)
   * entries
       * [GET /entries/](#get-entries)
       * [GET /entries/all](#get-entriesall)
       * [GET /entries/stats](#get-entriesstats)
       * [POST /entries/](#post-entries)
       * [PUT /entries/](#put-entries)    
       * [DELETE /entries/](#delete-entries)

# Установка

#### Ставим сервер
1. Скачайте и установите Open Server Panel Basic [отсюда](https://ospanel.io/download/). Это наш локальный сервер. По умолчанию стоит конфигурация «Full» весом в 800 Мб, нам же нужна только Basic весом в 300 Мб. С официального сайта качается медленно, поэтому лучше найти раздачу на [рутрекере](http://rutracker.org).
2. Запустите панель с помощью .exe файла, который вы установили.
3. После этого у вас появится иконка флажка на панели задач. Зеленый флаг — все запущено, желтый — запускается, красный — остановлен. Кликните ПКМ по флажку и выберите «Папка с сайтами» (см. скрин внизу).
4. Создайте папку «vk-app-server» (обязательно) и склонируйте любым способом этот репозиторий.
5. Готово! Теперь с запущенным сервером приложение может взаимодействовать с базой данных. Чтобы проверить, что у вас все получилось, перейдите в то же меню, где вы выбирали «Папку с сайтами» и найдите там меню «Мои сайты». Там должен появится наш сервер. Если этого не произошло, зайдите в «Настройки» → «Домены» и установите «Автопоиск доменов».
<img src="https://i.ibb.co/8j24VDg/esp-K30e4-ZQ.jpg" alt="Панель управления OpenServer">

#### Устанавливаем пакеты
1. Отлично, сайт есть. Теперь необходимо установить библиотеки. Для этого качаем установщик менеджера php пакетов composer [отсюда](https://getcomposer.org/Composer-Setup.exe).
2. Следуем указаниям установщика. Далее включаем Open Server, если он еще не был запущен, и прописываем для проверки в командной консоли ```composer -v```. Вы должны будите увидеть версию composer и его команды. Если что-то пошло не так, убедитесь в том, что путь до php.exe файла, который вы указывали при установке, есть в PATH.
4. В командной консоли идем в папку с нашим сайтом и прописываем ```composer install``` (см. скрин ниже).
5. Готово! Перейдите по [ссылке](http://vk-app-server/). Если все работает, сервер загрузится и вернет вам какой-то ответ.
<img src="https://i.ibb.co/0fHbdwM/NXc2s9-KCo.jpg" alt="Установка пакетов с помощью composer">

# Взаимодействие с API
Все взаимодействия клиента с БД происходят через REST API. REST API — это интерфейс взаимодействия клиента с сервером, при котором вся логика работы скрыта внутри сервера. Клиенту лишь нужно сделать правильный запрос с корректными данными, чтобы получить ответ. Сейчас мы и разберемся, как это делать.

#### Изучаем promise
Для http запросов используется библиотека [axios](https://github.com/axios/axios). Все действия в ней выполняются асинхронно. Это значит, что программа продолжит выполнятся, даже если запрос еще не завершился. Чтобы обрабатывать такие запросы, axios поддерживает конструкцию из JS, которая называется [promise](https://learn.javascript.ru/promise-basics). Прочтите о них и приступайте к следующему шагу.

Итак, с промисами разобрались. Однако, если мы сейчас попробуем сделать запрос, мы не сможем сразу же получить данные. Как же быть, когда сначала нужно получить информацию с БД, чтобы продолжить выполнение программы? Для этого мы должны использовать ключевое слово [await в async функции](https://learn.javascript.ru/async-await). Прочтите материал и приступайте к следующему шагу.

Итак, знания о асинхронных запросах у нас уже имеются. Подробнее можно почитать [тут](https://learn.javascript.ru/async). А теперь перейдем к самому вкусному.

#### Делаем запросы
Для взаимодействия с REST API необходимо послать запрос по определенному адресу. Адрес в данной архитектуре зачастую обозначает таблицу, с которой мы хотим работать. Пусть у нас будет сервер по адресу ```example.com```, который работает с таблицей ```table```. Чтобы обратиться к ней, нужно послать запрос на ```example.com/table/```.

Чтобы сервер понимал, что вы хотите сделать, необходимо использовать разные методы запросов. В API используются каждому из них соответствует какое-то действие в БД. Например: GET (read), POST (create), PUT (update) и DELETE (ну тут понятно). Итак, мы хотим прочесть данные из нашей таблички ```table```. Тут все просто, делаем GET запрос на адрес, который мы рассматривали выше. Однако что, если нам нужно получить данные конкретной строки или создать ее?

Для этого в теле запросе указываются необходимые параметры, которые требует API. Об этом можно прочесть в документации. Для разных методов запроса они передаются по-разному, но мы это опустим. Всю логику работы с запросами берет на себя функция ```api()``` из ```src/utils/api.js```. Просто передайте ей метод в виде строки, URL после адреса сервера (т.е. нашу табличку. Например, так: ```/table/```) и объект данных. Ниже представлен пример использования метода ```GET /vk/users/``` из API.
```js
import api from "./utils/api";

const App = () => {
   let [usersInfo, setUsersInfo] = useState(null);

   // Обратите внимание! Все запросы выполняются внутри async функции
   const fetchUsersInfo = async () => {      
      const usersInfoPromise = await api("GET", "/vk/users/", {
         user_ids: [331480448, 505643430],
      });

      // Обратите внимание! async функции всегда возвращают promise, поэтому данные будут хранится в поле data
      // Из-за того, что fetchUsersInfo - это тоже async функция, мы должны не вернуть данные, а сразу их обработать
      // В противном случае мы опять бы вернули promise
      setUsersInfo(usersInfoPromise.data);
   };

   fetchUsersInfo();
};
```

#### Обрабатываем результат
Порой получить один лишь результат мало. А что, если мы его вообще не получим? Как нам вообще понять, что все прошло штатно? Для этого каждый метод API возвращает так называемые [коды состояния](https://ru.wikipedia.org/wiki/Список_кодов_состояния_HTTP). О тех кодах, которые поддерживает API, вы можете прочитать ниже. На данный момент *(14.05.20)* все ошибки отлавливаются внутри функции ```api()``` и выбрасываются в консоль Dev Tools, поэтому при любых неполадках в первую очередь рекомендуется проверить логи. Однако на самом деле, это не самое лучшее решение и в будущем необходимо напрямую обрабатывать все ошибки. В дальнейшем список кодов возврата будет пополнятся, а API станет еще более гибким.

# Коды возврата
* **200 OK** - Ответ на успешный GET запрос
* **201 Created** - Ответ на успешный POST запрос
* **204 No Content** - Ответ на успешный запрос, который не будет возвращать тело (DELETE или PUT)
* **400 Bad Request** - Ошибка в запросе: неверный URL для таблицы, указаны не все параметры, запрос к БД не выполнен и т.д.
* **401 Unauthorized** - Недействительны данные аутентификации в заголовке ```VK-API-SIGN```
* **404 Not found** - Запрашивается несуществующий ресурс. Например, указана неверная таблица
* **429 Too Many Requests** - Запрос отклоняется из-за ограничения скорости
* **500 Internal Server Error** - Любая внутренняя ошибка сервера. Например, ошибка при инициализации соединения с БД

# Методы API
Методы API нельзя выполнить без корректного заголовка ```VK-API-SIGN``` с [подписью](https://vk.com/dev/vk_apps_docs3?f=6.1%20%D0%9F%D0%BE%D0%B4%D0%BF%D0%B8%D1%81%D1%8C%20%D0%BF%D0%B0%D1%80%D0%B0%D0%BC%D0%B5%D1%82%D1%80%D0%BE%D0%B2%20%D0%B7%D0%B0%D0%BF%D1%83%D1%81%D0%BA%D0%B0), т.к. все действия выполняются относительно VK ID, указанного в ней. Проверив подпись, сервер решит, можно ли доверять юзеру.

## GET /statAccess/
Возвращает id пользователей, к которым есть доступ или которым дан доступ
#### Параметры
*  ```type``` *(обязательно)* - Если указано значение ```fromId```, запрос вернет VK ID пользователей, которые дали юзеру доступ. Если указано значение ```toId```, запрос вернет VK ID пользователей, которым юзер дал доступ на чтение своей статистики.
#### Ответ
Массив чисел - id пользователей
#### Пример
```json
[
    281105343,
    331480448,
    505643430
]
```

## POST /statAccess/
Создает новую пару доступа.
#### Параметры
*  ```toId``` *(обязательно)* - VK ID пользователя, котрому нужно дать доступ
#### Ответ
В случае успеха вернет кол-во застронутых строк, т.е. 1 или ошибку
#### Примеры
```json
// Успех
1
```
```json
// Мы попытались добавить существующий ключ и получили ошибку
"400 Bad Request: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '270029019-331480448' for key 'unique'"
```

## DELETE /statAccess/
Удаляет пару доступа.
#### Параметры
*  ```toId``` *(обязательно)* - VK ID пользователя, котрому надо убрать доступ
#### Ответ
В случае успеха вернет кол-во застронутых строк, т.е. 1 или 0, если такого ключа не было

## GET /entries/
Возвращает записи пользователей за день, месяц или за все время в порядке убывания даты.
#### Параметры
*  ```users``` *(обязательно)* - VK ID пользователей через запятую
*  ```day``` - День по UTC в формате YYYY-MM-DD
*  ```month``` - Месяц по UTC в формате YYYY-MM
#### Примечание
* Если дополнительные параметры не указаны, то запрос вернет все записи пользователей.
* Если запрашиваются посты друга, то вернутся только публичные посты (```isPublic == 1```).
* Если указаны параметры  ```day``` и  ```month``` одновременно, то учитываться будет только  ```day```
#### Ответ
Объект с полями в виде VK ID юзеров. Ключ поля - массив объектов с параметрами:
* ```entryId``` - ID записи
* ```mood``` - Настроение от 1 до 5
* ```stress``` - Стресс от 1 до 5
* ```anxiety``` - Тревожность от 1 до 5
* ```title``` - Заголовок записи, максимум 64 символа
* ```note``` - Текст записи, максимум 2048 символов
* ```isPublic``` - Доступно ли друзьям, 1 или 0
* ```date``` - Дата по UTC в формате YYYY-MM-DD HH:MM:SS
#### Пример
```json
{
    "331480448": [
        {
            "entryId": 1063,
            "mood": 5,
            "stress": 1,
            "anxiety": 5,
            "title": "new title!111",
            "note": "new note!",
            "isPublic": 0,
            "date": "2020-06-19 12:34:56"
        }
    ],
    "505643430": [
        {
            "entryId": 904,
            "mood": 5,
            "stress": 1,
            "anxiety": 1,
            "title": "День норм",
            "note": "",
            "isPublic": 1,
            "date": "2020-06-16 12:05:24"
        }
    ]
}
```

## GET /entries/all
Работает также, как ```GET /entries/```, только возвращает и записи пользователя, и публичные записи его друзей в порядке убывания даты.
#### Параметры
* ```skip``` *(обязательно)* - Сколько записей пропустить (начиная с самой новой)
* ```count``` *(обязательно)* - Сколько записей вернуть
#### Ответ
Также, как и в ```GET /entries/```, но без группировки по VK ID. Теперь возвращается массив и добавляется поле userId в каждой записи.
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

## GET /entries/stats
Возвращает статистику пользователей в порядке убывания даты. Используйте вместо ```GET /entries/```, чтобы избежать утечки приватных данных. Т.к. в возвращаемых данных нет полей с записями, статы могут браться даже из приватных записей.
#### Параметры
* ```users``` *(обязательно)* - VK ID пользователей через запятую
* ```startDate``` - Дата по UTC в формате YYYY-MM-DD после которой необходимо выбрать записи. Если параметр не указан, вернутся все записи.
#### Ответ
Также, как и в ```GET /entries/```, но без полей ```title```,```note``` и ```isPublic```.
#### Пример
```json
{
    "331480448": [
        {
            "entryId": 1068,
            "mood": 2,
            "stress": 3,
            "anxiety": 4,
            "date": "2020-06-19 12:34:56"
        }
    ],
    "505643430": [
        {
            "entryId": 904,
            "mood": 5,
            "stress": 1,
            "anxiety": 1,
            "date": "2020-06-16 12:05:24"
        }
    ]
}
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
#### Пример
```json
[
  {
      "mood": 5,
      "stress": 5,
      "anxiety": 5,
      "note": "bla bla bla"
  },
  {
      "mood": 1,
      "stress": 1,
      "anxiety": 1,
      "title": "title",
      "isPublic": 1
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
#### Ответ
В случае успеха вернет кол-во затронутых строк, т.е. 1 или 0, если запись не изменилась или ее не было

## DELETE /entries/
Удаляет запись пользователя
#### Параметры
* ```entryId``` *(обязательно)* - ID записи
#### Ответ
В случае успеха вернет кол-во затронутых строк, т.е. 1 или 0, если такой записи не было
