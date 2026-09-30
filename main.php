<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'baza.class.php';

$veza = new Baza();
$veza->spojiDB();

require 'session.class.php';
Sesija::kreirajSesiju();

?>

<!DOCTYPE html>

<html lang="hr">
    <head>
        <title>Ama studio</title>
        <meta charset="utf-8">
        <meta name="author" content="Viktor Goleš">

        <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/skeleton.css">
        <link rel="stylesheet" href="css/perso.css">

        <link href="https://cdn.datatables.net/v/dt/dt-3.0.4/datatables.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>

        <script>
            $(document).ready(function() {
                $('#tablica').DataTable({
                    columnDefs: [
                    {
                        orderable: false,
                        searchable: false,
                        targets: 7
                    }
                ]
                });
            });
        </script>


    </head>
<?php

if (!isset($_SESSION['id'])) {
    header('Location: prijava.php');
    exit;
}

// ==========================================
// BRISANJE TERMINA
// ==========================================

$dohvatiIme = "SELECT ime FROM user WHERE id = ?";

$rezultatImena = $veza->preparedSelect(
    $dohvatiIme,
    "i",
    $_SESSION['id']
);

$user = $rezultatImena -> fetch_assoc();
$ime = $user['ime'];
$poruka = "";

if (isset($_POST['obrisi'])) {

    $terminID = (int)$_POST['termin_id'];

    // Prvo brišemo usluge povezane s terminom
    $veza->preparedUpdate(
        "DELETE FROM termin_usluga WHERE termin_id = ?",
        "i",
        $terminID
    );

    // Zatim brišemo sam termin
    $sql = "DELETE FROM termin
            WHERE id = ?
            AND user_id = ?";

    $uspjeh = $veza->preparedUpdate(
        $sql,
        "ii",
        $terminID,
        $_SESSION['id']
    );

    if ($uspjeh) {
        $poruka = "Termin je uspješno obrisan!";
    } else {
        $poruka = "Greška prilikom brisanja termina.";
    }
}


// ==========================================
// PRIPREMA TERMINA ZA UREĐIVANJE
// ==========================================

$terminZaUrediti = null;

if (isset($_POST['update'])) {

    $terminID = (int)$_POST['termin_id'];

    $rezultatTermina = $veza->preparedSelect(
        "SELECT id, client_id, date, time, komentari
         FROM termin
         WHERE id = ?
         AND user_id = ?",
        "ii",
        $terminID,
        $_SESSION['id']
    );

    if ($rezultatTermina && $rezultatTermina->num_rows > 0) {

        $terminZaUrediti = $rezultatTermina->fetch_assoc();
    }

    if ($terminZaUrediti !== null) {

        $rezultatUsluga = $veza->preparedSelect(
            "SELECT 
                termin_usluga.id,
                termin_usluga.usluga_id,
                termin_usluga.kolicina
            FROM termin_usluga
            WHERE termin_usluga.termin_id = ?",
            "i",
            $terminID
        );

        $uslugeZaUrediti = [];

        while ($usluga = $rezultatUsluga->fetch_assoc()) {
            $uslugeZaUrediti[] = $usluga;
        }
    }
}


// ==========================================
// SPREMANJE IZMIJENJENOG TERMINA
// ==========================================

if (isset($_POST['spremi'])) {

    $terminID = (int)$_POST['termin_id'];

    $datum = $_POST['datum'] ?? '';
    $vrijeme = $_POST['vrijeme'] ?? '';
    $komentari = trim($_POST['komentari'] ?? '');

    // Ažuriranje osnovnih podataka termina
    $sql = "UPDATE termin
            SET date = ?,
                time = ?,
                komentari = ?
            WHERE id = ?
            AND user_id = ?";

    $uspjeh = $veza->preparedUpdate(
        $sql,
        "sssii",
        $datum,
        $vrijeme,
        $komentari,
        $terminID,
        $_SESSION['id']
    );

    // Ažuriranje usluga
    if ($uspjeh && isset($_POST['usluga'])) {

        foreach ($_POST['usluga'] as $terminUslugaID => $uslugaID) {

            $terminUslugaID = (int)$terminUslugaID;
            $uslugaID = (int)$uslugaID;

            $kolicina = (int)($_POST['kolicina'][$terminUslugaID] ?? 1);

            if ($kolicina < 1) {
                $kolicina = 1;
            }

            // Dohvat aktualne cijene odabrane usluge
            $cijenaRezultat = $veza->preparedSelect(
                "SELECT cijena
                 FROM usluga
                 WHERE id_usluga = ?",
                "i",
                $uslugaID
            );

            if ($cijenaRezultat && $cijenaRezultat->num_rows > 0) {

                $usluga = $cijenaRezultat->fetch_assoc();
                $cijena = $usluga['cijena'];

                $veza->preparedUpdate(
                    "UPDATE termin_usluga
                     SET usluga_id = ?,
                         kolicina = ?,
                         cijena = ?
                     WHERE id = ?
                     AND termin_id = ?",
                    "iidii",
                    $uslugaID,
                    $kolicina,
                    $cijena,
                    $terminUslugaID,
                    $terminID
                );
            }
        }
    }

    if ($uspjeh) {
        $poruka = "Uspješno ažuriranje!";
    } else {
        $poruka = "Greška prilikom ažuriranja termina!";
    }
}

