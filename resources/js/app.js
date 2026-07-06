import "./bootstrap";

const toggleButton = document.querySelector("[data-nav-toggle]");
const navigationMenu = document.querySelector("[data-nav-menu]");

if (toggleButton && navigationMenu) {
    toggleButton.addEventListener("click", () => {
        navigationMenu.classList.toggle("site-nav--open");
    });
}
