<?php
include 'functions.php';
openHtml();
loadSideBar();
$res=getReservations();
printReservations($res, $_SERVER['PHP_SELF']);
closeHtml();
?>