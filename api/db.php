<?php

// DB Credentials, change this for production in your hosting panel
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "borrow_book_monitor_db";
$db_port = 3306;

/*
|--------------------------------------------------------------------------
| Custom ID / UUID-style generator
|--------------------------------------------------------------------------
|
| Generates a random string based on a pattern.
| Not a true UUID v4 and NOT cryptographically secure.
|
| Pattern symbols:
|  - 'N' → random digit (0-9)
|  - 'A' → random lowercase letter (a-z)
|  - 'X' → random digit or lowercase letter (0-9, a-z)
|  - Any other character → preserved as-is (e.g., dashes, underscores)
|
| Default pattern: "XXXX-XXXX-XX"
|
| Example usage:
|  generate_uuid_v4_manual();          // e.g., "a3k9-4d7f-x2"
|  generate_uuid_v4_manual("NNAA-XX"); // e.g., "47bg-a3"
|
*/
function generate_uuid_v4_manual(string $pattern = "XXXX-XXXX-XX"): string {
    $result = '';

    for ($i = 0; $i < strlen($pattern); $i++) {
        $char = $pattern[$i];
        if ($char === 'N') {
            $result .= (string) mt_rand(0, 9);
        } elseif ($char === 'A') {
            $result .= chr(mt_rand(97, 122)); // a-z
        } elseif ($char === 'X') {
            // Either number or lowercase letter
            if (mt_rand(0, 1) === 0) {
                $result .= (string) mt_rand(0, 9);
            } else {
                $result .= chr(mt_rand(97, 122));
            }
        } else {
            // Preserve fixed character (dash, underscore, etc.)
            $result .= $char;
        }
    }

    return $result;
}

/*
|--------------------------------------------------------------------------
| transactionalMySQLQuery
|--------------------------------------------------------------------------
|
| Executes a single MySQL query inside a transaction, similar to pool.query in PostgreSQL.
|
| Features:
|  - Supports SELECT, INSERT, UPDATE, DELETE
|  - Prepared statements automatically if parameters are provided
|  - Each query runs inside a transaction and commits or rolls back automatically
|  - Prevents multi-statement queries
|
| Usage:
|
| SELECT:
|   $result = transactionalMySQLQuery(
|       "SELECT * FROM users WHERE username = ?",
|       ["johndoe"]
|   );
|   if (is_string($result)) echo "Error: $result";
|   else print_r($result);
|
| INSERT / UPDATE / DELETE:
|   $result = transactionalMySQLQuery(
|       "INSERT INTO users (first_name, last_name) VALUES (?, ?)",
|       ["John", "Doe"]
|   );
|   if ($result === true) echo "Success!";
|   else echo "Error: $result";
|
*/
function transactionalMySQLQuery(string $query, array $params = []) {
    global $db_host, $db_user, $db_pass, $db_name, $db_port;

    // Prevent multiple SQL statements
    if (substr_count(trim($query), ";") > 1) {
        return "Only one SQL statement is allowed per query.";
    }

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    if ($mysqli->connect_errno) {
        return "Connection failed: " . $mysqli->connect_error;
    }

    try {
        // Decide if transaction is needed (skip for simple SELECT)
        $is_select = preg_match('/^\s*SELECT/i', $query);
        if (!$is_select) {
            $mysqli->begin_transaction();
        }

        if (!empty($params)) {
            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }

            // Bind types automatically
            $types = "";
            foreach ($params as $p) {
                if (is_int($p)) $types .= "i";
                elseif (is_float($p)) $types .= "d";
                else $types .= "s";
            }

            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Execution failed: " . $stmt->error);
            }

            if ($is_select) {
                $res = $stmt->get_result();
                $data = $res->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                $mysqli->close();
                return $data;
            }

            $stmt->close();
            $mysqli->commit();
            $mysqli->close();
            return true;

        } else {
            $res = $mysqli->query($query);
            if ($res === false) {
                throw new Exception($mysqli->error);
            }

            if ($res === true) {
                if (!$is_select) $mysqli->commit();
                $mysqli->close();
                return true;
            }

            $data = $res->fetch_all(MYSQLI_ASSOC);
            $res->free();
            $mysqli->close();
            return $data;
        }

    } catch (Exception $e) {
        if (!$is_select) $mysqli->rollback();
        $mysqli->close();
        return "Query error: " . $e->getMessage();
    }
}


// --------------------------------------------------------------
// Auto create first admin account if no admin exists

$check_admin = transactionalMySQLQuery(
    "SELECT COUNT(*) AS total FROM system_users WHERE role = 'admin'"
);

if (is_string($check_admin)) {
    die("<h1>Error checking admin users: $check_admin</h1>");
}

// If no admin exists, create the default admin
if ((int)$check_admin[0]['total'] === 0) {
    $create_admin = transactionalMySQLQuery(
        "INSERT INTO system_users (first_name, last_name, role, username, password)
         VALUES (?, ?, ?, ?, ?)",
        ["System", "Admin", "admin", "admin", password_hash("1234", PASSWORD_DEFAULT)]
    );

    if ($create_admin !== true) {
        die("<h1>Failed to create admin account: $create_admin</h1>");
    }
}
