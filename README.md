# SCM – web + administrace

Kompletní projekt webu baseballové/softballové akademie SCM Kladno **včetně
vlastní administrace**. Web (PHP MVC, dvojjazyčný CZ/EN) a administrace jsou
v jedné složce a sdílí jednu SQLite databázi – web z ní čte, administrace ji
spravuje.

## Rychlé nasazení (po naklonování z GitHubu)

```bash
# 1) konfigurace (mimo git – obsahuje hash hesla a secret)
cp config/config.example.php config/config.php
php admin/lib/hash_password.php          # vygeneruje hash hesla → vlož do config.php
php -r "echo bin2hex(random_bytes(32));" # vygeneruje app_secret → vlož do config.php
#    do config.php dopiš i admin_username

# 2) naplnění databáze výchozími daty (jednorázově, idempotentní)
php admin/lib/seed.php

# 3) hotovo – web běží, administrace je na /admin/
```

Bez kroku 1–2 se web **nerozbije** – jen ukáže prázdné soupisky a rozpisy
„se připravují". Administrace bez `config.php` srozumitelně upozorní, že
konfigurace chybí.

Podrobnosti o administraci (přihlášení, bezpečnost, co umí) jsou v
[`admin/README.md`](admin/README.md).

## Struktura
```
SCM/
├── .htaccess              # přepis URL na index.php?route=...
├── index.php              # front controller (routing + jazyky + helpery)
├── app/
│   └── views/
│       ├── layout/
│       │   ├── head.php    # původní skeleton/head.php (cesty přes asset(), dynamický <title>)
│       │   ├── header.php  # původní header + navbar sloučené, dvojjazyčné
│       │   └── footer.php  # původní footer + copyright + toTop, dvojjazyčné
│       ├── cs/            # české pohledy (z původních *-cz.php)
│       └── en/            # anglické pohledy
└── public/
    ├── css/               # style.css, responsivity.css
    ├── js/                # main.js
    └── images/            # roztříděné do podsložek podle stránek (viz MAPA-OBRAZKU.txt)
```

## URL schéma
| Stará URL              | Nová CZ URL         | Nová EN URL            |
|------------------------|---------------------|------------------------|
| homepage-cz.php        | /                   | /en                    |
| aboutpage-cz.php       | /about              | /en/about              |
| staffpage-cz.php       | /staff              | /en/staff              |
| playerspage-cz.php     | /players            | /en/players            |
| partnerspage-cz.php    | /partners           | /en/partners           |
| summerpage-cz.php      | /schedule-summer    | /en/schedule-summer    |
| winterpage-cz.php      | /schedule-winter    | /en/schedule-winter    |
| contactpage-cz.php     | /contact            | /en/contact            |
| wintercamp.php         | /wintercamp         | /en/wintercamp         |

Čeština je výchozí jazyk (bez prefixu v URL), angličtina má prefix /en.
Přepínač CZ/EN se generuje automaticky (switchUrl()) a vždy vede na
stejnou stránku v druhém jazyce.

## Helpery (dostupné ve všech pohledech i layoutech)
- url('slug')      -> odkaz v aktuálním jazyce, např. url('contact')
- switchUrl()      -> stejná stránka v druhém jazyce
- asset('cesta')   -> soubor v public/, např. asset('images/SCM.png')

Každý pohled si na začátku nastaví $pageTitle a $bodyClass,
layout je použije v <head> a <body class="...">.

## Co se oproti starému webu opravilo
- Duplicitní <head> a <html> bloky na každé stránce jsou pryč – vše řeší layout.
- navbar.php + navbar-cz.php, footer.php + footer-cz.php, header.php + header-cz.php
  jsou sloučené do jednoho dvojjazyčného souboru (překlady v poli nahoře).
- V českých stránkách vedly odkazy "kontaktujte nás zde" omylem na anglickou
  verzi (contactpage.php) – teď vedou správně přes url('contact').
- Rozbitý odkaz v footer-cz (contactpage.php-cz) opraven.
- Rok v copyrightu se generuje automaticky (date('Y')).

## Obrázky (public/images/)
Roztříděné do podsložek podle použití – přehled je v public/images/MAPA-OBRAZKU.txt.
- common/    logo, favicon, Miners logo, zástupné foto (používá víc stránek)
- home/, about/, staff/, partners/, schedule/   obrázky konkrétních stránek
- _nepouzivane/   soubory, na které nikde nevede odkaz (background.jpg,
  british.png/ico, czech.png) – můžeš smazat, nebo použít později
