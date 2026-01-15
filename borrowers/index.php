<?php

  session_start();
  require_once("../api/db.php");
  
  // If there are no logged user yet
  if(!isset($_SESSION["user_id"])) {
    header("Location: /"); // Go back to index page
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
    
    <title>Borrowers Management</title>

  </head>
  <body>

    <div class="navbar"></div>

    <div class="min-h-screen w-full 
        bg-neutral-900 text-neutral-50
        flex items-center justify-center
        ">
        
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-bold text-center">Manage Book Borrowers here...</h2>
        </div>
        
    </div>

  </body>
</html>