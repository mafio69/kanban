# list: Kanban urzędowy — budowa
description: Backlog pionowymi plasterkami (backend+SQL+UX razem). Każdy etap: najpierw test e2e (czerwony), potem implementacja aż zielony, na końcu screenshot + kontrola wizualna w przeglądarce. Testy nie do oszukania (losowy token round-trip, realny Postgres, zero mockowania testowanej warstwy) — patrz docs/testy-protokol.md. Auth Google na końcu. Hosting: osobny docker compose na 192.168.1.172, kanban.virral.mf przez Cloudflare Tunnel. Import: php bin/console app:todo:import docs/kanban-todo-import.md

- [ ] E0.T Harness e2e Playwright + zasada test-first (pierwszy task, na przyszłość)
  Cel: Playwright uruchamia realną appkę przeciw realnemu Postgres testowemu; szablon testu z anti-fake (losowy token round-trip + screenshot).
  Weryfikacja: przykładowy test health przechodzi na realnym stacku, artefakt screenshot powstaje.
- [ ] E0.1 Ochrona testów — git pre-commit + commit-msg (podwójna zgoda)
  Cel: zmiana tests/** wymaga pliku .allow-test-change ORAZ tokenu [allow-test-change] w opisie commita.
  Weryfikacja: bash scripts/install-git-hooks.sh; próba zmiany testu bez zgody = commit odrzucony.
- [ ] E0.2 Szkielet Slim + kontener DI + strona SPA hello
  Cel: appka wstaje, GET /api/health zwraca 200, SPA renderuje stronę startową.
  Weryfikacja: e2e otwiera stronę w przeglądarce i widzi element startowy + screenshot.
- [ ] E0.3 Instalacja PostgreSQL + bazy kanban_urzedowy i kanban_urzedowy_test
  Cel: baza produkcyjna i testowa na serwerze, dostęp przez PDO z .env (DB_*).
  Weryfikacja: appka łączy się z bazą, healthcheck DB zielony.
- [ ] E0.4 Migracja 001_create_sprawy.sql + runner bin/migrate
  Cel: tabela sprawy wg modelu (enumy source/stage, gmail_thread_id UNIQUE nullable, indeksy stage+position), tabela migrations, idempotentny runner.
  Weryfikacja: migracja na czystej bazie, drugie uruchomienie nic nie zmienia.
- [ ] E1.T e2e: sprawy z bazy widoczne w 5 kolumnach Kanbana
  Cel: seed spraw z losowymi tokenami w różnych stage; test otwiera appkę i sprawdza, że każda karta jest we właściwej kolumnie + screenshot.
  Weryfikacja: test czerwony przed implementacją E1, zielony po.
- [ ] E1.1 Backend GET /api/sprawy (etap, position, filtr archived)
  Cel: lista spraw z etapem i kolejnością, ?archived=0|1.
  Weryfikacja: smoke test + asercja round-trip tokenu w E1.T.
- [ ] E1.2 SPA: siatka 5 kolumn + render kart + przełącznik zarchiwizowane
  Cel: Tailwind grid, karty z GET /api/sprawy, przełącznik pokaż zarchiwizowane.
  Weryfikacja: E1.T zielony.
- [ ] E1.V Kontrola wizualna etapu Board w przeglądarce
  Cel: użytkownik potwierdza wygląd i zawartość kolumn na żywej appce.
  Weryfikacja: screenshot z E1.T + akceptacja użytkownika.
- [ ] E2.T e2e: sync Gmail z fixture pokazuje maile jako karty w Nowe
  Cel: nagrany fixture wątków z etykiety; klik Odśwież; asercja, że maile (token w treści) pojawiają się w Nowe; drugi sync nie dubluje + screenshot.
  Weryfikacja: czerwony przed E2, zielony po.
- [ ] E2.1 GmailSyncService + POST /api/sync/gmail (dedup gmail_thread_id)
  Cel: pobiera wątki z etykiety, wstawia jako Nowe, bez duplikatów. Google API przez nagrany fixture w teście.
  Weryfikacja: test dedupu, ponowny sync nie duplikuje.
- [ ] E2.2 GET /api/sprawy/{id}/email (proxy treści, tylko source=email)
  Cel: pełna treść maila na żądanie, token nie trafia do przeglądarki.
  Weryfikacja: dla source!=email zwraca 409/404.
- [ ] E2.3 UX: przycisk Odśwież + podgląd maila (modal/link)
  Cel: Odśwież woła sync, karta pokazuje temat/nadawcę/skrót, podgląd treści.
  Weryfikacja: E2.T zielony.
- [ ] E2.V Kontrola wizualna etapu Gmail w przeglądarce
  Cel: użytkownik widzi realnie pobrane (z fixture) maile w kolumnie Nowe.
  Weryfikacja: screenshot + akceptacja użytkownika.
- [ ] EG.T e2e: nowy mail o istniejącym znaku sprawy dołącza się, nie tworzy nowej karty
  Cel: fixture z dwoma mailami o tym samym znaku sprawy od tego samego nadawcy; drugi ma się dopiąć do istniejącej sprawy, liczba kart bez zmian + screenshot.
  Weryfikacja: czerwony przed EG, zielony po. Patrz docs/decyzja-grupowanie-maili.md.
- [ ] EG.1 Migracja 002: model wielu maili pod jedną sprawą (np. tabela sprawa_emaile)
  Cel: sprawa może zbierać wiele wątków; koniec zasady 1 mail = 1 sprawa.
  Weryfikacja: migracja aplikuje się, dedup po thread nadal działa.
- [ ] EG.2 Ekstrakcja znaku sprawy (regex) + dopasowanie do istniejącej sprawy przy syncu
  Cel: wyciąga znak/sygnaturę z treści, grupuje maile o tym samym znaku i nadawcy zamiast tworzyć nową sprawę.
  Weryfikacja: test jednostkowy ekstrakcji na przykładach pism + EG.T.
- [ ] EG.3 UX: maile zgrupowane pod jedną sprawą (lista wątków na karcie)
  Cel: karta pokazuje, że sprawa zbiera kilka maili; podgląd każdego.
  Weryfikacja: EG.T zielony.
- [ ] EG.V Kontrola wizualna etapu Grupowanie + decyzja o wektorach
  Cel: użytkownik ocenia, czy dopasowanie deterministyczne wystarcza; jeśli nie — otwieramy temat pgvector (osobna decyzja).
  Weryfikacja: screenshot + akceptacja; ewentualny wpis o wektorach do docs/decyzja-grupowanie-maili.md.
- [ ] E3.T e2e: skan jpg -> OCR -> karta w Nowe z edytowalnym ocr_text
  Cel: upload testowego obrazu; OcrService oddaje znany tekst z tokenem (fixture); asercja: nowa karta, ocr_text widoczny i edytowalny, edycja zapisuje się (round-trip DB) + screenshot.
  Weryfikacja: czerwony przed E3, zielony po.
- [ ] E3.1 OcrService — wrapper OvhOcr (domyślnie medium, fallback Vision)
  Cel: ścieżka obrazu -> tekst; realne wywołanie chmury tylko poza testami (w teście fixture).
  Weryfikacja: test jednostkowy na fixture, bez realnego wywołania chmury.
- [ ] E3.2 POST /api/sprawy/skan (zapis scan_path + ocr_text, sprawa source=skan Nowe)
  Cel: multipart upload, zapis oryginału i tekstu, nowa sprawa w Nowe.
  Weryfikacja: plik zapisany, rekord utworzony (asercja w E3.T).
- [ ] E3.3 GET /api/sprawy/{id}/skan (obraz + tekst, tylko source=skan)
  Cel: podgląd oryginału obok ocr_text.
  Weryfikacja: dla source!=skan zwraca 409/404.
- [ ] E3.4 UX: Dodaj sprawę ze skanu + widok skan obok tekstu + prepare->confirm
  Cel: przycisk wyboru jpg/png (lub aparat), edytowalny ocr_text, nic z OCR nie leci dalej bez potwierdzenia.
  Weryfikacja: E3.T zielony, edycja tekstu zapisana.
- [ ] E3.V Kontrola wizualna etapu Skan/OCR w przeglądarce
  Cel: użytkownik widzi skan obok tekstu i edytuje ocr_text.
  Weryfikacja: screenshot + akceptacja użytkownika.
- [ ] E4.T e2e: drag&drop karty zmienia etap trwale (stan z DB po reload)
  Cel: przeciągnij kartę Nowe->W trakcie, przeładuj stronę, asercja że karta jest w nowej kolumnie i ma nową position (dane z DB, nie z pamięci JS) + screenshot.
  Weryfikacja: czerwony przed E4, zielony po.
- [ ] E4.1 PATCH /api/sprawy/{id}/move (repozycjonowanie w kolumnach)
  Cel: {stage, position} przelicza kolejność w kolumnie docelowej i źródłowej.
  Weryfikacja: test jednostkowy repozycjonowania + E4.T.
- [ ] E4.2 UX: drag&drop -> /move bez przeładowania (SortableJS/HTML5)
  Cel: przeciągnięcie karty od razu woła /move.
  Weryfikacja: E4.T zielony.
- [ ] E4.V Kontrola wizualna etapu Drag&drop w przeglądarce
  Cel: użytkownik przeciąga kartę i widzi trwałą zmianę po odświeżeniu.
  Weryfikacja: screenshot + akceptacja użytkownika.
- [ ] E5.T e2e: zadanie Google Tasks (new|existing), Odłóż/Zrobione, delete
  Cel: fixture Tasks; dodaj zadanie new i existing (asercja utworzone/dopisane); Odłóż i Zrobione zmieniają etap+archived; delete usuwa kartę ale nie źródło + screenshot.
  Weryfikacja: czerwony przed E5, zielony po.
- [ ] E5.1 POST /api/sprawy/{id}/task (new|existing, prepare->confirm)
  Cel: nowe zadanie albo dopisanie maila/skanu jako notatki; nic z niepotwierdzonego OCR bez zgody.
  Weryfikacja: testy obu ścieżek na fixture Tasks.
- [ ] E5.2 PATCH /api/sprawy/{id}/archive + DELETE /api/sprawy/{id}
  Cel: przełącznik Zrobione/widoczne; usunięcie z Kanbana bez ruszania maila/pliku.
  Weryfikacja: rekord znika, scan_path na dysku zostaje.
- [ ] E5.3 POST /api/sprawy/{id}/event — Calendar (opcjonalne)
  Cel: termin/przypomnienie, zapis calendar_event_id.
  Weryfikacja: test tworzenia eventu na fixture.
- [ ] E5.4 UX: akcje na karcie (zadanie/notatka, Odłóż/Zrobione, termin, edycja notes)
  Cel: mini-okno new|existing, przyciski etapów 4/5, opcjonalny termin.
  Weryfikacja: E5.T zielony.
- [ ] E5.V Kontrola wizualna etapu Zadania/Archiwum w przeglądarce
  Cel: użytkownik dodaje zadanie, odkłada, zamyka i usuwa sprawę.
  Weryfikacja: screenshot + akceptacja użytkownika.
- [ ] E6.T smoke e2e przez kanban.virral.mf (po wdrożeniu, przed auth)
  Cel: pełny happy-path na żywej instancji przez tunel z danymi testowymi/fixture.
  Weryfikacja: test przechodzi przeciw https://kanban.virral.mf.
- [ ] E6.1 Osobny docker-compose.yml (nginx + php-fpm, Postgres zewnętrzny)
  Cel: wspólna sieć Dockera, wolumeny na scan_path i logi.
  Weryfikacja: docker compose up -d, /api/health z hosta.
- [ ] E6.2 Dockerfile php-fpm (PHP 8.2+, pdo_pgsql, mbstring, fileinfo, json)
  Cel: composer install --no-dev w buildzie.
  Weryfikacja: obraz buduje się, php -m pokazuje rozszerzenia.
- [ ] E6.3 nginx: SPA + proxy /api/* do php-fpm (origin HTTP, TLS na Cloudflare)
  Cel: serwuje SPA i API, nasłuch wewnętrzny HTTP.
  Weryfikacja: curl http://host:port/ zwraca SPA.
- [ ] E6.4 Cloudflare Tunnel cloudflared -> kanban.virral.mf (bez otwartych portów)
  Cel: tunel wychodzący origin -> Cloudflare, DNS kanban.virral.mf -> tunel.
  Weryfikacja: https://kanban.virral.mf ładuje appkę, cert Cloudflare ważny.
- [ ] E6.5 .env prod + migracje na bazie docelowej + logi/restart policy
  Cel: realne sekrety poza obrazem, bin/migrate na prod, restart: unless-stopped.
  Weryfikacja: tabela sprawy istnieje, po docker restart appka i tunel wstają same.
- [ ] E6.V Kontrola wizualna wdrożenia w przeglądarce
  Cel: użytkownik otwiera kanban.virral.mf i przechodzi happy-path.
  Weryfikacja: screenshot z E6.T + akceptacja użytkownika.
- [ ] E7.T e2e: podłączenie konta Google i status połączenia
  Cel: fixture OAuth; po flow UX pokazuje "połączono jako <email z fixture>", refresh token zapisany w Postgres (asercja przez API/DB, nie mock) + screenshot.
  Weryfikacja: czerwony przed E7, zielony po.
- [ ] E7.1 GoogleClientFactory + OAuth2 offline (refresh token w Postgres)
  Cel: jednorazowa autoryzacja w przeglądarce, token po stronie serwera, nigdy w odpowiedzi do frontendu.
  Weryfikacja: backend odświeża access token bez ponownego logowania.
- [ ] E7.2 UX: przycisk Połącz konto Google + status połączenia
  Cel: widoczny stan (połączono/rozłączono) i email konta.
  Weryfikacja: E7.T zielony.
- [ ] E7.3 Realne podłączenie konta na żywej instancji + prawdziwy sync Gmail/Tasks
  Cel: jednorazowy realny OAuth na kanban.virral.mf, sync z prawdziwej etykiety działa.
  Weryfikacja: prawdziwe maile z etykiety pojawiają się w Nowe na żywej appce.
- [ ] E7.V Kontrola wizualna etapu Auth + przegląd bezpieczeństwa
  Cel: token Google nie w odpowiedzi frontendu, brak sekretów w repo/obrazie, appka tylko za tunelem.
  Weryfikacja: DevTools + git grep + skan obrazu + akceptacja użytkownika.
