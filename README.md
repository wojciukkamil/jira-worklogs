# Jira - Worklogs

Aplikacja umożliwiająca wizualizację czasu zalogowanego w Jira. Umożliwaia dodawanię / edycję / usuwanie zalogowanego czasu pracy.

## 1. Technologie
![PHP Version][ico-php-version]
![Symfony Version][ico-symfony-version]

## 2. Uruchomienie aplikacji

### 2.1 Wygenerowanie tokena Jira

Jeżeli nie mamy w Jira wygenerowanego tokena API należy go wygenerować, przechodząc kolejno kroki:

`Zarządzaj ustawieniami konta` => `Bezpieczeństwo` => `Utwórz tokeny API i nimi zarządzaj`

### 2.2 Konfiguracja aplikacji

Aby uruchomić aplikację należy utworzyć plik `.env.local` a następnie spokpiować do niego zawartość  `.env.example`.
Do działania komunikacji z API JIRA wymagane są wartości:
```ini
# JIRA
JIRA_URL=""
JIRA_AUTH_EMAIL=""
JIRA_AUTH_TOKEN=""
```

### 2.3 Uruchomienie kontenera

Nalezy zbudować kontener `docker`:
```bash
docker compose up --build -d
```

### 2.4 Istalacja zależności

Aby zainstalować pakiety wymagane do działania aplikacji należy wewnątrz kontenera `nginx_jira_worklogs` uruchomić:
```bash
composer install
```

### 2.5 Odpaleneie aplikacji
Aplikację należy odpalić w przeglądarce pod adresem:
```
http://localhost:11111/
```

[ico-php-version]: https://img.shields.io/badge/PHP-8.5.6-blue?style=appveyor
[ico-symfony-version]: https://img.shields.io/badge/Symfony-8.0.11-black?style=appveyor