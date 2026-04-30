# AGENTS Instructions

## Hlavní Pravidla

1. Piš kód minimalisticky, čistě a bez zbytečných komentářů.
2. Dodržuj DRY. Když vidíš podobnost, nejdřív hledej existující helper, service nebo lokální vzor.
3. Nepřidávej dokumentaci mimo `AGENTS.md`, pokud si ji uživatel výslovně nevyžádá.
4. Nevytvářej velké monolitické soubory. Novou logiku rozděluj podle existujících vrstev.
5. Žádný legacy kód. Aplikace je ve vývoji, zpětná kompatibilita není priorita.
6. Repo je ve fázi redukce kódu a čištění duplicit. Preferuj menší API, mazání nepoužitého a sjednocení vzorů.
7. Pro lokální konfiguraci a nástroje používej `.env.local`. Hodnoty z něj nezveřejňuj.
8. Nespouštěj vlastní server, pokud o to uživatel výslovně nepožádá.
9. Testovací doména je `http://tinycms.test/`. Preferuj ji místo `localhost`.

## Lokální Nástroje

- Defaultní `php` z PATH může být špatně nakonfigurované a nemusí mít PDO/GD.
- Pro lint/testy používej Laragon PHP z `TINYCMS_PHP_BIN` v `.env.local`.
- Lokální URL ber z `TINYCMS_BASE_URL`.
- MySQL CLI nástroje ber podle potřeby z `TINYCMS_MYSQL_BIN` a `TINYCMS_MYSQLD_BIN`.
- Laragon PHP má potřebné `PDO`, `pdo_mysql`, `gd`, `mysqli`, `curl`, `mbstring`.
- Známý lokální PHP warning na `pdo_firebird` ignoruj, pokud se neřeší PHP konfigurace.

## Rychlá Mapa Repa

