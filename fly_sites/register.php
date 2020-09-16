<?php

include 'funzioniphp.php';
include 'https_redirect.php';

if(isset($_POST['registrati']) && ($_SERVER["REQUEST_METHOD"] == "POST")){
    // crea utente nel database
    $status_r=registra_utente($_POST['email'],$_POST['psw'],$_POST['psw-repeat']);

    if($status_r=="OK"){
      header("Location: login.php");
    }


}
else
{
  $status_r=-1;
  session_start();
  if(userLoggedIn()!=false)
    header("Location: home.php");

}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <script type="text/javascript" src="fun.js">    </script>
    <link type="text/css" rel="stylesheet" href="login.css">
    <link type="text/css" rel="stylesheet" href="my_style.css">
    <script  type="text/javascript" src="jquery-2.1.1.min.js"></script>
    <noscript>
    <strong>
    You don't have javascript enabled.  Good luck with that.
   </strong>
    </noscript>

</head>
<body id="my_body">
    <!-- test cookie abilitati    -->
    <script type="text/javascript">
            cookie_enabled();
    </script>

<div class="header">
  <h1>Benvenuto sul sito di prenotazione Voli</h1>

</div>

<div id="conteiner" class="conteiner">
  <div class="menu">
  <ul>
    <li><a href="home.php">Mappa posti aereo</a></li>
      <li><a href="login.php">Area Personale</a></li>
      <li><a href="register.php">Registrati</a></li>

    </ul>
  </div>

  <div class="content" id="contenuto">
    <div class="loginform">
        <form action="register.php" method="post">
        <div class="container">
            <h1>Register form</h1>
            <p>Riempi i campi sottostanti per registrarti nel nostro sistema.</p>
            <hr>

            <label for="email"><b>Email</b></label>
            <input type="email" placeholder="Enter Email" name="email" required>

            <label for="psw"><b>Password</b></label>
            <input type="password" placeholder="Enter Password" name="psw" required>

            <label for="psw-repeat"><b>Repeat Password</b></label>
            <input type="password" placeholder="Repeat Password" name="psw-repeat" required>
            <hr>
            <button type="submit" name="registrati">REGISTRATI</button>
            <?php
            if($status_r==4 || $status_r==3)
              echo "<p><b>Le password deve contenere almeno un carattere alfabetico minuscolo, ed almeno un altro carattere che sia
                alfabetico maiuscolo oppure un carattere numerico</b></p>";
            if($status_r==5)
              echo "<p><b>Le password non coincidono! </b></p>";
            if($status_r==0)
              echo "<p><b>Qualcosa è andato storto nella registrazione!</b></p>";
              if($status_r==6)
              echo "<p><b>Utente già registrato</b></p>";
            ?>

        </div>
    </form>    
</div>
  </div>
</div>
<div class="footer">
<p>MANISI COSIMO s265598 </p>
</div>

</body>
</html>
