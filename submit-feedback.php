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
    $rating = floatval($_POST['rating']);
    $description = $_POST['description'];
    $clientid = $_SESSION['clientid'];
    $today = date('Y-m-d'); // Current date
    
    // Validate data
    if ($rating < 1 || $rating > 10) {
        die("Invalid rating value");
    }
    
    if (strlen($description) > 30) {
        die("Description too long");
    }
    
    // Connect to Oracle
    $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
    
    if (!$conn) {
        $e = oci_error();
        die("Database connection failed: " . htmlentities($e['message']));
    }
    
    // Generate a new feedback ID
    $query = "SELECT 'F' || LPAD(NVL(MAX(SUBSTR(FEEDBACKID, 2)), 0) + 1, 2, '0') AS NEW_ID FROM FEEDBACK";
    $stid = oci_parse($conn, $query);
    
    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        oci_close($conn);
        die("Error generating feedback ID: " . htmlentities($e['message']));
    }
    
    $row = oci_fetch_assoc($stid);
    $feedbackid = $row['NEW_ID'];
    oci_free_statement($stid);
    
    // Insert the feedback
    $query = "INSERT INTO FEEDBACK (FEEDBACKID, RATING, FEEDBACKDATE, CLIENTID, DESCRIPTION) 
              VALUES (:feedbackid, :rating, TO_DATE(:feedbackdate, 'YYYY-MM-DD'), :clientid, :description)";
              
    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ':feedbackid', $feedbackid);
    oci_bind_by_name($stid, ':rating', $rating);
    oci_bind_by_name($stid, ':feedbackdate', $today);
    oci_bind_by_name($stid, ':clientid', $clientid);
    oci_bind_by_name($stid, ':description', $description);
    
    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        oci_free_statement($stid);
        oci_close($conn);
        die("Error submitting feedback: " . htmlentities($e['message']));
    }
    
    oci_free_statement($stid);
    oci_close($conn);
    
    // Redirect back to the client dashboard with success message
    $_SESSION['feedback_success'] = true;
    header("Location: client-dashboard.php#feedback");
    exit();
}

// If not a POST request, redirect to dashboard
header("Location: client-dashboard.php");
exit();
?>