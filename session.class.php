<?php

class Sesija {

    const USER = "user";
    const SESSION_NAME = "login_session";

    static function kreirajSesiju() {
         if (session_id() == "") {
            session_name(self::SESSION_NAME);
            session_start();
        }
    }

    static function kreirajKorisnika($user) {
        self::kreirajSesiju();
        $_SESSION[self::USER] = $user;
    }

    static function dajKorisnika() {
        self::kreirajSesiju();
        if (isset($_SESSION[self::USER])) {
            $user[self::USER] = $_SESSION[self::USER];
        } else {
            return null;
        }
        return $user;
    }

    static function obrisiSesiju() {
        session_name(self::SESSION_NAME);

        if (session_id() != "") {
            session_unset();
            session_destroy();
        }
    }

}
?>