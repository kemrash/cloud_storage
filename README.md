# Облачное хранилище

Данный проект представляет собой систему облачного хранения, реализованную на PHP. Система позволяет пользователям загружать, скачивать и управлять своими файлами посредством REST API.

## Функциональные возможности

- **Аутентификация пользователей** — вход в систему и управление сеансами.
- **Загрузка и скачивание файлов** — быстрая и надёжная передача данных.
- **Управление файлами, папками и пользователями** — операции переименования, удаления и другие.
- **Безопасное хранение данных** — защита личной информации пользователей.
- **Восстановление пароля** — отправка инструкции для восстановления доступа на электронную почту.

## Установка

1. **Клонирование репозитория:**
   ```bash
   git clone https://github.com/kemrash/cloud_storage.git
   ```
2. **Переход в каталог проекта:**
   ```bash
   cd cloud_storage
   ```
3. **Настройка веб-сервера:**  
   Настройте ваш веб-сервер (например, XAMPP) для обслуживания каталога проекта.
4. **Передача параметров настройки базы данных и SMTP-сервера:**  
   Передайте действующие параметры настройки базы данных и SMTP-сервера, а также желаемые данные администратора, в эндпоинт:
   ```http
   POST /install
   ```

## Использование

### REST API Эндпоинты

- `GET /users/list` – Получить список пользователей с общедоступными данными.
- `GET /users/get/{id}` – Получить информацию о конкретном пользователе.
- `PUT /users/update` – Обновить данные пользователя.
- `POST /users/login` – Авторизовать пользователя.
- `GET /users/logout` – Завершить сеанс пользователя.
- `GET /users/reset_password` – Инициировать процедуру сброса пароля с отправкой email-сообщения со ссылкой на страницу восстановления (в ссылке передаются ID и токен).
- `PATCH /users/reset_password` – Сбросить пароль. При переходе по ссылке из письма в теле запроса указывается новый пароль.
- `GET /users/search/{email}` – Поиск пользователя по email.
- `GET /admin/users/list` – Получить список пользователей (для администратора).
- `POST /admin/users/create` – Создать нового пользователя (администратор).
- `GET /admin/users/get/{id}` – Получить информацию о конкретном пользователе (администратор).
- `DELETE /admin/users/delete/{id}` – Удалить пользователя (администратор).
- `PUT /admin/users/update/{id}` – Обновить данные пользователя (администратор).
- `GET /files/list` – Получить список файлов.
- `GET /files/get/{id}` – Получить информацию о файле.
- `POST /files/add` – Добавить новый файл в указанную папку.
- `PATCH /files/rename` – Переименовать файл.
- `DELETE /files/remove/{id}` – Удалить файл.
- `GET /files/share/{id}` – Получить список пользователей, с которыми файл расшарен.
- `PUT /files/share/{id}/{user_id}` – Добавить пользователя в список доступа к файлу.
- `DELETE /files/share/{id}/{user_id}` – Удалить пользователя из списка доступа к файлу.
- `GET /files/download` – Скачать файл.
- `GET /directories/list` – Получить список директорий.
- `POST /directories/add` – Добавить новую директорию.
- `PATCH /directories/rename` – Переименовать директорию.
- `GET /directories/get/{id}` – Получить информацию о директории.
- `DELETE /directories/delete/{id}` – Удалить директорию.
- `GET /` – Получить главную страницу.
- `POST /install` – Установить систему.

### Примеры запросов

Ниже приведены примеры запросов, извлечённые из коллекции Postman:

#### 1. Получить список пользователей

```bash
curl -X GET "http://localhost/users/list"
```

#### 2. Получить информацию о пользователе (id = 1)

```bash
curl -X GET "http://localhost/users/get/1"
```

#### 3. Авторизация пользователя

```bash
curl -X POST "http://localhost/users/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=example@mail.ru&password=12345"
```

#### 4. Обновление данных пользователя

