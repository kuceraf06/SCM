/* =====================================================================
   SCM – hlavní skript
   1) tlačítko "nahoru" + schovávání hlavičky při scrollu dolů
   2) popupy s detaily trenérů (jedna funkce pro všechny karty)
   3) formátování telefonního čísla v kontaktním formuláři
   ===================================================================== */

/* ---------- 1) Scroll: tlačítko nahoru + schovávání hlavičky ---------- */

const toTopButton = document.querySelector(".to-top");
const siteHeader = document.querySelector(".header");
window.addEventListener("scroll", () => {
    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

    // tlačítko nahoru se objeví po odscrollování 100 px
    toTopButton.classList.toggle("active", currentScroll > 100);

    // zmenšené logo, dokud nejsme úplně nahoře (efekt má jen mobilní CSS)
    siteHeader.classList.toggle("shrink", currentScroll > 10);
});

// výchozí stav loga i při načtení stránky mimo její vršek (např. kotva)
siteHeader.classList.toggle("shrink", (window.pageYOffset || document.documentElement.scrollTop) > 10);

/* ---------- 2) Popupy trenérů ---------- */

// Otevře/zavře popup č. n (volá se z onclick="togglePopup(n)" na fotce i křížku)
function togglePopup(n) {
    const popup = document.getElementById("popup-" + n);
    popup.classList.toggle("active");
    if (popup.classList.contains("active")) {
        popup.addEventListener("click", closePopupOnClick);
    } else {
        popup.removeEventListener("click", closePopupOnClick);
    }
}

// Klik na ztmavené pozadí (mimo obsah popupu) popup zavře
function closePopupOnClick(event) {
    const popup = event.currentTarget;
    const target = event.target;
    if (!target.closest(".popup-content") && !target.matches(".close-btn")) {
        popup.classList.remove("active");
        popup.removeEventListener("click", closePopupOnClick);
    }
}

// Klik úplně mimo popup (i mimo fotky) zavře všechny otevřené popupy
document.addEventListener("click", function (event) {
    if (!event.target.closest(".popup") && !event.target.matches(".staff-photo img")) {
        document.querySelectorAll(".popup.active").forEach((popup) => {
            popup.classList.remove("active");
        });
    }
});

/* ---------- 3) Číselná pole (telefon): povolí jen platné znaky ---------- */

document.querySelectorAll("[inputmode='numeric']").forEach((input) => {
    input.addEventListener("beforeinput", function () {
        const beforeValue = input.value;
        input.addEventListener(
            "input",
            function () {
                if (input.validity.patternMismatch) {
                    input.value = beforeValue;
                }
            },
            { once: true }
        );
    });
});