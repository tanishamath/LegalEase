<?php
$username = "system";  
$password = "tanuoracle1";  
$connection_string = "localhost/XE";  

$conn = oci_connect($username, $password, $connection_string);

if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

$query = "SELECT DISTINCT Specialization FROM Lawyers";
$stmt = oci_parse($conn, $query);
oci_execute($stmt);

$specializations = array();
while ($row = oci_fetch_assoc($stmt)) {
    $specializations[] = $row['SPECIALIZATION'];
}

echo json_encode($specializations);

oci_close($conn);
?>