$rez = $veza->preparedSelect(
    "SELECT admin
    FROM user
    WHERE id = ?",
    "i",
    $_SESSION['id']
);
$admin = $rez->fetch_assoc();

?>
<body>
    <header>
        <ul class = "nav">
            <li><a href='logout.php'>Odjava</a></li>
            <li><a href='popis_klijenata.php'>Klijenti</a></li>
            <li><a href='dodaj_klijenta.php'>Dodaj klijenta</a></li>
            <li><a href='dodaj_termin.php'>Dodaj termin</a></li>
           <li><a href='prosli_termini.php'>Moji prošli termini</a></li>
           <?php if($admin['admin'] === 1) {echo "<li><a href='generiraj_kod.php'>Kod za registraciju</a></li>";} ?>
        </ul>
    </header>
    <div class = "container">
        <h3>Pozdrav <?php echo $ime;?> </h3>
        <h5> Moji nadolazeći termini</h5>
        <?php if(!empty($poruka)) {echo "<p class = 'poruka'>" . $poruka . "</p>";} 

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
                    termin_usluga.cijena,
                    '€)'
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
        AND t.date >= CURDATE()

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

$usluge = $veza->selectDB(
    "SELECT id_usluga, naziv, cijena
     FROM usluga
     ORDER BY naziv"
);

if ($odg->num_rows != 0) {
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
                    <th>Akcije</th>
                </tr>

            </thead>

            <tbody>
        "; 
    while ($termin = $odg->fetch_assoc()) {

        print "<tr>";
        if (
            $terminZaUrediti !== null &&
            (int)$terminZaUrediti['id'] === (int)$termin['id']
        ) {

            print "
                <form method='POST'>

                    <input type='hidden'
                        name='termin_id'
                        value='" . $termin['id'] . "'>
            ";


            // KLIJENT
            // IME
            print "
                <td>
                    " . htmlspecialchars($termin['ime']) . "
                </td>
            ";

            // PREZIME
            print "
                <td>
                    " . htmlspecialchars($termin['prezime']) . "
                </td>
            ";


            // DATUM
            print "
                <td>
                    <input type='date'
                        name='datum'
                        value='" . htmlspecialchars(
                            $termin['date']
                        ) . "'>
                </td>
            ";

            // VRIJEME
            print "
                <td>
                    <input type='time'
                        name='vrijeme'
                        value='" . htmlspecialchars(
                            $termin['time']
                        ) . "'>
                </td>
            ";

            // KOMENTARI
            print "
                <td>
                    <textarea name='komentari'>" .
                        htmlspecialchars(
                            $termin['komentari']
                        ) .
                    "</textarea>
                </td>
            ";

            // USLUGE
            print "<td>";

            foreach ($uslugeZaUrediti as $usluga) {

                $terminUslugaID = (int)$usluga['id'];
                $uslugaID = (int)$usluga['usluga_id'];
                $kolicina = (int)$usluga['kolicina'];

                print "
                    <div style='margin-bottom: 10px;'>

                        <select name='usluga[$terminUslugaID]'>
                ";

                $sveUsluge = $veza->preparedSelect(
                    "SELECT id_usluga, naziv
                    FROM usluga
                    ORDER BY naziv"
                );

                while ($opcija = $sveUsluge->fetch_assoc()) {

                    $selected = '';

                    if ((int)$opcija['id_usluga'] === $uslugaID) {
                        $selected = 'selected';
                    }

                    print "
                        <option value='" . $opcija['id_usluga'] . "' $selected>
                            " . htmlspecialchars($opcija['naziv']) . "
                        </option>
                    ";
                }

                print "
                        </select>

                        <input
                            type='number'
                            name='kolicina[$terminUslugaID]'
                            value='$kolicina'
                            min='1'
                            style='width: 70px;'
                        >

                    </div>
                ";
            }

            print "</td>";

            // CIJENA - samo prikaz
            print "
                <td>
                    " . $termin['ukupna_cijena'] . " €
                </td>
            ";

            // SPREMI
            print "
                <td>

                    <button type='submit'
                            name='spremi'>
                        Spremi
                    </button>

                </td>

                </form>
            ";
        } else {


            // ==================================
            // NORMALNI PRIKAZ
            // ==================================         

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

            print "
                <td>

                    <form method='POST'>

                        <input type='hidden'
                            name='termin_id'
                            value='" . $termin['id'] . "'>

                        <button type='submit'
                                name='update'>
                            Uredi
                        </button>

                    </form>


                    <form method='POST'
                        onsubmit=\"return confirm(
                            'Jeste li sigurni da želite obrisati ovaj termin?'
                        );\">

                        <input type='hidden'
                            name='termin_id'
                            value='" . $termin['id'] . "'>

                        <button type='submit'
                                name='obrisi'>
                            Obriši
                        </button>

                    </form>

                </td>
            ";
        }


        print "</tr>";
    }

} else {
    print "
    <div class = 'prazno'>
        <h1>Trenutno nema nikakvih termina!</h1>
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