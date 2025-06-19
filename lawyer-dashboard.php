<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['lawyerid'])) {
    header("Location: login.php");
    exit();
}

// Include the lawyer profile data
include 'lawyer_profile.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lawyer Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark ">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="images/compliant.png" alt="LegalEase Logo" width="50" height="50" class="me-2">
            <span>LegalEase Lawyer</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#clients">Clients and Cases</a></li>
                <li class="nav-item"><a class="nav-link" href="#schedule">Schedule</a></li>
                
                <li class="nav-item"><a class="nav-link" href="#finance">Finance</a></li>
                <li class="nav-item"><a class="nav-link" href="#profile">Profile</a></li>
                <li class="nav-item"><a class="nav-link btn btn-danger text-white" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<p></p>

<div class="bg-image-container">
    <div class="overlay">
        <h2 id="typing-text">Welcome <?php echo htmlspecialchars($lawyer['FNAME']); ?></h2> 
        <p>Manage your work with LegalEase!</p>
    </div>
</div>

<!-- Clients and Cases Section -->
<!-- Clients and Cases Section -->
<!-- Clients and Cases Section -->
<section id="clients" class="container mt-5">
    <h2>Clients and Cases</h2>
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Your Cases</h5>
        </div>
        <div class="card-body">
            <p class="card-text">View all your active and past cases.</p>
            
            <?php
            // Connect to Oracle
            $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
            
            if (!$conn) {
                $e = oci_error();
                echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
            } else {
                // Get the lawyer's benefit value for joining with cases
                $benefit = $lawyer['BENEFIT'];
                
                // Query to get cases where the lawyer is involved
                // Using DISTINCT to avoid duplicates
                $query = "SELECT c.CASEID, c.CLIENTID, c.TYPE, c.STATUS, 
                          TO_CHAR(c.FILINGDATE, 'YYYY-MM-DD') AS FILINGDATE,
                          n.FNAME || CASE WHEN n.MNAME IS NOT NULL THEN ' ' || n.MNAME ELSE '' END || ' ' || n.LNAME AS CLIENT_NAME,
                          cl.CPHONE, cl.UEMAIL
                          FROM CASES c
                          JOIN CLIENT cl ON c.CLIENTID = cl.CLIENTID
                          JOIN NAME n ON cl.UEMAIL = n.UEMAIL
                          JOIN PAYMENT p ON cl.CLIENTID = p.CLIENTID
                          WHERE p.BENEFIT = :benefit
                          ORDER BY c.STATUS, c.FILINGDATE DESC";
                
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ':benefit', $benefit);
                $success = oci_execute($stid);
                
                if (!$success) {
                    $e = oci_error($stid);
                    echo '<div class="alert alert-danger">Query failed: ' . htmlentities($e['message']) . '</div>';
                } else {
                    // Buffer the results
                    $cases = array();
                    
                    while ($row = oci_fetch_assoc($stid)) {
                        // Create a unique key for each case to avoid duplicates
                        $uniqueKey = $row['CASEID'] . '-' . $row['CLIENTID'];
                        if (!isset($cases[$uniqueKey])) {
                            $cases[$uniqueKey] = $row;
                        }
                    }
                    
                    if (count($cases) > 0) {
                        // Display cases in a table
                        echo '<div class="table-responsive">
                              <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Case ID</th>
                                        <th scope="col">Client Name</th>
                                        <th scope="col">Contact Info</th>
                                        <th scope="col">Case Type</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Filing Date</th>
                                    </tr>
                                </thead>
                                <tbody>';
                        
                        foreach ($cases as $case) {
                            // Determine badge color based on status
                            $statusClass = '';
                            switch ($case['STATUS']) {
                                case 'Open':
                                    $statusClass = 'bg-success';
                                    break;
                                case 'Closed':
                                    $statusClass = 'bg-secondary';
                                    break;
                                case 'Pending':
                                    $statusClass = 'bg-warning text-dark';
                                    break;
                                default:
                                    $statusClass = 'bg-info';
                            }
                            
                            echo '<tr>
                                <td>' . htmlspecialchars($case['CASEID']) . '</td>
                                <td>' . htmlspecialchars($case['CLIENT_NAME']) . '<br>
                                    <small class="text-muted">ID: ' . htmlspecialchars($case['CLIENTID']) . '</small></td>
                                <td><i class="bi bi-envelope"></i> ' . htmlspecialchars($case['UEMAIL']) . '<br>
                                    <i class="bi bi-telephone"></i> ' . htmlspecialchars($case['CPHONE']) . '</td>
                                <td>' . htmlspecialchars($case['TYPE']) . '</td>
                                <td><span class="badge ' . $statusClass . '">' . htmlspecialchars($case['STATUS']) . '</span></td>
                                <td>' . htmlspecialchars($case['FILINGDATE']) . '</td>
                            </tr>';
                        }
                        
                        echo '</tbody>
                            </table>
                          </div>';
                        
                        // Add case statistics cards
                        $openCases = count(array_filter($cases, function($case) { return $case['STATUS'] === 'Open'; }));
                        $closedCases = count(array_filter($cases, function($case) { return $case['STATUS'] === 'Closed'; }));
                        $pendingCases = count(array_filter($cases, function($case) { return $case['STATUS'] === 'Pending'; }));
                        
                        echo '<div class="row mt-4">
                            <div class="col-md-3">
                                <div class="card text-white bg-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Cases</h5>
                                        <h3>' . count($cases) . '</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Open Cases</h5>
                                        <h3>' . $openCases . '</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Pending Cases</h5>
                                        <h3>' . $pendingCases . '</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Closed Cases</h5>
                                        <h3>' . $closedCases . '</h3>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    } else {
                        echo '<div class="alert alert-info">You have no assigned cases at this time.</div>';
                    }
                }
                
                oci_free_statement($stid);
                oci_close($conn);
            }
            ?>
        </div>
    </div>
