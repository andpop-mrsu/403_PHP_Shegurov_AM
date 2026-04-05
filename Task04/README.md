# Task04: SPA + REST API на Laravel

Игра **«Арифметическая прогрессия»** из предыдущей лабораторной работы перенесена на фреймворк **Laravel**.

## Описание

Игроку показывается ряд из 10 чисел, образующий арифметическую прогрессию со случайным шагом. Один из элементов заменён точками (`..`). Задача игрока — определить пропущенное число.

Приложение реализовано как SPA-интерфейс на главной странице с REST API на Laravel. Все данные об играх сохраняются в базе данных SQLite.

## Что реализовано

- `GET /` — SPA-интерфейс игры;
- `GET /api/games` — список всех игр;
- `GET /api/games/{id}` — информация о конкретной игре и её ответах;
- `POST /api/games` — создание новой игры;
- `POST /api/games/{id}/steps` — отправка ответа игрока.

## Какие данные сохраняются в базе

Для каждой игры сохраняются:
- имя игрока;
- дата начала игры;
- дата завершения игры;
- статус игры;
- результат игры;
- прогрессия с пропущенным числом;
- полная прогрессия;
- пропущенное число;
- ответ игрока;
- правильность ответа.

## Требования

- PHP 8.2+
- Composer
- SQLite
- PHP-расширения `pdo_sqlite` и `fileinfo`
- GNU Make (для `make install`)

## Установка

В Linux установка выполняется одной командой:

```bash
cd Task04
make install
```

Команда `make install` автоматически:

- устанавливает зависимости через `composer install`;
- создаёт .env из .env.example, если файла ещё нет;
- создаёт каталог database, если нужно;
- создаёт файл базы данных database/database.sqlite;
- генерирует APP_KEY;
- выполняет миграции.

## Запуск

```bash
cd Task04
php artisan serve
```

Открыть в браузере:

- `http://localhost:8000/`

## Быстрая проверка API (PowerShell)

```powershell
# Создать новую игру
$game = Invoke-RestMethod -Uri "http://localhost:8000/api/games" -Method Post -ContentType "application/json" -Body '{"player_name":"Anton"}'
$game

# Отправить ответ
Invoke-RestMethod -Uri ("http://localhost:8000/api/games/" + $game.id + "/steps") -Method Post -ContentType "application/json" -Body '{"answer":"10"}'

# Получить список игр
Invoke-RestMethod -Uri "http://localhost:8000/api/games" -Method Get

# Получить информацию по конкретной игре
Invoke-RestMethod -Uri ("http://localhost:8000/api/games/" + $game.id) -Method Get
```
