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

$greska = array();

$ime = "";
$prezime = "";
$email = "";
$mobitel = "";
$komentari = "";

$poruka = "";

$postojiKlijent = false;

if (isset($_POST['submit'])) {
    
    $ime = mb_convert_case(trim($_POST['ime']), MB_CASE_TITLE, 'UTF-8');
    $prezime = mb_convert_case(trim($_POST['prezime']), MB_CASE_TITLE, 'UTF-8');
    $email = trim($_POST['email'] ?? '');
    $mobitel = trim($_POST['mobitel'] ?? '');
    $komentari = trim($_POST['komentari'] ?? '');

    $registriran = date("Y-m-d H:i:s");

    // Provjeravamo je li korisnik već potvrdio duplikat
    $potvrdiDuplikat = isset($_POST['potvrdi_duplikat'])
        && $_POST['potvrdi_duplikat'] === '1';

    if (empty($ime)) {
        $greska[] = "Nije popunjeno: Ime";
    }

    if (empty($prezime)) {
        $greska[] = "Nije popunjeno: Prezime";
    }

    /*
     * Duplikat provjeravamo samo ako korisnik
     * još nije potvrdio da ga želi dodati.
     */
    if (empty($greska) && !$potvrdiDuplikat) {

        $odg = $veza->preparedSelect(
            "SELECT id
             FROM client
             WHERE ime = ? AND prezime = ?",
            "ss",
            $ime,
            $prezime
        );

        if ($odg && $odg->num_rows > 0) {
            $postojiKlijent = true;
        }
    }

    /*
     * Dodajemo klijenta ako:
     * - ne postoji
     * ILI
     * - korisnik je potvrdio dodavanje duplikata
     */
    if (
        empty($greska)
        && (!$postojiKlijent || $potvrdiDuplikat)
    ) {

        $insert = "INSERT INTO client
                   (ime, prezime, registriran, email, mobitel, komentari)
                   VALUES (?, ?, ?, ?, ?, ?)";

        $uspjeh = $veza->preparedUpdate(
            $insert,
            "ssssss",
            $ime,
            $prezime,
            $registriran,
            $email,
            $mobitel,
            $komentari
        );

        if ($uspjeh) {

            $poruka = "Korisnik je uspješno dodan!";

            $ime = "";
            $prezime = "";
            $email = "";
            $mobitel = "";
            $komentari = "";

        } else {

            $greska[] =
                "Dogodila se pogreška prilikom dodavanja korisnika!";
        }
    }
}
?>

<!DOCTYPE html>

<html lang="hr">

<head>

    <title>Ama studio - Dodavanje klijenta</title>

    <meta charset="utf-8">

    <meta name="author" content="Viktor Goleš">

    <link
        href="//fonts.googleapis.com/css?family=Raleway:400,300,600"
        rel="stylesheet"
        type="text/css"
    >

    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/skeleton.css">
    <link rel="stylesheet" href="css/perso.css">

</head>


<body>


<header>

    <ul class="nav">

        <li>
            <a href="main.php">
                Početna stranica
            </a>
        </li>

    </ul>

</header>

    <div class="container">

        <div class="obrazac">

            <form
                name="klijent"
                id="forma"
                method="post"
                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
            >
                <?php if ($postojiKlijent): ?>

                <input
                    type="hidden"
                    name="potvrdi_duplikat"
                    id="potvrdi_duplikat"
                    value="0"
                >

            <?php endif; ?>
                <p class="upute">
                    Popunite obrazac za dodavanje klijenata traženim podatcima
                </p>
                <label for="ime">Ime: </label>

                <input id="ime"
                       class="txtBox"
                       name="ime"
                       type="text"
                       placeholder="Unesite ime"
                       value="<?= htmlspecialchars($ime) ?>"
                       autofocus>

                <br>

                <label for="prezime">Prezime: </label>

                <input id="prezime"
                       class="txtBox"
                       name="prezime"
                       type="text"
                       placeholder="Unesite prezime"
                       value="<?= htmlspecialchars($prezime) ?>">

                <br>

                <label for="email">E-mail: </label>

                <input id="email"
                       class="txtBox"
                       type="email"
                       name="email"
                       placeholder="primjer@gmail.com"
                       value="<?= htmlspecialchars($email) ?>">

                <br>

                <label for="mobitel">Mobitel: </label>

                <input id="mobitel"
                       class="txtBox"
                       name="mobitel"
                       type="text"
                       placeholder="Unesite broj mobitela"
                       value="<?= htmlspecialchars($mobitel) ?>">

                <br>

                <label for="komentari">Komentari: </label>

                <textarea id="komentari"
                          class="u-full-width"
                          name="komentari"
                          placeholder="Unesite komentare"><?= htmlspecialchars($komentari) ?></textarea>

                <br>

                <?php if (!empty($greska)): ?>
                    <p class="greska">
                        <?php foreach ($greska as $g) {echo $g . "<br>"; } ?>
                    </p>
                <?php else: ?>
                    <p class ="uspjeh"> <?php echo $poruka . "<br>"; ?></p>
                <?php endif; ?>

                <input name="submit"
                       class="button-primary"
                       type="submit"
                       value="Dodaj klijenta">

            </form>

        </div>
    </div>

    <?php if ($postojiKlijent): ?>

<script>

    const potvrda = confirm(
        "Klijent s istim imenom i prezimenom već postoji.\n\n" +
        "Želite li ga ipak dodati?"
    );

    if (potvrda) {

        document.getElementById("potvrdi_duplikat").value = "1";

        document.getElementById("forma").submit();

    }

</script>

<?php endif; ?>

<footer>

    <p>
        &copy; 2026 Viktor Goleš
    </p>

</footer>

</body>
</html>