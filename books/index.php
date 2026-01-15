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
        die("<h1>User not found</h1>");
    }

    $current_user = $current_user_result[0];
    $role = $current_user['role'];

    $alert_message = "";
    $alert_class = "";

    // ---------- PROCESS POST ----------
    if ($_SERVER["REQUEST_METHOD"] === "POST" && in_array($role, ['admin', 'moderator'])) {

        // ---------- Delete Book ----------
        if (isset($_POST['delete_book'])) {
            $book_id = intval($_POST['delete_book']);
            $delete_result = transactionalMySQLQuery(
                "DELETE FROM books WHERE id=?",
                [$book_id]
            );

            if ($delete_result === true) {
                $alert_message = "Book deleted successfully!";
                $alert_class = "bg-green-600";
            } else {
                $alert_message = "Error deleting book: $delete_result";
                $alert_class = "bg-red-600";
            }
        }

        // ---------- Add / Update Book ----------
        if (isset($_POST['save_book'])) {
            $book_name = trim($_POST['book_name'] ?? '');
            $book_author = trim($_POST['book_author'] ?? '');
            $copies_available = $_POST['copies_available'] ?? '';
            $edit_id = intval($_POST['edit_id'] ?? 0);

            if ($book_name === '' || $book_author === '' || !is_numeric($copies_available) || intval($copies_available) < 1) {
                $alert_message = "All fields are required and copies must be at least 1.";
                $alert_class = "bg-red-600";
            } else {
                if ($edit_id > 0) {
                    // Update existing book
                    $update_result = transactionalMySQLQuery(
                        "UPDATE books SET book_name=?, book_author=?, copies_available=? WHERE id=?",
                        [$book_name, $book_author, intval($copies_available), $edit_id]
                    );
                    if ($update_result === true) {
                        $alert_message = "Book updated successfully!";
                        $alert_class = "bg-green-600";
                    } else {
                        $alert_message = "Error updating book: $update_result";
                        $alert_class = "bg-red-600";
                    }
                } else {
                    // Add new book
                    
                    $book_uuid = '';
                    do {
                        $book_uuid = generate_uuid_v4_manual();

                        // Check if UUID already exists
                        $existing = transactionalMySQLQuery(
                            "SELECT COUNT(*) AS total FROM books WHERE uuid = ?",
                            [$book_uuid]
                        );

                        if (is_string($existing)) {
                            $alert_message = "Error checking UUID collision: $existing";
                            $alert_class = "bg-red-600";
                            break; // stop if error
                        }

                    } while ((int)$existing[0]['total'] > 0); // repeat until unique

                    $insert_result = transactionalMySQLQuery(
                        "INSERT INTO books (id, book_name, book_author, copies_available, created_by) VALUES (?, ?, ?, ?, ?)",
                        [$book_uuid, $book_name, $book_author, intval($copies_available), $user_id]
                    );

                    if ($insert_result === true) {
                        $alert_message = "Book added successfully!";
                        $alert_class = "bg-green-600";
                    } else {
                        $alert_message = "Error adding book: $insert_result";
                        $alert_class = "bg-red-600";
                    }
                }
            }
        }
    }

    // ---------- FETCH BOOKS ----------
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

        <?php if ($alert_message): ?>
            <div class="<?= $alert_class ?> text-white p-3 rounded-lg text-center">
                <?= htmlspecialchars($alert_message) ?>
            </div>
        <?php endif; ?>

        <!-- Add / Edit Book Form -->
        <?php if (in_array($role, ['admin', 'moderator'])): ?>
        <div class="bg-neutral-800 p-6 rounded-2xl shadow-md">
            <h2 class="text-xl font-semibold mb-4" id="form_title">Add New Book</h2>
            <form method="POST" id="book_form" class="flex flex-col gap-4">
                <input type="hidden" name="edit_id" id="edit_id" value="">
                <input type="text" name="book_name" id="book_name" placeholder="Book Name" required
                    class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                <input type="text" name="book_author" id="book_author" placeholder="Book Author" required
                    class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                <input type="number" name="copies_available" id="copies_available" placeholder="Copies Available" min="1" required
                    class="px-4 py-2 rounded-lg bg-neutral-700 text-neutral-100 border border-neutral-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                <div class="flex gap-4">
                    <button type="submit" name="save_book"
                            class="bg-green-600 hover:bg-green-500 transition px-4 py-2 rounded-lg font-semibold" id="save_button">
                        Add Book
                    </button>
                    <button type="button" id="cancel_edit"
                            class="bg-gray-600 hover:bg-gray-500 transition px-4 py-2 rounded-lg font-semibold hidden">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Books List -->
        <div class="bg-neutral-800 p-6 rounded-2xl shadow-md overflow-x-auto">
            <h2 class="text-xl font-semibold mb-4">Books List</h2>

            <table class="min-w-full table-auto border-collapse border border-neutral-700">
                <thead>
                    <tr class="bg-neutral-700">
                        <th class="px-4 py-2 border border-neutral-600 text-left">ID</th>
                        <th class="px-4 py-2 border border-neutral-600 text-left">Book Name</th>
                        <th class="px-4 py-2 border border-neutral-600 text-left">Author</th>
                        <th class="px-4 py-2 border border-neutral-600 text-left">Copies</th>
                        <th class="px-4 py-2 border border-neutral-600 text-left">Created By</th>
                        <?php if (in_array($role, ['admin', 'moderator'])): ?>
                            <th class="px-4 py-2 border border-neutral-600 text-left">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($books_result as $book): ?>
                    <tr class="hover:bg-neutral-700">
                        <td class="px-4 py-2 border border-neutral-600"><?= $book['id'] ?></td>
                        <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($book['book_name']) ?></td>
                        <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($book['book_author']) ?></td>
                        <td class="px-4 py-2 border border-neutral-600"><?= $book['copies_available'] ?></td>
                        <td class="px-4 py-2 border border-neutral-600"><?= htmlspecialchars($book['created_by'] ?? 'User Deleted') ?></td>
                        <?php if (in_array($role, ['admin', 'moderator'])): ?>
                            <td class="px-4 py-2 border border-neutral-600 flex gap-2 justify-center">
                                <button type="button" class="bg-green-600 hover:bg-green-500 px-2 py-1 rounded text-sm font-semibold edit-btn"
                                    data-id="<?= $book['id'] ?>"
                                    data-name="<?= htmlspecialchars($book['book_name'], ENT_QUOTES) ?>"
                                    data-author="<?= htmlspecialchars($book['book_author'], ENT_QUOTES) ?>"
                                    data-copies="<?= $book['copies_available'] ?>">
                                    Edit
                                </button>
                                <form method="POST" class="inline">
                                    <button type="submit" name="delete_book" value="<?= $book['id'] ?>"
                                            class="bg-red-600 hover:bg-red-500 px-2 py-1 rounded text-sm font-semibold">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($books_result) === 0): ?>
                    <tr>
                        <td colspan="<?= in_array($role, ['admin', 'moderator']) ? 6 : 5 ?>" class="px-4 py-2 text-center text-gray-400">
                            No books found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="footer"></div>

    <script>
    // Edit button logic
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const author = btn.dataset.author;
            const copies = btn.dataset.copies;

            document.getElementById('edit_id').value = id;
            document.getElementById('book_name').value = name;
            document.getElementById('book_author').value = author;
            document.getElementById('copies_available').value = copies;

            document.getElementById('form_title').textContent = 'Edit Book';
            document.getElementById('save_button').textContent = 'Save';
            document.getElementById('cancel_edit').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    document.getElementById('cancel_edit').addEventListener('click', () => {
        document.getElementById('edit_id').value = '';
        document.getElementById('book_name').value = '';
        document.getElementById('book_author').value = '';
        document.getElementById('copies_available').value = '';
        document.getElementById('form_title').textContent = 'Add New Book';
        document.getElementById('save_button').textContent = 'Add Book';
        document.getElementById('cancel_edit').classList.add('hidden');
    });
    </script>

    </body>
</html>