</section>

<!-- Schedule Section -->
<!-- Schedule Section -->
<section id="schedule" class="container mt-5">
    <h2>Schedule</h2>
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Upcoming Hearings</h5>
        </div>
        <div class="card-body">
            <p class="card-text">Track your upcoming court hearings and client meetings.</p>
            
            <?php
            // Connect to Oracle
            $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
            
            if (!$conn) {
                $e = oci_error();
                echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
            } else {
                // Get the lawyer's benefit value for joining with cases
                $benefit = $lawyer['BENEFIT'];
                
                // Query to get schedule for cases where the lawyer is involved
                // This joins SCHEDULE with CASES and CLIENT tables
                // Then filters by the lawyer's benefit matching payment benefit
                $query = "SELECT distinct s.CASEID, s.CLIENTID, c.TYPE, s.HEARINGDATE, s.TIME,
                          n.FNAME || CASE WHEN n.MNAME IS NOT NULL THEN ' ' || n.MNAME ELSE '' END || ' ' || n.LNAME AS CLIENT_NAME,
                          c.STATUS
                          FROM SCHEDULE s
                          JOIN CASES c ON s.CASEID = c.CASEID AND s.CLIENTID = c.CLIENTID
                          JOIN CLIENT cl ON s.CLIENTID = cl.CLIENTID
                          JOIN NAME n ON cl.UEMAIL = n.UEMAIL
                          JOIN PAYMENT p ON cl.CLIENTID = p.CLIENTID
                          WHERE p.BENEFIT = :benefit
                          ORDER BY s.HEARINGDATE ASC, s.TIME ASC";
                
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ':benefit', $benefit);
                $success = oci_execute($stid);
                
                if (!$success) {
                    $e = oci_error($stid);
                    echo '<div class="alert alert-danger">Query failed: ' . htmlentities($e['message']) . '</div>';
                } else {
                    // Check if any schedules exist
                    $hasRows = false;
                    
                    // Buffer the results to check if we have any rows
                    $schedules = array();
                    while ($row = oci_fetch_assoc($stid)) {
                        $schedules[] = $row;
                        $hasRows = true;
                    }
                    
                    if ($hasRows) {
                        // Display schedules in a table
                        echo '<div class="table-responsive">
                              <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Case ID</th>
                                        <th scope="col">Client</th>
                                        <th scope="col">Case Type</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Hearing Date</th>
                                        <th scope="col">Time</th>
                                    </tr>
                                </thead>
                                <tbody>';
                        
                        foreach ($schedules as $schedule) {
                            // Format the date from Oracle format to more readable format
                            $hearingDate = date('F j, Y', strtotime($schedule['HEARINGDATE']));
                            
                            // Determine badge color based on status
                            $statusClass = '';
                            switch ($schedule['STATUS']) {
                                case 'Open':
                                    $statusClass = 'bg-success';
                                    break;
                                case 'Closed':
                                    $statusClass = 'bg-secondary';
                                    break;
                                case 'Pending':
                                    $statusClass = 'bg-warning text-dark';
                                    break;
                                default:
                                    $statusClass = 'bg-info';
                            }
                            
                            echo '<tr>
                                <td>' . htmlspecialchars($schedule['CASEID']) . '</td>
                                <td>' . htmlspecialchars($schedule['CLIENT_NAME']) . ' (ID: ' . htmlspecialchars($schedule['CLIENTID']) . ')</td>
                                <td>' . htmlspecialchars($schedule['TYPE']) . '</td>
                                <td><span class="badge ' . $statusClass . '">' . htmlspecialchars($schedule['STATUS']) . '</span></td>
                                <td>' . $hearingDate . '</td>
                                <td>' . htmlspecialchars($schedule['TIME']) . '</td>
                            </tr>';
                        }
                        
                        echo '</tbody>
                            </table>
                          </div>';
                       
                    } else {
                        echo '<div class="alert alert-info">You have no scheduled hearings at this time.</div>';
                    }
                }
                
                oci_free_statement($stid);
                oci_close($conn);
            }
            ?>
            
           
        </div>
    </div>
