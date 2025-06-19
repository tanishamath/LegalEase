<?php
session_start();

// Check if user is logged in as a judge
if (!isset($_SESSION['judgeid']) || $_SESSION['user_type'] !== 'judge') {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit();
}

// Validate parameters
if (!isset($_GET['caseid']) || empty($_GET['caseid']) || !isset($_GET['clientid']) || empty($_GET['clientid'])) {
    echo '<div class="alert alert-danger">Invalid case or client ID</div>';
    exit();
}

$caseid = $_GET['caseid'];
$clientid = $_GET['clientid'];
$judgeid = $_SESSION['judgeid'];

// Connect to Oracle
$conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');

if (!$conn) {
    $e = oci_error();
    echo '<div class="alert alert-danger">Database connection failed: ' . htmlentities($e['message']) . '</div>';
    exit();
}

// First, check if this case is assigned to the logged-in judge
$authQuery = "SELECT COUNT(*) AS IS_AUTHORIZED
              FROM CJUDGE
              WHERE CASEID = :caseid
              AND CLIENTID = :clientid
              AND JUDGEID = :judgeid";

$authStid = oci_parse($conn, $authQuery);
oci_bind_by_name($authStid, ':caseid', $caseid);
oci_bind_by_name($authStid, ':clientid', $clientid);
oci_bind_by_name($authStid, ':judgeid', $judgeid);
oci_execute($authStid);

$auth = oci_fetch_assoc($authStid);
if (!$auth || $auth['IS_AUTHORIZED'] == 0) {
    echo '<div class="alert alert-warning">You are not authorized to view this case.</div>';
    oci_free_statement($authStid);
    oci_close($conn);
    exit();
}
oci_free_statement($authStid);

// Query to get detailed case information
$query = "SELECT c.CASEID, c.CLIENTID, c.TYPE, c.STATUS, c.DOCUMENT, 
          TO_CHAR(c.FILINGDATE, 'YYYY-MM-DD') AS FILINGDATE,
          cl.UEMAIL AS CLIENT_EMAIL, cl.CPHONE AS CLIENT_PHONE,
          cn.FNAME AS CLIENT_FNAME, cn.MNAME AS CLIENT_MNAME, cn.LNAME AS CLIENT_LNAME,
          l.LAWYERID, l.LAWFIRM, l.SPEC AS SPECIALIZATION, l.LPHONE,
          ln.FNAME AS LAWYER_FNAME, ln.MNAME AS LAWYER_MNAME, ln.LNAME AS LAWYER_LNAME,
          ct.COURTID, ct.TYPE AS COURT_TYPE, ct.CHAMBER,
          cloc.STREET, cloc.CITY
          FROM CASES c
          JOIN CLIENT cl ON c.CLIENTID = cl.CLIENTID
          JOIN NAME cn ON cl.UEMAIL = cn.UEMAIL
          LEFT JOIN LAWYER l ON cl.LAWYERID = l.LAWYERID
          LEFT JOIN NAME ln ON l.UEMAIL = ln.UEMAIL
          LEFT JOIN COURT ct ON ct.TYPE = c.TYPE
          LEFT JOIN CLOCATION cloc ON ct.COURTID = cloc.COURTID
          WHERE c.CASEID = :caseid AND c.CLIENTID = :clientid";
$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ':caseid', $caseid);
oci_bind_by_name($stid, ':clientid', $clientid);
$success = oci_execute($stid);

if (!$success) {
    $e = oci_error($stid);
    echo '<div class="alert alert-danger">Query failed: ' . htmlentities($e['message']) . '</div>';
    oci_free_statement($stid);
    oci_close($conn);
    exit();
}

$case = oci_fetch_assoc($stid);
oci_free_statement($stid);

if (!$case) {
    echo '<div class="alert alert-warning">Case not found.</div>';
    oci_close($conn);
    exit();
}

// Format client name
$clientName = $case['CLIENT_FNAME'];
if (!empty($case['CLIENT_MNAME'])) {
    $clientName .= ' ' . $case['CLIENT_MNAME'];
}
$clientName .= ' ' . $case['CLIENT_LNAME'];

