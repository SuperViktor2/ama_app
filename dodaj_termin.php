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
$uspjeh = "";

$user = $_SESSION['id'];

$usluge = $veza->selectDB(
    "SELECT id_usluga, naziv, cijena FROM usluga"
);

$klijenti = $veza->selectDB(
    "SELECT id, ime, prezime, red_flag FROM client ORDER BY prezime"
);

if (isset($_POST['submit'])) {

    $klijent = (int)($_POST['klijent'] ?? '');
    $datum = trim($_POST['datum'] ?? '');
    $vrijeme = trim($_POST['vrijeme'] ?? '');
    $komentari = trim($_POST['komentari'] ?? '');

    $odabraneUsluge = $_POST['usluga'] ?? array();

    if (empty($klijent)) {
        array_push($greska, "Nije popunjeno: Klijent");
    }

    if (empty($datum)) {
        array_push($greska, "Nije popunjeno: Datum");
    }

    if (empty($vrijeme)) {
        array_push($greska, "Nije popunjeno: Vrijeme");
    }

    if ($datum < date("Y-m-d")) {
        array_push($greska, "Datum ne može biti u prošlosti");
    }

    $odabraneUsluge = array_filter($odabraneUsluge);


    if (empty($odabraneUsluge)) {
        array_push($greska, "Morate odabrati barem jednu uslugu");
    }

    if (empty($greska)) {

        // Dodavanje termina
        $insert = "INSERT INTO termin
            (user_id, client_id, date, time, komentari)
            VALUES (?, ?, ?, ?, ?)";


        if ($veza->preparedUpdate(
            $insert,
            "iisss",
            $user,
            $klijent,
            $datum,
            $vrijeme,
            $komentari
        )) {

            $rezultatID = $veza->selectDB(
                "SELECT LAST_INSERT_ID() AS id"
            );

            $red = $rezultatID->fetch_assoc();
            $terminID = $red['id'];

            foreach ($odabraneUsluge as $uslugaID) {

                $rezultatUsluga = $veza->preparedSelect(
                    "SELECT cijena
                    FROM usluga
                    WHERE id_usluga = ?",
                    "i",
                    $uslugaID
                );

                $redUsluga = $rezultatUsluga ? $rezultatUsluga->fetch_assoc() : null;

                if (!$redUsluga) {
                    $greska[] = "Odabrana usluga ne postoji.";
                    continue;
                }

                $cijena = $redUsluga['cijena'];

                $insertUsluga = "INSERT INTO termin_usluga
                    (termin_id, usluga_id, kolicina, cijena)
                    VALUES (?, ?, 1, ?)";


                if (!$veza->preparedUpdate($insertUsluga, "iid", $terminID, $uslugaID, $cijena)) {
                    array_push(
                        $greska,
                        "Greška kod dodavanja usluge"
                    );
                }
            }


            if (empty($greska)) {

                $uspjeh = "Termin je uspješno dodan!";

            } else {

                echo "<p>Termin je dodan, ali dogodila se greška kod usluga.</p>";
            }

        } else {

            echo "<p>Dogodila se pogreška prilikom dodavanja termina!</p>";
        }
    }
}

?>


<!DOCTYPE html>

<html lang="hr">

    <head>
        <title>Ama studio - Dodavanje termina</title>
        <meta charset="utf-8">
        <meta name="author" content="Viktor Goleš">

        <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/skeleton.css">
        <link rel="stylesheet" href="css/perso.css">
    </head>


<body>


<header>
    <ul class = "nav">
        <li>
            <a href="main.php">
                Naslovna stranica
            </a>
        </li>
</ul>
</header>


