<?php
/**
 * Created by PhpStorm.
 * User: Alessandro Gaballo
 * Date: 07/06/2016
 * Time: 19:19
 */
include 'functions.php';
if (isset($_POST['hours'], $_POST['minutes'], $_POST['last'], $_POST['user']) && isset($_SESSION['231587_logged'])) {
    if (!stringEmpty(array($_POST['hours'], $_POST['minutes'], $_POST['last'], $_POST['user']))) {
        if (!validateInput($_POST['hours']) || !validateInput($_POST['minutes']) || !validateInput($_POST['last']) || !validateInput($_POST['user'])) {
            $_SESSION['231587_invalid_insert'] = true;
        } else {
            $start_time = $_POST['hours'] . ":" . $_POST['minutes'];
            if (!bookItem($_POST['user'], $start_time, $_POST['last'])) {
                $_SESSION['231587_invalid_insert'] = true;
            }
            else {
                echo "prenotato\n";
            }
        }
    }
}
redirect("mieprenotazioni.php");
?>