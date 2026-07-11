# Administrace SCM

Bezpečná administrace pro správu soupisky hráčů, trenérů a rozpisů tréninků.
Design vychází ze staré administrace SAFE (sidebar, Poppins font, boxicons),
ale v barvách SCM (zelená `#93C11F`). Data jsou v SQLite databázi a veřejný
web je z ní čte.

## Přihlašovací údaje (testovací)

- **URL:** `/admin/login/`
- **Jméno:** `admin`
- **Heslo:** `MinersAdmin2026!`

Heslo se ukládá jen jako hash (`password_hash`), nikdy v čitelné podobě.

### Nastavení / změna hesla bez příkazové řádky

Když nemáš přístup k terminálu, otevři v prohlížeči **`/admin/setup/`**:
- Při **prvním** nasazení (prázdný hash v configu) zadáš jméno a heslo a skript
  je bezpečně zapíše do `config.php` (vygeneruje i `app_secret`).
- Když už heslo nastavené je, stránka funguje jako **změna hesla** a vyžaduje
  zadání stávajícího hesla (aby ho přes ni nemohl přepsat nikdo cizí).

**Po nastavení hesla složku `admin/setup/` ze serveru smaž** (nebo ponech
přiložený `admin/setup/.htaccess`, který ji blokuje). Heslo pak jde kdykoliv
změnit i uvnitř administrace v sekci *Změna hesla*.

Alternativně (s terminálem) funguje i `php admin/lib/hash_password.php`.

## Nasazení na server (jak to funguje)

Projekt je nastavený tak, aby stačilo **stáhnout z gitu a nahrát na server** -
nic víc není potřeba spouštět.

1. **Kolega stáhne repozitář z gitu a nahraje soubory na server.** Hotovo.

2. **Databáze se vytvoří sama** při prvním otevření stránky, která ji potřebuje
   (Hráči, Trenéři, Rozpisy nebo přihlášení do administrace). SQLite databáze
   je jen soubor - PHP ho automaticky vytvoří v `storage/db/scm.sqlite` a založí
   tabulky. Není potřeba žádný příkaz z terminálu.
   Složka `storage/db/` je v gitu udržená přes `.gitkeep`, aby po stažení
   existovala. Databáze leží mimo web root (`public/`) a je chráněná `.htaccess`,
   takže ji nejde stáhnout z webu.

3. **Přihlašovací údaje jsou v `config/config.php`**, který je součástí gitu
   (viz `.gitignore`). Kolega tedy nic nenastavuje. Heslo lze kdykoliv změnit
   přímo v administraci (sekce *Změna hesla*).

### Aktualizace projektu (bez ztráty dat)

Když do gitu pošleš novou verzi kódu a kolega ji přetáhne:
- **Databáze se NEPŘEPÍŠE**, protože v gitu není (je v `.gitignore`). Přetažení
  z gitu se dotkne jen kódu, ne živé databáze na serveru.
- Živá data (změny, které klub udělal přes administraci) tak zůstanou zachovaná.
  To byl hlavní problém staré administrace - tady je vyřešený.

### Volitelně: databáze úplně mimo git checkout

Pokud bys chtěl databázi na produkci umístit mimo složku projektu (ještě
bezpečnější), nastav v `config/config.php` `db_path` na absolutní cestu, např.
`/var/lib/scm/scm.sqlite`. Když je `db_path` prázdné (výchozí), použije se
`storage/db/scm.sqlite` uvnitř projektu - taky funkční a mimo web root.

### Poznámka k bezpečnosti config.php

`config.php` je v gitu kvůli pohodlí (kolega nic nenastavuje). Obsahuje hash
hesla (ne čitelné heslo) a `app_secret`. Kdokoli s přístupem k repozitáři je
uvidí. Pro klubový web s jedním správcem je to přijatelný kompromis; pokud bys
config chtěl z gitu vyřadit, odkomentuj řádek `/config/config.php` v `.gitignore`
a heslo nastav na serveru přes `php admin/lib/hash_password.php`.

## Co administrace umí

- **Hráči** - přidání, úprava, odebrání. Pole: jméno, kategorie (U11-ŽENY),
  pozice, fotka. Zobrazují se seskupení podle kategorií.
- **Trenéři** - stejný princip. Pole: jméno, role (CZ/EN), licence (CZ/EN),
  telefon, e-mail, fotka, zařazení (interní/externí).
- **Rozpisy (léto/zima)** - přidání, úprava, odebrání tréninků. Den, čas a
  místo se zadávají **jen česky** a do angličtiny se **překládají automaticky**
  (slovník). Popis tréninku má **dvě samostatná pole** - české a anglické.

## Bezpečnost (co bylo opraveno oproti SAFE)

| Problém v SAFE | Řešení v SCM |
|---|---|
| Databáze verzovaná v gitu, deploy ji přepsal | DB mimo git i web root (`db_path`), v `.gitignore` |
| Auth bypass přes cookie `rememberMe=true` | Přihlášení jen v session; "zůstat přihlášen" přes HMAC-podepsaný token |
| Heslo natvrdo v kódu (`Admin25`) | Hash v `config.php` mimo git |
| Upload bez kontroly → RCE (`x.php`) | Ověření přes `getimagesize`, jen obrázky, náhodný název, `.htaccess` blokuje PHP v uploads |
| `.sqlite` stažitelná z webu | DB mimo `public/`, `.htaccess` blokuje přístup |
| Žádné CSRF | CSRF token ve všech formulářích |
| Kolize jmen souborů | Náhodné názvy uploadů |
| Chybové hlášky z DB na stránku | Chyby jen do logu, uživateli obecná hláška |
| EN duplikát tabulek, copy-paste | Jedna datová vrstva, sdílený layout, jazyk řeší překlad |

## Struktura

```
config/
  config.example.php   # šablona (v gitu)
  config.php           # skutečná konfigurace (NENÍ v gitu)
storage/db/            # databáze (NENÍ v gitu), mimo public/
admin/
  lib/                 # jádro: bootstrap, db, auth, helpers, translate, layout
  assets/admin.css     # styly administrace
  login/ logout/       # přihlášení
  players/ staff/      # správa soupisky
  schedule/            # správa rozpisů
  account/             # změna hesla
app/lib/data.php       # čtení dat pro veřejný web
public/images/uploads/ # nahrané fotky (NENÍ v gitu)
```