</section>


<!-- Finance Section -->
<!-- Finance Section - Updated with Payments Table -->
<section id="finance" class="container mt-5">
    <h2>Finance</h2>
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Payment Records</h5>
        </div>
        <div class="card-body">
            <p>Track payments received from clients.</p>
            
            <?php
            // Connect to Oracle
            $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
            
            if (!$conn) {
                $e = oci_error();
                echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
            } else {
                // Get the lawyer's benefit value
                $benefit = $lawyer['BENEFIT'];
                
                // Query to get payments for the logged-in lawyer based on their benefit
                $query = "SELECT p.PAYMENTID, p.AMOUNT, p.PAYMODE, c.CLIENTID, 
                          n.FNAME || CASE WHEN n.MNAME IS NOT NULL THEN ' ' || n.MNAME ELSE '' END || ' ' || n.LNAME AS CLIENT_NAME
                          FROM PAYMENT p
                          JOIN CLIENT c ON p.CLIENTID = c.CLIENTID
                          JOIN NAME n ON c.UEMAIL = n.UEMAIL
                          WHERE p.BENEFIT = :benefit
                          ORDER BY p.PAYMENTID";
                
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ':benefit', $benefit);
                $success = oci_execute($stid);
                
                if (!$success) {
                    $e = oci_error($stid);
                    echo '<div class="alert alert-danger">Query failed: ' . htmlentities($e['message']) . '</div>';
                } else {
                    // Buffer the results
                    $payments = array();
                    $total_amount = 0;
                    
                    while ($row = oci_fetch_assoc($stid)) {
                        $payments[] = $row;
                        $total_amount += $row['AMOUNT'];
                    }
                    
                    if (count($payments) > 0) {
                        // Display payments in a table
                        echo '<div class="table-responsive">
                              <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Payment ID</th>
                                        <th scope="col">Client</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Payment Mode</th>
                                    </tr>
                                </thead>
                                <tbody>';
                        
                        foreach ($payments as $payment) {
                            echo '<tr>
                                <td>' . htmlspecialchars($payment['PAYMENTID']) . '</td>
                                <td>' . htmlspecialchars($payment['CLIENT_NAME']) . ' (ID: ' . htmlspecialchars($payment['CLIENTID']) . ')</td>
                                <td>$' . number_format($payment['AMOUNT'], 2) . '</td>
                                <td>' . htmlspecialchars($payment['PAYMODE']) . '</td>
                            </tr>';
                        }
                        
                        echo '</tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <td colspan="2" class="text-end"><strong>Total Revenue:</strong></td>
                                    <td><strong>$' . number_format($total_amount, 2) . '</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            </table>
                          </div>';
                          
                        // Add payment statistics
                        echo '<div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card text-white bg-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Revenue</h5>
                                        <h3>$' . number_format($total_amount, 2) . '</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-white bg-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Payments</h5>
                                        <h3>' . count($payments) . '</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-white bg-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Average Payment</h5>
                                        <h3>$' . number_format($total_amount / count($payments), 2) . '</h3>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    } else {
                        echo '<div class="alert alert-info">No payment records found.</div>';
                    }
                }
                
                oci_free_statement($stid);
                oci_close($conn);
            }
            ?>
        </div>
    </div>
