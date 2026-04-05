const apiBase = "/api";

const startForm = document.getElementById("start-form");
const answerForm = document.getElementById("answer-form");
const refreshHistoryButton = document.getElementById("refresh-history");
const roundCard = document.getElementById("round-card");
const resultCard = document.getElementById("result-card");
const resultBox = document.getElementById("result-box");
const progressionText = document.getElementById("progression-text");
const gamesBody = document.getElementById("games-body");
const stepsCard = document.getElementById("steps-card");
const stepsBox = document.getElementById("steps-box");
const errorCard = document.getElementById("error-card");

let activeGameId = null;

async function apiRequest(path, options = {}) {
    const response = await fetch(`${apiBase}${path}`, {
        ...options,
        headers: {
            "Content-Type": "application/json",
            ...(options.headers || {}),
        },
    });

    let payload = null;
    const rawText = await response.text();

    if (rawText !== "") {
        try {
            payload = JSON.parse(rawText);
        } catch (error) {
            throw new Error("Сервер вернул невалидный JSON");
        }
    }

    if (!response.ok) {
        const message = payload && payload.error ? payload.error : `HTTP ${response.status}`;
        throw new Error(message);
    }

    return payload;
}

function showError(message) {
    if (!errorCard) {
        return;
    }

    errorCard.classList.remove("hidden");
    errorCard.textContent = message;
}

function clearError() {
    if (!errorCard) {
        return;
    }

    errorCard.classList.add("hidden");
    errorCard.textContent = "";
}

