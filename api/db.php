<?php

// DB Credentials, change this for production in your hosting panel
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "borrow_book_monitor_db";
$db_port = 3306;

/*

    This db.php file contains 2 essential functions for database management and database credentials:
    1. generate_uuid_v4_manual - Generates a UUID v4–style string (not cryptographically secure)
    2. transactionalMySQLQuery - Similar to pool.query in PostgreSQL. 
       It opens a connection, starts a transaction, executes ONE query, then commits or rolls back automatically.

    ------------------------------------------------------------------
    SELECT Usage:
    - Returns an array of rows on success
    - Returns an error string on failure

        $result = transactionalMySQLQuery(
            "SELECT * FROM users WHERE username = ?",
            ["johndoe"]
        );

        if (is_string($result)) {
            echo "Error: $result";
        } else {
            print_r($result);
        }

    ------------------------------------------------------------------
    INSERT / UPDATE / DELETE Usage:
    - Returns true on success
    - Returns an error string on failure

        $result = transactionalMySQLQuery(
            "INSERT INTO users (first_name, last_name, username, password)
             VALUES (?, ?, ?, ?)",
            ["John", "Doe", "johndoe", password_hash("password123", PASSWORD_DEFAULT)]
        );

        if ($result === true) {
            echo "Insert successful!";
        } else {
            echo "Error: $result";
        }

    ------------------------------------------------------------------
    NOTES:
    - This function supports ONLY ONE SQL statement per call
    - Multiple statements (multi-query) are intentionally NOT supported
    - Prepared statements are used automatically when parameters are provided
    - Each call is wrapped in a database transaction
    - For multiple queries, call this function multiple times or implement
      a dedicated transaction helper

*/

// UUID generator (manual, NOT cryptographically secure)
function generate_uuid_v4_manual() {
    $randomHex = function ($length) {
        $hex = '';
        for ($i = 0; $i < $length; $i++) {
            $hex .= dechex(mt_rand(0, 15));
        }
        return $hex;
    };

    return sprintf(
        '%s-%s-4%s-%s%s-%s',
        $randomHex(8),
        $randomHex(4),
        $randomHex(3),
        dechex(mt_rand(8, 11)),
        $randomHex(3),
        $randomHex(12)
    );
}

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
        $mysqli->begin_transaction();

        if (!empty($params)) {
            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }

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

            if (stripos(trim($query), "SELECT") === 0) {
                $res = $stmt->get_result();
                $data = $res->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                $mysqli->commit();
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
                $mysqli->commit();
                $mysqli->close();
                return true;
            }

            $data = $res->fetch_all(MYSQLI_ASSOC);
            $res->free();
            $mysqli->commit();
            $mysqli->close();
            return $data;
        }

    } catch (Exception $e) {
        $mysqli->rollback();
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
    die("Error checking admin users: $check_admin");
}

// If no admin exists, create the default admin
if ((int)$check_admin[0]['total'] === 0) {
    $create_admin = transactionalMySQLQuery(
        "INSERT INTO system_users (first_name, last_name, role, username, password)
         VALUES (?, ?, ?, ?, ?)",
        ["System", "Admin", "admin", "admin", password_hash("1234", PASSWORD_DEFAULT)]
    );

    if ($create_admin !== true) {
        die("Failed to create admin account: $create_admin");
    }
}
