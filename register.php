<?php
include 'functions.php';
secureConnection();
openHtml();
loadSideBar();
if(isset($_SESSION['231587_logged'])){
    redirect("mieprenotazioni.php");
}
if (isset($_POST['nome']) && isset($_POST['cognome']) && isset($_POST['email']) && isset($_POST['password'])
    && !stringEmpty(array($_POST['nome'], $_POST['cognome'], $_POST['email'], $_POST['password']))
) {
    if (register($_POST['nome'], $_POST['cognome'], $_POST['email'], $_POST['password'])) {
        echo "<div align='center'><h3>Congratulazioni, registrazione avvenuta con successo!</h3>\n";
        echo "<h4><a href ='index.php'>Effettua l'accesso</a></h4></div>";
    } else {
        $registration_error = true;
    }
}
if (!isset($_POST['email']) || $registration_error==true) {
    ?>


    <div class="col-md-10">
        <div>
            <h1>Registrazione:</h1>
            <form class="form-horizontal" role="form" action="register.php" method="post">
                <div class="form-group">
                    <label class="control-label col-sm-2">Nome:</label>
                    <div class="col-sm-4">
                        <input type="text" class="form-control" placeholder="Inserisci nome" name="nome" maxlength="32">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Cognome:</label>
                    <div class="col-sm-4">
                        <input type="text" class="form-control" placeholder="Inserisci cognome" name="cognome"
                               maxlength="32">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2">Email:</label>
                    <div class="col-sm-4">
                        <input type="email" class="form-control" placeholder="Inserisci email" name="email"
                               maxlength="32">
                    </div>

                </div>
                <div class="form-group">
                    <label class="control-label col-sm-2"">Password:</label>
                    <div class="col-sm-4">
                        <input type="password" class="form-control" placeholder="Inserisci password" name="password">
                        <?php if (/*isset($invalid_email) && $invalid_email == true*/ isset($_SESSION['231587_ERR_MSG'], $registration_error) && $registration_error==true ) {
                            printMessage($_SESSION['231587_ERR_MSG'],"error");
                            unset($_SESSION['231587_ERR_MSG']);
                        } ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary">Registrati</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php } ?>

</div>

</body>
</html>