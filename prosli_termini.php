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

    ?>

    <!DOCTYPE html>

    <html lang="hr">
        <head>
            <title>Ama studio</title>
            <meta charset="utf-8">
            <meta name="author" content="Viktor Goleš">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">


            <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

            <link rel="stylesheet" href="css/normalize.css">
            <link rel="stylesheet" href="css/skeleton.css">
            <link rel="stylesheet" href="css/perso.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

            <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>

            <script>
                $(document).ready(function() {
                    $('#tablica').DataTable({

                    });
                });
            </script>


        </head>


        </head>
        <body>
            <header>

                <input type="checkbox" id="menu-toggle">
        <label for="menu-toggle" class="menu-ikona"><img src="icons/app.png"></label>

                <ul class = "nav">
                    <li><a href='logout.php'>Odjava</a></li>
                    <li><a href='main.php'>Početna stranica</a></li>
                    <li><a href='popis_klijenata.php'>Klijenti</a></li>
                    <li><a href='dodaj_klijenta.php'>Dodaj klijenta</a></li>
                    <li><a href='dodaj_termin.php'>Dodaj termin</a></li>
                </ul>
            </header>
            <div class = "container">
                <h3>Moji Prošli Termini</h3>
<?php

// ==========================================
// DOHVAT TERMINA
// ==========================================

$sql = "SELECT 
            c.ime,
            c.prezime,
            t.id,
            t.client_id,
            t.date,
            t.time,
            t.komentari,

            GROUP_CONCAT(
                CONCAT(
                    u.naziv,
                    ' × ',
                    termin_usluga.kolicina,
                    ' (',
                    termin_usluga.cijena * termin_usluga.kolicina,
                    ' €)'
                )
                SEPARATOR ', <br>'
            ) AS usluge,

            SUM(
                termin_usluga.cijena * termin_usluga.kolicina
            ) AS ukupna_cijena

        FROM client c

        JOIN termin t
            ON c.id = t.client_id

        JOIN termin_usluga
            ON t.id = termin_usluga.termin_id

        JOIN usluga u
            ON termin_usluga.usluga_id = u.id_usluga

        WHERE t.user_id = {$_SESSION['id']}
        AND t.date < CURDATE()

        GROUP BY
            t.id,
            c.ime,
            c.prezime,
            t.client_id,
            t.date,
            t.time,
            t.komentari

        ORDER BY t.date, t.time";


$odg = $veza->selectDB($sql);

if($odg->num_rows != 0) {

    print "               
                <div class = 'content'>
                    <table id='tablica'>

                        <thead>

                            <tr>
                                <th>Ime</th>
                                <th>Prezime</th>
                                <th>Datum</th>
                                <th>Vrijeme</th>
                                <th>Komentari</th>
                                <th>Usluge</th>
                                <th>Ukupna cijena</th>
                            </tr>

                        </thead>

                        <tbody>
        ";

    while ($termin = $odg->fetch_assoc()) {

        print "<tr>";

        print "<td>" .
            htmlspecialchars($termin['ime']) .
            "</td>";

        print "<td>" .
            htmlspecialchars($termin['prezime']) .
            "</td>";

        print "<td>" .
            htmlspecialchars($termin['date']) .
            "</td>";

        print "<td>" .
            htmlspecialchars($termin['time']) .
            "</td>";

        print "<td>" .
            htmlspecialchars($termin['komentari']) .
            "</td>";

        print "<td>" .
            $termin['usluge'] .
            "</td>";

        print "<td>" .
            $termin['ukupna_cijena'] .
            " €</td>";

        print "</tr>";
    }
} else {
    print "
    <div class = 'prazno'>
        <h1>Trenutno nemam prošlih termina!</h1>
    </div>
    ";
}
?>

            </tbody>
        </table>

        </div>
    </div>
        <footer>
            <p>&copy; 2026
                Viktor Goleš
        </footer>
    </body>
</html>