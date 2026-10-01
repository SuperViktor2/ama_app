<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'baza.class.php';
require 'session.class.php';

Sesija::kreirajSesiju();

if (!isset($_SESSION['id'])) {
    header('Location: prijava.php');
    exit;
}

$veza = new Baza();
$veza->spojiDB();

$poruke = [];

/* =========================
   BRISANJE KLIJENTA
   ========================= */

if (isset($_POST['obrisi'])) {

    $klijentID = (int)($_POST['klijent_id'] ?? 0);

    // Provjera ima li klijent termine
    $provjera = $veza->preparedSelect(
        "SELECT id FROM termin WHERE client_id = ?",
        "i",
        $klijentID
    );

    if ($provjera && $provjera->num_rows > 0) {

        $poruke[] = "Klijent se ne može obrisati jer ima postojeće termine.";

    } else {

        $uspjeh = $veza->preparedUpdate(
            "DELETE FROM client WHERE id = ?",
            "i",
            $klijentID
        );

        if ($uspjeh) {
            $poruke[] = "Klijent je uspješno obrisan.";
        } else {
            $poruke[] = "Greška prilikom brisanja klijenta.";
        }
    }
}


/* =========================
   AŽURIRANJE KLIJENTA
   ========================= */

if (isset($_POST['spremi'])) {

    $klijentID = (int)($_POST['klijent_id'] ?? 0);

    $ime = trim($_POST['ime'] ?? '');
    $prezime = trim($_POST['prezime'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobitel = trim($_POST['mobitel'] ?? '');
    $komentari = trim($_POST['komentari'] ?? '');
    $red_flag = isset($_POST['red_flag']) ? 1 : 0;


    /* Dohvati trenutnu sliku */

    $rezultat = $veza->preparedSelect(
        "SELECT slika FROM client WHERE id = ?",
        "i",
        $klijentID
    );

    $klijent = $rezultat->fetch_assoc();

    $putanjaSlike = $klijent['slika'];


    /* Ako je odabrana nova slika */

    if (
        isset($_FILES['slika']) &&
        $_FILES['slika']['error'] === UPLOAD_ERR_OK
    ) {

        $tmp = $_FILES['slika']['tmp_name'];

        $tip = mime_content_type($tmp);

        $dozvoljeniTipovi = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];


        if (!isset($dozvoljeniTipovi[$tip])) {

            $poruke[] = "Dozvoljene su samo JPG, PNG i WebP slike.";

        } else {

            $ekstenzija = $dozvoljeniTipovi[$tip];

            $nazivDatoteke =
                bin2hex(random_bytes(16)) . "." . $ekstenzija;

            $novaPutanjaSlike =
                "uploads/clients/" . $nazivDatoteke;


            if (move_uploaded_file($tmp, $novaPutanjaSlike)) {

                $putanjaSlike = $novaPutanjaSlike;

            } else {

                $poruke[] = "Greška prilikom spremanja nove slike.";
            }
        }
    }


    /* Ažuriranje podataka */

    $uspjeh = $veza->preparedUpdate(
        "UPDATE client
         SET ime = ?,
             prezime = ?,
             email = ?,
             mobitel = ?,
             komentari = ?,
             slika = ?,
             red_flag = ?
         WHERE id = ?",
        "ssssssii",
        $ime,
        $prezime,
        $email,
        $mobitel,
        $komentari,
        $putanjaSlike,
        $red_flag,
        $klijentID
    );


    if ($uspjeh) {
        $poruke[] = "Podaci klijenta su uspješno ažurirani.";
    } else {
        $poruke[] = "Greška prilikom ažuriranja klijenta.";
    }
}

?>

<!DOCTYPE html>

<html lang="hr">

<head>

    <title>Ama studio - Popis klijenata</title>

    <meta charset="utf-8">

    <meta name="author" content="Viktor Goleš">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <link
        href="//fonts.googleapis.com/css?family=Raleway:400,300,600"
        rel="stylesheet"
        type="text/css"
    >

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
                        targets: [0, 9]
                    }
                ]
            });
        });
    </script>

</head>


