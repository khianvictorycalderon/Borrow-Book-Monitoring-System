import { FooterComponent } from "../components/footer.js";
import { NavBarComponent, attachNavBarActions } from "../components/navbar.js";

// Derive the app's base path from the script tag src attribute,
// which is always correct regardless of which page loads this file.
// e.g. src="/Borrow-Book-Monitoring-System/assets/main.js"
//   => base = "/Borrow-Book-Monitoring-System"
const scriptSrc = document.currentScript
  ? document.currentScript.src
  : Array.from(document.querySelectorAll('script[src*="main.js"]')).pop()?.src ?? "";

const url = new URL(scriptSrc);
// Strip "/assets/main.js" to get the app root path
const base = url.pathname.replace(/\/assets\/main\.js$/, "");

document.addEventListener("DOMContentLoaded", () => {

  // Login form — only exists on the login page
  const form = document.getElementById("login_form");
  if (form) {
    form.addEventListener("submit", (event) => {
      const username = document.getElementById("login_username").value;
      const password = document.getElementById("login_password").value;
      if (username.trim() === "" || password.trim() === "") {
        alert("Please fill in all fields");
        event.preventDefault();
      }
    });
  }

  // Navbar
  document.querySelectorAll(".navbar").forEach(navbarEl => {
    const buttonClassName = "text-neutral-100 hover:text-green-500 font-medium transition duration-300 cursor-pointer";

    const navbarButtons = [
      {
        label: "Books",
        className: buttonClassName,
        action: () => window.location.href = base + "/books/"
      },
      {
        label: "Borrowers",
        className: buttonClassName,
        action: () => window.location.href = base + "/borrowers/"
      },
      {
        label: "Logs",
        className: buttonClassName,
        action: () => window.location.href = base + "/logs/"
      },
      {
        label: "Account",
        className: buttonClassName,
        action: () => window.location.href = base + "/account/"
      },
      {
        label: "Register",
        className: buttonClassName,
        action: () => window.location.href = base + "/register/"
      },
      {
        label: "Log Out",
        className: buttonClassName,
        action: () => {
          fetch(base + "/api/logout.php").then(() => {
            window.location.href = base + "/";
          });
        }
      }
    ];

    navbarEl.innerHTML = NavBarComponent({
      title: "Monitor Borrowed Books",
      className: "!bg-neutral-800 !text-neutral-100",
      buttons: navbarButtons,
      buttonsAlignment: "right"
    });

    attachNavBarActions(navbarEl, navbarButtons);
  });

  // Footer
  document.querySelectorAll(".footer").forEach(footer => {
    footer.innerHTML = FooterComponent;
  });

});