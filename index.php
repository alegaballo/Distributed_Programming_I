<?php
include 'functions.php';
secureConnection();
if (isset($_SESSION['231587_logged']) && $_SESSION['231587_logged'] == true) {
    redirect("mieprenotazioni.php");
}
openHtml();
loadSideBar();
?>
<div class="col-md-10">
    <div>
        <h1>Effettua il login:</h1>
        <form class="form-horizontal" role="form" action="mieprenotazioni.php" method="post">
            <div class="form-group">
                <label class="control-label col-sm-2">Email:</label>
                <div class="col-sm-4">
                    <input type="email" class="form-control" placeholder="Inserisci email" name="email">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-2">Password:</label>
                <div class="col-sm-4">
                    <input type="password" class="form-control" placeholder="Inserisci password" name="password">
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-4">
                    <button type="submit" class="btn btn-primary">Login</button><br>
                    <?php
                    if (isset($_SESSION['231587_invalid_login'])) {
                        //echo $invalid_login;
                        printMessage($invalid_login, "error");
                        unset($_SESSION['231587_invalid_login']);
                    } else if (isset($_SESSION['231587_session_expired'])) {
                        //echo $invalid_session;
                        printMessage($invalid_session, "error");
                        unset($_SESSION['231587_session_expired']);
                    }
                    else{
                        echo "<br>";
                    }
                    ?>
                    <p>Non ancora registrato? <a href="register.php">Registrati ora!</a></p>
                </div>
            </div>
        </form>

        <!--
            <p>Non ancora registrato? <a href="register.php">Registrati ora!</a></p>
        </form> -->
    </div>
</div>

</div>

</body>
</html>