<div class="container">
    <div class="obrazac">
            <form
                name="termin"
                id="forma"
                method="post"
                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
            >
            <!-- KLIJENT -->
                 <p> Popunite obrazac za dodavanje termina</p>

            <label for="klijent">
                Klijent:
            </label>

            <select
                id="klijent"
                class="u-full-width"
                name="klijent"
                required
            >
                <option value="">
                    -- Odaberi klijenta --
                </option>

                <?php while ($klijent = $klijenti->fetch_assoc()) { ?>

                    <option
                        value="<?php echo $klijent['id']; ?>"
                        data-red-flag="<?php echo $klijent['red_flag']; ?>"
                    >
                        <?php echo htmlspecialchars($klijent['prezime'] . " " . $klijent['ime']); ?>
                    </option>

                <?php } ?>

            </select>
             <br>
            <label for="datum">
                Datum:
            </label>

            <input
                id="datum"
                class="txtBox"
                name="datum"
                type="date"
            />

            <br>

            <label for="vrijeme">
                Vrijeme:
            </label>

            <input
                id="vrijeme"
                class="txtBox"
                name="vrijeme"
                type="time"
            />

            <br>

            <label>
                Usluge:
            </label>

            <div id="usluge-container">
                <div class="usluga-red">
                    <select
                        name="usluga[]"
                        class="odabir-usluge"
                    >

                        <option value="">
                            -- Odaberi uslugu --
                        </option>


                        <?php

                        // Ponovno dohvaćamo usluge
                        $uslugeDropdown = $veza->selectDB(
                            "SELECT id_usluga, naziv
                             FROM usluga
                             ORDER BY naziv"
                        );


                        while (
                            $usluga = $uslugeDropdown->fetch_assoc()
                        ) {

                        ?>

                            <option
                                value="<?php echo $usluga['id_usluga']; ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $usluga['naziv']
                                );
                                ?>

                            </option>

                        <?php

                        }

                        ?>

                    </select>

                </div>

            </div>


            <br>


            <!-- KOMENTARI -->

            <label for="komentari">
                Komentari:
            </label>

            <textarea
                id="komentari"
                class="u-full-width"
                name="komentari"
                placeholder="Unesite komentare"
            ></textarea>
            <?php if (!empty($greska)): ?>
                <p class="greska">
                    <?php foreach ($greska as $g) {echo $g . "<br>"; } ?>
                </p>
            <?php else: ?> 
                <p class ="uspjeh"> <?php echo $uspjeh . "<br>"; ?></p>
            <?php endif; ?> 

            <input
                name="submit"
                class="button-primary"
                type="submit"
                value="Dodaj termin"
                form="forma"
            />
        </form>

    </div>

</div>


<footer>

    <p>
        &copy; 2026 Viktor Goleš
    </p>

</footer>

<script>

const container = document.getElementById(
    "usluge-container"
);


// Kada se promijeni bilo koji dropdown
container.addEventListener("change", function(e) {


    // Provjeravamo je li promijenjen dropdown usluge

    if (
        !e.target.classList.contains(
            "odabir-usluge"
        )
    ) {

        return;

    }


    // Dohvaćamo sve dropdownove

    const sviDropdowni =
        container.querySelectorAll(
            ".odabir-usluge"
        );


    // Zadnji dropdown

    const zadnjiDropdown =
        sviDropdowni[sviDropdowni.length - 1];


    // Ako je zadnji dropdown popunjen
    // stvaramo novi dropdown

    if (
        zadnjiDropdown.value !== ""
    ) {


        const noviRed =
            document.createElement("div");


        noviRed.className =
            "usluga-red";


        noviRed.innerHTML = `

            <select
                name="usluga[]"
                class="odabir-usluge"
            >

                <option value="">
                    -- Odaberi uslugu --
                </option>

                <?php

                $uslugeJS = $veza->selectDB(
                    "SELECT id_usluga, naziv
                     FROM usluga"
                );


                while (
                    $u = $uslugeJS->fetch_assoc()
                ) {

                    echo '<option value="' .
                        $u['id_usluga'] .
                        '">' .
                        htmlspecialchars($u['naziv']) .
                        '</option>';

                }

                ?>

            </select>

        `;

        container.appendChild(
            noviRed
        );

    }

});

    document.getElementById("forma").addEventListener("submit", function(e) {

        const klijent = document.getElementById("klijent");

        const odabranaOpcija =
            klijent.options[klijent.selectedIndex];

        const redFlag =
            odabranaOpcija.dataset.redFlag;

        if (redFlag === "1") {

            const potvrda = confirm(
                "Klijent je označen crvenom zastavicom. Želite li nastaviti?"
            );

            if (!potvrda) {
                e.preventDefault();
            }
        }

    });

</script>


</body>

</html>