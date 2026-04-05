<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task04: Арифметическая прогрессия</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<main class="layout">
    <section class="hero">
        <div class="hero__badge">Laravel + SQLite</div>
        <h1>Игра «Арифметическая прогрессия»</h1>
        <p class="hero__text">
            Игроку показывается ряд из 10 чисел арифметической прогрессии с одним пропущенным значением.
            Нужно определить, какое число скрыто.
        </p>
    </section>

    <section class="panel panel--accent">
        <div class="panel__head">
            <div>
                <h2>Новая игра</h2>
                <p class="muted">Введите имя игрока и начните новую попытку.</p>
            </div>
        </div>

        <form id="start-form" class="stack">
            <div>
                <label for="player_name">Имя игрока</label>
                <input
                    id="player_name"
                    name="player_name"
                    type="text"
                    placeholder="Например, Иван"
                    autocomplete="off"
                    required
                >
            </div>

            <div>
                <button type="submit">Начать игру</button>
            </div>
        </form>
    </section>

    <section class="panel hidden" id="round-card">
        <div class="panel__head">
            <div>
                <h2>Текущий вопрос</h2>
                <p class="muted">Найдите пропущенное число в прогрессии.</p>
            </div>
        </div>

        <div class="question-box">
            <code id="progression-text">...</code>
        </div>

        <form id="answer-form" class="stack">
            <div>
                <label for="answer">Ваш ответ</label>
                <input
                    id="answer"
                    name="answer"
                    type="text"
                    placeholder="Введите число"
                    autocomplete="off"
                    required
                >
            </div>

            <div>
                <button type="submit">Проверить</button>
            </div>
        </form>
    </section>

    <section class="panel hidden" id="result-card">
        <div class="panel__head">
            <div>
                <h2>Результат</h2>
                <p class="muted">Проверьте, правильно ли был найден пропущенный элемент.</p>
            </div>
        </div>

        <div id="result-box" class="result"></div>
    </section>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2>История игр</h2>
                <p class="muted">Здесь сохраняются игроки, даты, результаты и сведения о прогрессиях.</p>
            </div>
            <div>
                <button id="refresh-history" type="button">Обновить</button>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Игрок</th>
                    <th>Старт</th>
                    <th>Завершение</th>
                    <th>Статус</th>
                    <th>Итог</th>
                    <th>Ходов</th>
                    <th>Детали</th>
                </tr>
                </thead>
                <tbody id="games-body">
                <tr>
                    <td colspan="8">Загрузка...</td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel hidden" id="steps-card">
        <div class="panel__head">
            <div>
                <h2>Детали выбранной игры</h2>
                <p class="muted">Ответ игрока, правильное число и полная прогрессия.</p>
            </div>
        </div>

        <div id="steps-box"></div>
    </section>

    <section class="panel alert hidden" id="error-card"></section>
</main>

<script src="/app.js"></script>
</body>
</html>
