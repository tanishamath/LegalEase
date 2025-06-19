<?php
session_start();
$error_message = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connection to Oracle
    $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
    
    if (!$conn) {
        $e = oci_error();
        $error_message = "Database connection failed: " . htmlentities($e['message']);
    } else {
        // Get form data
        $email = $_POST['email'];
        $password = $_POST['password']; // In a real app, this would be hashed
        
        // First check if it's a client
        $query = "SELECT CLIENTID, UEMAIL FROM CLIENT WHERE UEMAIL = :email";
        $stid = oci_parse($conn, $query);
        oci_bind_by_name($stid, ':email', $email);
        $success = oci_execute($stid);
        
        if (!$success) {
            $e = oci_error($stid);
            $error_message = "Query failed: " . htmlentities($e['message']);
        } else {
            $client = oci_fetch_assoc($stid);
            
            if ($client) {
                // Client login successful
                $_SESSION['user_type'] = 'client';
                $_SESSION['clientid'] = $client['CLIENTID'];
                $_SESSION['email'] = $client['UEMAIL'];
                
                // Redirect to client dashboard
                header("Location: client-dashboard.php");
                exit();
            } else {
                // Check if it's a lawyer
                oci_free_statement($stid);
                $query = "SELECT LAWYERID, UEMAIL FROM LAWYER WHERE UEMAIL = :email";
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ':email', $email);
                $success = oci_execute($stid);
                
                if (!$success) {
                    $e = oci_error($stid);
                    $error_message = "Query failed: " . htmlentities($e['message']);
                } else {
                    $lawyer = oci_fetch_assoc($stid);
                    
                    if ($lawyer) {
                        // Lawyer login successful
                        $_SESSION['user_type'] = 'lawyer';
                        $_SESSION['lawyerid'] = $lawyer['LAWYERID'];
                        $_SESSION['email'] = $lawyer['UEMAIL'];
                        
                        // Redirect to lawyer dashboard
                        header("Location: lawyer-dashboard.php");
                        exit();
                    } else {
                        // Check if it's a judge
                        oci_free_statement($stid);
                        $query = "SELECT JUDGEID, UEMAIL FROM JUDGE WHERE UEMAIL = :email";
                        $stid = oci_parse($conn, $query);
                        oci_bind_by_name($stid, ':email', $email);
                        $success = oci_execute($stid);
                        
                        if (!$success) {
                            $e = oci_error($stid);
                            $error_message = "Query failed: " . htmlentities($e['message']);
                        } else {
                            $judge = oci_fetch_assoc($stid);
                            
                            if ($judge) {
                                // Judge login successful
                                $_SESSION['user_type'] = 'judge';
                                $_SESSION['judgeid'] = $judge['JUDGEID'];
                                $_SESSION['email'] = $judge['UEMAIL'];
                                
                                // Redirect to judge dashboard
                                header("Location: judge-dashboard.php");
                                exit();
                            } else {
                                $error_message = "Invalid email or password";
                            }
                        }
                    }
                }
            }
        }
        
        oci_free_statement($stid);
        oci_close($conn);
    }
}
?>