function escapeHtml(value) {
    const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
    };

    return String(value).replace(/[&<>"']/g, (symbol) => map[symbol]);
}

function renderGames(games) {
    if (!gamesBody) {
        return;
    }

    if (!Array.isArray(games) || games.length === 0) {
        gamesBody.innerHTML = '<tr><td colspan="8">Записей пока нет.</td></tr>';
        return;
    }

    gamesBody.innerHTML = games
        .map((game) => {
            const gameId = escapeHtml(game.id);
            const playerName = escapeHtml(game.player_name ?? "");
            const startedAt = escapeHtml(game.started_at ?? "");
            const finishedAt = escapeHtml(game.finished_at ?? "-");
            const status = escapeHtml(game.status ?? "");
            const result = escapeHtml(game.result ?? "-");
            const stepsCount = escapeHtml(game.steps_count ?? 0);

            return `
                <tr>
                    <td>${gameId}</td>
                    <td>${playerName}</td>
                    <td>${startedAt}</td>
                    <td>${finishedAt}</td>
                    <td>${status}</td>
                    <td>${result}</td>
                    <td>${stepsCount}</td>
                    <td>
                        <button type="button" class="view-steps" data-id="${gameId}">
                            Детали
                        </button>
                    </td>
                </tr>
            `;
        })
        .join("");
}

function renderSteps(gamePayload) {
    if (!stepsCard || !stepsBox) {
        return;
    }

    const game = gamePayload.game || {};
    const steps = Array.isArray(gamePayload.steps) ? gamePayload.steps : [];

    if (steps.length === 0) {
        stepsCard.classList.remove("hidden");
        stepsBox.innerHTML = `
            <div class="details-card">
                <p><strong>Игра #${escapeHtml(game.id ?? "")}</strong></p>
                <p>Для этой игры пока нет сохранённых ходов.</p>
            </div>
        `;
        return;
    }

    const rows = steps
        .map((step) => {
            const stepNumber = escapeHtml(step.step_number);
            const answeredAt = escapeHtml(step.answered_at);
            const progressionWithGap = escapeHtml(step.progression_with_gap);
            const progressionFull = escapeHtml(step.progression_full);
            const userAnswer = escapeHtml(step.user_answer);
            const missingNumber = escapeHtml(step.missing_number);
            const resultLabel = step.is_correct ? "Верно" : "Неверно";

            return `
                <tr>
                    <td>${stepNumber}</td>
                    <td>${answeredAt}</td>
                    <td><code>${progressionWithGap}</code></td>
                    <td>${userAnswer}</td>
                    <td>${missingNumber}</td>
                    <td>${resultLabel}</td>
                    <td><code>${progressionFull}</code></td>
                </tr>
            `;
        })
        .join("");

    stepsCard.classList.remove("hidden");
    stepsBox.innerHTML = `
        <div class="details-card">
            <p><strong>Игра #${escapeHtml(game.id ?? "")}</strong></p>
            <p>Игрок: <strong>${escapeHtml(game.player_name ?? "")}</strong></p>
            <p>Статус: <strong>${escapeHtml(game.status ?? "")}</strong></p>
            <p>Результат: <strong>${escapeHtml(game.result ?? "-")}</strong></p>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Шаг</th>
                        <th>Дата</th>
                        <th>Прогрессия с пропуском</th>
                        <th>Ответ игрока</th>
                        <th>Правильный ответ</th>
                        <th>Итог</th>
                        <th>Полная прогрессия</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

async function loadGames() {
    try {
        const games = await apiRequest("/games", { method: "GET" });
        renderGames(games);
    } catch (error) {
        showError(error.message);
    }
}

if (startForm) {
    startForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearError();

        const formData = new FormData(startForm);
        const playerName = (formData.get("player_name") || "").toString().trim();

        if (playerName === "") {
            showError("Введите имя игрока");
            return;
        }

        try {
            const game = await apiRequest("/games", {
                method: "POST",
                body: JSON.stringify({ player_name: playerName }),
            });

            activeGameId = game.id;

            if (progressionText) {
                progressionText.textContent = Array.isArray(game.progression)
                    ? game.progression.join(" ")
                    : "";
            }

            roundCard?.classList.remove("hidden");
            resultCard?.classList.add("hidden");
            stepsCard?.classList.add("hidden");

            if (resultBox) {
                resultBox.className = "result";
                resultBox.innerHTML = "";
            }

            if (stepsBox) {
                stepsBox.innerHTML = "";
            }

            answerForm?.reset();
        } catch (error) {
            showError(error.message);
        }
    });
}

if (answerForm) {
    answerForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearError();

        if (activeGameId === null) {
            showError("Сначала начните игру");
            return;
        }

        const formData = new FormData(answerForm);
        const answer = (formData.get("answer") || "").toString().trim();

        try {
            const result = await apiRequest(`/games/${activeGameId}/steps`, {
                method: "POST",
                body: JSON.stringify({ answer }),
            });

            resultCard?.classList.remove("hidden");
            roundCard?.classList.add("hidden");
            activeGameId = null;

            if (progressionText) {
                progressionText.textContent = "";
            }

            const resultClass = result.is_correct ? "result result--ok" : "result result--fail";
            const resultTitle = result.is_correct ? "Верно!" : "Неверно";

            if (resultBox) {
                resultBox.className = resultClass;
                resultBox.innerHTML = `
                    <p class="result__title">${resultTitle}</p>
                    <p>Ваш ответ: <strong>${escapeHtml(result.user_answer)}</strong></p>
                    <p>Правильный ответ: <strong>${escapeHtml(result.correct_answer)}</strong></p>
                    <p>Прогрессия: <code>${escapeHtml(result.progression_with_gap)}</code></p>
                    <p>Полная прогрессия: <code>${escapeHtml(result.progression_full)}</code></p>
                `;
            }

            await loadGames();
        } catch (error) {
            showError(error.message);
        }
    });
}

if (refreshHistoryButton) {
    refreshHistoryButton.addEventListener("click", async () => {
        clearError();
        await loadGames();
    });
}

if (gamesBody) {
    gamesBody.addEventListener("click", async (event) => {
        const target = event.target;

        if (!(target instanceof HTMLElement) || !target.classList.contains("view-steps")) {
            return;
        }

        const gameId = target.dataset.id;
        if (!gameId) {
            return;
        }

        clearError();

        try {
            const gamePayload = await apiRequest(`/games/${gameId}`, {
                method: "GET",
            });

            renderSteps(gamePayload);
        } catch (error) {
            showError(error.message);
        }
    });
}

void loadGames();
