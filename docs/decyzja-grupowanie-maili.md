# Decyzja: grupowanie maili w sprawy (a nie mnożenie tasków)

Data: 2026-07-14. Decyzję podjął wykonawca na prośbę użytkownika ("wybierz sam").

## Problem

Kolejne maile w tej samej sprawie urzędowej (np. następne pismo z ZUS w nowym wątku)
tworzą osobne karty na Kanbanie. Chcemy je **grupować pod jedną sprawą**, nie zakładać
nowego zadania za każdym razem.

## Rozważane drogi

1. **Znak sprawy + nadawca (deterministyczne, lokalne).** Pisma urzędowe niosą numer/znak
   sprawy (np. "znak:", "sygnatura:", "nr sprawy:"). Wyciągamy go regexem i grupujemy maile
   o tym samym znaku od tego samego nadawcy. Dokładne, darmowe, zero AI.
2. **Pełnotekstowe (tsvector).** Grupowanie po wspólnych słowach — mniej precyzyjne dla
   urzędówki (odmiana polskich słów, config `simple` bez stemmingu).
3. **Wektory (pgvector + embeddingi).** Grupowanie po znaczeniu. Wymaga wywołania modelu
   embeddingów per mail (koszt + drugi wyjątek od "zero AI poza OCR") i strojenia progu
   podobieństwa (ryzyko sklejenia różnych spraw).

## Decyzja

- **Start: droga 1 (znak sprawy + nadawca).** Zgodne z regułą "nie twórz abstrakcji na
  przyszłość — dopiero przy drugiej potrzebie". Dla pism urzędowych zwykle pewniejsze niż
  semantyka.
- **Wektory: odłożone.** Wracamy do nich tylko, jeśli deterministyczne dopasowanie okaże się
  za słabe (maile bez znaku sprawy, luźny tekst). Wtedy dokładamy `pgvector` bez przebudowy
  reszty — patrz "Jak nie zamknąć drogi do wektorów".
- **Model teraz bez zmian.** E1 (board) nie potrzebuje grupowania. Kolumny/tabele pod
  grupowanie dodajemy migracją `002_*` dopiero w etapie EG.

## Zakres etapu EG (po E2 — synchronizacja Gmaila)

Sprawa przestaje być "1 mail = 1 sprawa" — jedna sprawa może zbierać wiele maili/wątków.
To implikuje zmianę modelu (np. tabela `sprawa_emaile` albo lista `gmail_thread_id` pod
sprawą) — projektujemy przy EG, nie teraz.

## Jak nie zamknąć drogi do wektorów

Trzymamy pełny `ocr_text`/treść maila w bazie (już mamy). Gdyby przyszły wektory: `CREATE
EXTENSION vector`, kolumna `embedding vector(N)` w osobnej migracji, embeddingi liczone przy
synchronizacji. Nic z obecnego modelu nie trzeba wtedy usuwać — to dołożenie, nie przeróbka.
