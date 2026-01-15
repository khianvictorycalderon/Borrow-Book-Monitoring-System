import { NavBarComponent, attachNavBarActions } from "../components/navbar.js";

document.querySelectorAll(".navbar").forEach(navbarEl => {

  const buttonClassName = "text-neutral-100 hover:text-green-500 font-medium transition duration-300 cursor-pointer";

  const navbarButtons = [
    { 
      label: "Books", 
      className: buttonClassName,
      action: () => window.location.href = "/books" 
    },
    { 
      label: "Borrowers", 
      className: buttonClassName,
      action: () => window.location.href = "/borrowers" 
    },
    { 
      label: "Logs", 
      className: buttonClassName,
      action: () => window.location.href = "/logs" 
    },
    { 
      label: "Account", 
      className: buttonClassName,
      action: () => window.location.href = "/account" 
    },
    { 
      label: "Log Out", 
      className: buttonClassName,
      action: () => {

        fetch("/api/logout.php").then(() => {
          window.location.href = "/";
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

  // Attach mobile toggle & button actions (must match buttons)
  attachNavBarActions(navbarEl, navbarButtons);
});

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login_form");

  form.addEventListener("submit", (event) => {
    const username = document.getElementById("login_username").value;
    const password = document.getElementById("login_password").value;

    if (username.trim() === "" || password.trim() === "") {
      alert("Please fill in all fields");
      event.preventDefault();
      return;
    }

  });
});
