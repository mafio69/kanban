# Propozycja: Kanban zapytań urzędowych (samodzielna appka Slim + SPA)

Status: projekt do akceptacji — jeszcze nie implementowane.

## Motywacja

Prototyp budowany z Perplexity (Arkusz Google + Apps Script — skrypt Google do
automatyzacji arkuszy) skończył się kolorowanym arkuszem z podpowiedziami w
komórkach, nie prawdziwym Kanbanem: żeby cokolwiek zrobić, trzeba wejść w menu i
wpisywać numery. Zero klikalnych przycisków na karcie.

Zdecydowałeś się na inne podejście niż Google Apps Script: samodzielna appka,
backend (część serwerowa) w PHP na Slim (lekki framework — szkielet do budowy
appek webowych, dużo mniejszy niż Symfony) na Twoim lokalnym serwerze, frontend
(część w przeglądarce) jako SPA (Single Page Application — jedna strona HTML,
która sama się przebudowuje przez JavaScript, bez przeładowań) w Tailwindzie
(biblioteka CSS z gotowymi klasami do stylowania). Powód: dane i tokeny Google
zostają na Twojej infrastrukturze, zero kodu wygenerowanego przez inne AI z
dostępem do skrzynki, zero publicznego adresu appki Google do zgadnięcia.

## Sprawa jako centralny obiekt (doprecyzowanie)

Appka jest centrum, nie Gmail. Gmail, Kalendarz i Zadania to narzędzia, którymi
appka zarządza — my (Ty + appka) tylko układamy ich dane w logiczną całość
wokół jednego obiektu: **sprawy**. Sprawa może powstać z dwóch źródeł:

1. **E-mail** — z etykiety Gmail, jak w pierwszej wersji projektu.
2. **Skan dokumentu** — zdjęcie/skan papierowego pisma z urzędu (jpg/png),
   przepuszczone przez OCR (Optical Character Recognition — rozpoznawanie
   tekstu na obrazie) i zamienione na tekst, z którego appka zakłada nową
   sprawę tak samo jak z maila.

Etapy Kanbana (Nowe/W trakcie/Czekam/Odłożone/Zrobione) są wspólne dla obu
źródeł — sprawa nie wie i nie musi wiedzieć, skąd przyszła, żeby przechodzić
przez etapy. To zostawia miejsce na kolejne źródła w przyszłości (np. ręczne
dodanie sprawy z poziomu appki) bez zmiany reszty modelu.

## Architektura

```
┌─────────────────────────┐        ┌──────────────────────────────┐
│  Frontend (SPA)         │  REST  │  Backend (Slim, PHP 8.2+)     │
│  Tailwind + vanilla JS  │◄──────►│  routes /api/*                 │
│  karty (sprawy), d&d    │  API   │  PostgreSQL (tabela sprawy)      │
└─────────────────────────┘        │  Google API PHP Client          │
        ▲                          │  mafio69/ovh-ocr                │
        │ upload jpg/png           └───────┬─────────────────┬──────┘
        │ (skan pisma)                     │ OAuth2 (offline) │ HTTPS (klucz API)
        │                          ┌────────┴──────────┐   ┌──┴───────────────┐
        └──────────────────────────┤ Gmail · Calendar ·│   │ OVH AI Endpoints │
                                    │ Tasks API         │   │ (+ fallback      │
                                    └───────────────────┘   │  Google Vision)  │
                                                             └──────────────────┘
```

Wszystko poza samym wywołaniem OCR woła jeden mały serwer PHP-FPM + nginx
(może pojechać jako kolejny serwis w istniejącym Dockerze
`docker-fast-php-logger`, albo osobny `docker compose` — do ustalenia, nie
wpływa na projekt appki). `mafio69/ovh-ocr` to zwykła zależność Composer, nie
osobny proces — jedyna różnica względem reszty appki: to wywołanie realnie
leci do chmury (OVH, ew. Google Vision), nie zostaje lokalnie.

## Model danych (PostgreSQL na już działającym serwerze, jedna tabela `sprawy`)

Zmiana względem pierwszej wersji: zamiast nowego pliku SQLite — istniejący,
już działający serwer PostgreSQL (masz taki uruchomiony gdzie indziej w swojej
infrastrukturze). Appka dostaje własną bazę/schemat (np. `kanban_urzedowy`) na
tym serwerze, nie miesza tabel z innymi projektami. Mniej ruchomych części do
pilnowania niż osobny plik SQLite, skoro Postgres i tak już działa.