// Format lawyer name
$lawyerName = '';
if (!empty($case['LAWYER_FNAME'])) {
    $lawyerName = $case['LAWYER_FNAME'];
    if (!empty($case['LAWYER_MNAME'])) {
        $lawyerName .= ' ' . $case['LAWYER_MNAME'];
    }
    $lawyerName .= ' ' . $case['LAWYER_LNAME'];
}

// Get hearing schedule information
$scheduleQuery = "SELECT TO_CHAR(HEARINGDATE, 'YYYY-MM-DD') AS HEARINGDATE, TIME
                  FROM SCHEDULE
                  WHERE CASEID = :caseid AND CLIENTID = :clientid";

$scheduleStid = oci_parse($conn, $scheduleQuery);
oci_bind_by_name($scheduleStid, ':caseid', $caseid);
oci_bind_by_name($scheduleStid, ':clientid', $clientid);
oci_execute($scheduleStid);

$schedule = oci_fetch_assoc($scheduleStid);
oci_free_statement($scheduleStid);
oci_close($conn);
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Case Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Case ID:</strong> <?php echo htmlspecialchars($case['CASEID']); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($case['TYPE']); ?></p>
                        <p><strong>Status:</strong> 
                            <?php 
                            $statusClass = '';
                            switch ($case['STATUS']) {
                                case 'Open':
                                    $statusClass = 'text-success';
                                    break;
                                case 'Closed':
                                    $statusClass = 'text-secondary';
                                    break;
                                case 'Pending':
                                    $statusClass = 'text-warning';
                                    break;
                                default:
                                    $statusClass = 'text-info';
                            }
                            echo '<span class="' . $statusClass . '">' . htmlspecialchars($case['STATUS']) . '</span>';
                            ?>
                        </p>
                        <p><strong>Filing Date:</strong> <?php echo htmlspecialchars($case['FILINGDATE']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <?php if ($schedule): ?>
                        <p><strong>Next Hearing:</strong> <?php echo htmlspecialchars($schedule['HEARINGDATE']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($schedule['TIME']); ?></p>
                        <?php else: ?>
                        <p><strong>Hearing Schedule:</strong> <span class="text-warning">Not scheduled yet</span></p>
                        <?php endif; ?>
                        <p><strong>Court:</strong> 
                            <?php if (!empty($case['COURTID'])): ?>
                                <?php echo htmlspecialchars($case['COURTID']) . ' - ' . htmlspecialchars($case['CHAMBER']); ?>
                            <?php else: ?>
                                <span class="text-warning">Not assigned</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Location:</strong> 
                            <?php if (!empty($case['ADDRESS'])): ?>
                                <?php echo htmlspecialchars($case['ADDRESS']) . ', ' . htmlspecialchars($case['CITY']); ?>
                            <?php else: ?>
                                <span class="text-warning">Not available</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="mt-3">
                    <p><strong>Case Document:</strong> 
                        <a href="documents/<?php echo htmlspecialchars($case['DOCUMENT']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-file-earmark-text"></i> View <?php echo htmlspecialchars($case['DOCUMENT']); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card border-info h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Client Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($clientName); ?></p>
                <p><strong>Client ID:</strong> <?php echo htmlspecialchars($case['CLIENTID']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($case['CLIENT_EMAIL']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($case['CLIENT_PHONE']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card border-success h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Lawyer Information</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($lawyerName)): ?>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($lawyerName); ?></p>
                <p><strong>Lawyer ID:</strong> <?php echo htmlspecialchars($case['LAWYERID']); ?></p>
                <p><strong>Law Firm:</strong> <?php echo htmlspecialchars($case['LAWFIRM']); ?></p>
                <p><strong>Specialization:</strong> <?php echo htmlspecialchars($case['SPECIALIZATION']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($case['LPHONE']); ?></p>
                <?php else: ?>
                <p class="text-center py-4 text-danger">No lawyer assigned to this case.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($schedule): ?>
<div class="row">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Notes for Judge</h5>
            </div>
            <div class="card-body">
                <div class="form-floating">
                    <textarea class="form-control" placeholder="Enter your notes about this case here" id="judgeNotes" style="height: 100px"></textarea>
                    <label for="judgeNotes">Case Notes (these are not saved, for your reference only)</label>
                </div>
                <div class="mt-3">
                    <button class="btn btn-outline-primary" disabled>Save Notes</button>
                    <small class="text-muted ms-2">Note: This feature is currently under development</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>