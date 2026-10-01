<?php
error_reporting(E_ERROR);

    require 'baza.class.php';


    $veza = new Baza();
    $veza->spojiDB();

    //echo "SUCCESS! Connected to the database. <br>";

    $greska = array();

    if (isset($_POST['submit'])) {


        $ime = mb_convert_case(trim($_POST['ime']), MB_CASE_TITLE, 'UTF-8');
        $prezime = mb_convert_case(trim($_POST['prezime']), MB_CASE_TITLE, 'UTF-8');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $act_key = strtoupper(trim($_POST['act_key'] ?? ''));

        $hashpassword = password_hash($password, PASSWORD_DEFAULT);

        $insert = "INSERT INTO user (ime, prezime, email, username, password)
            VALUES (?, ?, ?, ?, ?)";

        $valuser = "SELECT username FROM user WHERE username = ?";
        $valemail = "SELECT email FROM user WHERE email = ?";
    

        $odg = $veza->preparedSelect($valuser, "s", $username);
        $odg2 = $veza->preparedSelect($valemail, "s", $email);

        if (mysqli_num_rows($odg) > 0) {
                array_push($greska, "Korisničko ime " . $username . " već postoji!");
        } 

        if (mysqli_num_rows($odg2) > 0) {
            array_push($greska, "Email " . $email . " se već koristi!");
        } 

        foreach ($_POST as $k => $v) {
            if (empty($v)) {
                array_push($greska, "Nije popunjeno: " . $k);
            }
        }

        if ($password != $_POST['potvrda']) {
            array_push($greska, "Lozinke nisu identicne!");
        }

        if(strlen($password) < 5) {
            array_push($greska, "Lozinka mora imati vise od 5 znakova!");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            array_push($greska, "Neispravan format emaila!");
        }

        $aktivacijskiKod = null; 
        
        if (!empty($act_key)) { 
            $sqlKod = "SELECT id, act_key, created, expired, used 
                        FROM act_key 
                        WHERE act_key = ? 
                        AND used = 0 AND expired > NOW()"; 
                        
            $rezultatKoda = $veza->preparedSelect( $sqlKod, "s", $act_key ); 
            if (!$rezultatKoda || $rezultatKoda->num_rows === 0) { 
                array_push( $greska, "Aktivacijski ključ nije valjan ili je istekao!" ); 
            } else { 
                $aktivacijskiKod = $rezultatKoda->fetch_assoc(); 
            }
        }

        if (empty($greska)) {

            if($veza->preparedUpdate($insert, "sssss", $ime, $prezime, $email, $username, $hashpassword)) {
                $updateKod = "UPDATE act_key 
                                SET used = 1 
                                WHERE id = ? AND used = 0"; 
                $kodAzuriran = $veza->preparedUpdate( $updateKod, "i", $aktivacijskiKod['id'] ); 
                if ($kodAzuriran) {
                    header('Location: prijava.php'); 
                    exit(); 
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">


        <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/skeleton.css">
        <link rel="stylesheet" href="css/perso.css">
    </head>

    <body>
      <div class="container">
        <div class="obrazac">
            <form name="registracija" id="forma" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <p class="upute">Popunite obrazac za registraciju traženim podatcima</p>


                <label for="ime">Ime: </label>
                <input id="ime" class="txtBox" name="ime" type="text" placeholder="Unesite ime" autofocus /><br>

                <label for="prezime">Prezime: </label>
                <input id="prezime" class="txtBox" name="prezime" type="text" placeholder="Unesite prezime" /><br>

                <label for="username">Korsiničko ime: </label>
                <input id="username" class="txtBox" name="username" type="text" placeholder="Unesite korisničko ime"/><br>

                <label for="email">E-mail: </label>
                <input id="email" class="txtBox" type="email" name="email" placeholder="primjer@gmail.com"/>
                <br>
                
                 <label for="password">Lozinka: </label>
                 <input id="password" class="txtBox" name="password" type="password" placeholder="Unesite lozinku" maxlength="50"/><br>

                <label for="potvrda">Ponovite lozinku: </label>
                <input id="potvrda" class="txtBox" name="potvrda" type="password" placeholder="Ponovno unesite istu lozinku" maxlength="50"/><br>

                <label for="act_key">Aktivacijski ključ: </label>
                <input id="act_key" class="txtBox" type="text" name="act_key"/>

                <?php if (!empty($greska)): ?>
                    <p class="greska">
                        <?php foreach ($greska as $g) {echo $g . "<br>"; } ?>
                    </p>
                <?php endif; ?>
                <input name="submit" class="button-primary" type="submit" value="Registriraj se" form="forma"/>
                <a class = "button" href=\Ama_app/prijava.php>Nazad na prijavu</a>


            </form>
        </div>
      </div>
        <footer>

            <p>&copy; 2026
                Viktor Goleš
        </footer>
    </body>
</html>