Všechny cesty v kódu i CSS jsou na novou strukturu přepsané a zkontrolované.

## Zbývá / před nasazením
1. V app/views/cs/contact.php a en/contact.php je proměnná
   $toEmail = "kuceraf@spskladno.cz" – sem chodí zprávy z formuláře,
   změň na ostrou adresu (např. scm@minerskladno.cz).
2. Wintercamp: v style.css nejsou žádné styly pro .forms/.camps-form
   (na starém webu byly zřejmě jinde) – stránka funguje, ale je nenastylovaná.
   Formulář má navíc action="#", tzn. nikam neodesílá (stejně jako v originále).
3. Wintercamp není v navigaci (nebyl ani na starém webu) – je dostupný
   na /wintercamp a /en/wintercamp. Kdyžtak řekni a přidám odkaz do navbaru.
4. Logo v potvrzovacím mailu se nově skládá automaticky z adresy webu
   ($_SERVER['HTTP_HOST']) – už nemíří na starý hosting.


## Refaktoring (červenec 2026)

Kompletní pročištění kódu při zachování stejného vzhledu i funkčnosti.
Ověřeno automaticky: všech 18 stránek vyrenderováno před a po úpravách,
DOM porovnán prvek po prvku, CSS porovnáno vlastnost po vlastnosti.

### Přejmenované třídy (staré -> nové)
| Stránka  | Staré názvy -> nové |
|----------|---------------------|
| úvodka   | main -> hero, buttons -> hero-buttons |
| o nás    | aboutpage -> about-section |
| trenéři  | wrapper -> roster-card, heading-staff -> roster-heading, team-mem -> staff-card, image-staff -> staff-photo, content-staff -> popup-content, role-content -> popup-role, para-content -> popup-text |
| hráči    | player-mem -> player-card, image-player -> player-photo, information -> player-info, invisible -> placeholder-card |
| partneři | partner-wrap -> partners-page, container-partners -> partners-desktop, partner-in -> partners-list, mobile-partners -> partners-mobile, partner-in-mobile -> partners-list-mobile |
| rozpisy  | scheduleMain -> schedule-page, schedule-main -> practice-list, top -> practice-photo, bottom -> practice-text |
| kontakt  | contact-container -> contact-page, left -> contact-map, right -> contact-panel, cz-right -> contact-panel-cs, contactMethod -> contact-methods, method -> contact-method, contactIcon -> contact-icon, sub-heading -> contact-label, para -> contact-value, different -> contact-text-email, different-2 -> contact-text-phone |
| navigace | before -> link-underline, before-half -> link-underline-top, icons -> menu-icons, mobile -> nav-mobile-group, mobile-menu -> nav-mobile-menu, mobile-header (na <p>) -> nav-mobile-title |
| patička  | container -> footer-container, row -> footer-row |
| pomocné  | <br id="width-1..4"> -> třídy partners-break-narrow/wide, roster-break-narrow/wide |

### Smazané zbytečnosti (nikde nestylované / nefunkční)
- třídy: tiny, image, first/second/third/fourth-column, staff, members, mobile-contact
- nepoužitá id formulářových polí (subject, message, button) - name zůstává
- duplicitní id (width-N, invisible, aboutButton) nahrazena třídami / jedním id
- neplatné CSS deklarace, které prohlížeč stejně ignoroval:
  margin-right se čtyřmi hodnotami, filter: '', duplicitní color
- duplicitní CSS pravidla v responsivity.css (footer/social bloky, .para,
  contact-box - vždy pokryto jiným breakpointem se stejnou hodnotou)
- jQuery + maskedinput v hlavičce - v celém webu se nepoužívaly

### JavaScript
- 12 kopií funkce togglePopupN() -> jedna togglePopup(n)
- dva scroll listenery (tlačítko nahoru + schovávání hlavičky) sloučeny
- skript dropdownů přepsán do smyčky (stejné chování, třetina kódu)

### Opravené chyby v HTML (jediné vědomé odchylky vzhledu)
1. staff: u karty Filipa Kučery byl zapomenutý tag </p> navíc, který
   vytvářel neviditelný odstavec a dělal kartu o ~7 px vyšší než ostatní.
   Smazán -> karty jsou nyní zarovnané stejně.
