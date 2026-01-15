<?php
  session_start();
  require_once("../api/db.php");

  // Only logged-in users can register new accounts
  if(!isset($_SESSION["user_id"])) {
      header("Location: /");
      exit();
  }

  $user_id = $_SESSION["user_id"];

  // Fetch current user info
  $current_user = transactionalMySQLQuery(
      "SELECT id, first_name, last_name, username, role FROM system_users WHERE id = ?",
      [$user_id]
  );

  if (is_string($current_user) || count($current_user) === 0) {
      die("<h1>User not found: " . (is_string($current_user) ? $current_user : "" . "</h1>"));
  }

  $current_user = $current_user[0];

  // Only admin can access registration
  if ($current_user['role'] !== 'admin') {
      die("<h1>Access denied: Only admin can create new users.</h1>");
  }

  $alert_message = "";
  $alert_class = "";

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $first_name = trim($_POST['first_name'] ?? '');
      $last_name  = trim($_POST['last_name'] ?? '');
      $username   = trim($_POST['username'] ?? '');
      $role       = $_POST['role'] ?? '';
      $password   = $_POST['password'] ?? '';
      $confirm_password = $_POST['confirm_password'] ?? '';

      if (!$first_name || !$last_name || !$username || !$role || !$password || !$confirm_password) {
          $alert_message = "All fields are required.";
          $alert_class = "bg-red-600";
      } elseif ($password !== $confirm_password) {
          $alert_message = "Password and confirmation do not match.";
          $alert_class = "bg-red-600";
      } elseif (!in_array($role, ['moderator', 'staff'])) {
          $alert_message = "Invalid role selected.";
          $alert_class = "bg-red-600";
      } else {
          // Check if username already exists
          $existing = transactionalMySQLQuery(
              "SELECT id FROM system_users WHERE username = ?",
              [$username]
          );
          if (is_string($existing)) {
              $alert_message = "Error checking username: $existing";
              $alert_class = "bg-red-600";
          } elseif (count($existing) > 0) {
              $alert_message = "Username already exists.";
              $alert_class = "bg-red-600";
          } else {
              // Insert new user
              $hashed = password_hash($password, PASSWORD_DEFAULT);
              $insert = transactionalMySQLQuery(
                  "INSERT INTO system_users (first_name, last_name, role, username, password)
                  VALUES (?, ?, ?, ?, ?)",
                  [$first_name, $last_name, $role, $username, $hashed]
              );

              if ($insert === true) {
                  $alert_message = "User created successfully!";
                  $alert_class = "bg-green-600";
                  // Optionally clear form fields
                  $first_name = $last_name = $username = $role = '';
              } else {
                  $alert_message = "Error creating user: $insert";
                  $alert_class = "bg-red-600";
              }
          }
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
      <title>Register New User</title>
  </head>
  <body class="bg-neutral-900 text-neutral-50 pt-20">

      <div class="navbar"></div>

      <div class="min-h-screen max-w-3xl mx-auto p-6 flex flex-col gap-8">

          <?php if($alert_message): ?>
              <div class="<?= $alert_class ?> text-white p-3 rounded-lg text-center">
                  <?= htmlspecialchars($alert_message) ?>
              </div>
          <?php endif; ?>

          <div class="bg-neutral-800 p-6 rounded-2xl shadow-md">
              <h2 class="text-xl font-semibold mb-4">Register New User</h2>
              <form method="POST" class="flex flex-col gap-4">
                  <input type="text" name="first_name" placeholder="First Name" value="<?= htmlspecialchars($first_name ?? '') ?>"
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>

                  <input type="text" name="last_name" placeholder="Last Name" value="<?= htmlspecialchars($last_name ?? '') ?>"
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>

                  <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($username ?? '') ?>"
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>

                  <select name="role" required
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                      <option value="" disabled <?= empty($role) ? 'selected' : '' ?>>Select Role</option>
                      <option value="moderator" <?= ($role ?? '') === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                      <option value="staff" <?= ($role ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                  </select>

                  <input type="password" name="password" placeholder="Password"
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>

                  <input type="password" name="confirm_password" placeholder="Confirm Password"
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>

                  <button type="submit"
                          class="bg-green-600 hover:bg-green-500 transition px-4 py-2 rounded-lg font-semibold">
                      Register User
                  </button>
              </form>
          </div>

      </div>

      <div class="footer"></div>

  </body>
</html>
