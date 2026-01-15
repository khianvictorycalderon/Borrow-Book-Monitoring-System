document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login_form");

  form.addEventListener("submit", (event) => {
    const username = document.getElementById("login_username").value;
    const password = document.getElementById("login_password").value;

    if (username.trim() === "" || password.trim() === "") {
      alert("Please fill in all fields");
      event.preventDefault(); // ❌ stop form submission
      return;
    }
    
  });
});
