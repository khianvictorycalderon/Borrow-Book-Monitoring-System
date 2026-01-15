<?php
  session_start();
  require_once("../api/db.php");

  // Only logged-in users
  if (!isset($_SESSION["user_id"])) {
      header("Location: /");
      exit();
  }

  $user_id = $_SESSION["user_id"];

  // Fetch current user info
  $current_user_result = transactionalMySQLQuery(
      "SELECT id, first_name, last_name, username, role FROM system_users WHERE id = ?",
      [$user_id]
  );

  if (is_string($current_user_result) || count($current_user_result) === 0) {
      die("<h1>User not found: " . (is_string($current_user_result) ? $current_user_result : "") . "</h1>");
  }

  $current_user = $current_user_result[0];

  $alert_message = "";
  $alert_class = "";

  // Handle Add/Edit form submission (Admin & Moderator only)
  if ($_SERVER["REQUEST_METHOD"] === "POST" && in_array($current_user['role'], ['admin', 'moderator'])) {

      $first_name = trim($_POST['first_name'] ?? '');
      $middle_name = trim($_POST['middle_name'] ?? '');
      $last_name = trim($_POST['last_name'] ?? '');
      $description = trim($_POST['description'] ?? '');
      $edit_id = $_POST['edit_id'] ?? null;

      if (!$first_name || !$last_name) {
          $alert_message = "First Name and Last Name are required.";
          $alert_class = "bg-red-600";
      } else {
          if ($edit_id) {
              // Update existing borrower
              $update_result = transactionalMySQLQuery(
                  "UPDATE borrowers SET first_name = ?, middle_name = ?, last_name = ?, description = ? WHERE id = ?",
                  [$first_name, $middle_name, $last_name, $description, $edit_id]
              );

              if ($update_result === true) {
                  $alert_message = "Borrower updated successfully!";
                  $alert_class = "bg-green-600";
                  $first_name = $middle_name = $last_name = $description = '';
                  $edit_id = null;
              } else {
                  $alert_message = "Error updating borrower: $update_result";
                  $alert_class = "bg-red-600";
              }
          } else {
              // Generate unique borrower ID
              do {
                  $borrower_id = generate_uuid_v4_manual("XXX-XXXX-XXX");
                  $exists = transactionalMySQLQuery(
                      "SELECT id FROM borrowers WHERE id = ?",
                      [$borrower_id]
                  );
              } while (count($exists) > 0);

              // Insert new borrower
              $insert_result = transactionalMySQLQuery(
                  "INSERT INTO borrowers (id, first_name, middle_name, last_name, description, created_by)
                  VALUES (?, ?, ?, ?, ?, ?)",
                  [$borrower_id, $first_name, $middle_name, $last_name, $description, $user_id]
              );

              if ($insert_result === true) {
                  $alert_message = "Borrower added successfully!";
                  $alert_class = "bg-green-600";
                  $first_name = $middle_name = $last_name = $description = '';
              } else {
                  $alert_message = "Error adding borrower: $insert_result";
                  $alert_class = "bg-red-600";
              }
          }
      }
  }

  // Handle Delete action (Admin & Moderator only)
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id']) && in_array($current_user['role'], ['admin', 'moderator'])) {
      $delete_id = $_POST['delete_id'];
      $delete_result = transactionalMySQLQuery(
          "DELETE FROM borrowers WHERE id = ?",
          [$delete_id]
      );

      if ($delete_result === true) {
          $alert_message = "Borrower deleted successfully!";
          $alert_class = "bg-green-600";
      } else {
          $alert_message = "Error deleting borrower: $delete_result";
          $alert_class = "bg-red-600";
      }
  }

  // Fetch all borrowers
  $borrowers_result = transactionalMySQLQuery(
      "SELECT b.id, b.first_name, b.middle_name, b.last_name, b.description, u.username AS created_by
      FROM borrowers b
      LEFT JOIN system_users u ON b.created_by = u.id
      ORDER BY b.id DESC"
  );
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
  <body class="bg-neutral-900 text-neutral-50 pt-20">

  <div class="navbar"></div>

  <div class="min-h-screen max-w-5xl mx-auto p-6 flex flex-col gap-8">

      <?php if ($alert_message): ?>
          <div class="<?= $alert_class ?> text-white p-3 rounded-lg text-center">
              <?= htmlspecialchars($alert_message) ?>
          </div>
      <?php endif; ?>

      <?php if (in_array($current_user['role'], ['admin', 'moderator'])): ?>
      <div class="bg-neutral-800 p-6 rounded-2xl shadow-md">
          <h2 class="text-xl font-semibold mb-4" id="form_title">Add New Borrower</h2>
          <form method="POST" class="flex flex-col gap-4" id="borrower_form">
              <input type="hidden" name="edit_id" id="edit_id" value="<?= htmlspecialchars($edit_id ?? '') ?>">

              <input type="text" name="first_name" placeholder="First Name" value="<?= htmlspecialchars($first_name ?? '') ?>"
                  class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>

              <input type="text" name="middle_name" placeholder="Middle Name (Optional)" value="<?= htmlspecialchars($middle_name ?? '') ?>"
                  class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500">

              <input type="text" name="last_name" placeholder="Last Name" value="<?= htmlspecialchars($last_name ?? '') ?>"
                  class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>

              <textarea name="description" placeholder="Description (Optional)"
                  class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($description ?? '') ?></textarea>

              <div class="flex gap-2">
                  <button type="submit"
                          class="bg-green-600 hover:bg-green-500 transition px-4 py-2 rounded-lg font-semibold"
                          id="save_button">
                      Save
                  </button>
                  <button type="button"
                          class="bg-gray-600 hover:bg-gray-500 transition px-4 py-2 rounded-lg font-semibold"
                          id="cancel_button">
                      Cancel
                  </button>
              </div>
          </form>
      </div>
      <?php endif; ?>

      <!-- Borrowers List -->
      <div class="bg-neutral-800 p-6 rounded-2xl shadow-md overflow-x-auto">
          <h2 class="text-xl font-semibold mb-4">Borrowers List</h2>
          <table class="min-w-full table-auto border-collapse border border-neutral-700">
              <thead>
                  <tr class="bg-neutral-700">
                      <th class="px-4 py-2 border border-neutral-600">ID</th>
                      <th class="px-4 py-2 border border-neutral-600">First Name</th>
                      <th class="px-4 py-2 border border-neutral-600">Middle Name</th>
                      <th class="px-4 py-2 border border-neutral-600">Last Name</th>
                      <th class="px-4 py-2 border border-neutral-600">Description</th>
                      <th class="px-4 py-2 border border-neutral-600">Created By</th>
                      <?php if (in_array($current_user['role'], ['admin', 'moderator'])): ?>
                          <th class="px-4 py-2 border border-neutral-600">Actions</th>
                      <?php endif; ?>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($borrowers_result as $b): ?>
                  <tr class="hover:bg-neutral-700">
                      <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($b['id']) ?></td>
                      <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($b['first_name']) ?></td>
                      <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($b['middle_name']) ?></td>
                      <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($b['last_name']) ?></td>
                      <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($b['description']) ?></td>
                      <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($b['created_by'] ?? 'User Deleted') ?></td>
                      <?php if (in_array($current_user['role'], ['admin', 'moderator'])): ?>
                          <td class="px-4 py-2 border border-neutral-600 flex gap-2">
                              <button class="edit_button bg-green-600 hover:bg-green-500 px-2 py-1 rounded text-sm font-semibold"
                                      data-id="<?= $b['id'] ?>"
                                      data-first_name="<?= htmlspecialchars($b['first_name'], ENT_QUOTES) ?>"
                                      data-middle_name="<?= htmlspecialchars($b['middle_name'], ENT_QUOTES) ?>"
                                      data-last_name="<?= htmlspecialchars($b['last_name'], ENT_QUOTES) ?>"
                                      data-description="<?= htmlspecialchars($b['description'], ENT_QUOTES) ?>">
                                  Edit
                              </button>
                              <form method="POST" style="display:inline">
                                  <input type="hidden" name="delete_id" value="<?= $b['id'] ?>">
                                  <button type="submit"
                                          class="bg-red-600 hover:bg-red-500 px-2 py-1 rounded text-sm font-semibold">
                                      Delete
                                  </button>
                              </form>
                          </td>
                      <?php endif; ?>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (count($borrowers_result) === 0): ?>
                  <tr>
                      <td colspan="<?= in_array($current_user['role'], ['admin', 'moderator']) ? 7 : 6 ?>"
                          class="px-4 py-2 text-center text-gray-400">
                          No borrowers found.
                      </td>
                  </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>

  </div>

  <div class="footer"></div>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
      const form = document.getElementById("borrower_form");
      const formTitle = document.getElementById("form_title");
      const editIdInput = document.getElementById("edit_id");
      const cancelBtn = document.getElementById("cancel_button");

      document.querySelectorAll(".edit_button").forEach(btn => {
          btn.onclick = () => {
              editIdInput.value = btn.dataset.id;
              form.first_name.value = btn.dataset.first_name;
              form.middle_name.value = btn.dataset.middle_name;
              form.last_name.value = btn.dataset.last_name;
              form.description.value = btn.dataset.description;
              formTitle.textContent = "Edit Borrower";
          };
      });

      cancelBtn.onclick = () => {
          editIdInput.value = "";
          form.first_name.value = "";
          form.middle_name.value = "";
          form.last_name.value = "";
          form.description.value = "";
          formTitle.textContent = "Add New Borrower";
      };
  });
  </script>

  </body>
</html>
