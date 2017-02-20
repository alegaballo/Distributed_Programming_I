<?php
include "credentials.php";
session_start();
updateSession();
function updateSession() //WORKING
{
    $now = time();
    if (isset($_SESSION['231587_lastvisit'], $_SESSION['231587_logged']) && ($now - $_SESSION['231587_lastvisit']) > 120 && $_SESSION['231587_logged'] == true) {
        mySessionDestroy();
        redirect("index.php");
        return false;
    }
    $_SESSION['231587_lastvisit'] = $now;
    return true;
}


function stringEmpty($strings) //WORKING
{
    foreach ($strings as $s) {
        if ($s == "") {
            return true;
        }
    }
    return false;
}

function validateInput(&$string) //WORKING
{ //gli spazi a inizio e fine stringa non sono considerati errati
    $string = trim($string);
    $data = strip_tags($string);
    $data = stripslashes($data);
    //echo $string." ".$data."<br>";
    if ($data != $string)
        return false;
    return true;
}

function mySessionDestroy() //WORKING
{
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 24 * 3600, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

function secureConnection() //WORKING
{
    if ($_SERVER["HTTPS"] != "on") {
        mySessionDestroy();
        header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

function login($user, $password) //WORKING
{
    global $server, $owner, $psw, $DB;
    if (!validateInput($user) || !validateInput($password)) {
        return false;
    }

    if (!($link = mysqli_connect($server, $owner, $psw, $DB))) {
        echo "invalid connection";
    }
    if (mysqli_real_escape_string($link, $user) != $user) {
        mysqli_close($link);
        return false;
    }
    $user = mysqli_real_escape_string($link, $user);
    //non c'è bisogno dell'escape della password perchè si applica md5
    $password = md5($password);
    $query = "select email from utenti where password='" . $password . "' and email='" . $user . "';";
    if (!($res = mysqli_query($link, $query))) {
        //echo "invalid query";
        syslog(LOG_ERR, "invalid query");
    }
    if (mysqli_num_rows($res) == 0) {
        //echo "login failed";
        syslog(LOG_ERR, "login failed");
        mysqli_free_result($res);
        mysqli_close($link);
        return false;
    }
    $_SESSION['231587_user'] = $user;
    $_SESSION['231587_logged'] = true;
    $_SESSION['231587_lastvisit'] = time();
    mysqli_free_result($res);
    mysqli_close($link);
    return true;
}

function deleteReservation($p_id) //WORKING
{
    global $server, $owner, $psw, $DB, $db_error, $db_query_error;
    if (!($link = mysqli_connect($server, $owner, $psw, $DB))) {
        $_SESSION['231587_ERR_MSG']=$db_error;
        return false;
    }
    mysqli_autocommit($link,false);
    $p_id = mysqli_real_escape_string($link, $p_id);
    echo "pid " . $p_id . "\n";
    $query = "SELECT timestamp from prenotazioni WHERE p_id='" . $p_id . "' FOR UPDATE;";
    if (!($res = mysqli_query($link, $query))) {
        echo "select failed";
        syslog(LOG_ERR, "invalid query:".$query);
        $_SESSION['231587_ERR_MSG']=$db_query_error;
        mysqli_close($link);
        return false;
    }

    $tm = implode(" ", mysqli_fetch_array($res, MYSQLI_NUM));
    $timestamp = strtotime($tm);
    $t = time();
    //echo ($t)." ".$timestamp."\n";
    mysqli_free_result($res);
    if (($t - $timestamp) > 60) { //è passato un minuto dall'inserimento
        $query = "DELETE from prenotazioni WHERE p_id='" . $p_id . "'";
        if (!($res = mysqli_query($link, $query))) {
            //echo "delete failed\n";
            syslog(LOG_ERR, "invalid query:\n" . $query);
            $_SESSION['231587_ERR_MSG']=$db_query_error;
            mysqli_close($link);
            return false;
        }
        $_SESSION['231587_deleted'] = true;
        //azzero l'id se la tabella è vuota
        if(mysqli_num_rows(mysqli_query($link,"SELECT * FROM prenotazioni;"))==0){
            mysqli_query($link, "TRUNCATE prenotazioni;");    
        }

    } else {
        $_SESSION['231587_early_deletion'] = true;
        //echo "early deletion ".$_SESSION['231587_deleted'];
    }
    mysqli_commit($link);
    mysqli_close($link);
    return true;
}

function register($nome, $cognome, $email, $password) //WORKING
{
    global $server, $owner, $psw, $DB, $max_str_length, $db_error;
    if (!validateInput($nome) || !validateInput($cognome) || !validateInput($email) || !validateInput($password)) {
        //echo "input non validi<br>";
        $_SESSION['231587_ERR_MSG'] = "ERRORE: sono stati inseriti caratteri non validi";
        return false;
    }
    if (!($link = mysqli_connect($server, $owner, $psw, $DB))) {
        //echo "invalid connection<br>";
        $_SESSION['231587_ERR_MSG'] = $db_error;
        //echo $_SESSION['231587_ERR_MSG']."<br>";
        return false;
    }

    $_nome = mysqli_real_escape_string($link, $nome);
    $_cognome = mysqli_real_escape_string($link, $cognome);
    $_email = mysqli_real_escape_string($link, $email);
    $_password = mysqli_real_escape_string($link, $password);

    if ($_nome != $nome || $_cognome != $cognome || $_email != $email || $_password != $password) {
        mysqli_close($link);
        $_SESSION['231587_ERR_MSG'] = "ATTENZIONE: sono stati inseriti caratteri non validi";
        return false;
    }

    //controllo che sia una email valida
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        mysqli_close($link);
        $_SESSION['231587_ERR_MSG'] = "ATTENZIONE: email non valida";
        return false;
    }
    //controllo che la dimensione dei campi da inserire nel db non sia maggiore del consentito
    if (strlen($nome) > $max_str_length || strlen($cognome) > $max_str_length || strlen($email) > $max_str_length) {
        mysqli_close($link);
        $_SESSION['231587_ERR_MSG'] = "ATTENZIONE: i campi <b><i>nome</i></b>, <b><i>cognome</i></b> ed <b><i>email</i></b> devono essere lunghi al massimo 32 caratteri";
        return false;
    }
    $password = md5($password);
    $query = "INSERT INTO utenti (email, nome, cognome, password) VALUES ('" . $email . "', '" . $nome . "', '" . $cognome . "', '" . $password . "');";
    if (!($res = mysqli_query($link, $query))) {
        syslog(LOG_ERR, "invalid query");
        mysqli_close($link);
        $_SESSION['231587_ERR_MSG'] = "ATTENZIONE: email gi&agrave; in uso";
        return false;
    }
    mysqli_close($link);
    return true;
}

function getReservations() //WORKING
{
    global $server, $owner, $psw, $DB;
    $array = array();
    if (!($link = mysqli_connect($server, $owner, $psw, $DB))) {
        echo "invalid connection";
    }

    $query = "SELECT macchina,inizio,durata FROM prenotazioni ORDER BY inizio;";
    if (!($res = mysqli_query($link, $query))) {
        echo "invalid query<br>";
        syslog(LOG_ERR, "invalid query:\n" . $query);
    }
    $i = 0;
    while (($row = mysqli_fetch_array($res, MYSQLI_NUM)) != NULL) {
        $array[$i] = $row;
        $i++;
    }
    mysqli_free_result($res);
    mysqli_close($link);
    return $array;
}

function getUserReservations($user) //WORKING
{
    global $server, $owner, $psw, $DB;
    $array = array();
    if (!($link = mysqli_connect($server, $owner, $psw, $DB))) {
        echo "invalid connection";
    }
    //$query="select * from prenotazioni where email='".$user."';";
    $query = "SELECT user, macchina, inizio, durata, p_id FROM `prenotazioni` WHERE user='" . $user . "' ORDER BY inizio";
    if (!($res = mysqli_query($link, $query))) {
        echo "invalid query<br>";
        syslog(LOG_ERR, "invalid query:\n" . $query);
    }
    $i = 0;
    while (($row = mysqli_fetch_array($res, MYSQLI_NUM)) != NULL) {
        $array[$i] = $row;
        $i++;
    }
    mysqli_free_result($res);
    mysqli_close($link);
    return $array;

}

function redirect($url) //WORKING
{
    header('HTTP/1.1 307 temporary redirect');
    header("Location: " . $url, "logging in");
    exit();
}

function checkTime(&$inizio, &$durata) //WORKING
{
    if (!is_numeric($durata) || $durata < 0)
        return false;
    $start = explode(":", $inizio);
    if (!is_numeric($start[0]) || !is_numeric($start[1]) || $start[0] > 23 || $start[0] < 0 || $start[1] > 59 || $start[1] < 0) {
        return false;
    }
    if (strlen($start[0]) == 1 && $start[0] < 10) {
        $start[0] = "0" . $start[0];
    }
    if (strlen($start[1]) == 1 && $start[1] < 10) {
        $start[1] = "0" . $start[1];
    }
    //controllo che la prenotazione non si estenda oltre il massimo consentito
    $tm_1 = strtotime(implode(":", $start)) + 60 * $durata;
    $tm_2 = strtotime("23:59");
    if ($tm_1 >= $tm_2) {
        echo "durata: " . $durata . " diff: " . (($tm_1 - $tm_2)) . "\n";
        $durata -= ($tm_1 - $tm_2) / 60;
        $_SESSION['231587_res_truncated'] = true;
    }
    return true;
}

//i parametri sono già stati validati prima della chiamata
function bookItem($user, $inizio, $durata) //WORKING
{
    global $items, $server, $owner, $psw, $DB;
    if (!($link = mysqli_connect($server, $owner, $psw, $DB))) {
        echo "invalid connection<br>";
        return false;
    }

    $_user = mysqli_real_escape_string($link, $user);
    $_inizio = mysqli_real_escape_string($link, $inizio);
    $_durata = mysqli_real_escape_string($link, $durata);

    if ($_user != $user || $_inizio != $inizio || $_durata != $durata) {
        mysqli_close($link);
        return false;
    }

    //devo controllare che l'utente esista
    $query = "SELECT email FROM utenti WHERE email='" . $user . "';";
    if (!($res = mysqli_query($link, $query))) {
        echo "invalid query <br>";
        syslog(LOG_ERR, "invalid query:\n" . $query);
        mysqli_free_result($res);
        mysqli_close($link);
        return false;
    }
    if (mysqli_num_rows($res) == 0) {
        mysqli_free_result($res);
        mysqli_close($link);
        return false;
    }
    //arrivato qui sono certo che l'utente esista

    //controllo che l'orario sia corretto
    if (!checkTime($inizio, $durata)) {
        mysqli_close($link);
        return false;
    }

    mysqli_autocommit($link, false);
    $query = "SELECT * FROM prenotazioni FOR UPDATE "; //BLOCCO TUTTA LA TABELLA
    if (!($res = mysqli_query($link, $query))) {
        echo "invalid query <br>";
        syslog(LOG_ERR, "invalid query:\n" . $query);
        mysqli_free_result($res);
        mysqli_close($link);
        return false;
    }
    mysqli_free_result($res);
    //cerco di schedulare le macchine in modo casuale
    $machine = rand(0, 3);
    for ($i = 0; $i < $items; $i++) {
        $trovato = true;
        $query = "SELECT inizio, durata FROM prenotazioni WHERE macchina=" . (/*$i + 1*/
                $machine + 1) . ";";
        if (!($res = mysqli_query($link, $query))) {
            echo "invalid query <br>";
            syslog(LOG_ERR, "invalid query:\n" . $query);
        } else {
            while (($entry = mysqli_fetch_array($res, MYSQLI_NUM)) != NULL) {
                echo implode(" ", $entry) . "<br>";
                if (overlap($inizio, $durata, $entry[0], $entry[1])) {
                    echo "overlap<br>";
                    $trovato = false;
                    break;
                }
            }
            if ($trovato) { //i rappresenta la stampante che posso prenotare
                echo "trovato<br>";
                break;
            }
        }
        if (!$trovato) {
            $machine++;
            $machine %= 4;
        }
    }
    mysqli_free_result($res);
    if (!$trovato) {
        echo "non trovato<br>";
        return false;
    }
    echo $machine . "<br>";
    //$query="INSERT INTO prenotazioni (user, macchina, inizio, durata) VALUES ('".$user."','".$i."','".$inizio.":00','".$durata."');";
    $query = "INSERT INTO `prenotazioni` (`user`, `macchina`, `inizio`, `durata`, `timestamp`) VALUES ('" . $user . "', '" . (/*$i + 1*/
            $machine + 1) . "', '" . $inizio . ":00', '" . $durata . "', CURRENT_TIMESTAMP)";
    echo $query . "<br>";
    if (!($res = mysqli_query($link, $query))) {
        echo "invalid query<br>";
        syslog(LOG_ERR, "invalid query:\n" . $query);
        mysqli_close($link);
        return false;
    }
    echo "fuori dal for<br>";
    mysqli_commit($link);
    mysqli_close($link);
    return true;
}

function overlap($req_start, $req_duration, $cur_start, $cur_duration) //WORKING
{
    //echo $req_start." ".$req_duration." ".$cur_start." ".$cur_duration."<br>";
    $req_start = strtotime($req_start); //converto in timestamp
    $req_duration = 60 * $req_duration; //converto in secondi
    $cur_start = strtotime($cur_start);
    $cur_duration = 60 * $cur_duration;

    $req_end = $req_start + $req_duration;
    $cur_end = $cur_start + $cur_duration;
    //echo $req_start." ".$req_duration." ".$cur_start." ".$cur_duration."<br>";
    if (($req_start <= $cur_start && $req_end >= $cur_start) || ($req_start <= $cur_end && $req_end >= $cur_end) || ($req_start <= $cur_start && $req_end >= $cur_end) ||
        ($req_start >= $cur_start && $req_end <= $cur_end)
    ) {
        return true;
    }
    return false;
}

function itemAvailable() //WORKING
{
    global $items, $server, $owner, $psw, $DB;
    if (!($link = mysqli_connect($server, $owner, $psw, $DB))) {
        echo "invalid connection<br>";
    } else {
        $query = "SELECT SUM(durata) FROM prenotazioni;";
        if (!($res = mysqli_query($link, $query))) {
            echo "invalid query";
            syslog(LOG_ERR, "invalid query:\n" . $query);
        } else {
            $sum = mysqli_fetch_array($res, MYSQLI_NUM);
            if ($sum[0] == $items * 24 * 60) {
                mysqli_free_result($res);
                mysqli_close($link);
                return false;
            } else {
                mysqli_free_result($res);
                mysqli_close($link);
                return true;
            }
        }
    }
    mysqli_close($link);
    return false;
}

function printMessage($msg, $result) //WORKING
{
    $class = "alert alert-success";
    switch ($result) {
        case "error":
            echo '<br><div class="alert alert-danger" role="alert">
            <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
            <span class="sr-only">Error:</span>
                ' . $msg . '
            </div>';
            break;
        case "warning":
            echo '<br><div class="alert alert-warning" role="alert">
          <span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span>
          <span class="sr-only">Warning:</span>
            ' . $msg . '
        </div>';
            break;
        default:
            echo '<br><div class="alert alert-success" role="alert">
          <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
          <span class="sr-only">Warning:</span>
            ' . $msg . '
        </div>';
            break;
    }
}

function printReservations($reservations, $caller) //WORKING
{
    if (strpos($caller, "mieprenotazioni") !== false) {
        $caller = "mieprenotazioni.php";
    }
    switch ($caller) {
        case "mieprenotazioni.php":
            echo "<div class='col-md-10'><h1>Le tue prenotazioni</h1><form action='delete.php' method='post'><table class='table table-bordered table-hover'><tr><th>UTENTE</th><th>STAMPANTE</th><th>ORA INIZIO</th><th>ORA FINE</th><th>DURATA (minuti)</th><th></th></tr>";
            foreach ($reservations as $row) {
                $n = count($row);
                echo "<tr>";
                for ($i = 0; $i < $n; $i++) {
                    if ($i == ($n - 1)) { //insert checkbox
                        echo "<td align='center'><input type='checkbox' name='p_ids[]' value='" . $row[$i] . "'></td>";
                    } else {

                        if ($i == 2) {
                            echo "<td>" . substr($row[$i], 0, 5) . "</td>";
                            $finish = strtotime($row[$i]) + ($row[$i + 1] * 60);
                            echo "<td>" . date("H:i", $finish) . "</td>";
                        } else {
                            echo "<td>" . $row[$i] . "</td>";
                        }
                    }
                }
                echo "</tr>";
            }
            echo "</table><button type='submit' class='btn btn-danger'>Cancella prenotazioni selezionate</button></table></form>";
            break;
        default:
            echo "<div class='col-md-10'><h1>Tutte le prenotazioni</h1><table class='table table-bordered table-hover'><tr><th>STAMPANTE</th><th>ORA INIZIO</th><th>ORA FINE</th><th>DURATA (minuti)</th>";
            foreach ($reservations as $row) {
                echo "<tr>";
                //uso substr per eliminare i secondi
                for ($i = 0; $i < sizeof($row); $i++) {
                    if ($i == 1) {
                        echo "<td>" . substr($row[$i], 0, 5) . "</td>";
                        $finish = strtotime($row[$i]) + ($row[$i + 1] * 60);
                        echo "<td>" . date("H:i", $finish) . "</td>";
                    } else {
                        echo "<td>$row[$i]</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
    }

}

function loadSideBar() //WORKING
{
    echo '
    <div class="col-md-2">
    <div class="panel panel-default">
        <div class="panel-heading">
            <a data-toggle="collapse" id="toggleMe" href="#collapse1">
                <h3 class="panel-title">Men&ugrave; <span class="glyphicon glyphicon-collapse-up right"
                                                                     onclick="toggleIcon()" id="icon"></span></h3>
            </a>
        </div>
        <ul class="list-group collapse in" id="collapse1">';
    if (!isset($_SESSION['231587_logged'])) {
        echo ' <li class="list-group-item"><a href="index.php"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
            <li class="list-group-item"><a href="register.php"><span class="glyphicon glyphicon-pencil"></span>Registrati</a></li>';
    } else {
        echo '<li class="list-group-item"><a href="logout.php"><span class="glyphicon glyphicon-log-out"></span> Logout</a>
            </li>';
    }
    echo '
            <li class="list-group-item"><a href="mieprenotazioni.php"><span class="glyphicon glyphicon-edit"></span> Le
                tue prenotazioni</a></li>
            <li class="list-group-item"><a href="prenotazioni.php"><span class="glyphicon glyphicon-list"></span> Tutte
                le prenotazioni</a></li>
        </ul>
    </div>
</div>';
}

function closeHtml() //WORKING
{
    echo "</div></body></html>";
}

function printHours() //WORKING
{
    for ($i = 0; $i < 24; $i++) {
        if ($i < 10) {
            echo '<option value="0' . $i . '">0' . $i . '</option><br>';
        } else {
            echo '<option value="' . $i . '">' . $i . '</option><br>';
        }
    }
}

function printMinutes() //WORKING
{
    for ($i = 0; $i < 60; $i++) {
        if ($i < 10) {
            echo '<option value="0' . $i . '">0' . $i . '</option><br>';
        } else {
            echo '<option value="' . $i . '">' . $i . '</option><br>';
        }
    }
}

//da cancellare, non è utilizzata
function printDuration() //WORKING
{
    for ($i = 1; $i < 60; $i++) {
        echo '<option value="' . $i . '">' . $i . '</option><br>';
    }
}

function openHtml() //WORKING
{
    echo '<!DOCTYPE html>
<html>
<head>
<meta charset="ISO-8859-1">
<!-- codifica UTF-8 -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<!--<script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script> -->
<script type="text/javascript" src="JSFunctions.js"></script>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet"
	href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"
	integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7"
	crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet"
	href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css"
	integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r"
	crossorigin="anonymous">

	<!-- Personal css-->
	<link rel="stylesheet" type="text/css" href="my_stylesheet.css">
<!-- Latest compiled and minified JavaScript -->
<script
	src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"
	integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS"
	crossorigin="anonymous"></script>
</head>
<body>
<nav class="navbar navbar-default">
  <div class="container">
    <div class="navbar-header">
		<h3 class="navbar-text">Benvenuto nel servizio di prenotazione stampanti</h3>
    </div>';
    if (isset($_SESSION['231587_logged'])) {
        echo '<a class="navbar-text navbar-right" href="logout.php"><span class="glyphicon glyphicon-log-out"></span> Logout</a><p class="navbar-text navbar-right">Utente: ' . $_SESSION['231587_user'] . '</p>';
    }
    echo '
  </div>
</nav>

<div class="container-fluid">
	<noscript><p class="error">Javascript risulta disabilitato, il sito potrebbe non funzionare correttamente</p></noscript>';
}