| kolumna | typ | opis |
|---|---|---|
| `id` | int PK | |
| `source` | enum: `email`\|`skan`\|`reczne` | skąd powstała sprawa |
| `gmail_thread_id` | string, nullable, unikalny | tylko gdy `source = email`, dedup przy synchronizacji |
| `scan_path` | string, nullable | ścieżka do zapisanego oryginału zdjęcia/skanu, tylko gdy `source = skan` |
| `ocr_text` | text, nullable | pełny tekst wyciągnięty z obrazu przez Tesseract |
| `subject`, `sender`, `snippet` | string | podgląd bez wołania Gmaila; dla skanu `sender` może być puste, `subject` — pierwsza linia OCR albo wpisana ręcznie |
| `label` | string, nullable | etykieta Gmail (tylko dla `source = email`) |
| `stage` | enum | patrz niżej |
| `position` | int | kolejność w kolumnie (drag&drop) |
| `google_task_id` | string, nullable | jeśli dodano zadanie |
| `google_task_list_id` | string, nullable | | 
| `calendar_event_id` | string, nullable | jeśli dodano termin |
| `notes` | text | Twoje własne notatki na karcie |
| `archived` | bool | patrz "Odłożone vs Zrobione" niżej |
| `created_at`, `updated_at` | datetime | |