2. players: zástupné karty na konci 4. sloupce měly rozbité tagy
   (<h4>&nbsp;</p>). Přepsány na validní zápis se stejnou výškou karty,
   takže svislé zarovnání sloupců zůstalo beze změny.
3. about: prohozené uzavírací tagy </div></section> - opraveno pořadí,
   výsledný DOM je prokazatelně totožný (nulový rozdíl).


## Rozdělení CSS (červenec 2026)

CSS je rozdělené po vzoru baseballWebu - každá stránka má svůj soubor:

| Soubor          | Co obsahuje                                              |
|-----------------|----------------------------------------------------------|
| style.css       | reset, hlavička, navbar (desktop i mobil), patička, tlačítko nahoru, úvodní stránka (hero) + responzivní úpravy layoutu |
| pageshared.css  | sdílené kusy podstránek: nadpis s podtržením (.heading) a bílá karta (.roster-card) pro trenéry/hráče |
| about.css       | stránka O nás                                            |
| staff.css       | Trenéři (karty + popupy)                                 |
| players.css     | Hráči (sloupce, karty, zástupné karty)                   |
| partners.css    | Partneři (dlaždice + mobilní seznam)                     |
| schedule.css    | Letní i zimní rozpis (sdílejí styly)                     |
| contact.css     | Kontakt (mapa, formulář, hlášky, kontaktní údaje)        |
| wintercamp.css  | zatím prázdný - připravený pro budoucí styly formuláře   |
| 404.css         | stránka nenalezeno                                       |

Napojení: každý pohled si nastaví $pageCss (např. 'about') a head.php
k základnímu style.css přilinkuje pageshared.css + CSS dané stránky.
Úvodní stránka načítá jen style.css. Responzivní pravidla každé stránky
jsou na konci jejího souboru. Soubor responsivity.css tím zanikl.

404 stránka dostala nový text a zelené tlačítko "Zpět na úvodní stránku"
ve stylu webu (stejný vzhled jako tlačítko Více na O nás).

Ověřeno automaticky: součet pravidel všech nových souborů se přesně
rovná původním dvěma souborům (251 pravidel, jediná přidaná věc jsou
styly 404) a každá stránka má načtená všechna pravidla, která na ní
mohou platit.


## Nový design podle SAFE webu (červenec 2026)

Web převlečen do designu sesterského SAFE webu (safe.minerskladno.cz),
akorát se zelenou #93C11F místo zlaté:

- Fonty: Titan One (nadpisy, navigace) + Inter (běžný text), jako SAFE.
- Nový topheader: úzká černá lišta úplně nahoře s názvem klubu a ikonami
  sociálních sítí. Pokud chceš obrázkové ikonky přesně jako na SAFE
  (české loga baseballu/softballu atd.), nakopíruj jejich PNG do
  public/images/common/ a vyměň <i> ikony v layout/header.php za <img> -
  CSS na to je připravené.
- Hlavička: černý pruh, logo vlevo, navigace Titan One, hover zeleně,
  CZ/EN zeleně vpravo.
- Patička: bílá, 4 sloupce (logo, Další weby, Sledujte nás, Kontaktujte
  nás) + černý copyright pruh - struktura 1:1 podle SAFE.
