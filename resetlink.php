<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'baza.class.php';
require 'mail.php';

$veza = new Baza();
$veza->spojiDB();

$greska = array();

if (isset($_POST['submit'])) {

    $input = trim($_POST['username'] ?? '');

    if ($input === '') {

        $greska[] = "Unesite korisničko ime ili email.";

    } else {

        $emailQuery = "SELECT id, username, email
                       FROM user
                       WHERE username = ? OR email = ?";

        $result = $veza->preparedSelect(
            $emailQuery,
            "ss",
            $input,
            $input
        );

        if (!$result) {

            $greska[] = "Došlo je do greške pri pristupu bazi.";

        } else {

            $row = $result->fetch_assoc();

            if (!$row) {

                $greska[] = "Korisničko ime ili email ne postoji.";

            } else {

                $id = $row['id'];
                $email = $row['email'];
                $username = $row['username'];

                // Generiranje tokena
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                // Spremanje tokena u bazu
                $insert = "INSERT INTO password_resets
                           (user_id, token_hash, expires, created)
                           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())";

                $uspjesno = $veza->preparedUpdate(
                    $insert,
                    "ss",
                    $id,
                    $tokenHash
                );

                if (!$uspjesno) {

                    $greska[] = "Došlo je do greške pri izradi linka za resetiranje lozinke.";

                } else {

                    // PRODUKCIJSKI URL
                    $resetLink = 'https://amastudio.hr/passreset.php?token=' . urlencode($token);

                    $naslov = "Link za novu lozinku";

                    $poruka = "Pozdrav " . $username . ",

Link za resetiranje lozinke:

" . $resetLink . "

Link vrijedi 10 minuta.

Ako niste tražili resetiranje lozinke, molimo vas da zanemarite ovu poruku.";

                    if (posaljiMail($email, $naslov, $poruka)) {

                        $greska[] = "Link za resetiranje lozinke je poslan na vašu email adresu.";

                    } else {

                        $greska[] = "Došlo je do pogreške prilikom slanja emaila.";
                    }
                }
            }
        }
    }
}

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
    </head>

    <body>
        <div class="container">
            <div class = "obrazac">
                <form name="prijava" id="forma" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">

                    <p class="upute">Unesite korisničko ime ili email.</p>

                    <label for="username">Korsiničko ime: </label>
                    <input id="username" class="txtBox" name="username" type="text" placeholder="Unesite korisničko ime" autofocus /><br>
                    
                    <?php if (!empty($greska)): ?>
                        <p class="greska">
                            <?php foreach ($greska as $g) {echo $g . "<br>"; } ?>
                        </p>
                    <?php endif; ?>
                    
                    <input name="submit" class="button-primary" type="submit" value="Resetiraj" form="forma"/>
                    <a class = "button" href="prijava.php">Natrag na prijavu</a>
                </form>
            </div>
        </div>
        <footer>    

            <p>&copy; 2026
                Viktor Goleš
        </footer>
    </body>
</html>