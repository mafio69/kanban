# Kanban urzędowy — etapy (flow) do wizualnej kontroli

Przegląd dla człowieka. Pełny backlog do importu: [`kanban-todo-import.md`](./kanban-todo-import.md).
Zasady testów i ochrona: [`testy-protokol.md`](./testy-protokol.md).
Konspekt źródłowy: [`kanban-zapytania-urzedowe.md`](./kanban-zapytania-urzedowe.md).

## Jak pracujemy

Pionowe plasterki: każdy etap to **backend + SQL + UX razem**, nie osobno warstwami.
Kolejność w każdym etapie jest sztywna:

1. **Test e2e najpierw** (czerwony) — Playwright na realnej appce + realnym Postgresie.
2. **Implementacja** aż test zielony.
3. **Kontrola wizualna** — screenshot + Ty patrzysz na działający UX w przeglądarce.

Testów nie da się oszukać: losowy token seedowany do bazy musi realnie przejść SQL → API →
render i pojawić się w DOM; zero mockowania testowanej warstwy; zewnętrzne API (Google, OVH)
tylko z nagranych fixture. Testy są chronione git hookiem — zmiana wymaga Twojej **podwójnej
zgody** (plik `.allow-test-change` + token `[allow-test-change]` w commicie).

## Etapy (co realnie widać po każdym)

**E0 — Fundament i ochrona testów.** Harness Playwright, git hooki ochrony testów, szkielet
Slim + strona startowa, PostgreSQL + baza + migracja `sprawy`. Widać: pusta appka wstaje,
health zielony.

**E1 — Board (read-only).** Sprawy z bazy w 5 kolumnach Kanbana. Widać: karty w kolumnach
Nowe/W trakcie/Czekam/Odłożone/Zrobione, przełącznik zarchiwizowane.

**E2 — Gmail.** Sync z etykiety (w teście fixture), przycisk Odśwież, podgląd maila. Widać:
pobrane maile jako karty w Nowe, bez duplikatów.

**E3 — Skan / OCR.** Upload jpg/png → OCR → karta w Nowe z **edytowalnym** `ocr_text`
(prepare→confirm). Widać: skan obok tekstu, edycja tekstu zapisuje się.

**E4 — Drag&drop.** Przeciąganie kart między kolumnami, trwałe w bazie. Widać: karta zostaje
w nowej kolumnie po odświeżeniu.

**E5 — Zadania i archiwum.** Google Tasks (nowe/istniejące), Odłóż/Zrobione, usuwanie,
opcjonalny termin w kalendarzu. Widać: akcje na karcie działają.

**E6 — Wdrożenie.** Osobny `docker compose` (nginx + php-fpm, Postgres zewnętrzny),
Cloudflare Tunnel → `kanban.virral.mf`. Widać: appka pod domeną przez tunel, smoke happy-path.

**E7 — Auth (ostatni).** Google OAuth connect: realne podłączenie konta na żywej instancji,
refresh token w Postgresie, prawdziwy sync Gmail/Tasks. Widać: „połączono jako …”, realne
maile z etykiety w Nowe. Domyka etapy Gmail/Tasks, które wcześniej chodziły na fixture.

## Wciąż do potwierdzenia (nie blokuje E0–E1)

Nazwa etykiety Gmail; nazwa listy Google Tasks; host/port serwera Postgres; nazwa zmiennej
`.env` z tokenem OVH AI Endpoints (potwierdzić przy pierwszym użyciu klasy `OvhOcr\`).
