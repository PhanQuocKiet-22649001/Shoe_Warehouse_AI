<?php
// config/database.php

function getConnection() {
    $conn_string = "host=localhost port=5432 dbname=shoe_warehouse_ai user=admin_shoe_shop password=123456";

    $connection = pg_connect($conn_string);

    if (!$connection) {
        die("❌ Kết nối PostgreSQL thất bại!");
    }

    return $connection;
}

function closeConnection($connection) {
    if ($connection) {
        pg_close($connection);
    }
}
?>