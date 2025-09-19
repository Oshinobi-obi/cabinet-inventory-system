<?php
require_once 'includes/config.php';

echo "<h1>Database Structure Check</h1>";

try {
    // Check if qr_path column exists in cabinets table
    $stmt = $pdo->query("DESCRIBE cabinets");
    $columns = $stmt->fetchAll();
    
    echo "<h2>Cabinets table columns:</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $qrPathExists = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'qr_path') {
            $qrPathExists = true;
        }
    }

    // Check users table for password_changed_at
    echo "<hr>";
    echo "<h2>Users table columns:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    $pwdChangedExists = false;
    foreach ($userColumns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
        if ($column['Field'] === 'password_changed_at') {
            $pwdChangedExists = true;
        }
    }
    echo "</table>";
    if ($pwdChangedExists) {
        echo "<p>✅ password_changed_at column exists in users table</p>";
    } else {
        echo "<p>❌ password_changed_at column does not exist in users table</p>";
        echo "<h3>SQL to add password_changed_at column:</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px;'>ALTER TABLE users ADD COLUMN password_changed_at TIMESTAMP NULL DEFAULT NULL AFTER password;\n</pre>";
        echo "<form method='post'>";
        echo "<button type='submit' name='add_pwd_changed' onclick=\"return confirm('Add password_changed_at column to users?')\">Add password_changed_at Column</button>";
        echo "</form>";
        if (isset($_POST['add_pwd_changed'])) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN password_changed_at TIMESTAMP NULL DEFAULT NULL AFTER password");
                echo "<p>✅ password_changed_at column added successfully! <a href=''>Refresh page</a></p>";
            } catch (Exception $e) {
                echo "<p>❌ Failed to add column: " . $e->getMessage() . "</p>";
            }
        }
    }
    echo "</table>";
    
    if ($qrPathExists) {
        echo "<p>✅ qr_path column exists in cabinets table</p>";
        
        // Show sample data
        $stmt = $pdo->query("SELECT id, cabinet_number, name, qr_path FROM cabinets LIMIT 5");
        $cabinets = $stmt->fetchAll();
        
        echo "<h2>Sample cabinet data:</h2>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Cabinet Number</th><th>Name</th><th>QR Path</th></tr>";
        foreach ($cabinets as $cabinet) {
            echo "<tr>";
            echo "<td>{$cabinet['id']}</td>";
            echo "<td>{$cabinet['cabinet_number']}</td>";
            echo "<td>{$cabinet['name']}</td>";
            echo "<td>" . ($cabinet['qr_path'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>❌ qr_path column does not exist in cabinets table</p>";
        echo "<h2>SQL to add qr_path column:</h2>";
        echo "<pre style='background: #f5f5f5; padding: 10px;'>";
        echo "ALTER TABLE cabinets ADD COLUMN qr_path VARCHAR(255) NULL AFTER photo_path;";
        echo "</pre>";
        
        echo "<p><strong>You need to run this SQL command to add the qr_path column before testing QR code saving.</strong></p>";
        
        // Optionally, auto-run the ALTER TABLE command
        echo "<form method='post'>";
        echo "<button type='submit' name='add_column' onclick='return confirm(\"Are you sure you want to add the qr_path column to the cabinets table?\")'>Add qr_path Column</button>";
        echo "</form>";
        
        if (isset($_POST['add_column'])) {
            try {
                $pdo->exec("ALTER TABLE cabinets ADD COLUMN qr_path VARCHAR(255) NULL AFTER photo_path");
                echo "<p>✅ qr_path column added successfully! <a href=''>Refresh page</a></p>";
            } catch (Exception $e) {
                echo "<p>❌ Failed to add column: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<p><a href="cabinet.php">← Back to Cabinet Management</a></p>
<p><a href="qr_database_test.php">Test QR Database Functionality</a></p>
