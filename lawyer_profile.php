<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['lawyerid'])) {
    header("Location: login.php");
    exit();
}

$lawyerid = $_SESSION['lawyerid'];

// Connect to Oracle
$conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');

if (!$conn) {
    $e = oci_error();
    echo 'Database connection failed: ' . htmlentities($e['message']);
    exit;
}

// Get lawyer data
$query = "SELECT l.lawyerid, l.lawfirm, l.lphone, l.uemail, l.spec, l.benefit, 
          n.fname, n.mname, n.lname
          FROM LAWYER l
          JOIN NAME n ON l.uemail = n.uemail 
          WHERE l.lawyerid = :lawyerid";

$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ':lawyerid', $lawyerid);
$success = oci_execute($stid);

if (!$success) {
    $e = oci_error($stid);
    echo 'Query failed: ' . htmlentities($e['message']);
    exit;
}

$lawyer = oci_fetch_assoc($stid);

if (!$lawyer) {
    echo 'No lawyer profile found!';
    exit;
}

oci_free_statement($stid);
oci_close($conn);
?>