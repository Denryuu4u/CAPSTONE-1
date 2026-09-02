<?php
/** Add or edit a customer (from customers.php modals). */
require_once __DIR__ . '/../includes/helpers.php';
require_page('customers', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

$id      = (int) ($_POST['id'] ?? 0);
$name    = trim((string) ($_POST['name'] ?? ''));
$contact = trim((string) ($_POST['contact_person'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$phone   = trim((string) ($_POST['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$industry= trim((string) ($_POST['industry'] ?? ''));

if ($name === '') { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Name is required.']); exit; }

try {
    if ($id > 0) {
        db()->prepare("UPDATE customers SET name=?, contact_person=?, email=?, phone=?, address=?, industry=? WHERE id=?")
            ->execute([$name, $contact ?: null, $email ?: null, $phone ?: null, $address ?: null, $industry ?: null, $id]);
        log_audit('Customers', "Edited customer #{$id}", $name);
    } else {
        db()->prepare("INSERT INTO customers (name, contact_person, email, phone, address, industry) VALUES (?,?,?,?,?,?)")
            ->execute([$name, $contact ?: null, $email ?: null, $phone ?: null, $address ?: null, $industry ?: null]);
        $id = (int) db()->lastInsertId();
        log_audit('Customers', "Added customer #{$id}", $name);
    }
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
