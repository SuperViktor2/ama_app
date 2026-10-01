<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'baza.class.php';
$veza = new Baza();
$veza->spojiDB();

require 'session.class.php';
Sesija::kreirajSesiju();

if (!isset($_SESSION['id'])) {
    header('Location: prijava.php');
    exit;
}

// Provjera je li trenutni korisnik admin
$rezultat = $veza->preparedSelect(
    "SELECT admin
     FROM user
     WHERE id = ?",
    "i",
    $_SESSION['id']
);

if (!$rezultat || $rezultat->num_rows === 0) {
    header('Location: main.php');
    exit;
}

$user = $rezultat->fetch_assoc();

if ((int)$user['admin'] !== 1) {
    header('Location: main.php');
    exit;
}

$poruka = "";
$kod = "";

// GENERIRANJE KODA
if (isset($_POST['generiraj'])) {

    // Skup znakova koji ćemo koristiti
    $znakovi = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    $kod = '';

    for ($i = 0; $i < 6; $i++) {
        $kod .= $znakovi[random_int(0, strlen($znakovi) - 1)];
    }

    // Vrijeme generiranja
    $created = date('Y-m-d H:i:s');

    // Istek nakon 10 minuta
    $expired = date('Y-m-d H:i:s', time() + 600);

    $uspjeh = $veza->preparedUpdate(
        "INSERT INTO act_key
            (act_key, created, expired, used)
         VALUES (?, ?, ?, 0)",
        "sss",
        $kod,
        $created,
        $expired
    );

    if ($uspjeh) {
        $poruka = "Kod je uspješno generiran.";
    } else {
        $poruka = "Greška prilikom generiranja koda.";
        $kod = "";
    }
}

?>

<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Generiraj kod</title>

    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/skeleton.css">
    <link rel="stylesheet" href="css/perso.css">
</head>

<body>

    <header>

        <input type="checkbox" id="menu-toggle">
        <label for="menu-toggle" class="menu-ikona">Meni</label>

        <ul class = "nav">
            <li><a href='logout.php'>Odjava</a></li>
            <li><a href="main.php">Početna stranica</a></li>
            <li><a href='popis_klijenata.php'>Klijenti</a></li>
            <li><a href='dodaj_klijenta.php'>Dodaj klijenta</a></li>
            <li><a href='dodaj_termin.php'>Dodaj termin</a></li>
           <li><a href='prosli_termini.php'>Moji prošli termini</a></li>
        </ul>
    </header>

<div class="container">

    <h3>Generiranje aktivacijskog koda za registraciju</h3>
    <h4>Upute za korištenje</h4>
    <ul>
        <li>Generiraj aktivacijski kod samo ako želiš dopustiti nekome da se registrira.</li>
        <li>Aktivacijski kod se generira odmah i istjeće za 10 minuta.</li>
        <li>Nakon što generiraš aktivacijski kod, daj ga osobi koja se želi registrirati.</li>
        <li>Aktivacijski kod je potrebno unjeti tokom procesa registracije u za to predviđeno polje.</li>
        <li>Nakon registracije / uporabe aktivacijskog koda, on istjeće i ne može se više upotrijebiti.</li>
    </ul>

    <?php
    if (!empty($poruka)) {
        echo "<p>" . htmlspecialchars($poruka) . "</p>";
    }
    ?>

    <?php if (!empty($kod)): ?>
        <div class ="kod">
            <h4 style = "color: black;">Aktivacijski kod:</h4>

            <div style="font-size: 32px; font-weight: bold;">
                <?php echo htmlspecialchars($kod); ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST">
        <button type="submit" name="generiraj" class = "button-primary">
            Generiraj kod
        </button>
    </form>

</div>

</body>
</html>