<?php

require '../config/database.php';
require '../auth/cek_login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM jkp WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

header("location:data.php");
exit;