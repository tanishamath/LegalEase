<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['clientid'])) {
    header("Location: login.php");
    exit();
}

// Use the clientid from session 
$clientid = $_SESSION['clientid'];

// Include the client profile data
include 'client_profile.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark ">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="images/compliant.png" alt="LegalEase Logo" width="50" height="50" class="me-2">
            <span>LegalEase Client</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#cases">Cases</a></li>
                <li class="nav-item"><a class="nav-link" href="#schedule">Schedule</a></li>
                
                
                <li class="nav-item"><a class="nav-link" href="#feedback">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="#finance">Finance</a></li>
                <li class="nav-item"><a class="nav-link" href="#profile">Profile</a></li>
    
                <li class="nav-item"><a class="nav-link btn btn-danger text-white" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<p> </p>
<div class="bg-image-container">
    <div class="overlay">
        <h2 id="typing-text">Welcome Client</h2> 
        <p>Manage your case with LegalEase!</p>
    </div>
</div>
<!-- Cases Section -->
<!-- Cases Section -->
<section id="cases" class="container mt-5">
    <h2>Cases</h2>
    <div class="card shadow">
        <div class="card-body">
            <p class="card-text">View the details of your ongoing and past legal cases.</p>
            
            <?php
            // Connect to Oracle
            $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
            
            if (!$conn) {
                $e = oci_error();
                echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
            } else {
                // Query to get cases for the logged-in client
                $query = "SELECT CASEID, TYPE, STATUS, DOCUMENT, TO_CHAR(FILINGDATE, 'YYYY-MM-DD') AS FILINGDATE 
                          FROM CASES 
                          WHERE CLIENTID = :clientid 
                          ORDER BY FILINGDATE DESC";
                
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ':clientid', $clientid);
                $success = oci_execute($stid);
                
                if (!$success) {
                    $e = oci_error($stid);
                    echo '<div class="alert alert-danger">Query failed: ' . htmlentities($e['message']) . '</div>';
                } else {
                    // Check if any cases exist
                    $hasRows = false;
                    
                    // Buffer the results to check if we have any rows
                    $cases = array();
                    while ($row = oci_fetch_assoc($stid)) {
                        $cases[] = $row;
                        $hasRows = true;
                    }
                    
                    if ($hasRows) {
                        // Display cases in a table
                        echo '<div class="table-responsive">
                              <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Case ID</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Document</th>
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
                                <td>' . htmlspecialchars($case['TYPE']) . '</td>
                                <td><span class="badge ' . $statusClass . '">' . htmlspecialchars($case['STATUS']) . '</span></td>
                                <td><a href="documents/' . htmlspecialchars($case['DOCUMENT']) . '" target="_blank">' . htmlspecialchars($case['DOCUMENT']) . '</a></td>
                                <td>' . htmlspecialchars($case['FILINGDATE']) . '</td>
                            </tr>';
                        }
                        
                        echo '</tbody>
                            </table>
                          </div>';
                    } else {
                        echo '<div class="alert alert-info">You have no cases at this time.</div>';
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
<section id="schedule" class="container mt-5">
    <h2>Schedule</h2>
    <div class="card shadow">
        <div class="card-body">
            <p class="card-text">Manage your appointments with lawyers and track upcoming court dates.</p>
            
            <?php
            // Connect to Oracle
            $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
            
            if (!$conn) {
                $e = oci_error();
                echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
            } else {
                // Query to get schedule for the logged-in client
                $query = "SELECT s.CASEID, c.TYPE, s.HEARINGDATE, s.TIME 
                          FROM SCHEDULE s
                          JOIN CASES c ON s.CASEID = c.CASEID AND s.CLIENTID = c.CLIENTID
                          WHERE s.CLIENTID = :clientid 
                          ORDER BY s.HEARINGDATE ASC";
                
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ':clientid', $clientid);
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
                                        <th scope="col">Case Type</th>
                                        <th scope="col">Hearing Date</th>
                                        <th scope="col">Time</th>
                                    </tr>
                                </thead>
                                <tbody>';
                        
                        foreach ($schedules as $schedule) {
                            // Format the date from Oracle format to more readable format
                            $hearingDate = date('F j, Y', strtotime($schedule['HEARINGDATE']));
                            
                            echo '<tr>
                                <td>' . htmlspecialchars($schedule['CASEID']) . '</td>
                                <td>' . htmlspecialchars($schedule['TYPE']) . '</td>
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
<!-- Feedback Section -->
<section id="feedback" class="container mt-5">
    <h2>Feedback</h2>
    <div class="row">
        <!-- Previous Feedback -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Your Previous Feedback</h5>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_SESSION['feedback_success']) && $_SESSION['feedback_success'] === true) {
                        echo '<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <strong>Thank you!</strong> Your feedback has been submitted successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                        // Clear the session variable
                        unset($_SESSION['feedback_success']);
                    }
                    // Connect to Oracle
                    $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
                    
                    if (!$conn) {
                        $e = oci_error();
                        echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
                    } else {
                        // Query to get feedback for the logged-in client
                        $query = "SELECT FEEDBACKID, RATING, TO_CHAR(FEEDBACKDATE, 'YYYY-MM-DD') AS FEEDBACKDATE, DESCRIPTION 
                                  FROM FEEDBACK 
                                  WHERE CLIENTID = :clientid 
                                  ORDER BY FEEDBACKDATE DESC";
                        
                        $stid = oci_parse($conn, $query);
                        oci_bind_by_name($stid, ':clientid', $clientid);
                        $success = oci_execute($stid);
                        
                        if (!$success) {
                            $e = oci_error($stid);
                            echo '<div class="alert alert-danger">Query failed: ' . htmlentities($e['message']) . '</div>';
                        } else {
                            // Check if any feedback exists
                            $hasRows = false;
                            
                            // Buffer the results to check if we have any rows
                            $feedbacks = array();
                            while ($row = oci_fetch_assoc($stid)) {
                                $feedbacks[] = $row;
                                $hasRows = true;
                            }
                            
                            if ($hasRows) {
                                echo '<div class="list-group">';
                                foreach ($feedbacks as $feedback) {
                                    // Format the date
                                    $feedbackDate = date('F j, Y', strtotime($feedback['FEEDBACKDATE']));
                                    
                                    // Determine badge color based on rating
                                    $ratingClass = 'bg-success';
                                    if ($feedback['RATING'] < 7) {
                                        $ratingClass = 'bg-warning text-dark';
                                    } else if ($feedback['RATING'] < 5) {
                                        $ratingClass = 'bg-danger';
                                    }
                                    
                                    echo '<div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Feedback #' . htmlspecialchars($feedback['FEEDBACKID']) . '</h6>
                                            <small>' . $feedbackDate . '</small>
                                        </div>
                                        <p class="mb-1">' . htmlspecialchars($feedback['DESCRIPTION']) . '</p>
                                        <span class="badge ' . $ratingClass . '">' . htmlspecialchars($feedback['RATING']) . '/10</span>
                                    </div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-info">You have not submitted any feedback yet.</div>';
                            }
                        }
                        
                        oci_free_statement($stid);
                        oci_close($conn);
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Submit New Feedback -->
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Submit New Feedback</h5>
                </div>
                <div class="card-body">
                    <form id="feedbackForm" action="submit-feedback.php" method="POST">
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating (1-10)</label>
                            <input type="range" class="form-range" min="1" max="10" step="0.5" id="rating" name="rating" value="8">
                            <div class="text-center">
                                <span id="ratingValue">8.0</span>/10
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Comments</label>
                            <textarea class="form-control" id="description" name="description" rows="4" maxlength="30" placeholder="Please share your experience (max 30 characters)" required></textarea>
                            <div class="form-text text-end"><span id="charCount">0</span>/30</div>
                        </div>
                        <button type="submit" class="btn btn-outline-warning w-100">Submit Feedback</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Finance Section -->
<!-- Finance Section -->
<section id="finance" class="container mt-5">
    <h2>Finance</h2>
    <div class="row">
        <!-- Payment History -->
        <div class="col-md-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Connect to Oracle
                    $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
                    
                    if (!$conn) {
                        $e = oci_error();
                        echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
                    } else {
                        // Query to get payments for the logged-in client
                        $query = "SELECT PAYMENTID, AMOUNT, PAYMODE, BENEFIT 
                                  FROM PAYMENT 
                                  WHERE CLIENTID = :clientid 
                                  ORDER BY PAYMENTID";
                        
                        $stid = oci_parse($conn, $query);
                        oci_bind_by_name($stid, ':clientid', $clientid);
                        $success = oci_execute($stid);
                        
                        if (!$success) {
                            $e = oci_error($stid);
                            echo '<div class="alert alert-danger">Query failed: ' . htmlentities($e['message']) . '</div>';
                        } else {
                            // Buffer the results to check if we have any rows
                            $payments = array();
                            while ($row = oci_fetch_assoc($stid)) {
                                $payments[] = $row;
                            }
                            
                            if (count($payments) > 0) {
                                // Display payments in a table
                                echo '<div class="table-responsive">
                                      <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th scope="col">Payment ID</th>
                                                <th scope="col">Amount</th>
                                                <th scope="col">Payment Mode</th>
                                                <th scope="col">Beneficiary</th>
                                            </tr>
                                        </thead>
                                        <tbody>';
                                
                                foreach ($payments as $payment) {
                                    echo '<tr>
                                        <td>' . htmlspecialchars($payment['PAYMENTID']) . '</td>
                                        <td>$' . number_format($payment['AMOUNT'], 2) . '</td>
                                        <td>' . htmlspecialchars($payment['PAYMODE']) . '</td>
                                        <td>' . htmlspecialchars($payment['BENEFIT']) . '</td>
                                    </tr>';
                                }
                                
                                echo '</tbody>
                                    </table>
                                  </div>';
                            } else {
                                echo '<div class="alert alert-info">You have no payment history yet.</div>';
                            }
                        }
                        
                        oci_free_statement($stid);
                        oci_close($conn);
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- New Payment Form -->
        <div class="col-md-5">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Make a Payment</h5>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_SESSION['payment_success']) && $_SESSION['payment_success'] === true) {
                        echo '<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <strong>Success!</strong> Your payment has been processed.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
                        // Clear the session variable
                        unset($_SESSION['payment_success']);
                    }
                    
                    // Connect to get available benefits
                    $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
                    $benefits = array();
                    
                    if ($conn) {
                        $query = "SELECT DISTINCT BENEFIT FROM LAWYER ORDER BY BENEFIT";
                        $stid = oci_parse($conn, $query);
                        if (oci_execute($stid)) {
                            while ($row = oci_fetch_assoc($stid)) {
                                $benefits[] = $row['BENEFIT'];
                            }
                        }
                        oci_free_statement($stid);
                        oci_close($conn);
                    }
                    ?>
                    <form id="paymentForm" action="submit-payment.php" method="POST">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount ($)</label>
                            <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="paymode" class="form-label">Payment Method</label>
                            <select class="form-select" id="paymode" name="paymode" required>
                                <option value="">Select payment method</option>
                                <option value="Online">Online</option>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Card">Card</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="benefit" class="form-label">Beneficiary</label>
                            <select class="form-select" id="benefit" name="benefit" required>
                                <option value="">Select beneficiary</option>
                                <?php
                                foreach ($benefits as $benefit) {
                                    echo '<option value="' . htmlspecialchars($benefit) . '">' . htmlspecialchars($benefit) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-success w-100">Process Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Profile Section (Moved to the End) -->
<!-- Profile Section -->
<!-- Profile Section -->
<section id="profile" class="container mt-5">
    <h2>Profile</h2>
    <div class="card p-4 d-flex flex-row align-items-center shadow-lg">
        <img src="images/man.png" alt="Client Profile Picture" class="rounded-circle me-4" width="150" height="150">
        <div>
            <h4>
                <?php 
                    // Construct full name from the NAME table data
                    $fullName = $client['FNAME'];
                    if (!empty($client['MNAME'])) {
                        $fullName .= ' ' . $client['MNAME'];
                    }
                    $fullName .= ' ' . $client['LNAME'];
                    echo htmlspecialchars($fullName);
                ?>
            </h4>
            <p><strong>Client ID:</strong> <span class="text-primary">#<?php echo $client['CLIENTID']; ?></span></p>
            <p><strong>Email:</strong> <?php echo $client['UEMAIL']; ?></p>
            <p><strong>Phone:</strong> <?php echo $client['CPHONE']; ?></p>
            <p><strong>Lawyer ID:</strong> <?php echo $client['LAWYERID']; ?></p>
        </div>
    </div>
</section>


<p>
</p>
<footer class="bg-dark text-white pt-4">
    <div class="container text-center text-md-start">
        <div class="row">
            <!-- Quick Links (Updated to match Client Navbar) -->
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#profile" class="text-white">Profile</a></li>
                    <li><a href="#cases" class="text-white">Cases</a></li>
                    <li><a href="#schedule" class="text-white">Schedule</a></li>
                    <li><a href="#feedback" class="text-white">Feedback</a></li>
                    <li><a href="#finance" class="text-white">Finance</a></li>
                </ul>
            </div>

            <!-- Follow Us Section -->
            <div class="col-md-4 text-center">
                <h5>Follow Us</h5>
                <a href="https://www.facebook.com/" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                <a href="https://twitter.com/" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                <a href="https://www.instagram.com/" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                <a href="https://www.linkedin.com/" class="text-white"><i class="bi bi-linkedin"></i></a>
            </div>

            <!-- Contact Us Section -->
            <div class="col-md-4 text-end">
                <h5>Contact Us</h5>
                <p>Email: support@legalease.com</p>
                <p>Phone: +1 (555) 123-4567</p>
                <p>Address: 123 Legal Street, Justice City</p>
            </div>
        </div>
    </div>
    <div class="text-center mt-3 pb-3">
        © 2025 LegalEase. All Rights Reserved.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>


// Update rating value display when slider changes
document.addEventListener('DOMContentLoaded', function() {
    const ratingSlider = document.getElementById('rating');
    const ratingValue = document.getElementById('ratingValue');
    const description = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    
    if (ratingSlider && ratingValue) {
        ratingSlider.addEventListener('input', function() {
            ratingValue.textContent = parseFloat(this.value).toFixed(1);
        });
    }
    
    if (description && charCount) {
        description.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
});
</script>
</body>
</html>
