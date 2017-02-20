<?php
include 'functions.php';
secureConnection();
if (isset($_SESSION['231587_logged'])) {
    $rsv = getUserReservations($_SESSION['231587_user']);
    openHtml();
    loadSideBar();
    printReservations($rsv, $_SERVER['PHP_SELF']);
} else if (isset($_POST['email'], $_POST['password']) && !stringEmpty(array($_POST['email'], $_POST['password']))) {
    if (login($_POST['email'], $_POST['password'])) {
        $rsv = getUserReservations($_SESSION['231587_user']);
        openHtml();
        loadSideBar();
        printReservations($rsv, $_SERVER['PHP_SELF']);
    } else {
        $_SESSION['231587_invalid_login'] = true;
        redirect("index.php");
    }
} else {
    $_SESSION['231587_session_expired'] = true;
    redirect("index.php");
}

?>

<?php if (isset($_SESSION['231587_early_deletion'])) {
    if (isset($_SESSION['231587_deleted'])) {
        printMessage("Non &egrave; stato possibile cancellare tutte le prenotazioni selezionate, assicurati che sia trascorso almeno un minuto dall'inserimento", "warning");
        unset($_SESSION['231587_deleted']);
    } else {
        printMessage("Per poter cancellare una prenotazione, attendi un minuto dal momento dell'inserimento", "error");
    }
    unset($_SESSION['231587_early_deletion']);
} else if (isset($_SESSION['231587_ERR_MSG'])) {
    printMessage($_SESSION['231587_ERR_MSG'], "error");
    unset($_SESSION['231587_ERR_MSG']);
} ?>
<?php
if (isset($_SESSION['231587_invalid_insert'])) {
    //echo "<p class='error'>Impossibile inserire prenotazione</p><br>";
    printMessage("Impossibile inserire prenotazione", "error");
    unset($_SESSION['231587_invalid_insert']);
} else if (isset($_SESSION['231587_res_truncated'])) {
    //echo "<p class='error'>La durata della prenotazione inserita &egrave; stata accorciata, non &egrave; possibile estendere una prenotazione oltre la mezzanotte</p>";
    printMessage('La durata della prenotazione inserita &egrave; stata accorciata, non &egrave; possibile estendere una prenotazione oltre la mezzanotte', "warning");
    unset($_SESSION['231587_res_truncated']);
}
?>
<h3>Aggiungi prenotazione:</h3>
<form action="insert.php" method="post" class="form-inline">
    <label>Ora inizio:</label>
    <select class="form-control" name="hours"><?php printHours(); ?></select> :
    <select class="form-control" name="minutes"><?php printMinutes(); ?></select>
    <label>Durata:</label>
    <input class="form-control" type="number" min="1" name="last">
    <input class="form-control" type="hidden" name="user" value=<?php echo $_SESSION['231587_user'] ?>>
    <button type="submit" class="btn btn-primary" <?php if (!itemAvailable()) {
        echo "disabled";
    } ?>>Aggiungi
    </button>
</form>
</div>
<?php
closeHtml();
?>
