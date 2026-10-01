<?php
error_reporting(E_ALL);

require 'baza.class.php';

$token = $_GET['token'] ?? $_POST['token'] ?? null;

if (!$token) {
    echo "Nedostaje token za resetiranje lozinke.";
    exit;
}

$tokenHash = hash('sha256', $token);

$veza = new Baza();
$veza->spojiDB();

$errors = array();


$sql = "SELECT * FROM password_resets
        WHERE token_hash = ?
        AND expires > NOW()
        AND used IS NULL";

$result = $veza->preparedSelect($sql, "s", $tokenHash);

if ($result->num_rows === 0) {
    $errors[] = "Nevažeći ili istekao token za resetiranje lozinke!";

}

$row = $result->fetch_assoc();

$userId = $row['user_id'];

if (isset($_POST['submit'])) {

    $password = $_POST['password'];
    $uspjeh = "";

    if (empty($password)) {
        $errors[] =  "Lozinka ne smije biti prazna.";
    }

    if(strlen($password) < 5) {
        $errors[] = "Lozinka mora imati vise od 5 znakova!";
    }

    if (empty($errors)) {

        $hashpassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $update = "UPDATE user
                SET password = ?
                WHERE id = ?";

        $updateSuccess = $veza->preparedUpdate(
            $update,
            "si",
            $hashpassword,
            $userId
        );

        $expire = "UPDATE password_resets
                SET used = NOW()
                WHERE token_hash = ?";

        $expireSuccess = $veza->preparedUpdate(
            $expire,
            "s",
            $tokenHash
        );

        if ($updateSuccess && $expireSuccess) {
            $uspjeh = "Lozinka je uspješno resetirana!";
        } else {
            $errors[] = "Dogodila se greška prilikom resetiranja lozinke.";
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
            <form name="reset" id="forma" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <p class="upute">Unesite novu lozinku.</p>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label for="password">Nova lozinka: </label>
                <input id="password" class="txtBox" name="password" type="password" placeholder="Unesite novu lozinku" autofocus /><br>
                <?php if (!empty($errors)): ?>

                    <?php foreach ($errors as $error): ?>

                        <p class="greska">
                            <?php echo htmlspecialchars($error); ?>
                        </p>

                    <?php endforeach; ?>

                <?php endif; ?>
                <?php if (!empty($uspjeh)): ?>

                    <p class="uspjeh">
                        <?php echo htmlspecialchars($uspjeh); ?>
                    </p>

                <?php endif; ?>
                <input name="submit" class="button-primary" type="submit" value="Resetiraj" form="forma"/>
                <a class="button" href="prijava.php"> Vrati me na prijavu</a>

            </form>
        </div>

      </div>

        <footer>

            <p>&copy; 2026
                Viktor Goleš </p>
        </footer>
    </body>
</html>