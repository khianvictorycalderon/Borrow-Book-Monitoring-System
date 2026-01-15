<?php
  session_start();
  require_once("../api/db.php");

  // Only logged-in users can access
  if(!isset($_SESSION["user_id"])) {
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

  // Handle Add Book form (admin & moderator only)
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_book']) && in_array($current_user['role'], ['admin', 'moderator'])) {
      $book_name = trim($_POST['book_name'] ?? '');
      $book_author = trim($_POST['book_author'] ?? '');
      $copies_available = intval($_POST['copies_available'] ?? 1);

      if (!$book_name || !$book_author || $copies_available < 1) {
          $alert_message = "All fields are required and copies must be at least 1.";
          $alert_class = "bg-red-600";
      } else {
          $insert_result = transactionalMySQLQuery(
              "INSERT INTO books (book_name, book_author, copies_available, created_by) VALUES (?, ?, ?, ?)",
              [$book_name, $book_author, $copies_available, $user_id]
          );

          if ($insert_result === true) {
              $alert_message = "Book added successfully!";
              $alert_class = "bg-green-600";
              $book_name = $book_author = '';
              $copies_available = 1;
          } else {
              $alert_message = "Error adding book: $insert_result";
              $alert_class = "bg-red-600";
          }
      }
  }

  // Fetch all books
  $books_result = transactionalMySQLQuery(
      "SELECT b.id, b.book_name, b.book_author, b.copies_available, u.username AS created_by
      FROM books b
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
      <title>Books</title>
  </head>
  <body class="bg-neutral-900 text-neutral-50 pt-20">

      <div class="navbar"></div>

      <div class="min-h-screen max-w-5xl mx-auto p-6 flex flex-col gap-8">

          <?php if($alert_message): ?>
              <div class="<?= $alert_class ?> text-white p-3 rounded-lg text-center">
                  <?= htmlspecialchars($alert_message) ?>
              </div>
          <?php endif; ?>

          <!-- Add Book Section (admin/moderator only) -->
          <?php if (in_array($current_user['role'], ['admin', 'moderator'])): ?>
          <div class="bg-neutral-800 p-6 rounded-2xl shadow-md">
              <h2 class="text-xl font-semibold mb-4">Add New Book</h2>
              <form method="POST" class="flex flex-col gap-4">
                  <input type="text" name="book_name" placeholder="Book Name" value="<?= htmlspecialchars($book_name ?? '') ?>"
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                  
                  <input type="text" name="book_author" placeholder="Book Author" value="<?= htmlspecialchars($book_author ?? '') ?>"
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>
                  
                  <input type="number" name="copies_available" placeholder="Copies Available" min="1"
                        class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500" required>

                  <button type="submit" name="add_book"
                          class="bg-green-600 hover:bg-green-500 transition px-4 py-2 rounded-lg font-semibold">
                      Add Book
                  </button>
              </form>
          </div>
          <?php endif; ?>

          <!-- Books List -->
          <div class="bg-neutral-800 p-6 rounded-2xl shadow-md overflow-x-auto">
              <h2 class="text-xl font-semibold mb-4">Books List</h2>

              <form method="POST" class="flex flex-col gap-2">
                  <table class="min-w-full table-auto border-collapse border border-neutral-700">
                      <thead>
                          <tr class="bg-neutral-700">
                              <th class="px-4 py-2 border border-neutral-600 text-left">ID</th>
                              <th class="px-4 py-2 border border-neutral-600 text-left">Book Name</th>
                              <th class="px-4 py-2 border border-neutral-600 text-left">Author</th>
                              <th class="px-4 py-2 border border-neutral-600 text-left">Copies</th>
                              <th class="px-4 py-2 border border-neutral-600 text-left">Created By</th>
                              <?php if(in_array($current_user['role'], ['admin', 'moderator'])): ?>
                                  <th class="px-4 py-2 border border-neutral-600 text-left">Actions</th>
                              <?php endif; ?>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach($books_result as $book): ?>
                          <tr class="hover:bg-neutral-700">
                              <td class="px-4 py-2 border border-neutral-600"><?= $book['id'] ?></td>
                              
                              <!-- Editable fields for admin/moderator -->
                              <?php if(in_array($current_user['role'], ['admin', 'moderator'])): ?>
                                  <td class="px-4 py-2 border border-neutral-600">
                                      <input type="text" name="book_name[<?= $book['id'] ?>]" value="<?= htmlspecialchars($book['book_name']) ?>"
                                          class="w-full px-2 py-1 rounded bg-neutral-700 text-neutral-100 border border-neutral-600">
                                  </td>
                                  <td class="px-4 py-2 border border-neutral-600">
                                      <input type="text" name="book_author[<?= $book['id'] ?>]" value="<?= htmlspecialchars($book['book_author']) ?>"
                                          class="w-full px-2 py-1 rounded bg-neutral-700 text-neutral-100 border border-neutral-600">
                                  </td>
                                  <td class="px-4 py-2 border border-neutral-600">
                                      <input type="number" name="copies_available[<?= $book['id'] ?>]" min="1" value="<?= $book['copies_available'] ?>"
                                          class="w-full px-2 py-1 rounded bg-neutral-700 text-neutral-100 border border-neutral-600">
                                  </td>
                                  <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($book['created_by'] ?? 'User Deleted') ?></td>
                                  <td class="px-4 py-2 border border-neutral-600">
                                      <button type="submit" name="update_book[<?= $book['id'] ?>]"
                                              class="bg-green-600 hover:bg-green-500 px-2 py-1 rounded text-sm font-semibold">
                                          Save
                                      </button>
                                  </td>
                              <?php else: ?>
                                  <!-- Staff view only -->
                                  <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($book['book_name']) ?></td>
                                  <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($book['book_author']) ?></td>
                                  <td class="px-4 py-2 border border-neutral-600"><?= $book['copies_available'] ?></td>
                                  <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($book['created_by'] ?? 'User Deleted') ?></td>
                              <?php endif; ?>
                          </tr>
                          <?php endforeach; ?>
                          <?php if(count($books_result) === 0): ?>
                          <tr>
                              <td colspan="<?= in_array($current_user['role'], ['admin', 'moderator']) ? 6 : 5 ?>" class="px-4 py-2 text-center text-gray-400">
                                  No books found.
                              </td>
                          </tr>
                          <?php endif; ?>
                      </tbody>
                  </table>
              </form>
          </div>
          
      </div>

      <div class="footer"></div>

  </body>
</html>
