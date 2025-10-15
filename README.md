# Framework

## Install

composer project akeb/framework

Composer config

```json
{
    "require": {
        "akeb/framework": "^1.0.0"
    }
}
```

or

```bash
composer require akeb/framework
```

## Usage

### For development

```bash
./run.sh --dev serve
```

### For production

```bash
./run.sh start
```

### Web Interface

- Web Site [http://127.0.0.1:61080/](http://127.0.0.1:61080/)
- PhpMyAdmin [http://127.0.0.1:61081/](http://127.0.0.1:61081/)

Default username: **```admin@admin.com```** and password: **```admin```**

## Issues

- [x] Авторизация
- [x] Локализация
- [x] Права доступа
- [x] Меню
- [x] Изменение пароль
- [x] Выход
- [x] Список групп
- [x] Добавление группы
- [x] Редактирование группы
- [x] Удаление группы
- [x] Список пользователей
- [x] Права доступа групп
- [x] Права доступа пользователей
- [x] Создание пользователя
- [x] Редактирование пользователя
- [x] Список групп пользователя
- [x] Добавление пользователя в группу
- [x] Удаление пользователя из группы
- [x] Требовать смены пароля
- [x] Блокировка обычной авторизации
- [x] Блокировка регистрации
- [x] Глобальное логирование
- [x] Права доступа любых новых объектов
- [x] Логировать изменения IP адреса пользователя
- [x] Авторизация через OpenID connect
- [x] Авторизация через oAuth2
- [x] Добавление двухфакторной авторизации
- [x] Создание отдельной ветки для будущих проектов
- [ ] Просмотр логов изменений
- [ ] Регистрация пользователей
- [ ] Функция забыли пароль
- [ ] Нотификация через Telegram
- [ ] Нотификация через Mattermost
- [ ] Нотификация через Почту

## Environments

| Environment                  |  Default             |   Type   | Description                   |
|------------------------------|:--------------------:|:--------:|-------------------------------|
| ---------------------------- | -------------------- | -------- | ----------------------------- |
| TZ                           | UTC                  | string   | Timezone                      |
| PASSWORD_SALT                |                      | string   | Password Salt                 |
| ---------------------------- | -------------------- | -------- | ----------------------------- |
| MYSQL_HOST                   | localhost            | string   | MySQL Host                    |
| MYSQL_PORT                   | 3306                 | integer  | MySQL Port                    |
| MYSQL_USERNAME               | root                 | string   | MySQL User                    |
| MYSQL_PASSWORD               |                      | string   | MySQL Password                |
| MYSQL_DB_NAME                | example              | string   | MySQL DB Name                 |
| MYSQL_DONT_USE_SLAVE         | true                 | boolean  | MySQL Dont Use Slave          |
| MYSQL_SLAVE_HOST             | MYSQL_HOST           | string   | MySQL Slave Host              |
| MYSQL_SLAVE_PORT             | MYSQL_PORT           | string   | MySQL Slave Port              |
| MYSQL_SLAVE_USERNAME         | MYSQL_USERNAME       | string   | MySQL Slave User              |
| MYSQL_SLAVE_PASSWORD         | MYSQL_PASSWORD       | string   | MySQL Slave Password          |
| MYSQL_SLAVE_DB_NAME          | MYSQL_DB_NAME        | string   | MySQL Slave DB Name           |
| ---------------------------- | -------------------- | -------- | ----------------------------- |
| APP_SIGNIN_ACTIVE            | true                 | boolean  | App Sign In Active            |
| APP_SIGNUP_ACTIVE            | true                 | boolean  | App Sign Up Active            |
| APP_DEBUG                    | false                | boolean  | App Debug                     |
| ---------------------------- | -------------------- | -------- | ----------------------------- |
| SMTP_HOST                    |                      | string   | SMTP Host                     |
| SMTP_PORT                    | 25                   | integer  | SMTP Port                     |
| SMTP_USERNAME                |                      | string   | SMTP User                     |
| SMTP_PASSWORD                |                      | string   | SMTP Password                 |
| SMTP_TLS                     | false                | boolean  | SMTP TLS                      |
| SMTP_SSL                     | false                | boolean  | SMTP SSL                      |
| ---------------------------- | -------------------- | -------- | ----------------------------- |
| OPENIDCONNECT_PROVIDER       |                      | string   | OpenID Connect Provider URL   |
| OPENIDCONNECT_CLIENT_ID      |                      | string   | OpenID Connect Client Id      |
| OPENIDCONNECT_CLIENT_SECRET  |                      | string   | OpenID Connect Client Secret  |
| OPENIDCONNECT_BUTTON         |                      | string   | OpenID Connect Button Title   |
| OPENIDCONNECT_SCOPE          | email profile openid | string   | OpenID Connect Scope          |
| OPENIDCONNECT_REGISTER       | true                 | boolean  | OpenID Connect Register Allow |
| ---------------------------- | -------------------- | -------- | ----------------------------- |
| OAUTH_CLIENT_ID              |                      | string   | OAuth Client Id               |
| OAUTH_CLIENT_SECRET          |                      | string   | OAuth Client Secret           |
| OAUTH_AUTHORIZATION_ENDPOINT |                      | string   | OAuth Authorization Endpoint  |
| OAUTH_TOKEN_ENDPOINT         |                      | string   | OAuth Token Endpoint          |
| OAUTH_USERINFO_ENDPOINT      |                      | string   | OAuth Userinfo Endpoint       |
| OAUTH_BUTTON                 |                      | string   | OAuth Button Title            |
| OAUTH_SCOPE                  | self_profile         | string   | OAuth Scope                   |
| OAUTH_REGISTER               | true                 | boolean  | OAuth Register Allow          |
