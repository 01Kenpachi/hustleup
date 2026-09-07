<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hustleup_db');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function set_flash($message, $tone = 'good')
{
    $_SESSION['flash'] = array('message' => $message, 'tone' => $tone);
}

function get_flash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function set_old($data)
{
    unset($data['password']);
    $_SESSION['old'] = $data;
}

function take_old()
{
    $old = isset($_SESSION['old']) ? $_SESSION['old'] : array();
    unset($_SESSION['old']);

    return $old;
}

function redirect($target)
{
    header('Location: ' . $target);
    exit;
}

function logged_in_campus_id()
{
    return isset($_SESSION['campus_id']) ? (int) $_SESSION['campus_id'] : 0;
}

function logged_in_role()
{
    return isset($_SESSION['role']) ? $_SESSION['role'] : '';
}

function current_student()
{
    global $conn;

    $campus_id = logged_in_campus_id();

    if ($campus_id === 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT campus_id, name, city, department, cgpa, semester FROM students WHERE campus_id = ?');
    $stmt->bind_param('i', $campus_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? $row : null;
}

function current_teacher()
{
    global $conn;

    $campus_id = logged_in_campus_id();

    if ($campus_id === 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT teacher_id, campus_id, name, teaching_skill, semester, contact_number, qualification FROM teachers WHERE campus_id = ?');
    $stmt->bind_param('i', $campus_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? $row : null;
}
