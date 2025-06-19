<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['judgeid']) || $_SESSION['user_type'] !== 'judge') {
    header("Location: login.php");
    exit();
}

$judgeid = $_SESSION['judgeid'];

// Include the judge profile data
include 'judge_profile.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judge Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .table-row-clickable {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .table-row-clickable:hover {
            background-color: #e9ecef !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="images/compliant.png" alt="LegalEase Logo" width="50" height="50" class="me-2">
            <span>LegalEase Judge</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#profile">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="#assigned-cases">Assigned Cases</a></li>
                
               
                <li class="nav-item"><a class="nav-link btn btn-danger text-white" href="index.html">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
<p></p>

<div class="bg-image-container">
    <div class="overlay">
        <h2 id="typing-text">Welcome Judge</h2>
        <p>Manage your assigned cases with LegalEase!</p>
    </div>
</div>

<section id="assigned-cases" class="container mt-5">
    <h2>Assigned Cases</h2>
    <div class="card shadow">
        <div class="card-body">
            <p class="card-text">Review and manage cases assigned to you.</p>
            
            <?php
            // Connect to Oracle
            $conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');
            
            if (!$conn) {
                $e = oci_error();
                echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
            } else {
                // Query to get cases assigned to the logged-in judge using the CJUDGE table
                $query = "SELECT c.CASEID, c.CLIENTID, c.TYPE, c.STATUS, c.DOCUMENT, 
          TO_CHAR(c.FILINGDATE, 'YYYY-MM-DD') AS FILINGDATE,
          n.FNAME || ' ' || NVL(n.MNAME || ' ', '') || n.LNAME AS CLIENT_NAME,
          ct.COURTID, ct.TYPE AS COURT_TYPE, ct.CHAMBER,
          cl.STREET, cl.CITY
          FROM CASES c
          JOIN CJUDGE cj ON c.CASEID = cj.CASEID AND c.CLIENTID = cj.CLIENTID
          JOIN CLIENT clt ON c.CLIENTID = clt.CLIENTID
          JOIN NAME n ON clt.UEMAIL = n.UEMAIL
          LEFT JOIN COURT ct ON ct.TYPE = c.TYPE
          LEFT JOIN CLOCATION cl ON ct.COURTID = cl.COURTID
          WHERE cj.JUDGEID = :judgeid
          ORDER BY c.FILINGDATE DESC";
                $stid = oci_parse($conn, $query);
                oci_bind_by_name($stid, ':judgeid', $judgeid);
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
                                        <th scope="col">Client</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Court</th>
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
                            
                            // Format court information
                            $courtInfo = '';
                            if (!empty($case['COURTID'])) {
                                $courtInfo = $case['COURTID'] . ' - ' . $case['CHAMBER'] . ', ' . $case['CITY'];
                            } else {
                                $courtInfo = 'Not assigned';
                            }
                            
                            echo '<tr class="table-row-clickable" onclick="viewCaseDetails(\'' . htmlspecialchars($case['CASEID']) . '\', \'' . htmlspecialchars($case['CLIENTID']) . '\')">
                                <td>' . htmlspecialchars($case['CASEID']) . '</td>
                                <td>' . htmlspecialchars($case['TYPE']) . '</td>
                                <td>' . htmlspecialchars($case['CLIENT_NAME']) . '</td>
                                <td><span class="badge ' . $statusClass . '">' . htmlspecialchars($case['STATUS']) . '</span></td>
                                <td>' . htmlspecialchars($courtInfo) . '</td>
                                <td>' . htmlspecialchars($case['FILINGDATE']) . '</td>
                            </tr>';
                        }
                        
                        echo '</tbody>
                            </table>
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

<!-- Case Details Modal -->
<div class="modal fade" id="caseDetailsModal" tabindex="-1" aria-labelledby="caseDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="caseDetailsModalLabel">Case Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="caseDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<section id="profile" class="container mt-5">
    <h2>Profile</h2>
    <div class="card p-4 d-flex flex-row align-items-center shadow-lg">
        <img src="images/judge.png" alt="Judge Profile Picture" class="rounded-circle me-4" width="150" height="150">
        <div>
            <h4>
                <?php 
                    // Construct full name from the NAME table data
                    $fullName = $judge['FNAME'];
                    if (!empty($judge['MNAME'])) {
                        $fullName .= ' ' . $judge['MNAME'];
                    }
                    $fullName .= ' ' . $judge['LNAME'];
                    echo htmlspecialchars($fullName);
                ?>
            </h4>
            <p><strong>Judge ID:</strong> <span class="text-primary">#<?php echo $judge['JUDGEID']; ?></span></p>
            <p><strong>Email:</strong> <?php echo $judge['UEMAIL']; ?></p>
        </div>
    </div>
</section>
<p></p>

<footer class="bg-dark text-white pt-4">
    <div class="container text-center text-md-start">
        <div class="row">
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="#profile" class="text-white">Profile</a></li>
                    <li><a href="#assigned-cases" class="text-white">Assigned Cases</a></li>
                    <li><a href="#schedule" class="text-white">Schedule</a></li>
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
    </div>
    <div class="text-center mt-3 pb-3">
        © 2025 LegalEase. All Rights Reserved.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewCaseDetails(caseId, clientId) {
    const modal = new bootstrap.Modal(document.getElementById('caseDetailsModal'));
    document.getElementById('caseDetailsModalLabel').textContent = 'Case Details: ' + caseId;
    
    // Show loading spinner
    document.getElementById('caseDetailsContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch case details via AJAX
    fetch('judge-case-details.php?caseid=' + encodeURIComponent(caseId) + '&clientid=' + encodeURIComponent(clientId))
        .then(response => response.text())
        .then(html => {
            document.getElementById('caseDetailsContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('caseDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    Error loading case details: ${error.message}
                </div>
            `;
        });
}

// Typing effect for welcome message
document.addEventListener('DOMContentLoaded', function() {
    const typingText = document.getElementById("typing-text");
    if (typingText) {
        typingText.textContent = ''; // Clear initial text
        const text = "Welcome Judge <?php echo $judge['FNAME']; ?>";
        const speed = 100;
        let index = 0;
        
        function typeWriter() {
            if (index < text.length) {
                typingText.textContent += text.charAt(index);
                index++;
                setTimeout(typeWriter, speed);
            }
        }
        typeWriter();
    }
});
</script>
</body>
</html>