</section>

<!-- Profile Section -->
<section id="profile" class="container mt-5">
    <h2>Profile</h2>
    <div class="card p-4 d-flex flex-row align-items-center shadow-lg">
        <img src="images/lawyer.png" alt="Profile Picture" class="rounded-circle me-4" width="150" height="150">
        <div>
            <h4>
                <?php 
                    // Construct full name from the NAME table data
                    $fullName = $lawyer['FNAME'];
                    if (!empty($lawyer['MNAME'])) {
                        $fullName .= ' ' . $lawyer['MNAME'];
                    }
                    $fullName .= ' ' . $lawyer['LNAME'];
                    echo htmlspecialchars($fullName);
                ?>
            </h4>
            <p><strong>Lawyer ID:</strong> <span class="text-primary">#<?php echo $lawyer['LAWYERID']; ?></span></p>
            <p><strong>Email:</strong> <?php echo $lawyer['UEMAIL']; ?></p>
            <p><strong>Phone:</strong> <?php echo $lawyer['LPHONE']; ?></p>
            <p><strong>Law Firm:</strong> <?php echo $lawyer['LAWFIRM']; ?></p>
            <p><strong>Specialization:</strong> <?php echo $lawyer['SPEC']; ?></p>
            <p><strong>Benefit:</strong> <?php echo $lawyer['BENEFIT']; ?></p>
        </div>
    </div>
</section>
<p></p>
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#clients" class="text-light">Clients and Cases</a></li>
                    <li><a href="#schedule" class="text-light">Schedule</a></li>
                    <li><a href="#finance" class="text-light">Finance</a></li>
                    <li><a href="#profile" class="text-light">Profile</a></li>
                </ul>
            </div>

            <div class="col-md-4 text-center">
                <h5>Follow Us</h5>
                <a href="https://www.facebook.com/" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                <a href="https://twitter.com/" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                <a href="https://www.instagram.com/" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                <a href="https://www.linkedin.com/" class="text-white"><i class="bi bi-linkedin"></i></a>
            </div>

            <div class="col-md-4 text-end">
                <h5>Contact Us</h5>
                <p>Email: support@legalease.com</p>
                <p>Phone: +1 (555) 123-4567</p>
                <p>Address: 123 Legal Street, Justice City</p>
            </div>
        </div>
        <hr class="bg-light">
        <div class="text-center">
            <p class="mb-0">&copy; 2025 LegalEase. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>