<body>


    <header>

        <input type="checkbox" id="menu-toggle">
        <label for="menu-toggle" class="menu-ikona">Meni</label>

        <ul class = "nav">
            <li><a href='logout.php'>Odjava</a></li>
            <li><a href='main.php'>Početna stranica</a></li>
            <li><a href='dodaj_klijenta.php'>Dodaj klijenta</a></li>
            <li><a href='dodaj_termin.php'>Dodaj termin</a></li>
           <li><a href='prosli_termini.php'>Moji prošli termini</a></li>
        </ul>
    </header>


<div class="container">

    <h3>Klijenti</h3>


    <!-- PORUKE -->

    <?php if (!empty($poruke)): ?>

        <?php foreach ($poruke as $poruka): ?>

            <p class="poruka">
                <?= htmlspecialchars($poruka) ?>
            </p>

        <?php endforeach; ?>

    <?php endif; ?>

    <div class="content">

    

        <table id="tablica">

            <thead>

                <tr>

                    <th>Slika</th>
                    <th>Ime</th>
                    <th>Prezime</th>
                    <th>Datum registracije</th>
                    <th>Email</th>
                    <th>Mobitel</th>
                    <th>Komentari</th>
                    <th>Broj posjeta</th>
                    <th>Crvena zastavica</th>
                    <th>Akcije</th>

                </tr>

            </thead>


            <tbody>


<?php

$sql = "
    SELECT
        c.id,
        c.slika,
        c.ime,
        c.prezime,
        c.registriran,
        c.email,
        c.mobitel,
        c.komentari,
        c.red_flag,
        COUNT(t.id) AS broj_termina

    FROM client c

    LEFT JOIN termin t
        ON c.id = t.client_id

    GROUP BY c.id

    ORDER BY c.registriran DESC
";

$odg = $veza->selectDB($sql);


while ($klijent = $odg->fetch_assoc()):

    $id = (int)$klijent['id'];

    $uredivanje =
        isset($_POST['update']) &&
        (int)$_POST['klijent_id'] === $id;

    $formId = "uredi_" . $id;

?>


<?php if ($uredivanje): ?>


    <!-- =========================
         REDAK ZA UREĐIVANJE
         ========================= -->

    <tr>


        <!-- SLIKA -->

        <td>

            <?php if (!empty($klijent['slika'])): ?>

                <img
                    src="<?= htmlspecialchars($klijent['slika']) ?>"
                    width="80"
                    height="80"
                    style="object-fit: cover;"
                >

            <?php else: ?>

                Nema slike

            <?php endif; ?>


            <br><br>


            <input
                type="file"
                name="slika"
                accept="image/jpeg,image/png,image/webp"
                form="<?= $formId ?>"
            >

        </td>


        <!-- IME -->

        <td>

            <input
                type="text"
                name="ime"
                value="<?= htmlspecialchars($klijent['ime']) ?>"
                form="<?= $formId ?>"
            >

        </td>


        <!-- PREZIME -->

        <td>

            <input
                type="text"
                name="prezime"
                value="<?= htmlspecialchars($klijent['prezime']) ?>"
                form="<?= $formId ?>"
            >

        </td>


        <!-- DATUM REGISTRACIJE -->

        <td>

            <?= htmlspecialchars($klijent['registriran']) ?>

        </td>


        <!-- EMAIL -->

        <td>

            <input
                type="email"
                name="email"
                value="<?= htmlspecialchars($klijent['email']) ?>"
                form="<?= $formId ?>"
            >

        </td>


        <!-- MOBITEL -->

        <td>

            <input
                type="text"
                name="mobitel"
                value="<?= htmlspecialchars($klijent['mobitel']) ?>"
                form="<?= $formId ?>"
            >

        </td>


        <!-- KOMENTARI -->

        <td>

            <textarea
                name="komentari"
                form="<?= $formId ?>"
            ><?= htmlspecialchars($klijent['komentari']) ?></textarea>

        </td>


        <!-- BROJ TERMINA -->

        <td>

            <?= $klijent['broj_termina'] ?>

        </td>


        <!-- RED FLAG -->

        <td>

            <input
                type="checkbox"
                name="red_flag"
                value="1"
                form="<?= $formId ?>"
                <?= $klijent['red_flag'] ? 'checked' : '' ?>
            >

        </td>


        <!-- AKCIJE -->

        <td>


            <!--
                Forma je prazna jer su inputi
                iz drugih <td>-ova povezani s njom
                pomoću atributa form=""
            -->

            <form
                id="<?= $formId ?>"
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="klijent_id"
                    value="<?= $id ?>"
                >

            </form>


            <button
                type="submit"
                name="spremi"
                form="<?= $formId ?>"
            >
                Spremi
            </button>


        </td>


    </tr>


