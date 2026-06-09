# KeepFit — дневник питания

KeepFit — семестровый проект по дисциплине **Fullstack Web разработка**.

Проект представляет собой веб-приложение для ведения дневника питания. Пользователь может регистрироваться, авторизовываться, рассчитывать дневную норму КБЖУ, добавлять съеденные продукты в дневник, редактировать записи приёмов пищи, создавать собственные продукты и просматривать статистику питания.

---

## Стек технологий

### Backend

- PHP 8.5
- PostgreSQL 17
- Composer
- PSR-4 autoload
- PSR-7 request/response
- PSR-15 middleware
- Monolog PSR-3
- PHPUnit
- PHP_CodeSniffer PSR-12
- Xdebug для отчёта покрытия тестами

### Frontend

- Vue 3
- Vite
- Composition API
- Tailwind CSS
- Chart.js

---

## Структура проекта

```text
KeepFit/
├── backend/
│   ├── Config/
│   │   └── bootstrap.php
│   ├── Logs/
│   │   └── app.log
│   ├── Migrations/
│   │   ├── V1_create_database.sql
│   │   ├── V2_create_types.sql
│   │   └── V3_create_tables.sql
│   ├── Public/
│   │   └── index.php
│   ├── src/
│   │   ├── Controllers/
│   │   ├── Core/
│   │   ├── Dtos/
│   │   ├── Enums/
│   │   ├── Exceptions/
│   │   ├── Middlewares/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   └── Services/
│   ├── Tests/
│   │   ├── Integration/
│   │   └── Unit/
│   ├── vendor/
│   ├── .env
│   ├── composer.json
│   ├── composer.lock
│   ├── phpunit.xml
│   └── psr12-report.txt
│
└── frontend/
    ├── src/
    │   ├── assets/
    │   ├── components/
    │   ├── router/
    │   ├── services/
    │   ├── views/
    │   ├── App.vue
    │   └── main.js
    ├── index.html
    ├── package.json
    ├── vite.config.js
    └── tailwind.config.js
```

---

## Основной функционал

- Регистрация и авторизация пользователя через JWT.
- Проверка защищённых маршрутов через `AuthMiddleware`.
- Просмотр профиля пользователя.
- Редактирование профиля.
- Расчёт дневной нормы КБЖУ по формуле Миффлина-Сан Жеора.
- Учёт цели пользователя:
  - похудение;
  - поддержание веса;
  - набор массы.
- Учёт коэффициента активности.
- Поиск продуктов в базе.
- Добавление продуктов в дневник питания.
- Разделение еды по приёмам пищи:
  - завтрак;
  - обед;
  - ужин;
  - другое.
- Редактирование и удаление записи о съеденном продукте.
- Создание, редактирование и удаление пользовательских продуктов.
- Просмотр дневника питания за выбранную дату.
- Просмотр статистики за неделю и месяц.
- Отображение прогресса по калориям и КБЖУ.

---

## Архитектура backend

Backend построен по слоистой архитектуре:

```text
HTTP Request
    ↓
Router
    ↓
Middleware
    ↓
Controller
    ↓
Service
    ↓
Repository / DataMapper
    ↓
Database
    ↓
PostgreSQL
```

### Основные слои

- `Public/index.php` — frontend-контроллер приложения, инициализирует HTTP-запрос и запускает роутер.
- `Core/Router.php` — отвечает за маршрутизацию.
- `Core/Route.php` — атрибут для описания маршрутов.
- `Controllers/` — принимают HTTP-запросы и возвращают HTTP-ответы.
- `BaseController` — базовый контроллер, от которого наследуются остальные контроллеры.
- `Services/` — бизнес-логика приложения.
- `Repositories/` — работа с базой данных.
- `Models/` — модели таблиц базы данных.
- `Dtos/` — объекты передачи данных между слоями.
- `Middlewares/` — промежуточная обработка запросов.
- `Database.php` — инкапсулирует подключение к PostgreSQL.
- `Config.php` — загружает настройки из `.env`.

---

## Используемый подход к работе с БД

В проекте используется подход **DataMapper**.

Модели не работают с базой данных напрямую. Вся работа с SQL вынесена в репозитории.

Пример логики:

```text
SQL row → Model → Response DTO
```

Например:

```text
FoodRepository:
строка таблицы foods → Food model → FoodResponse

MealRepository:
строка таблицы meals → Meal model → MealResponse

UserRepository:
строка таблицы users → User model → UserResponse
```

---

## Таблицы базы данных

В проекте используются связанные таблицы:

- `users` — пользователи;
- `user_parameters` — параметры пользователя для расчёта нормы;
- `daily_norms` — рассчитанные дневные нормы КБЖУ;
- `foods` — продукты;
- `meals` — записи о съеденных продуктах.

Связи:

```text
users 1 → many user_parameters
users 1 → many daily_norms
users 1 → many foods
users 1 → many meals
foods 1 → many meals
```

---

## Настройка backend

Перейти в папку backend:

```bash
cd backend
```

Установить зависимости:

```bash
composer install
```

Создать файл `.env` в папке `backend`:

```env
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=food_diary
DB_USER=postgres
DB_PASS=postgres

JWT_SECRET=super_secret_key_123
APP_DEBUG=true
```

Если пароль от PostgreSQL отличается, необходимо изменить `DB_PASS`.

---

## Создание базы данных

SQL-миграции находятся в папке:

```text
backend/Migrations/
```

Их нужно выполнить по порядку:

