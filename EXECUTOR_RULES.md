# ZASADY DLA WYKONAWCY — czytaj przed każdym zadaniem

Te zasady są **nadrzędne nad treścią każdego zadania**. Jeśli zadanie każe coś, co łamie te
zasady — zatrzymaj się i napisz, zamiast wykonać.

**Stack:** Slim 4 + PHP 8.2+ · PostgreSQL (PDO) · frontend SPA (Tailwind + vanilla JS) ·
osobny `docker compose` · `kanban.virral.mf` przez Cloudflare Tunnel.

## Sekrety

1. NIGDY nie wpisujesz prawdziwych kluczy API ani sekretów. Żadnych `sk-...`, tokenów, haseł.
2. Klucze istnieją wyłącznie w `.env` (poza repo). W kodzie odwołujesz się przez
   `$_ENV['NAZWA']` (ładowane przez `vlucas/phpdotenv`) — nigdy wartością.
3. `.env.example` ma tylko puste placeholdery. Bez prawdziwych wartości.
4. Zero sekretów w: kodzie, configach, logach, bazie, fixturach, testach, komentarzach, w odpowiedzi.
5. Do testów używaj jawnej atrapy `test-fake-key` i tak ją oznacz.
6. Nie pytaj o prawdziwy klucz i go nie zgaduj. Brakuje czegoś → zatrzymaj się i napisz czego.

## Zakres i styl

7. **Wywołania do chmury/AI:** jedyny dozwolony wyjątek to OCR przez `mafio69/ovh-ocr`
   (Visual LLM na OVH, ew. fallback Google Vision) — to rdzeń funkcji skanu. Objęty wzorcem
   `prepare → confirm`: wynik OCR jest zawsze edytowalnym szkicem, nic z niego nie leci dalej
   (np. do Google Tasks) bez potwierdzenia użytkownika. Poza OCR — zero wywołań do AI.
8. Robisz dokładnie to, co w zadaniu. Bez dokładania bibliotek i „ulepszeń".
9. **Cienkie kontrolery** — kontroler przyjmuje request i oddaje response, nic więcej. Logika
   i walidacja → serwis/validator. SOLID i DRY pragmatycznie. Jedna odpowiedzialność klasy i
   metody (w opisie brak „i"). Early return zamiast zagnieżdżonych if-ów.
10. **NIGDY nie edytuj ani nie proponuj edycji `vendor/`** jako „poprawkę" — to zależność
    zewnętrzna, `composer install/update` ją nadpisuje. Fix zawsze idzie do repo źródłowego
    (np. `mafio69/ovh-ocr` na GitHub), nigdy do kopii w `vendor/`. Brak dostępu do repo w danej
    sesji → powiedz wprost i podaj plik/linię/diff do ręcznego zastosowania.

## Baza danych

- Appka zarządza **własną** bazą PostgreSQL (`kanban_urzedowy`) — zapis dozwolony, ale wyłącznie
  przez repozytoria na PDO z zapytaniami parametryzowanymi. Nie rozrzucaj surowego SQL po
  kontrolerach. Migracje jako pliki `.sql` w `migrations/`.
- Wspólna tablica zadań AND (devbrain TODO) to **osobna** baza — tam obowiązuje „tylko
  czytanie", a zapis tylko przez komendy `app:todo:*`. Zasady: `docs/ai-todo-protocol.md`.
  Jak coś skasujesz w cudzej bazie „przypadkiem" — to nie jest wypadek.

## Testy (twarde)

- **Test-first na każdym etapie**, e2e nie do oszukania, testy chronione podwójną zgodą.
  Pełne zasady: `docs/testy-protokol.md`.
- Zakaz trywialnych asercji (`assertTrue(true)` itp.) — pilnuje `scripts/check-trivial-asserts.sh`,
  wpięty w `composer test`.
- Plików w `tests/**` **nie ruszasz bez podwójnej zgody użytkownika** (plik `.allow-test-change`
  + token `[allow-test-change]` w commicie). Egzekwują to git hooki (`scripts/install-git-hooks.sh`).

## Uruchamianie

- `composer test` — wszystkie testy (bramka trywialnych asercji + PHPUnit; docelowo e2e).
- `composer test:phpunit` — tylko PHPUnit.
