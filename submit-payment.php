<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['clientid'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $amount = floatval($_POST['amount']);
    $paymode = $_POST['paymode'];
    $benefit = $_POST['benefit'];
    $clientid = $_SESSION['clientid'];
    
    // Validate data
    if ($amount <= 0) {
        die("Invalid amount value");
    }
    
    // Connect to Oracle
    $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
    
    if (!$conn) {
        $e = oci_error();
        die("Database connection failed: " . htmlentities($e['message']));
    }
    
    // Generate a new payment ID
    $query = "SELECT 'P' || LPAD(NVL(MAX(SUBSTR(PAYMENTID, 2)), 0) + 1, 2, '0') AS NEW_ID FROM PAYMENT";
    $stid = oci_parse($conn, $query);
    
    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        oci_close($conn);
        die("Error generating payment ID: " . htmlentities($e['message']));
    }
    
    $row = oci_fetch_assoc($stid);
    $paymentid = $row['NEW_ID'];
    oci_free_statement($stid);
    
    // Insert the payment
    $query = "INSERT INTO PAYMENT (PAYMENTID, AMOUNT, PAYMODE, BENEFIT, CLIENTID) 
              VALUES (:paymentid, :amount, :paymode, :benefit, :clientid)";
              
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':paymentid', $paymentid);
    oci_bind_by_name($stid, ':amount', $amount);
    oci_bind_by_name($stid, ':paymode', $paymode);
    oci_bind_by_name($stid, ':benefit', $benefit);
    oci_bind_by_name($stid, ':clientid', $clientid);
    
    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        oci_free_statement($stid);
        oci_close($conn);
        die("Error processing payment: " . htmlentities($e['message']));
    }
    
    oci_free_statement($stid);
    oci_close($conn);
    
    // Redirect back to the client dashboard with success message
    $_SESSION['payment_success'] = true;
    header("Location: client-dashboard.php#finance");
    exit();
}

// If not a POST request, redirect to dashboard
header("Location: client-dashboard.php");
exit();
?>