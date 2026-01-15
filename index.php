<?php

  require_once("db.php");
  
  // If there is a user already logged in
  if(isset($_SESSION["user_id"])) {
    header("Location: /logs"); // Go to logs page (by default)
    exit();
  }

  // Check for POST method
  if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["login_username"] ?? "");
    $password = trim($_POST["login_password"] ?? "");

    if ($username === "" || $password === "") {
      $error = "Username and password are required.";
    } else {
      // Login logic here...
    }
  }

?>

<!DOCTYPE html>
<html>
  <head>
    
  	<meta http-equiv='cache-control' content='no-cache'> 
    <meta http-equiv='expires' content='0'> 
    <meta http-equiv='pragma' content='no-cache'>

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png+jpg" href="/images/icons/book-borrow-monitoring-system.png">
    <script src="/assets/tailwind-3.4.17.js"></script>
    <script type="module" src="/assets/main.js"></script>
    
    <title>Borrow Book Monitoring System</title>

  </head>
  <body class="min-h-screen bg-neutral-950 text-neutral-100 flex items-center justify-center">

    <div class="w-full max-w-sm bg-neutral-900 rounded-2xl shadow-lg p-8">
      
      <h2 class="text-3xl font-semibold text-center mb-6">
        Welcome Back
      </h2>

      <form 
        method="POST" 
        action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>"
        class="space-y-5"
        id="login_form"
      >
        
        <div class="space-y-1">
          <label 
            for="login_username" 
            class="text-sm font-medium text-neutral-300"
          >
            Username
          </label>
          <input
            id="login_username"
            name="login_username"
            type="text"
            placeholder="Enter your username"
            class="w-full rounded-lg bg-neutral-800 border border-neutral-700 px-4 py-2
                  text-neutral-100 placeholder-neutral-400
                  focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                  transition"
          />
        </div>

        <div class="space-y-1">
          <label 
            for="login_password" 
            class="text-sm font-medium text-neutral-300"
          >
            Password
          </label>
          <input
            id="login_password"
            name="login_password"
            type="password"
            placeholder="Enter your password"
            class="w-full rounded-lg bg-neutral-800 border border-neutral-700 px-4 py-2
                  text-neutral-100 placeholder-neutral-400
                  focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                  transition"
          />
        </div>

        <button
          type="submit"
          class="w-full rounded-lg bg-green-600 py-2.5 font-semibold
                hover:bg-green-500 active:scale-[0.98]
                transition duration-200"
        >
          Login
        </button>

      </form>
    </div>

  </body>
</html>