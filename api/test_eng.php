<?php
require_once 'db_connect.php';
$engs = $pdo->query("SELECT * FROM engineers")->fetchAll();
print_r($engs);
$users = $pdo->query("SELECT * FROM users WHERE role='engineer'")->fetchAll();
print_r($users);
?>