- Tlačítko nahoru: zelené pozadí, černá šipka.
- Stránky: světlé pozadí #F8F4F4, bílé karty se stínem, zelené akcenty
  a tlačítka (hover tmavší zelená #7AA01A).
- Kontakt: přibyl nadpis "KONTAKTUJTE NÁS" jako na SAFE, formulář má
  zelené tóny při najetí/focusu a opravu barvy při autofillu.
- Breakpointy layoutu převzaté ze SAFE (mobilní menu při 991 px atd.),
  breakpointy specifické pro stránky SCM (hráči, trenéři) zůstaly.


## Mobilní menu ve stylu Miners/SAFE (červenec 2026)

Celoobrazovkové mobilní menu (černé pozadí, bílé texty jako desktop navbar):
- hamburger je na desktopu skrytý (selektor i.menu-icons kvůli pořadí načítání boxicons)
- hlavní úroveň + tři podmenu (O nás, Soupiska, Rozvrh), přepínání je ostré bez animace
- šipka zpět (bx-arrow-back) vrací na hlavní úroveň, křížek zavírá celé menu
- položky jsou zarovnané nahoru, menu se nescrolluje (scrolluje stránka pod ním)

Pozn.: topheader lišta zatím používá Font Awesome ikony. Miners používá
obrázková loga (softballczech.png, baseballczech.png, flickr.png, FB.png,
IG.png) - až je nakopíruješ do public/images/common/, stačí v
app/views/layout/header.php vyměnit <i> ikony za <img> (návod je tam v komentáři).


## Úpravy stránek trenéři/hráči (červenec 2026)

- Nadpisy (TRENÉŘI, HRÁČI) jsou nově MIMO bílý box, stejně jako na
  ostatních stránkách (třída .heading se stejným odsazením) - box
  .roster-card teď obsahuje jen samotné karty. Struktura je
  <main class="roster-page"> -> .heading + .roster-card.
- Bílá karta je širší (90 %, max 1400px) a vycentrovaná, takže obsah
  vyplní i dřívější prázdné kraje. Trenérské karty mají širší vodorovné
  rozestupy, sloupce hráčů se rozprostřou přes celou šířku.
- Karty hráčů se zarovnávají k vršku (align-items: flex-start), takže
  jméno/kategorie/pozice jdou hezky pod sebou i u hráčů s více řádky.
- Odstraněn tím i vizuální problém, kdy bílý box splýval s patičkou.
- Topheader lišta používá obrázková loga (softballczech, baseballczech,
  flickr, FB, IG) - PNG jsou v public/images/common/.
- Uklizeny návodné komentáře (výměna ikon, wintercamp).


## Přepracování mřížky hráčů a trenérů (červenec 2026)

Stránky hráči i trenéři přešly ze systému pevných sloupců na flexovou
mřížku, která se sama zalamuje:
- HRÁČI: karty pevné šířky (250px), 4 na řádek, zarovnané k vršku, takže
  jméno/kategorie/pozice jsou vždy hezky pod sebou i u dlouhých jmen na
  dva řádky. Sloupce (.players-column) zůstaly v HTML jako logické
  seskupení kategorií, ale přes display:contents se "rozpustí" do mřížky.
  Placeholder karty už nejsou potřeba (mřížka se zalamuje sama) - skryté.
- TRENÉŘI: karty pevné šířky, 4 na řádek (fotka nahoře, pod ní jméno a
  role). Odstraněn <center> obal i ruční zalamovací <br>. Na užších
  displejích 3, pak 2 na řádek.
- Opraven "rozbitý" footer: hlavní obsah (main) dostal flex:1, takže
  vždy vyplní volný prostor a patička už nikdy nevisí s mezerou nad sebou
  u krátkých stránek.

Ověřeno vizuálně (headless prohlížeč) na desktopu i mobilu.


## Oprava footeru a EN hráčů (červenec 2026)

- Footer: příčinou prázdna pod boxem byla DVOJITÁ spodní mezera -
  .roster-page i .roster-card měly obě margin-bottom 100px (dohromady
  200px prázdna). Sjednoceno: box bez spodního marginu, .roster-page
  má 80px. Ověřeno v prohlížeči - footer teď sedí hned pod obsahem.
- EN stránka hráčů nebyla v minulém kroku přestavěná (měla jiný nadpis
  ROSTER, tak ji .replace() minul) - zůstala jí stará struktura s
  nadpisem uvnitř boxu, což způsobovalo posun. Přestavěna stejně jako
  česká: nadpis venku (.heading) + .roster-card obal.
- Trenéři: přidán breakpoint - na displejích do 460px 1 karta na řádek
  (stejně jako se to už chová u hráčů), aby se text nemačkal.


## Footer přes celou šířku u hráčů/trenérů (červenec 2026)

Footer na stránkách hráči a trenéři nešel přes celou šířku a byl
oříznutý. Příčina: .staff-body a .players-body měly na flex kontejneru
(body) align-items: center a justify-content: center - to smrštilo
VŠECHNY potomky včetně footeru na šířku obsahu místo plné šířky.
Tyto vlastnosti tam zbyly z dřívějška pro centrování bílé karty, ale tu
teď centruje margin: 0 auto přímo v .roster-card, takže byly zbytečné.
Odstraněny -> footer je zase přes celou stránku jako všude jinde.
Ověřeno v prohlížeči (desktop i mobil, CS i EN): footer 100 % šířky,
box zůstal vycentrovaný, mřížka nedotčená.


## Trenéři: interní a externí (červenec 2026)

Stránka trenérů rozdělena na dvě sekce: "SCM – Trenéři" (9 interních) a
"SCM – Externí trenéři" (4 externí), každá s vlastním nadpisem se zeleným
podtržením. Seznam aktualizován podle podkladů:
- odebráni: David Jaklin, Nela Janáčková, Jitka Kopernická, Zuzka Vosátková
- přidáni: Radka Jakubová (interní) + Tomáš Duffek, Tomáš Ondra,
  Michala Kuchařová, Michal Borek (externí)
- role aktualizovány (Ředitel/Vedoucí, Trenér SCM, Trenér, Asistent,
  Trenér nadhozu baseball/softball, Trenér plavání)

Rozklikávací popup zachován a rozšířen: kromě fotky, jména a role teď
ukazuje i trenérskou licenci (pokud ji trenér má - jinak se řádek
nezobrazí) a klikací kontakt (telefon tel: + e-mail mailto:, zeleně).
Externí trenéři nemají licenci ani kontakt, takže jejich popup ukazuje
jen jméno a roli.

Implementace: trenéři jsou v PHP polích ($internalCoaches, $externalCoaches)
a vykreslují se smyčkou přes funkci renderCoach() - přidání/odebrání
trenéra je teď jen úprava pole. Prázdná licence/kontakt se automaticky
vynechá. Fotky, které nemáme (Karel Kuklík, Radka Jakubová, Vojtěch
Kuklík, Martin Kučera, všichni externí), používají zástupný obrázek -
až je dodáš do public/images/staff/, stačí upravit 'photo' v poli.


## Trenéři: vodorovné karty místo popupů (červenec 2026)

Design trenérů předělán podle vzoru minerskladno.cz - popupy úplně
odstraněny, místo nich vodorovné karty: fotka vlevo, jméno + role +
licence uprostřed, kontakt (telefon + e-mail, klikací, zeleně) vpravo.
Vše je hned vidět, nikam se neklika. Na fotku je jen jemný hover zoom
(scale 1.12) jako vizuální prvek. Rozdělení na interní/externí sekce
i barvy (#93C11F) zůstaly. Data trenérů dál v PHP polích + smyčka.
Responzivně: na užších displejích kontakt spadne pod obsah, na malých
mobilech je celá karta pod sebe na střed.


## Trenéři: bez boxu, barevné nadpisy sekcí (červenec 2026)

Design trenérů doladěn přesně podle minerskladno.cz:
- odstraněn velký bílý box (roster-card) - karty teď stojí volně na
  světlém pozadí, každá je vlastní bílá kartička se stínem
- nadpisy sekcí zarovnané vlevo v barevném zaobleném pruhu: interní
  trenéři tmavě šedý (#3a3a3a), externí černý (#000)
- karty zmenšeny (fotka 64px, nižší padding), aby zabíraly stejně
  místa jako na minerskladno.cz
Šířku obsahu (dřív ji držel bílý box) teď drží .roster-page.
Stránka hráčů bílý box (roster-card) používá dál beze změny.


## Trenéři: tablet layout - fotka vpravo (červenec 2026)

Doladěna střední (tabletová) responzivita karet trenérů podle
minerskladno.cz. Tři stavy:
- nad 850px: fotka vlevo, jméno/role/licence uprostřed, kontakt vpravo
- 461-850px (tablet): fotka VPRAVO, všechny texty (jméno, role, licence,
  telefon, e-mail) vlevo pod sebou - řešeno přes flex order + margin
- do 460px (mobil): vše pod sebe na střed, fotka nahoře


## Hráči: kategorie + karty jako trenéři (červenec 2026)

Stránka hráčů předělána na stejný design jako trenéři (vodorovné karty),
navíc rozdělená podle kategorií od nejmladší po nejstarší (U11, U11s, U13,
U13s, U15, U16s, U18, ŽENY):
- nahoře odkazové boxy (barevné, cyklují 4 barvy jako na minerskladno.cz),
  klik na box skočí na danou kategorii (kotva #cat-xxx). scroll-margin-top
  130px zajistí, že nadpis nezůstane schovaný pod fixní hlavičkou.
- každá kategorie má tmavě šedý nadpis-pruh (jako sekce u trenérů) a pod
  ním karty hráčů
- karta je identická s kartou trenéra (fotka + jméno vlevo), ale vpravo
  místo kontaktu je Kategorie + Pozice - černě a bez podtržení, protože
  to nejsou odkazy
- odstraněn starý velký bílý box i mřížka kulatých karet
Data hráčů jsou v PHP poli seskupená podle kategorie (přidání hráče =
úprava pole). Responzivita stejná jako u trenérů (tablet: fotka vpravo,
mobil: vše pod sebe).

Aktualizován staff.css na verzi dodanou uživatelem.


## Hráči: barvy podle pohlaví + nové kategorie (červenec 2026)

- Barvy kategorií (box nahoře i pruh nadpisu) podle pohlaví: kluci černá,
  holky zelená. V datech to řídí klíč 'type' => 'boys'/'girls' u každé
  kategorie (holky = kategorie končící na "s" nebo ženy).
- Přidány dvě nové kategorie: U18s a MUŽI (zatím bez hráčů - zobrazí se
  pruh nadpisu + text "Zatím žádní hráči."). Pořadí od nejmladší:
  U11, U11s, U13, U13s, U15, U16s, U18, U18s, MUŽI, ŽENY.
- Na desktopu a notebooku jsou teď všechny kategorie na jednom řádku
  (category-nav: nowrap, boxy flex:1). Na tabletu a mobilu se zalomí
  pod sebe (wrap) jako předtím.

Aktualizován players.css na verzi dodanou uživatelem (menší písmo meta).


## Hráči: box jen s pozicí + nový About (červenec 2026)

- Hráči: z pravé strany karty smazána kategorie (je zbytečná, hráči už
  jsou rozdělení podle kategorií nadpisy). V boxu zůstala jen pozice.
- About: text nahrazen novým zněním (SCM založeno 1.11.2018, přidružené
  kluby Piranhas Beroun a Red Crayfish Rakovník, cíle SCM...) rozděleným
  do tří odstavců. Layout předělán podle minerskladno.cz: text v
  odstavcích nahoře, pod ním široká fotka týmu (scm-team.jpg) přes celou
  šířku bílé karty (dřív byly fotka a text vedle sebe). EN verze
  přeložena. Nová fotka je v public/images/about/scm-team.jpg.


## Jednotná šířka stránek + ročníky + About podnadpis (červenec 2026)

1. Všechny podstránky sjednoceny na šířku 75 % (vycentrováno) na desktopu.
   Jakmile se obrazovka zmenší pod 1200px, obsah se roztáhne na 92 % (skoro
   plná šířka, malé symetrické odsazení) - jako minerskladno.cz. Řeší to
   sdílená třída .page-width v pageshared.css, přidaná na <main> každé
   stránky. Z jednotlivých CSS odstraněny konfliktní šířky (roster-page 80%,
   about-container 90%, contact-page/partners-page 100%). Homepage hero
   zůstává full-bleed (přes celou šířku, má obrázek na pozadí).
2. Homepage: rozsah ročníků rozšířen do věku 23 (yearMin = rok - 23 místo
   -21), takže teď ukazuje 2003-2016 (věk 10-23).
3. About: podnadpis pod hlavním nadpisem změněn na nový text (CS i EN)
   o SCM Miners Kladno jako centru pro baseballisty/softballisty 10-23 let.


## About bez br + rozpisy vedle sebe (červenec 2026)

- About: z podnadpisu odstraněn <br>, text je teď na jednom řádku (CS i EN).
- Rozpisy (léto/zima): po zúžení stránek na 75 % se boxy tréninků lámaly
  pod sebe moc brzy. Opraveno - bloky jsou teď pružné (flex: 1 1 420px),
  obrázek se zmenší s blokem (width 100 % místo fixních 500px), takže dva
  boxy sedí vedle sebe na desktopu i notebooku jako před změnou šířky.
  Pod 1000px se skládají pod sebe. Mezera mezi boxy zmenšena ze 100px na
  40px (30px na malých mobilech) - řeší ji gap na .practice-list.


## Header vždy viditelný + fakturační údaje (červenec 2026)

- Zrušeno vysouvání/zasouvání hlavičky na mobilu při scrollu - hlavička
  je teď vždy viditelná. Odstraněna .hide logika z main.js (a nepotřebná
  proměnná headerHeight) i pravidlo .header.hide z style.css. Zmenšování
  loga při scrollu (.shrink) zůstalo beze změny.
- Kontakt: pod kontaktní metody přidána elegantní karta "Adresa a
  fakturační údaje" (bílá karta se stínem, nadpis se zeleným podtržením,
  údaje ve dvou sloupcích se zeleným proužkem u každé položky):
  SPORTOVNÍ CENTRUM MINERS z.s., U Trati 3489 272 01 Kladno, IČO 08286175,
  účet 2901669835/2010. EN verze přeložena. Na mobilu údaje pod sebou.

Aktualizovány schedule.css, footer.php a rozpisy (summer/winter) na verze
dodané uživatelem.


## Kontakt: první blok jen email + telefon (červenec 2026)

Z prvního kontaktního bloku (contact-methods) odstraněna adresa - zůstal
jen email a telefon. Adresa je teď jen ve fakturační kartě níže (kde se
neopakuje zbytečně). Blok zůstal stejně velký, email a telefon se roztáhly
přes celý blok a mají větší písmo (label 22px, hodnota 18px). Zrušeno
dřívější zmenšování písma v media queries (bylo kvůli 3 metodám v řadě,
teď jsou 2 a mají dost místa). EN verze taky.

Aktualizovány české rozpisy (summer/winter) na verze dodané uživatelem.


## Rozpisy přepracovány + wintercamp smazán (červenec 2026)

- Rozpisy aktualizovány podle nových tréninků (CS i EN):
  ZIMA (3 tréninky): PÁ hala Brjanská, PÁ posilovna BIOS (U15+),
  NE atletická hala Sletiště + plavecký bazén Aquapark Kladno.
  LÉTO (2 tréninky): PÁ hřiště Rozdělov + nový trénink v posilovně BIOS
  s popisem (budování kondice/síly/rychlosti, prevence zranění, plány
  na míru) a fotkou bios-posilovna.jpg.
- Bloky titulkovány podle místa (dřív podle dne - nešlo, dva tréninky
  jsou v pátek). Layout doladěn: flex-basis 300px, takže 3 zimní bloky
  se vejdou do jedné řady na desktopu, na 900px a míň se skládají pod
  sebe. Nová fotka posilovny (.jfif -> .jpg) v images/schedule/.
- Smazána stránka wintercamp (pohledy cs+en, wintercamp.css, routa
  v index.php). /wintercamp teď vrací 404. V menu na ni nikde nebyl odkaz.

Aktualizovány anglické contact/summer/winter na verze dodané uživatelem
(summer/winter pak přepsány novými tréninky).


## Rozpisy: karty vždy stejně velké (červenec 2026)

- Uložen aktualizovaný obrázek posilovny (bios-posilovna.jpg).
- Z letního rozpisu odstraněna posilovna BIOS - zůstal jen jeden páteční
  trénink (hřiště Rozdělov). Léto má teď 1 kartu, zima 3 karty.
- Layout karet přepsán na CSS grid, aby byly VŽDY stejně široké i vysoké:
  - 2 sloupce pevné šířky (grid-template-columns) => stejná šířka
  - grid-auto-rows: 1fr => všechny řádky/karty stejně vysoké
  - lichá poslední karta (samotná v řádku) se roztáhne přes oba sloupce
    a vycentruje, ale s šířkou přesně jednoho sloupce (calc), takže je
    stejně široká jako ostatní - opraven problém, kdy spodní karta byla
    širší než dvě nad ní.
  - obrázek pevné výšky (object-fit: cover, 220px), text vyplní zbytek
    karty (flex:1), takže spodky karet lícují.
  Ověřeno měřením: na všech šířkách (1700-950px) mají všechny karty
  identickou šířku i výšku. Pod 900px jeden sloupec (karty pod sebe).


## Aktualizace anglických rozpisů (červenec 2026)

Uloženy anglické verze rozpisů (en/winter.php, en/summer.php) dodané
uživatelem jako aktuální báze pro budoucí změny. Žádné jiné úpravy.
Pozn.: uživatel zamýšlel nahrát i český winter, ale dva soubory se
stejným názvem winter.php se v uploadu přepsaly (zůstala jen anglická
verze), takže český winter zůstal beze změny z předchozí verze.


## Kontakt: bloky vždy pod sebou (červenec 2026)

Kontaktní stránka teď má tři bloky VŽDY pod sebou na všech velikostech:
formulář+mapa (contact-box) -> kontaktní info (contact-methods) ->
fakturační údaje (billing-info). Dřív se chovaly divně, protože
.contact-page byl flex s flex-wrap: wrap a justify-content: space-evenly
(bloky se snažily být vedle sebe a lámaly se nepředvídatelně). Změněno na
flex-direction: column + align-items: center + gap. Všechny tři bloky mají
sjednocenou šířku (100% / max 930px), takže jsou stejně široké a plynule se
zmenšují na menších displejích (odstraněna napevno nastavená width: 930px
z media query, která způsobovala přetékání). EN i CS.

Aktualizovány style.css a contact.css na verze dodané uživatelem.


## Kontakt: info vedle formuláře (červenec 2026)

Kontaktní stránka přeuspořádána podle vzoru SAFE:
- formulář (mapa + formulář) a kontaktní info (email + telefon) jsou teď
  VEDLE SEBE na desktopu; kontaktní info je úzký box napravo s položkami
  pod sebou (ikona + label + hodnota), vertikálně vycentrovaný vedle formuláře
- do HTML přidán wrapper .contact-top (flex row s wrap) obalující formulář
  a info; contact-page je sloupec (contact-top nahoře, fakturace dole)
- pod 950px se info zalomí pod formulář (celá šířka), aby se to nekřížilo
  s jednosloupcovým formulářem (872px)
- fakturační box je VŽDY dole, vycentrovaný na střed, na všech velikostech
Vyčištěny konfliktní media queries (staré vodorovné/grid uspořádání metod).
Ověřeno měřením: vedle sebe 1440-1000px, pod sebe od 900px, fakturace vždy
dole na střed (CS i EN).


## Kontakt: text v info boxu vycentrovaný (červenec 2026)

Text uvnitř kontaktního boxu (email/telefon) byl rozhozený - opraveno.
Ikony zůstávají vlevo (position: absolute), nadpisy i hodnoty jsou teď
vycentrované na střed boxu (contact-text: width 100% + text-align center,
contact-method: position relative). Hlavní příčina rozhození: zbytková
třída .contact-text-phone měla padding-left: 85px (a 80px v media query)
ze starého vodorovného layoutu, která telefon posouvala doprava -
zrušeno. Také sjednocena struktura telefonu s emailem (<p class=contact-value>
<a>). Ověřeno měřením v CS i EN na všech velikostech: nadpisy i hodnoty
vycentrované, ikony vlevo.


## Uložen upravený contact.css (červenec 2026)

Uložena verze contact.css dodaná uživatelem jako aktuální báze pro budoucí změny. Žádné jiné úpravy.


## ADMINISTRACE (červenec 2026)

Přidána kompletní bezpečná administrace v /admin/ pro správu soupisky hráčů,
trenérů a rozpisů. Data jsou v SQLite databázi (storage/db/, mimo public/) a
veřejné pohledy (players, staff, summer, winter) je z ní čtou přes app/lib/data.php.

Design vychází ze staré SAFE administrace (sidebar, Poppins, boxicons), ale
v barvách SCM (zelená #93C11F). Přihlášení: admin / MinersAdmin2026! (testovací).

Splněny obě povinné podmínky: (1) design jako SAFE s barvami SCM, (2) SQLite DB.
Navíc opraveny všechny bezpečnostní chyby SAFE - viz admin/README.md:
- DB mimo git i web root (config db_path), v .gitignore -> deploy ji nepřepíše
- žádný cookie bypass - přihlášení v session + HMAC-podepsaný remember token
- heslo jako hash v config.php mimo git (ne plaintext v kódu)
- bezpečný upload (getimagesize, náhodné názvy, .htaccess blokuje PHP) -> žádné RCE
- CSRF tokeny ve všech formulářích, prepared statements, chyby jen do logu

Rozpisy: den/čas/místo se zadávají česky a překládají se automaticky do AJ
(slovník v admin/lib/translate.php), popis tréninku má zvlášť pole CZ a EN.

Migrace stávajících dat: php admin/lib/seed.php (idempotentní).
Detaily nasazení: admin/README.md.
