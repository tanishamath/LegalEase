<?php
$username = "system";  
$password = "tanuoracle1";  
$connection_string = "localhost/XE";  

$conn = oci_connect($username, $password, $connection_string);

if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

if (isset($_POST['specialization'])) {
    $specialization = $_POST['specialization'];

    $query = "SELECT Lawyer_Name, Lawyer_ID, Lawyer_Email FROM Lawyers WHERE Specialization = :specialization";
    $stmt = oci_parse($conn, $query);

    oci_bind_by_name($stmt, ":specialization", $specialization);
    oci_execute($stmt);

    $lawyers = array();
    while ($row = oci_fetch_assoc($stmt)) {
        $lawyers[] = $row;
    }

    echo json_encode($lawyers);
}

oci_close($conn);
?>