- Entry point je `index.php`.
- `autoload.php` definuje konstanty, helpers a autoload pro namespace `App\` do `src/inc/`.
- Bootstrap aplikace je v `src/inc/bootstrap.php`; ručně skládá služby, controllery, view a routy.
- Routy jsou v `src/inc/routes/*.php` a registrují se přes `register_routes()`.
- Admin view jsou v `src/view/admin/`, install view v `src/view/install/`.
- Frontend šablony jsou v `themes/<theme>/`.
- Widgety jsou v `widgets/<name>/widget.php` plus `lang/cs.php` a `lang/en.php`.
- Assets administrace jsou v `src/assets/`, assets šablony v `themes/<theme>/assets/`.

## Request Flow

1. `index.php` načte `autoload.php`.
2. `autoload.php` načte `config.php`, konstanty, `src/inc/functions.php` a autoload.
3. `src/inc/bootstrap.php` zjistí, zda je aplikace nainstalovaná.
4. Bez `config.php` jsou povolené jen install routy a assets.
5. Po instalaci se načtou settings, theme settings, media konfigurace, locale, router a controllery.
6. Router řeší canonical URL a dispatchuje front/admin/API routy.
7. Front 404 jde přes `FrontController::notFound()`.

## Vrstvy

- `Controller\Admin\*`: render admin stránek, guard přes `guardAdmin()`, delegace do `AdminView`.
- `Controller\Api\*`: JSON endpointy, guard přes `guardApiAdmin()` nebo `guardApiAdminCsrf()`.
- `Controller\Front\Front`: veřejný web, detail obsahu, archivy, search, feed, sitemap.
- `Service\Application\*`: business logika a persistence pro entity.
- `Service\Infrastructure\Db\*`: PDO connection, query helper, table prefix, schema validace.
- `Service\Support\*`: malé obecné helpery pro request, i18n, media, CSRF, flash, datumy, escape.
- `View\View`: obecný renderer admin/install views.
- `View\AdminView`: mapuje admin use-casy na konkrétní šablony a společná data.
- `View\FrontView` a `Service\Front\Theme`: izolují render front šablony.

## Databáze

- Používej `Query` pro jednoduché `select`, `insert`, `update`, `delete`, `paginate`.
- Pro raw PDO sáhni jen u joinů, transakcí, `INSERT IGNORE` nebo hromadných operací.
- Názvy tabulek vždy řeš přes `Table::name()` nebo přes `Query`, který prefix přidává sám.
- Schema je v `Service\Application\SchemaDefinition`.
- Délky a allowed hodnoty drž přes `SchemaConstraintValidator`.
- Při přidání sloupce aktualizuj DDL i validační pravidla.

## Admin CRUD Vzor

1. Aplikační service má `paginate()`, `find()`, `save()`, případně `delete()` a `statusCounts()`.
2. Admin controller připraví data pro form/list a volá `AdminView`.
3. API controller řeší autorizaci, CSRF, volání service a jednotný JSON tvar.
4. `AdminView` přidá metodu pro konkrétní šablonu.
5. View v `src/view/admin/<entity>/` používá `$url`, `$csrfField`, `esc_*`, `icon()`.
6. Routy doplň do `src/inc/routes/admin.php`.
7. Překlady doplň do `src/inc/lang/cs.php` i `src/inc/lang/en.php`.

## API A Formuláře

- Úspěch: `apiOk(['message' => ..., 'redirect' => ...])`.
- Chyba: `apiError('CODE', message, status, ['errors' => ...])`.
- Formuláře s `data-api-submit` obsluhuje `src/assets/js/api/forms.js`.
- Listy obsluhují `api/list.js` a `api/list-renderers.js`.
- POST API endpointy chraň přes `guardApiAdminCsrf()`.
- Validace polí vracej pod stejnými názvy, jaké mají inputy.

## Šablony

- Šablony jsou v `themes/<theme>/`.
- Každá šablona může mít manifest `theme.json`.
- Theme metadata a nastavení řeší `Service\Application\Theme`.
- Admin UI pro šablony je na `admin/themes`.
- Theme nastavení se ukládá do existující tabulky `settings`, ale nepatří do globálního settings formuláře.
- Default theme má `show_logo`, `logo`, `favicon`, `enable_widgets`, `footer_text`.
- Vypnutí widgetů nesmí být jen CSS hack: šablona nemá generovat widget markup, když jsou widgety vypnuté.
- Pro šablony používej helpery `site_url()`, `theme_url()`, `get_head()`, `get_menu()`, `get_widget_area()`, `widgets_enabled()`, `include_partial()`.

## Widgety

- Widget je definice v `widgets/<slug>/widget.php`.
- Definice vrací `name`, `fields`, `render`.
- Podporovaná pole navazují na `Widget::normalizeData()`: `text`, `textarea`, `checkbox`, `number`, `select`.
- Render widgetu musí escapovat přes `esc_html`, `esc_attr`, `esc_url`, `esc_content`.
- Widget areas registruj v `themes/<theme>/functions.php`.

## Media A Uploady

- Uploady řeší `Service\Application\Upload`.
- Povolené image typy: jpg/jpeg/png/webp/gif.
- Upload generuje webp a thumbnail varianty.
- Cesty k variantám řeší `Service\Support\Media`.
- `uploads/` je runtime adresář, ne commitovat.

## JavaScript

- Globální namespace je `window.tinycms`.
- `core.js` definuje support helpers, icons a i18n.
- Admin layout načítá skripty podmíněně podle data atributů a obsahu šablony.
- Nové admin interakce spouštěj přes data atributy a malý modul v `src/assets/js/`.
- Sdílené API helpery jsou v `src/assets/js/api/http.js`, flash v `api/flash.js`, formuláře v `api/forms.js`.
- Nepiš inline JS do šablon, pokud nejde jen o krátké předání konfigurace.

## I18n A Escaping

- PHP překlady jsou v `src/inc/lang/cs.php` a `src/inc/lang/en.php`.
- Theme překlady jsou v `themes/<theme>/lang/`.
- Texty ber přes `I18n::t()` nebo helper `t()`.
- JS texty ber z `window.tinycmsI18n` přes `app.i18n`.
- Výstup v šablonách escapuj přes `esc_html`, `esc_attr`, `esc_url`, `esc_content`, `esc_json`.

## Před Dokončením

- Spusť lint změněných PHP souborů přes `TINYCMS_PHP_BIN`.
- U JSON manifestů ověř validní JSON.
- Spusť `git diff --check`.
- Nezveřejňuj hodnoty z `.env.local` ani `config.php`.
- Pokud lokální web kontroluješ ručně, používej `http://tinycms.test/`.
