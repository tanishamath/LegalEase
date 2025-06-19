<?php
session_start();


if (!isset($_SESSION['clientid'])) {
    echo json_encode(['error' => 'You must be logged in to view case details']);
    exit();
}

// Check if case ID is provided
if (!isset($_GET['caseid']) || empty($_GET['caseid'])) {
    echo json_encode(['error' => 'No case ID provided']);
    exit();
}

$caseid = $_GET['caseid'];
$clientid = $_SESSION['clientid'];

// Connect to Oracle
$conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');

if (!$conn) {
    $e = oci_error();
    echo json_encode(['error' => 'Database connection failed: ' . $e['message']]);
    exit();
}

// Query to get case details
$query = "SELECT c.CASEID, c.TYPE, c.STATUS, c.DOCUMENT, TO_CHAR(c.FILINGDATE, 'YYYY-MM-DD') AS FILINGDATE,
                 cl.UEMAIL as CLIENT_EMAIL, l.LAWFIRM, l.SPEC
          FROM CASES c
          JOIN CLIENT cl ON c.CLIENTID = cl.CLIENTID
          JOIN LAWYER l ON cl.LAWYERID = l.LAWYERID
          WHERE c.CASEID = :caseid AND c.CLIENTID = :clientid";

$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ':caseid', $caseid);
oci_bind_by_name($stid, ':clientid', $clientid);
$success = oci_execute($stid);

if (!$success) {
    $e = oci_error($stid);
    echo json_encode(['error' => 'Query failed: ' . $e['message']]);
} else {
    $case = oci_fetch_assoc($stid);
    
    if ($case) {
        // Return case details as HTML
        $statusClass = '';
        switch ($case['STATUS']) {
            case 'Open':
                $statusClass = 'success';
                break;
            case 'Closed':
                $statusClass = 'secondary';
                break;
            case 'Pending':
                $statusClass = 'warning';
                break;
            default:
                $statusClass = 'info';
        }
        
        $html = '
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    Case Summary
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Case ID:</strong> ' . htmlspecialchars($case['CASEID']) . '</p>
                            <p><strong>Type:</strong> ' . htmlspecialchars($case['TYPE']) . '</p>
                            <p><strong>Status:</strong> <span class="badge bg-' . $statusClass . '">' . htmlspecialchars($case['STATUS']) . '</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Filing Date:</strong> ' . htmlspecialchars($case['FILINGDATE']) . '</p>
                            <p><strong>Document:</strong> <a href="documents/' . htmlspecialchars($case['DOCUMENT']) . '" target="_blank">' . htmlspecialchars($case['DOCUMENT']) . '</a></p>
                            <p><strong>Law Firm:</strong> ' . htmlspecialchars($case['LAWFIRM']) . '</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-info text-white">
                    Additional Information
                </div>
                <div class="card-body">
                    <p><strong>Lawyer Specialization:</strong> ' . htmlspecialchars($case['SPEC']) . '</p>
                    <p><strong>Case Notes:</strong> This section would display detailed notes about the case progress.</p>
                </div>
            </div>
        ';
        
        echo $html;
    } else {
        echo '<div class="alert alert-danger">Case not found or you do not have permission to view it.</div>';
    }
}

oci_free_statement($stid);
oci_close($conn);
?>