<?php else: ?>


    <!-- =========================
         NORMALNI REDAK
         ========================= -->

    <tr class="<?= $klijent['red_flag'] ? 'crvena-zastavica' : '' ?>">


        <!-- SLIKA -->

        <td>

            <?php if (!empty($klijent['slika'])): ?>

                <img
                    src="<?= htmlspecialchars($klijent['slika']) ?>"
                    width="80"
                    height="80"
                    style="object-fit: cover;"
                >

            <?php else: ?>

                Nema slike

            <?php endif; ?>

        </td>


        <!-- IME -->

        <td>

            <?= htmlspecialchars($klijent['ime']) ?>

        </td>


        <!-- PREZIME -->

        <td>

            <?= htmlspecialchars($klijent['prezime']) ?>

        </td>


        <!-- DATUM -->

        <td>

            <?= htmlspecialchars($klijent['registriran']) ?>

        </td>


        <!-- EMAIL -->

        <td>

            <?= htmlspecialchars($klijent['email']) ?>

        </td>


        <!-- MOBITEL -->

        <td>

            <?= htmlspecialchars($klijent['mobitel']) ?>

        </td>


        <!-- KOMENTARI -->

        <td>

            <?php if (!empty(trim($klijent['komentari']))): ?>

                <a
                    href="#"
                    class="prikazi-komentare"
                    data-komentari="<?= htmlspecialchars($klijent['komentari'], ENT_QUOTES, 'UTF-8') ?>"
                >
                    Prikaži komentare
                </a>

            <?php else: ?>

                <span class="nema-komentara">
                    Prikaži komentare
                </span>

            <?php endif; ?>

        </td>


        <!-- BROJ TERMINA -->

        <td>

            <?= $klijent['broj_termina'] ?>

        </td>


        <!-- RED FLAG -->

        <td>

            <?= $klijent['red_flag'] ? 'Da' : 'Ne' ?>

        </td>


        <!-- AKCIJE -->

        <td>


            <!-- UREDI -->

            <form method="POST">

                <input
                    type="hidden"
                    name="klijent_id"
                    value="<?= $id ?>"
                >

                <button
                    type="submit"
                    name="update"
                >
                    Uredi
                </button>

            </form>


            <!-- OBRIŠI -->

            <?php if ($klijent['broj_termina'] == 0): ?>

                <form
                    method="POST"
                    onsubmit="return confirm(
                        'Jeste li sigurni da želite obrisati ovog klijenta?'
                    );"
                >

                    <input
                        type="hidden"
                        name="klijent_id"
                        value="<?= $id ?>"
                    >

                    <button
                        type="submit"
                        name="obrisi"
                    >
                        Obriši
                    </button>

                </form>

            <?php endif; ?>


        </td>


    </tr>


<?php endif; ?>


<?php endwhile; ?>


            </tbody>

        </table>

    </div>

</div>


<footer>

    <p>
        &copy; 2026 Viktor Goleš
    </p>

</footer>

<div id="modalKomentari" class="modal-komentari">

    <div class="modal-sadrzaj">

        <button
            type="button"
            id="zatvoriKomentare"
            class="zatvori-komentare"
        >
            ×
        </button>

        <h4>Komentari</h4>

        <p id="tekstKomentara"></p>

    </div>

</div>

<script>

    const linkoviKomentara =
        document.querySelectorAll('.prikazi-komentare');

    const modal =
        document.getElementById('modalKomentari');

    const tekstKomentara =
        document.getElementById('tekstKomentara');

    const zatvori =
        document.getElementById('zatvoriKomentare');


    linkoviKomentara.forEach(function(link) {

        link.addEventListener('click', function(event) {

            event.preventDefault();

            tekstKomentara.textContent =
                this.dataset.komentari;

            modal.style.display = 'flex';

        });

    });


    zatvori.addEventListener('click', function() {

        modal.style.display = 'none';

    });


    modal.addEventListener('click', function(event) {

        if (event.target === modal) {

            modal.style.display = 'none';

        }

    });

</script>

</body>

</html>