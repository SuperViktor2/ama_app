<?php

$config = require __DIR__ . '/config.php';

class Baza {
    
    /*
    const server = "localhost";
    const korisnik = $config['db_username'];
    const lozinka = $config['db_user_pass'];
    const baza = "amastudio_ama_db";
    */
    /*

    const server = "localhost";
    const korisnik = "root";
    const lozinka = "";
    const baza = "Ama_DB";

*/


    private $veza = null;
    private $greska = '';

    function spojiDB() {
        global $config;

        $this->veza = new mysqli(
            $config['db_host'],
            $config['db_username'],
            $config['db_user_pass'],
            $config['db_name']
        );

        if ($this->veza->connect_errno) {
            echo "Neuspješno spajanje na bazu: " . $this->veza->connect_errno . ", " .
            $this->veza->connect_error;
            $this->greska = $this->veza->connect_error;
        }
        return $this->veza;
    }

    function zatvoriDB() {
        $this->veza->close();
    }

    function selectDB($upit) {
        $rezultat = $this->veza->query($upit);
        if ($this->veza->connect_errno) {
            echo "Greška kod upita: {$upit} - " . $this->veza->connect_errno . ", " .
            $this->veza->connect_error;
            $this->greska = $this->veza->connect_error;
        }
        if (!$rezultat) {
            $rezultat = null;
        }
        return $rezultat;
    }

    function updateDB($upit) {
        $rezultat = $this->veza->query($upit);

        if (!$rezultat) {
            echo "Greška kod upita: {$upit} - "
                . $this->veza->errno . ", "
                . $this->veza->error;

            $this->greska = $this->veza->error;
        }

        return $rezultat;
    }

    function preparedSelect($sql, $types = "", ...$params) {

        $stmt = $this->veza->prepare($sql);

        if (!$stmt) {
            echo "Greška kod pripreme upita: "
                . $this->veza->error;
            return null;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            echo "Greška kod izvršavanja upita: "
                . $stmt->error;
            return null;
        }

        return $stmt->get_result();
    }

    function preparedUpdate($sql, $types = "", ...$params) {

        $stmt = $this->veza->prepare($sql);

        if (!$stmt) {
            echo "Greška kod pripreme upita: "
                . $this->veza->error;
            return false;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            echo "Greška kod izvršavanja upita: "
                . $stmt->error;
            return false;
        }

        return true;
    }

    function delete($tablica, $idKolona, $id) {

        $dozvoljeneTablice = [
            'client',
            'usluga',
            'termin'
        ];

        if (!in_array($tablica, $dozvoljeneTablice)) {
            return false;
        }

        $sql = "DELETE FROM $tablica WHERE $idKolona = ?";

        return $this->preparedUpdate(
            $sql,
            "i",
            $id
        );
    }
    
}

?>