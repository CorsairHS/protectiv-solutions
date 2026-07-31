/* ═══════════════════════════════════════════════════════════════
   PROTECTIV SOLUTIONS – MAIN SCRIPT
   Obsługuje: menu mobilne, akordeon produktów, lightbox galerii,
              wysyłkę formularza kontaktowego (mailto)
═══════════════════════════════════════════════════════════════ */

function setupMobileMenu() {
  const hamburger = document.getElementById("navbarHamburger");
  const links = document.getElementById("navbarLinks");
  if (!hamburger || !links) return;

  hamburger.addEventListener("click", function () {
    const isOpen = links.classList.toggle("is-open");
    hamburger.classList.toggle("is-open", isOpen);
    hamburger.setAttribute("aria-expanded", String(isOpen));
  });

  links.querySelectorAll("a").forEach(function (link) {
    link.addEventListener("click", function () {
      links.classList.remove("is-open");
      hamburger.classList.remove("is-open");
    });
  });
}

function setupProductAccordion() {
  const groups = document.querySelectorAll(".product-group");
  groups.forEach(function (group) {
    const toggle = group.querySelector(".product-group-toggle");
    if (!toggle) return;
    toggle.addEventListener("click", function () {
      group.classList.toggle("is-open");
    });
  });
}

function setupCategoryNavScrollSpy() {
  const links = document.querySelectorAll(".category-nav-link");
  const blocks = document.querySelectorAll(".category-block");
  if (!links.length || !blocks.length) return;

  const observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        links.forEach(function (link) {
          link.classList.toggle("is-active", link.getAttribute("href") === "#" + entry.target.id);
        });
      });
    },
    { rootMargin: "-40% 0px -50% 0px" }
  );

  blocks.forEach(function (block) { observer.observe(block); });
}

function setupGalleryLightbox() {
  const items = document.querySelectorAll("[data-lightbox-src]");
  const lightbox = document.getElementById("galleryLightbox");
  if (!items.length || !lightbox) return;

  const lightboxImg = lightbox.querySelector("img");
  const closeBtn = lightbox.querySelector(".gallery-lightbox-close");

  function open(src, alt) {
    lightboxImg.src = src;
    lightboxImg.alt = alt || "";
    lightbox.classList.add("is-open");
  }
  function close() {
    lightbox.classList.remove("is-open");
    lightboxImg.src = "";
  }

  items.forEach(function (item) {
    item.addEventListener("click", function () {
      open(item.getAttribute("data-lightbox-src"), item.getAttribute("data-lightbox-alt"));
    });
  });
  closeBtn.addEventListener("click", close);
  lightbox.addEventListener("click", function (e) {
    if (e.target === lightbox) close();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") close();
  });
}

function setupContactForm() {
  const form = document.getElementById("contactForm");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const name = form.querySelector("#contactName").value.trim();
    const email = form.querySelector("#contactEmail").value.trim();
    const phone = form.querySelector("#contactPhone").value.trim();
    const message = form.querySelector("#contactMessage").value.trim();

    const subject = encodeURIComponent("Zapytanie ze strony – " + name);
    const body = encodeURIComponent(
      "Imię i nazwisko: " + name +
      "\nTelefon: " + phone +
      "\nE-mail: " + email +
      "\n\nWiadomość:\n" + message
    );

    window.location.href = "mailto:andrzejz.ozon@gmail.com?subject=" + subject + "&body=" + body;

    const success = document.getElementById("formSuccessMessage");
    if (success) success.classList.remove("hidden");
  });
}

document.addEventListener("DOMContentLoaded", function () {
  setupMobileMenu();
  setupProductAccordion();
  setupCategoryNavScrollSpy();
  setupGalleryLightbox();
  setupContactForm();
  if (window.lucide) window.lucide.createIcons();
});
