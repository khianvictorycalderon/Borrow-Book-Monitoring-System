<?php

  require_once("db.php");
  
  // If there is a user already logged in
  if(isset($_SESSION["user_id"])) {
    header("Location: /logs"); // Go to logs page (by default)
    exit();
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
  <body>

    <div class="min-h-screen w-full 
        bg-neutral-900 text-neutral-50
        flex items-center justify-center
        ">
        
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-bold text-center">Login</h2>
            <form class="flex flex-col gap-2" method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
              <div class="flex flex-col gap-2 justify-center">
                <label for="login_username">Username: </label>
                <input class="p-2 rounded-md bg-neutral-200 text-neutral-800" id="login_username" name="login_username" type="text" placeholder="Enter your username...">
              </div>
              <div class="flex flex-col gap-2 justify-center">
                <label for="login_password">Password: </label>
                <input class="p-2 rounded-md bg-neutral-200 text-neutral-800" id="login_password" name="login_password" type="password" placeholder="Enter your password...">
              </div>
              <button class="rounded-md px-6 py-2 font-semibold bg-green-600 hover:bg-green-500 transition duration-300 mt-4">
                Login
              </button>
            </form>
        </div>
        
    </div>

  </body>
</html>