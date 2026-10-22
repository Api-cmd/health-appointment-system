<?php
// ===========================================================
// Migration: Change login from email to phone number
// This script updates the database to use phone as the unique identifier
// instead of email for authentication.
// ===========================================================

require_once 'config.php';

echo "<h2>Migration: Email to Phone Login</h2>";

try {
    // Step 1: Check if phone column is already unique
    echo "<h3>Step 1: Checking current phone column constraints...</h3>";
    $stmt = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'phone'");
    $phoneIndex = $stmt->fetch();
    
    if ($phoneIndex) {
        echo "<p>Phone column already has an index. Skipping index creation.</p>";
    } else {
        // Add unique index on phone column
        echo "<p>Adding unique index on phone column...</p>";
        $pdo->exec("ALTER TABLE users ADD UNIQUE INDEX idx_phone (phone)");
        echo "<p style='color:green'>✓ Unique index on phone added successfully.</p>";
    }
    
    // Step 2: Check if email column is still required/unique
    echo "<h3>Step 2: Modifying email column constraints...</h3>";
    $stmt = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'email'");
    $emailIndex = $stmt->fetch();
    
    if ($emailIndex) {
        echo "<p>Removing unique constraint from email column...</p>";
        $pdo->exec("ALTER TABLE users DROP INDEX email");
        echo "<p style='color:green'>✓ Email unique constraint removed.</p>";
    }
    
    // Make email nullable since it's no longer required for login
    echo "<p>Making email column nullable...</p>";
    $pdo->exec("ALTER TABLE users MODIFY email VARCHAR(255) NULL");
    echo "<p style='color:green'>✓ Email column is now nullable.</p>";
    
    // Step 3: Update login_attempts table to use phone instead of email
    echo "<h3>Step 3: Updating login_attempts table...</h3>";
    
    // Check if phone column exists in login_attempts
    $stmt = $pdo->query("SHOW COLUMNS FROM login_attempts LIKE 'phone'");
    $phoneColumn = $stmt->fetch();
    
    if (!$phoneColumn) {
        echo "<p>Adding phone column to login_attempts table...</p>";
        $pdo->exec("ALTER TABLE login_attempts ADD COLUMN phone VARCHAR(20) NULL AFTER email");
        echo "<p style='color:green'>✓ Phone column added to login_attempts.</p>";
    } else {
        echo "<p>Phone column already exists in login_attempts.</p>";
    }
    
    // Copy existing email data to phone column for migration
    echo "<p>Migrating existing email data to phone column...</p>";
    $pdo->exec("UPDATE login_attempts SET phone = email WHERE phone IS NULL AND email IS NOT NULL");
    echo "<p style='color:green'>✓ Email data migrated to phone column.</p>";
    
    // Make email nullable in login_attempts
    echo "<p>Making email column nullable in login_attempts...</p>";
    $pdo->exec("ALTER TABLE login_attempts MODIFY email VARCHAR(255) NULL");
    echo "<p style='color:green'>✓ Email column in login_attempts is now nullable.</p>";
    
    echo "<h3 style='color:green'>Migration completed successfully!</h3>";
    echo "<p>The database is now configured to use phone numbers for login instead of email.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Update the PHP backend files to use phone for authentication</li>";
    echo "<li>Update the frontend forms to use phone instead of email</li>";
    echo "<li>Test the registration and login functionality</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Migration failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check the error and fix any issues before proceeding.</p>";
}
?>
