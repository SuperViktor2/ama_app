<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'baza.class.php';

$veza = new Baza();
$veza->spojiDB();

echo "SUCCESS! Connected to the database.";

?>