```bash
curl -X PUT "http://localhost/users/update" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "id=1&email=example@mail.ru&password=13&role=admin&age=25&gender=male"
```

#### 5. Завершение сеанса пользователя

```bash
curl -X GET "http://localhost/users/logout"
```

#### 6. Поиск пользователя по email

```bash
curl -X GET "http://localhost/users/search/example@mail.ru"
```

#### 7. Инициация сброса пароля (отправка email)

```bash
curl -G "http://localhost/users/reset_password" \
  --data-urlencode "email=example@mail.ru"
```

#### 8. Сброс пароля (установка нового пароля)

```bash
curl -X PATCH "http://localhost/users/reset_password?id=1&token=33a5cb46c1d5fea3722ab83649df52dce1a6092b18ef26ab3a26bbce5885bf1d" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "password=123"
```

#### 9. Получить список пользователей (администратор)

```bash
curl -X GET "http://localhost/admin/users/list"
```

#### 10. Создание нового пользователя (администратор)

```bash
curl -X POST "http://localhost/admin/users/create" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=1@2.ru&password=12&role=user"
```

#### 11. Получить информацию о пользователе (администратор, id = 2)

```bash
curl -X GET "http://localhost/admin/users/get/2"
```

#### 12. Обновление данных пользователя (администратор, id = 2)

```bash
curl -X PUT "http://localhost/admin/users/update/2" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=1@3.ru&password=13&role=admin"
```

#### 13. Удаление пользователя (администратор, id = 2)

```bash
curl -X DELETE "http://localhost/admin/users/delete/2"
```

#### 14. Получить список файлов

```bash
curl -X GET "http://localhost/files/list"
```

#### 15. Получить информацию о файле (id = 2)

```bash
curl -X GET "http://localhost/files/get/2"
```

#### 16. Загрузка файла в указанную папку

```bash
curl -X POST "http://localhost/files/add" \
  -F "file=@/path/to/your/file" \
  -F "folderId=2"
```

#### 17. Переименование файла

```bash
curl -X PATCH "http://localhost/files/rename" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "id=3&fileName=test rename.webp"
```

#### 18. Удаление файла (id = 1)

```bash
curl -X DELETE "http://localhost/files/remove/1"
```

#### 19. Получить список пользователей, с которыми файл расшарен (id = 2)

```bash
curl -X GET "http://localhost/files/share/2"
```

#### 20. Добавить пользователя (user_id = 1) к файлу (id = 2)

```bash
curl -X PUT "http://localhost/files/share/2/1"
```

#### 21. Удалить пользователя (user_id = 1) из доступа к файлу (id = 2)

```bash
curl -X DELETE "http://localhost/files/share/2/1"
```

#### 22. Скачать файл

```bash
curl -X GET "http://localhost/files/download?file=67af1498534ac3.06494234_7d07372b-ca4b-4bc9-b9fa-a93b8651a24f"
```

#### 23. Получить список директорий

```bash
curl -X GET "http://localhost/directories/list"
```

#### 24. Создание новой директории

```bash
curl -X POST "http://localhost/directories/add" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "parentId=2&name=new folder"
```

#### 25. Переименование директории

```bash
curl -X PATCH "http://localhost/directories/rename" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "id=4&name=test rename"
```

#### 26. Получить информацию о директории (id = 2)

```bash
curl -X GET "http://localhost/directories/get/2"
```

#### 27. Удаление директории (id = 3)

```bash
curl -X DELETE "http://localhost/directories/delete/3"
```

#### 28. Установка системы (передача параметров)

```bash
curl -X POST "http://localhost/install" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "dbHost=localhost&dbName=cloud_storage&dbUser=root&dbPassword=&smtpHost=mail.host.ru&smtpPort=465&smtpUser=example@mail.ru&smtpPassword=&smtpFrom=example@mail.ru&adminUser=example@mail.ru&adminPassword=12345"
```

Так же пример запросов в качестве коллекции для Postman. По пути `Postman/cloud_storage.postman_collection.json`
