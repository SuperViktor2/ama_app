<?php
//error_reporting(E_ERROR);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'baza.class.php';

$veza = new Baza();
$veza->spojiDB();

require 'session.class.php';
Sesija::kreirajSesiju();

$greska = array();

if (isset($_POST['submit'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $greska[] = "Nije popunjeno: Korisničko ime";
    }

    if ($password === '') {
        $greska[] = "Nije popunjeno: Lozinka";
    }
    
    if (empty($greska)) {

        $sql = "SELECT * FROM user WHERE username = ?";
        $odg = $veza->preparedSelect($sql, "s", $username);

        if ($odg->num_rows > 0)  {
            
            $user = $odg->fetch_assoc();

            $hashedPassword = $user['password'];

            if (password_verify($password, $hashedPassword)) {

                session_regenerate_id(true);

                Sesija::kreirajKorisnika($user['username']); 
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                header('Location: main.php');
                exit;

            } else {
                array_push($greska, "Netočna lozinka ili korisničko ime");
            }
        } else {
            array_push($greska, "Netočna lozinka ili korisničko ime");
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/skeleton.css">
        <link rel="stylesheet" href="css/perso.css">
    </head>

    <body>
        <div class = "container">
            <div class = "obrazac">
                <form name="prijava" id="forma" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">

                    <p class="upute">Unesite korisničko ime i lozinku u obrazac za prijavu.</p>

                    <label for="username">Korisničko ime: </label>
                    <input class = "u-full-width" username" class="txtBox" name="username" type="text" placeholder="Korisničko ime" autofocus/><br>
                    
                    <label for="password">Lozinka: </label>
                    <input class = "u-full-width" id="password" class="txtBox" name="password" type="password" placeholder="Lozinka" size="30"/><br>
                    
                    <?php if (!empty($greska)): ?>
                        <p class="greska">
                            <?php foreach ($greska as $g) {echo $g . "<br>"; } ?>
                        </p>
                    <?php endif; ?>

                    <input name="submit" class="button-primary" type="submit" value="Prijava" form="forma"/><br>
                    <a class="button" href="resetlink.php"> Zaboravljena lozinka</a>
                    <p> Nemaš račun? </p>
                    <a href="registracija.php">Registriraj se</a>
                </form>
            </div>
        </div>
        <footer>

            <p>&copy; 2026
                Viktor Goleš
        </footer>
    </body>
</html>