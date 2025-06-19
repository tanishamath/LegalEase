<?php
// Connection string with proper error handling
$conn = oci_connect('system', 'tanuoracle1', 'localhost/XE');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Make sure we're using the correct case for column names
// Oracle column names are typically uppercase by default
// Update the query to join with the NAME table
$query = "SELECT c.CLIENTID, c.UEMAIL, c.CPHONE, c.LAWYERID, 
          n.FNAME, n.MNAME, n.LNAME 
          FROM CLIENT c
          LEFT JOIN NAME n ON c.UEMAIL = n.UEMAIL
          WHERE c.CLIENTID = :cid";
$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ':cid', $clientid);
$success = oci_execute($stid);


if (!$success) {
    $e = oci_error($stid);
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Fetch result with proper case handling
$client = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);

// If no client found, set default values to avoid undefined index errors
if (!$client) {
    $client = array(
        'CLIENTID' => 'Not found',
        'UEMAIL' => 'Not found',
        'CPHONE' => 'Not found',
        'LAWYERID' => 'Not found'
    );
}

oci_free_statement($stid);
oci_close($conn);
?>

