<?php
  session_start();
  require_once("../api/db.php");

  if(!isset($_SESSION["user_id"])) {
      header("Location: /");
      exit();
  }

  $user_id = $_SESSION["user_id"];

  // Fetch current user info
  $user_result = transactionalMySQLQuery(
      "SELECT id, first_name, last_name, username, role, password FROM system_users WHERE id = ?",
      [$user_id]
  );

  if (is_string($user_result) || count($user_result) === 0) {
      die("<h1>User not found: " . (is_string($user_result) ? $user_result : "" . "</h1>"));
  }

  $user = $user_result[0];

  // Check if this user is the sole admin
  $admin_count_result = transactionalMySQLQuery(
      "SELECT COUNT(*) AS total FROM system_users WHERE role = 'admin'"
  );
  $sole_admin = ($user['role'] === 'admin' && (int)$admin_count_result[0]['total'] === 1);

  $alert_message = "";
  $alert_class = "";

  if ($_SERVER["REQUEST_METHOD"] === "POST") {

      // Account Details Update
      if (isset($_POST["update_details"])) {
          $first_name = trim($_POST["first_name"] ?? "");
          $last_name  = trim($_POST["last_name"] ?? "");

          if ($first_name === "" || $last_name === "") {
              $alert_message = "First name and Last name cannot be empty.";
              $alert_class = "bg-red-600";
          } else {
              $update_result = transactionalMySQLQuery(
                  "UPDATE system_users SET first_name = ?, last_name = ? WHERE id = ?",
                  [$first_name, $last_name, $user_id]
              );
              if ($update_result === true) {
                  $alert_message = "Account details updated successfully!";
                  $alert_class = "bg-green-600";
                  $user['first_name'] = $first_name;
                  $user['last_name'] = $last_name;
              } else {
                  $alert_message = "Error updating details: $update_result";
                  $alert_class = "bg-red-600";
              }
          }
      }

      // Password Update
      if (isset($_POST["update_password"])) {
          $old_password = $_POST["old_password"] ?? "";
          $new_password = $_POST["new_password"] ?? "";
          $confirm_password = $_POST["confirm_password"] ?? "";

          if ($old_password === "" || $new_password === "" || $confirm_password === "") {
              $alert_message = "All password fields are required.";
              $alert_class = "bg-red-600";
          } elseif (!password_verify($old_password, $user['password'])) {
              $alert_message = "Old password is incorrect.";
              $alert_class = "bg-red-600";
          } elseif ($new_password !== $confirm_password) {
              $alert_message = "New password and confirmation do not match.";
              $alert_class = "bg-red-600";
          } else {
              $hashed = password_hash($new_password, PASSWORD_DEFAULT);
              $pass_update = transactionalMySQLQuery(
                  "UPDATE system_users SET password = ? WHERE id = ?",
                  [$hashed, $user_id]
              );
              if ($pass_update === true) {
                  $alert_message = "Password updated successfully!";
                  $alert_class = "bg-green-600";
              } else {
                  $alert_message = "Error updating password: $pass_update";
                  $alert_class = "bg-red-600";
              }
          }
      }

      // Delete Account
      if (isset($_POST["delete_account"]) && !$sole_admin) {
          $delete_result = transactionalMySQLQuery(
              "DELETE FROM system_users WHERE id = ?",
              [$user_id]
          );
          if ($delete_result === true) {
              session_destroy();
              header("Location: /");
              exit();
          } else {
              $alert_message = "Error deleting account: $delete_result";
              $alert_class = "bg-red-600";
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
      <title>Manage Account</title>
  </head>
  <body class="min-h-screen bg-neutral-900 text-neutral-50 pt-20">

    <div class="navbar"></div>

    <div class="max-w-3xl mx-auto p-6 flex flex-col gap-8">

        <?php if($alert_message): ?>
            <div class="<?= $alert_class ?> text-white p-3 rounded-lg text-center">
                <?= htmlspecialchars($alert_message) ?>
            </div>
        <?php endif; ?>

        <!-- Account Details Section -->
        <div class="bg-neutral-800 p-6 rounded-2xl shadow-md">
            <h2 class="text-xl font-semibold mb-4">Account Details</h2>
            <form method="POST">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block mb-1 text-sm">First Name</label>
                        <input name="first_name" type="text" value="<?= htmlspecialchars($user['first_name']) ?>"
                              class="w-full px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Last Name</label>
                        <input name="last_name" type="text" value="<?= htmlspecialchars($user['last_name']) ?>"
                              class="w-full px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Username</label>
                        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" readonly
                              class="w-full px-4 py-2 rounded-lg bg-neutral-700 text-neutral-400 border border-neutral-600 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Role</label>
                        <input type="text" value="<?= htmlspecialchars(strtoupper($user['role'])) ?>" readonly
                              class="w-full px-4 py-2 rounded-lg bg-neutral-700 text-neutral-400 border border-neutral-600 cursor-not-allowed">
                    </div>
                    <button type="submit" name="update_details"
                            class="bg-green-600 hover:bg-green-500 transition px-4 py-2 rounded-lg font-semibold">
                        Update Details
                    </button>
                </div>
            </form>
        </div>

        <!-- Update Password Section -->
        <div class="bg-neutral-800 p-6 rounded-2xl shadow-md">
            <h2 class="text-xl font-semibold mb-4">Update Password</h2>
            <form method="POST" class="flex flex-col gap-4">
                <input name="old_password" type="password" placeholder="Old Password"
                      class="flex-1 px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                <input name="new_password" type="password" placeholder="New Password"
                      class="flex-1 px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                <input name="confirm_password" type="password" placeholder="Confirm New"
                      class="flex-1 px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                <button type="submit" name="update_password"
                        class="bg-green-600 hover:bg-green-500 transition px-4 py-2 rounded-lg font-semibold w-full">
                    Update Password
                </button>
            </form>
        </div>

        <!-- Delete Account Section -->
        <?php if (!$sole_admin): ?>
        <div class="bg-neutral-800 p-6 rounded-2xl shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-red-400 text-center">Delete Account</h2>
            <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account? This action cannot be undone.');">
                <button type="submit" name="delete_account"
                        class="bg-red-600 hover:bg-red-500 transition px-4 py-2 rounded-lg font-semibold w-full">
                    Delete My Account
                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>

    <div class="footer"></div>

  </body>
</html>
