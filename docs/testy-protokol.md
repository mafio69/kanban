# Protokół testów — Kanban urzędowy

Zasady dla **każdego AI** i człowieka pracującego nad tym repo. Cel: testy, których AI nie
jest w stanie po cichu oszukać, i etapy, które kończą się realnie działającym UX widocznym
w przeglądarce.

## 1. Test-first na każdym etapie

Każdy etap (pionowy plasterek: backend + SQL + UX razem) zaczyna się od napisania testu
e2e, **zanim** powstanie implementacja. Kolejność w tasku jest sztywna:

1. `E<n>.T` — napisz test e2e (na start **czerwony**, bo funkcji jeszcze nie ma).
2. `E<n>.1..k` — implementacja aż test przechodzi (**zielony**).
3. `E<n>.V` — weryfikacja: e2e zielony + screenshot + **kontrola wizualna użytkownika w przeglądarce**.

Etap jest "zrobiony" dopiero po kroku `.V`, nie po samym zielonym teście.

## 2. Testy nie do oszukania (anti-fake)

Problem: AI potrafi napisać test, który "przechodzi" nie sprawdzając niczego (trywialna
asercja, zamockowana cała warstwa, fake method udająca sukces). Reguły, które to blokują:

- **E2E na realnym stacku.** Playwright uruchamia prawdziwą przeglądarkę przeciw realnie
  działającej appce (nginx + php-fpm) i **realnemu** Postgresowi testowemu. Warstwa, którą
  testujemy, nie może być zamockowana.
- **Losowy token round-trip.** Test seeduje do bazy losowy UUID/token per uruchomienie,
  potem sprawdza, że **ten sam** token pojawił się w DOM przeglądarki. AI nie zahardkoduje
  wyniku, bo token jest losowy i musi realnie przejść przez SQL → API → render.
- **Dowód wizualny.** Każdy test e2e zapisuje screenshot jako artefakt — do Twojej kontroli
  wizualnej, niezależnej od tego, co twierdzi kod testu.
- **Zakaz trywialnych asercji.** Nie wolno `assertTrue(true)`, `expect(true).toBe(true)`,
  ani asercji na zamockowany stub zamiast realnej odpowiedzi. Asercja musi dotyczyć realnej
  treści z realnego przepływu.
- **Zewnętrzne API (Google, OVH) przez nagrane fixture, nie mock w kodzie testu.** Render
  i zapis do naszej bazy testujemy realnie; tylko odpowiedź chmury jest odtwarzana z
  nagranego pliku (deterministycznie), z unikalnym tokenem w treści, który musi dojść do UI.

## 3. Ochrona testów — podwójna zgoda

AI **nie edytuje** plików w `tests/**` bez Twojej podwójnej zgody. Egzekwuje to git hook
(`scripts/git-hooks/`), nie tylko dobra wola:

1. **Pierwsza zgoda** — istnieje plik `.allow-test-change` w katalogu repo (tworzysz go Ty:
   `touch .allow-test-change`). `pre-commit` odrzuca commit ruszający `tests/**`, gdy go nie ma.
2. **Druga zgoda** — komunikat commita zawiera token `[allow-test-change]`. `commit-msg`
   odrzuca commit ruszający `tests/**` bez tego tokenu.

Obie bramki naraz = podwójna zgoda. Po zmianie skasuj `.allow-test-change`, żeby wróciła
blokada. Instalacja hooków: `bash scripts/install-git-hooks.sh`.

Zasada obowiązuje też, gdy hook nie jest zainstalowany (np. inne narzędzie AI) — to umowa,
hook jest tylko jej twardym wzmocnieniem.