Dostęp przez PDO (natywny interfejs PHP do baz danych) z parametrami
połączenia w `.env` (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`)
— zero configu poza tym, migracje jako zwykłe pliki `.sql` (prosto, bez
dokładania Doctrine/ORM-a tylko dla jednej tabeli).

## Etapy Kanbana

Z Twojego opisu (ZUS jako przykład — sprawa się ciągnie, nic teraz nie trzeba
robić, ale nie jest zamknięta) wychodzą **5 etapów**, celowo bez nadmiaru bytów:

1. **Nowe** — świeży mail z etykiety, jeszcze nieotwarty
2. **W trakcie** — pracujesz nad tym teraz
3. **Czekam** — piłka po stronie urzędu (ZUS itp.), Ty nic nie robisz, sprawa żyje
4. **Odłożone** — wygląda na skończone, ale może wrócić; ląduje na dole listy,
   **nie jest archiwizowane** (czyli nadal widoczne, w przeciwieństwie do 5)
5. **Zrobione** — faktycznie zamknięte, `archived = true`, domyślnie ukryte
   (przełącznik "pokaż zarchiwizowane" je odsłania)

Różnica 4 vs 5: "Odłożone" to świadomy stan pośredni, nie kosz. Trzymamy się
tych pięciu — więcej etapów na start rzeczywiście zaczyna mnożyć byty, jak
napisałeś.

## API backendu (Slim, REST — architektura, gdzie każdy zasób ma swój adres URL)

- `GET /api/sprawy` — lista spraw z etapem i kolejnością
- `POST /api/sync/gmail` — pobiera nowe wątki z etykiety Gmail, wstawia jako
  "Nowe" (dedup po `gmail_thread_id`) — przycisk "Odśwież" w UI
- `POST /api/sprawy/skan` — multipart upload jpg/png, backend woła Tesseract,
  zapisuje oryginał (`scan_path`) i wynik OCR (`ocr_text`), tworzy nową sprawę
  w etapie "Nowe" — to jest drugie, równoległe wejście do systemu obok maila
- `PATCH /api/sprawy/{id}/move` — `{stage, position}` — wynik przeciągnięcia karty
- `PATCH /api/sprawy/{id}` — edycja pól ręcznie (m.in. poprawienie `ocr_text`
  po przejrzeniu — patrz "Recenzja OCR" niżej)
- `POST /api/sprawy/{id}/task` — `{mode: "new"|"existing", task_list_id, task_id?}`
  — tworzy zadanie w Google Tasks **albo** dopisuje mail/skan jako notatkę do
  istniejącego (dokładnie ta logika 0/1/2/3, którą chciałeś, tylko jako
  prawdziwy przycisk + lista wyboru, nie wpisywanie liczby w oknie dialogowym)
- `POST /api/sprawy/{id}/event` — opcjonalnie: termin w kalendarzu (np.
  przypomnienie o terminie odpowiedzi)
- `PATCH /api/sprawy/{id}/archive` — `{archived: bool}`
- `GET /api/sprawy/{id}/email` — pełna treść maila na żądanie, tylko gdy
  `source = email` (backend proxy — token Google nigdy nie trafia do przeglądarki)
- `GET /api/sprawy/{id}/skan` — oryginalny obraz + tekst OCR, tylko gdy
  `source = skan`
- `DELETE /api/sprawy/{id}` — usuwa sprawę z Kanbana (nie maila/pliku źródłowego)

## Frontend

Jedna strona, kolumny jako siatka CSS, karty przeciągane (biblioteka SortableJS
albo natywne HTML5 drag&drop — do wyboru przy implementacji). Na każdej karcie:

- temat, nadawca, etykieta, skrót treści
- 👁 **Zobacz e-mail** — modal z treścią (albo prosty link "Otwórz w Gmailu",
  bez dodatkowego zapytania do API — tańsza opcja)
- ✅ **Dodaj zadanie / dołącz notatkę** — mini-okno: "Nowe zadanie" albo wybór
  z listy istniejących zadań
- 📅 **Dodaj termin** (opcjonalnie, w pełnym zakresie)
- 🗄 **Zrobione** / ⏸ **Odłóż** — przenosi między etapem 4 i 5
- 🖼 **Zobacz skan** — tylko gdy `source = skan`: pokazuje oryginalne zdjęcie
  obok wyciągniętego tekstu (odpowiednik "Zobacz e-mail" dla drugiego źródła)

Nad kolumnami: przycisk **"📷 Dodaj sprawę ze skanu"** — wybór pliku jpg/png z
dysku albo zdjęcie z telefonu (jeśli otwierasz appkę na komórce), od razu woła
`/api/sprawy/skan`.

Przeciągnięcie karty między kolumnami = wywołanie `/move`, bez przeładowania
strony.

## OCR — skan dokumentu do tekstu

Aktualizacja: masz już gotową bibliotekę — **`mafio69/ovh-ocr`** (Twój
prywatny pakiet, MIT). Wyciąga tekst z obrazu przez Visual LLM (model językowy
z widzeniem — rozpoznaje tekst na zdjęciu inteligentniej niż klasyczny OCR,
lepiej radzi sobie z krzywym/słabym skanem) na OVH AI Endpoints, z opcjonalnym
fallbackiem (zapasowym rozwiązaniem, gdy główne zawiedzie) do Google Vision.
Obsługuje PL/EN — dokładnie to, czego trzeba do polskich pism urzędowych.
Wycofuję wcześniejszą rekomendację Tesseracta na rzecz tej biblioteki.

Konsekwencje dla appki (potwierdzone, `composer.json` biblioteki już widziałem):
- Repo: `https://github.com/mafio69/ovh-ocr` (potwierdzone), PHP `^8.1`,
  namespace PSR-4 `OvhOcr\` w `src/`, zależy od `guzzlehttp/guzzle` (klient
  HTTP) + `ext-json`/`ext-mbstring`/`ext-fileinfo`. Dodajemy jako pakiet VCS w
  `composer.json` appki, ten sam wzorzec co `mafio69/fast-php-logger` w
  `docker-fast-php-logger` — `"repositories": [{"type": "vcs", "url": "https://github.com/mafio69/ovh-ocr"}]`.
- Biblioteka sama sugeruje `vlucas/phpdotenv` do wczytania configu z `.env` —
  appka Slim najpewniej już ma własne wczytywanie `.env`, więc to się złoży
  bez dodatkowej pracy.
- Sekret: klucz do OVH AI Endpoints w `.env` appki (dokładną nazwę zmiennej
  potwierdzimy przy pierwszym użyciu klasy z `OvhOcr\`), zero w kodzie/repo,
  zgodnie z Twoją regułą sekretów.
- To jedyne miejsce w appce, gdzie dane (obraz skanu) lecą do chmury (OVH,
  ew. Google Vision) — inaczej niż reszta appki, która jest lokalna. Świadomy
  wyjątek, bo biblioteka już istnieje i robi dokładnie to, co potrzebne;
  zaznaczam to wprost, żebyś miał to policzone w głowie przy ocenie
  bezpieczeństwa całości.
- `POST /api/sprawy/skan` w backendzie woła tę bibliotekę zamiast (wcześniej
  założonego) binarnego `tesseract` — reszta przepływu (zapis `scan_path` +
  `ocr_text`, nowa sprawa w etapie "Nowe") bez zmian.

### Jak to działa pod spodem (z Twojej dokumentacji `OVH_ENDPOINTS.md`)

Biblioteka woła `POST /v1/chat/completions` na
`https://oai.endpoints.kepler.ai.cloud.ovh.net/v1` — API zgodne z formatem
OpenAI (chat + obraz jako `image_url` w base64), autoryzacja nagłówkiem
`Authorization: Bearer <OVH_AI_ENDPOINTS_ACCESS_TOKEN>`. To nie klasyczny OCR,
tylko Visual LLM (model językowy, który "czyta" obraz i opisuje/przepisuje,
co na nim widzi) — stąd nazwa "OCR" jest tu w cudzysłowie: model potrafi się
pomylić twórczo (halucynować), nie tylko literówkowo.

Trzy modele do wyboru (katalog `ovh-model-list.json`): `Qwen3.5-9B` (lite,
tanio/szybko), `Mistral-Small-3.2-24B` (medium, sensowny balans), `Qwen3.6-27B`
(premium, najlepszy przy krzywych/słabych skanach). Koszt jest pomijalny przy
Twojej skali (pojedyncze skany, nie tysiące) — proponuję **medium**
(`Mistral-Small-3.2-24B`) jako domyślny, z możliwością ręcznego przełączenia
na premium dla trudnego skanu.

**Ważne ograniczenie wprost z Twojej dokumentacji**: "dokumenty
prawne/dokumenty urzędowe — halucynacje LLM są niedopuszczalne" i
"rozpoznawanie pisma odręcznego — słabe". Sprawy urzędowe to dokładnie ta
kategoria, gdzie model może np. zmyślić numer sprawy albo datę, która wygląda
wiarygodnie, ale jest zła. Dlatego etap **"Nowe"** dla sprawy ze skanu musi
zawsze pokazywać `ocr_text` jako pole **edytowalne**, nie tylko podgląd — traktuj
wynik OCR jako szkic do sprawdzenia, nie gotowy fakt, zanim cokolwiek z tego
polecisz dalej (np. jako treść zadania w Google Tasks). To ten sam nawyk co
Twój wzorzec "prepare → confirm" z devbraina (żadne dane od AI nie lecą dalej
bez Twojego potwierdzenia) — tu robimy to samo, tylko dla wyniku OCR zamiast
odpowiedzi AI.

## Integracja z Google (Gmail / Calendar / Tasks)

`google/apiclient` (oficjalna biblioteka Google dla PHP) po stronie backendu.
OAuth2 w trybie offline (raz autoryzujesz przez przeglądarkę, backend dostaje
refresh token i trzyma go w `.env`/lokalnej tabeli, nigdy w kodzie ani w
odpowiedziach do frontendu). Zakresy (scopes — uprawnienia, o które appka
prosi): `gmail.readonly` + `gmail.modify` (jeśli chcesz też oznaczać maile jako
przeczytane/etykietować z poziomu Kanbana), `tasks`, `calendar.events`.

## Bezpieczeństwo

- Refresh token Google tylko po stronie serwera, w `.env`, zgodnie z regułą
  "sekrety tylko przez zmienne środowiskowe" z Twoich CLAUDE.md.
- Appka dostępna tylko lokalnie / za Twoim reverse proxy, nie publiczny URL
  Google jak w Apps Script.
- Brak zewnętrznego kodu (Perplexity) z dostępem do skrzynki — cały kod
  przechodzi przez code review tak jak reszta Twoich repo.

## Założenia do potwierdzenia przed implementacją

1. **Nazwa etykiety Gmail** do synchronizacji (nie udało mi się otworzyć
   `gmail_filtry_urzedowe.xml` — spróbuj załączyć ponownie albo podaj nazwę
   etykiety wprost).
2. **Nazwa listy Google Tasks**, do której mają trafiać nowe zadania (np.
   "Sprawy urzędowe" — pojawiało się wcześniej w rozmowie z Perplexity).
3. **Hosting**: nowy serwis w istniejącym Dockerze `docker-fast-php-logger`,
   czy osobny projekt/`docker compose` — możliwe obie opcje, nie wpływa na kod
   appki, tylko na plik compose.
4. Rozumiem "single side" jako literówkę od **SPA (single page)** — potwierdź,
   jeśli chodziło o coś innego.
5. ~~Biblioteka OCR~~ — rozstrzygnięte: `mafio69/ovh-ocr`
   (`github.com/mafio69/ovh-ocr`, potwierdzone). ~~Silnik domyślny~~ —
   rozstrzygnięte: **OVH jako pierwszy wybór**, Google Vision tylko jako
   fallback (zgodnie z domyślnym zachowaniem biblioteki).
6. **PostgreSQL** — rozumiem, że masz już działający serwer Postgres gdzie
   indziej w swojej infrastrukturze i appka ma się do niego podłączyć zamiast
   dostawać własną SQLite. Potwierdź: host/port serwera i czy appka dostaje
   nową, dedykowaną bazę na tym serwerze (rekomendacja) czy ma dzielić bazę z
   innym projektem.

## Nie robimy teraz

- Multi-user / logowanie wielu osób — appka jest jednoosobowa, jak devbrain.
- Automatyczna klasyfikacja treści maila/skanu przez AI (np. rozpoznanie typu
  pisma) — na razie tylko etykieta Gmail albo ręczny wybór decyduje, co trafia
  na Kanban i w jaki etap.
- Powiadomienia push / e-mail o zmianach na Kanbanie.
- Import historycznych maili sprzed uruchomienia synchronizacji (tylko to, co
  przyjdzie z etykietą po starcie appki, chyba że powiesz inaczej).
- Automatyczne odczytywanie zdjęć z dysku/chmury — skan trafia do systemu
  tylko przez świadome kliknięcie "Dodaj sprawę ze skanu", nie przez
  skanowanie folderu w tle.
