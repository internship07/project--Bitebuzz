<?php
include 'conf.php';

$result = $conn->query("SELECT * FROM menu");
$menu = [];

while ($row = $result->fetch_assoc()) {
    $menu[] = $row;
}

echo json_encode($menu);
?>
