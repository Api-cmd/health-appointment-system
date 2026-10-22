<?php
// ===========================================================
// php/register.php
// Handles new patient self-registration.
//
// CALLED BY: pages/register.html form (action="../php/register.php")
// METHOD: POST only
// RETURNS: redirects to index.html with a message in the URL
// ===========================================================

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.html');
    exit();
}

// --- Collect and clean inputs ---
$full_name = clean($_POST['full_name'] ?? '');
$phone     = clean($_POST['phone']     ?? '');
$email     = clean($_POST['email']     ?? '');  // Optional now
$password  =       $_POST['password']  ?? '';  // don't strip from password
$confirm   =       $_POST['confirm']   ?? '';

$errors = [];

// --- Validate each field ---

// Name: required, at least 2 characters
if (strlen($full_name) < 2) {
    $errors[] = 'Full name is required.';
}

// Email: optional, but if provided must be valid
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address or leave it blank.';
}

// Phone: digits, spaces, dashes, and + allowed. Min 9 characters.
// This matches Tanzanian numbers like +255712345678 or 0712345678
if (!preg_match('/^[+\d\s\-]{9,20}$/', $phone)) {
    $errors[] = 'Enter a valid phone number (e.g. +255712345678).';
}

// Password: minimum 8 characters
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

// Confirm: must match password
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}

// --- Check if phone is already registered ---
// We only do this check if other validation passed,
// to avoid unnecessary database queries.
if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        $errors[] = 'That phone number is already registered.';
    }
}

// --- If any validation failed, send errors back to the form ---
if (!empty($errors)) {
    // Encode errors as a URL-safe string and pass them back
    $encoded = urlencode(implode('|', $errors));
    header("Location: ../pages/register.html?errors={$encoded}");
    exit();
}

// --- Hash the password before saving ---
// PASSWORD_DEFAULT uses bcrypt, which is a strong one-way hash.
// It automatically includes a random "salt" to prevent rainbow table attacks.
// Each time you hash the same password, you get a different hash,
// but password_verify() still knows they match.
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// --- Insert the new user into the database ---
$stmt = $pdo->prepare("
    INSERT INTO users (full_name, phone, email, password, role)
    VALUES (:name, :phone, :email, :password, 'patient')
");
$stmt->execute([
    ':name'     => $full_name,
    ':phone'    => $phone,
    ':email'    => $email ?: 'no-email@hams.local',
    ':password' => $hashed_password,
]);

// Get the newly created user ID
$user_id = $pdo->lastInsertId();

// Create session for auto sign-in
$_SESSION['user_id']   = $user_id;
$_SESSION['user_name'] = $full_name;
$_SESSION['user_role'] = 'patient';
$_SESSION['user_phone'] = $phone;
$_SESSION['login_ip']   = $_SERVER['REMOTE_ADDR'];

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Redirect to patient dashboard
header('Location: ../pages/dashboard.html');
exit();
?>