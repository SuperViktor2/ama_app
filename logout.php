<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'session.class.php';
Sesija::kreirajSesiju();

Sesija::obrisiSesiju();
header('Location: prijava.php');
exit;

?>