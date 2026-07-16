# Powtarzalne srodowisko e2e: przegladarki z oficjalnego obrazu Playwright + PHP 8.3
# (serwer testowy aplikacji). Testy montuja sie z repo przy uruchomieniu.
FROM mcr.microsoft.com/playwright:v1.48.0-jammy

# Bez interaktywnych pytan apt (inaczej tzdata blokuje build pytajac o strefe czasowa).
ARG DEBIAN_FRONTEND=noninteractive
ENV TZ=Europe/Warsaw

RUN apt-get update \
    && apt-get install -y --no-install-recommends software-properties-common ca-certificates tzdata \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
