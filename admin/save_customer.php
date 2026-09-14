<?php
/** Add or edit a customer (from customers.php modals and the walk-in flow).
 *  When a password is supplied on a NEW customer, a usable Client login account
 *  is created and linked, so walk-in customers can sign in. */
require_once __DIR__ . '/../includes/helpers.php';
require_page('customers', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

function sc_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

$id       = (int) ($_POST['id'] ?? 0);
$name     = trim((string) ($_POST['name'] ?? ''));
$contact  = trim((string) ($_POST['contact_person'] ?? ''));
$email    = trim((string) ($_POST['email'] ?? ''));
$phone    = trim((string) ($_POST['phone'] ?? ''));
$address  = trim((string) ($_POST['address'] ?? ''));
$industry = trim((string) ($_POST['industry'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($name === '') sc_fail('Name is required.');

$pdo = db();
try {
    if ($id > 0) {
        $pdo->prepare("UPDATE customers SET name=?, contact_person=?, email=?, phone=?, address=?, industry=? WHERE id=?")
            ->execute([$name, $contact ?: null, $email ?: null, $phone ?: null, $address ?: null, $industry ?: null, $id]);
        log_audit('Customers', "Edited customer #{$id}", $name);
        echo json_encode(['ok' => true, 'id' => $id]);
        exit;
    }

    // New customer WITH a login account (walk-in flow supplies a password).
    if ($password !== '') {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) sc_fail('A valid email is required to create a login.');
        if (strlen($password) < 8) sc_fail('Password must be at least 8 characters.');

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO users (full_name, email, phone, location, role, status, password_hash, email_verified)
                 VALUES (?,?,?,?, 'Client', 'Active', ?, 1)"
            )->execute([$name, $email, $phone ?: null, $address ?: null, password_hash($password, PASSWORD_BCRYPT)]);
            $userId = (int) $pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO customers (user_id, name, contact_person, email, phone, address, industry)
                 VALUES (?,?,?,?,?,?,?)"
            )->execute([$userId, $name, $contact ?: null, $email ?: null, $phone ?: null, $address ?: null, $industry ?: null]);
            $id = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e->getCode() === '23000') sc_fail('That email is already in use.', 409);
            throw $e;
        }
        log_audit('Customers', "Added customer #{$id} with login account", $name);
        echo json_encode(['ok' => true, 'id' => $id, 'account' => true]);
        exit;
    }

    // New customer, record only (no login) — the customers.php quick-add path.
    $pdo->prepare("INSERT INTO customers (name, contact_person, email, phone, address, industry) VALUES (?,?,?,?,?,?)")
        ->execute([$name, $contact ?: null, $email ?: null, $phone ?: null, $address ?: null, $industry ?: null]);
    $id = (int) $pdo->lastInsertId();
    log_audit('Customers', "Added customer #{$id}", $name);
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23000') sc_fail('That email is already in use.', 409);
    sc_fail('Server error: ' . $e->getMessage(), 500);
}