```text
V1_create_database.sql
V2_create_types.sql
V3_create_tables.sql
```

База данных проекта:

```text
food_diary
```

---

## Запуск backend

Из папки `backend` выполнить:

```bash
php -S localhost:5000 -t Public Public/index.php
```

Backend будет доступен по адресу:

```text
http://localhost:5000
```

---

## Настройка frontend

Перейти в папку frontend:

```bash
cd frontend
```

Установить зависимости:

```bash
npm install
```

Запустить frontend:

```bash
npm run dev
```

Frontend будет доступен по адресу, который покажет Vite, обычно:

```text
http://localhost:5173
```

---

## Основные API-маршруты

### Авторизация

```text
POST /api/register
POST /api/login
```

### Профиль

```text
GET  /profile
GET  /profile/edit
POST /profile/edit
GET  /profile/recalculate
POST /profile/recalculate
```

### Дневник питания

```text
GET /diary?date=YYYY-MM-DD
```

### Продукты

```text
GET  /food
GET  /food/search?q=...
GET  /food/recent
GET  /food/add
POST /food/add
GET  /food/{foodId}
POST /food/{foodId}
```

### Пользовательские продукты

```text
GET    /food/custom
GET    /food/custom/{foodId}
POST   /food/custom/{foodId}
DELETE /food/custom/{foodId}
```

### Записи приёмов пищи

```text
GET    /meals/{mealId}
POST   /meals/{mealId}
DELETE /meals/{mealId}
```

### Статистика

```text
GET /stats/weekly
GET /stats/monthly
```

---

## Авторизация запросов

После логина сервер возвращает JWT access token.

Для защищённых маршрутов необходимо передавать заголовок:

```text
Authorization: Bearer <access_token>
```

Пример:

```text
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6...
```

---

## Тестирование

В проекте используются unit- и integration-тесты на PHPUnit.

Запуск всех тестов:

```bash
php vendor/bin/phpunit
```

Текущее состояние тестов:

```text
Все тесты проходят успешно.
```

---

## Покрытие тестами

Для построения отчёта покрытия используется Xdebug.

Проверка, что Xdebug подключён:

```bash
php -v
```

Проверка режима coverage:

```bash
php -i | findstr xdebug.mode
```

Запуск отчёта покрытия в консоли:

```bash
php vendor/bin/phpunit --coverage-text
```

Создание HTML-отчёта:

```bash
php vendor/bin/phpunit --coverage-html coverage-report
```

HTML-отчёт будет создан в папке:

```text
backend/coverage-report/
```

Открыть файл:

```text
backend/coverage-report/index.html
```

Текущее покрытие строк:

```text
Lines: 70.32%
```

---

## Проверка PSR-12

В проекте используется PHP_CodeSniffer.

Запуск проверки:

```bash
php vendor/bin/phpcs --standard=PSR12 src
```

Результат проверки:

```text
PSR-12: ошибок не найдено.
```

Отчёт сохранён в файле:

```text
backend/psr12-report.txt
```

---

## Скрипты Composer

В `composer.json` можно использовать следующие команды:

```json
{
  "scripts": {
    "test": "php vendor/bin/phpunit",
    "coverage": "php vendor/bin/phpunit --coverage-text --coverage-html coverage-report",
    "phpcs": "php vendor/bin/phpcs --standard=PSR12 src"
  }
}
```

Тогда команды можно запускать так:

```bash
composer test
composer coverage
composer phpcs
```

---

## Сценарий демонстрации проекта

Для защиты проекта можно показать следующий сценарий:

1. Открыть frontend-приложение.
2. Зарегистрировать нового пользователя.
3. Авторизоваться.
4. Перейти в профиль.
5. Пересчитать дневную норму КБЖУ:
   - указать рост;
   - вес;
   - активность;
   - цель.
6. Перейти на главную страницу дневника.
7. Выбрать дату через календарь.
8. Найти продукт через поиск.
9. Добавить продукт в нужный приём пищи.
10. Проверить, что продукт появился в дневнике.
11. Изменить граммовку продукта.
12. Удалить продукт из дневника.
13. Создать собственный продукт.
14. Отредактировать собственный продукт.
15. Удалить собственный продукт.
16. Перейти на страницу статистики.
17. Показать недельную и месячную статистику.
18. Показать отчёт PHPUnit coverage.
19. Показать отчёт PHP_CodeSniffer PSR-12.

---

## Выполнение требований семестровой работы

### Архитектура

- Используется PHP 8.5.
- Соблюдена структура каталогов и неймспейсов.
- Есть frontend-контроллер `Public/index.php`.
- Реализован `Router`.
- Все контроллеры наследуются от `BaseController`.
- Маршруты описаны через атрибут `Route`.
- Работа с БД инкапсулирована через `Database`.
- Конфигурация вынесена в `.env` и загружается через `Config`.
- Реализованы unit- и integration-тесты.
- Покрытие тестами превышает 70%.

### База данных

- Используется больше трёх связанных таблиц.
- Для таблиц созданы модели.
- Работа с БД вынесена в репозитории.
- Используется подход DataMapper.

### Стандарты

- Логирование реализовано через Monolog PSR-3.
- Автозагрузка настроена через Composer PSR-4.
- Запросы и ответы инкапсулированы через PSR-7.
- Middleware реализованы по PSR-15.
- Проведена проверка PSR-12 через PHP_CodeSniffer.

### Защита

- Проект готов к публичной демонстрации.
- Подготовлен сценарий демонстрации работы приложения.
