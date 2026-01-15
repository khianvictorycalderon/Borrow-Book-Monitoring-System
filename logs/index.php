<?php
  session_start();
  require_once("../api/db.php");

  // Redirect if not logged in
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
      die("<h1>User not found</h1>");
  }

  $current_user = $current_user_result[0];
  $alert_message = "";
  $alert_class = "";

     // Handle book logging
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['book_id'], $_POST['borrower_id'])) {
        $book_id = trim($_POST['book_id']);
        $borrower_id = trim($_POST['borrower_id']);

        // Validate IDs exist
        $book_check = transactionalMySQLQuery("SELECT id, copies_available FROM books WHERE id = ?", [$book_id]);
        $borrower_check = transactionalMySQLQuery("SELECT id FROM borrowers WHERE id = ?", [$borrower_id]);

        if (count($book_check) === 0) {
            $alert_message = "Selected book does not exist.";
            $alert_class = "bg-red-600";
        } elseif (count($borrower_check) === 0) {
            $alert_message = "Selected borrower does not exist.";
            $alert_class = "bg-red-600";
        } else {
            $book = $book_check[0];

            // Get last log for this borrower + book
            $last_log_result = transactionalMySQLQuery(
                "SELECT action_type FROM borrowed_books_log 
                WHERE book_id = ? AND borrower_id = ? 
                ORDER BY action_date DESC LIMIT 1",
                [$book_id, $borrower_id]
            );

            // Determine next action type
            $next_action = (!count($last_log_result) || $last_log_result[0]['action_type'] === 'returned') ? 'borrowed' : 'returned';

            // Update copies
            if ($next_action === 'borrowed') {
                if ($book['copies_available'] <= 0) {
                    $alert_message = "Cannot borrow: no copies available.";
                    $alert_class = "bg-red-600";
                    $next_action = null; // abort
                } else {
                    transactionalMySQLQuery(
                        "UPDATE books SET copies_available = copies_available - 1 WHERE id = ?",
                        [$book_id]
                    );
                }
            } elseif ($next_action === 'returned') {
                transactionalMySQLQuery(
                    "UPDATE books SET copies_available = copies_available + 1 WHERE id = ?",
                    [$book_id]
                );
            }

            // Insert log
            if ($next_action) {
                $insert_result = transactionalMySQLQuery(
                    "INSERT INTO borrowed_books_log (book_id, borrower_id, logger_id, action_type) VALUES (?, ?, ?, ?)",
                    [$book_id, $borrower_id, $user_id, $next_action]
                );

                if ($insert_result === true) {
                    $alert_message = "Book logged successfully as " . ucfirst($next_action) . "!";
                    $alert_class = "bg-green-600";
                } else {
                    $alert_message = "Error logging book: $insert_result";
                    $alert_class = "bg-red-600";
                }
            }
        }
    }

  // Fetch all books & borrowers for logging
  $books_result = transactionalMySQLQuery("SELECT id, book_name FROM books ORDER BY book_name ASC");
  $borrowers_result = transactionalMySQLQuery("SELECT id, first_name, middle_name, last_name FROM borrowers ORDER BY first_name ASC");

  // Fetch logs
  $logs_result = transactionalMySQLQuery("
      SELECT l.book_id, l.borrower_id, l.logger_id, l.action_type, l.action_date,
            b.book_name, br.first_name, br.middle_name, br.last_name, u.username AS logger_username
      FROM borrowed_books_log l
      LEFT JOIN books b ON l.book_id = b.id
      LEFT JOIN borrowers br ON l.borrower_id = br.id
      LEFT JOIN system_users u ON l.logger_id = u.id
      ORDER BY l.action_date DESC
  ");
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
      <title>Book Logging</title>
  </head>
  <body class="bg-neutral-900 text-neutral-50 pt-20">

  <div class="navbar"></div>

  <div class="min-h-screen max-w-5xl mx-auto p-6 flex flex-col gap-8">

      <?php if ($alert_message): ?>
          <div class="<?= $alert_class ?> text-white p-3 rounded-lg text-center">
              <?= htmlspecialchars($alert_message) ?>
          </div>
      <?php endif; ?>

      <!-- Log Book Form -->
      <div class="bg-neutral-800 p-6 rounded-2xl shadow-md">
          <h2 class="text-xl font-semibold mb-4">Log a Book</h2>
          <form method="POST" class="flex flex-col gap-4" id="log_form">

              <!-- Book Selector -->
              <div class="relative">
                  <label class="text-sm font-medium mb-1 block">Book</label>
                  <input type="text" id="book_input" placeholder="Search book..." autocomplete="off"
                        class="w-full px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                  <input type="hidden" name="book_id" id="book_id">
                  <div id="book_list" class="absolute z-50 w-full max-h-60 overflow-y-auto bg-neutral-700 border border-neutral-600 rounded-lg mt-1 hidden"></div>
              </div>

              <!-- Borrower Selector -->
              <div class="relative">
                  <label class="text-sm font-medium mb-1 block">Borrower</label>
                  <input type="text" id="borrower_input" placeholder="Search borrower..." autocomplete="off"
                        class="w-full px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                  <input type="hidden" name="borrower_id" id="borrower_id">
                  <div id="borrower_list" class="absolute z-50 w-full max-h-60 overflow-y-auto bg-neutral-700 border border-neutral-600 rounded-lg mt-1 hidden"></div>
              </div>

              <button type="submit" class="bg-green-600 hover:bg-green-500 transition px-4 py-2 rounded-lg font-semibold">
                  Log Book
              </button>
          </form>
      </div>

      <!-- Logs Table -->
      <div class="bg-neutral-800 p-6 rounded-2xl shadow-md overflow-x-auto">
          <h2 class="text-xl font-semibold mb-4">Borrowed Books History</h2>
          <table class="min-w-full table-auto border-collapse border border-neutral-700">
              <thead>
                  <tr class="bg-neutral-700">
                      <th class="px-4 py-2 border border-neutral-600 text-left">Book</th>
                      <th class="px-4 py-2 border border-neutral-600 text-left">Borrower</th>
                      <th class="px-4 py-2 border border-neutral-600 text-left">Log Date</th>
                      <th class="px-4 py-2 border border-neutral-600 text-left">Status</th>
                      <th class="px-4 py-2 border border-neutral-600 text-left">Logged By</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($logs_result as $log): ?>
                      <tr class="hover:bg-neutral-700">
                          <td class="px-4 py-2 border border-neutral-600">
                              <?= htmlspecialchars($log['book_name'] ?? 'Deleted') ?>
                          </td>
                          <td class="px-4 py-2 border border-neutral-600">
                              <?php
                                  if ($log['first_name'] && $log['last_name']) {
                                      echo htmlspecialchars(trim($log['first_name'].' '.($log['middle_name'] ?? '').' '.$log['last_name']));
                                  } else {
                                      echo "Deleted";
                                  }
                              ?>
                          </td>
                          <td class="px-4 py-2 border border-neutral-600">
                              <?= htmlspecialchars(date("Y-m-d H:i", strtotime($log['action_date']))) ?>
                          </td>
                          <td class="px-4 py-2 border border-neutral-600">
                              <?= ucfirst($log['action_type']) ?>
                          </td>
                          <td class="px-4 py-2 border border-neutral-600">
                              <?= htmlspecialchars($log['logger_username'] ?? 'Deleted') ?>
                          </td>
                      </tr>
                  <?php endforeach; ?>
                  <?php if(count($logs_result) === 0): ?>
                      <tr>
                          <td colspan="5" class="px-4 py-2 text-center text-gray-400">No logs found.</td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>

  </div>

  <div class="footer"></div>

  <script>
  const books = <?= json_encode($books_result) ?>;
  const borrowers = <?= json_encode(array_map(function($b){
      $b['full_name'] = trim($b['first_name'].' '.($b['middle_name'] ?? '').' '.$b['last_name']);
      return $b;
  }, $borrowers_result)) ?>;

  // Generic searchable select
  function setupSearchable(inputId, hiddenId, listId, data, displayKey) {
      const input = document.getElementById(inputId);
      const hidden = document.getElementById(hiddenId);
      const list = document.getElementById(listId);

      function showList(filtered) {
          if(filtered.length === 0) {
              list.innerHTML = '<div class="px-4 py-2 text-gray-400">No results</div>';
          } else {
              list.innerHTML = filtered.map(item => `
                  <div class="px-4 py-2 cursor-pointer hover:bg-green-600 hover:text-white flex justify-between items-center">
                      <span>${item[displayKey]}</span>
                      <span class="text-xs italic text-gray-400">${item.id}</span>
                  </div>
              `).join('');
          }
          list.classList.remove('hidden');
      }

      input.addEventListener('input', () => {
          const val = input.value.toLowerCase();
          if (!val) return list.classList.add('hidden'); // hide if empty
          const filtered = data.filter(d => d[displayKey].toLowerCase().includes(val));
          showList(filtered);
          hidden.value = '';
      });

      input.addEventListener('focus', () => {
          if(input.value) showList(data);
      });

      document.addEventListener('click', (e) => {
          if (!list.contains(e.target) && e.target !== input) list.classList.add('hidden');
      });

      list.addEventListener('click', (e) => {
          const div = e.target.closest('div');
          if(div) {
              const idSpan = div.querySelector('span.text-xs');
              const nameSpan = div.querySelector('span:first-child');
              if(idSpan && nameSpan) {
                  const id = idSpan.textContent;
                  const selected = data.find(d => d.id === id);
                  if(selected) {
                      input.value = selected[displayKey];
                      hidden.value = id;
                      list.classList.add('hidden');
                  }
              }
          }
      });
  }

  setupSearchable('book_input','book_id','book_list', books, 'book_name');
  setupSearchable('borrower_input','borrower_id','borrower_list', borrowers, 'full_name');
  </script>

  </body>
</html>
