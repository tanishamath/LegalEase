<?php
// Connection string with proper error handling
$conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Updated query to join with NAME table
$query = "SELECT j.JUDGEID, j.UEMAIL, n.FNAME, n.MNAME, n.LNAME 
          FROM JUDGE j 
          LEFT JOIN NAME n ON j.UEMAIL = n.UEMAIL 
          WHERE j.JUDGEID = :cid";

$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ':cid', $judgeid);
$success = oci_execute($stid);

if (!$success) {
    $e = oci_error($stid);
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Fetch result with proper case handling
$judge = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);

// If no judge found, set default values to avoid undefined index errors
if (!$judge) {
    $judge = array(
        'JUDGEID' => 'Not found',
        'UEMAIL' => 'Not found',
        'FNAME' => 'Not found',
        'MNAME' => '',
        'LNAME' => ''
    );
}

oci_free_statement($stid);
oci_close($conn);
?>