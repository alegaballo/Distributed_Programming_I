<?php
/**
 * Created by PhpStorm.
 * User: Alessandro Gaballo
 * Date: 06/06/2016
 * Time: 18:11
 */

include 'functions.php';
if (isset($_POST['p_ids']) && $_SESSION['231587_logged']) {
    //echo "parametri settati";
    //necessario per il corretto funzionamento della stampa del messaggio
    if (isset($_SESSION['231587_deleted'])) {
        unset($_SESSION['231587_deleted']);
    }
    foreach ($_POST['p_ids'] as $p_id) {
        if (!stringEmpty(array($p_id)) && is_numeric($p_id)) {
            deleteReservation($p_id);
            //echo "tornato dalla funzione";
        }
    }
}
redirect("mieprenotazioni.php");
?>