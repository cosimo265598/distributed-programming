<?php
include 'funzioniphp.php';
include 'session.php';

if(creaDB_table($GLOBALS['fila'],$GLOBALS['posto'])==1)
  echo "creazione nuova disposizione posti aereo nel DB";
include 'https_redirect.php';

if( ($_SERVER["REQUEST_METHOD"] == "POST")  && isset($_POST['login']) ){
    $status=login_utente($_POST['email'],$_POST['psw']);
}
else
{
  $status=-1;
  //session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <meta http-equiv="refresh" content="121;URL= login.php">   <!-- redirect dopo 121 secondi alla pagina login.php, un secondo in piu rispetto al normale -->
    <script type="text/javascript" src="fun.js"></script>
    <link type="text/css" rel="stylesheet" href="login.css">
    <link type="text/css" rel="stylesheet" href="my_style.css">
    <script  type="text/javascript" src="jquery.js"></script>
    <noscript>
    <strong>
    You don't have javascript enabled.  Good luck with that.
   </strong>
    </noscript>

    <script> mostra_utente_ajax()</script>
        

</head>
<body id="my_body">
    <!-- test cookie abilitati    -->
    <script type="text/javascript">
            cookie_enabled();
    </script>
<div class="header">
  <h1>Benvenuto sul sito di prenotazione Voli</h1>

  <div id="mostrauser" class="nomeloginheader"></div>  
</div>

<div id="conteiner" class="conteiner">
  <div class="menu">
  <ul>
    <li><a href="home.php">Mappa posti aereo</a></li>
      <li><a href="login.php">Area Personale</a></li>
      <?php
        if(userLoggedIn()==false)
           echo '<li><a href="register.php">Registrati</a></li>';
        if(userLoggedIn()!=false){
          echo '<li><a href="home.php?logout=yes">Logout</a></li>';
        }
        ?>
    </ul>
  </div>

  <div class="content" id="contenuto">
    
  <?php
    if(userLoggedIn()==false){
    ?>
  
    <div id="loginform" class="loginform">
        <h1>Login form</h1>
        <form action="login.php" method="post">

            <div class="imgcontainer">
                <img src="avatar_user.png" alt="Avatar" class="avatar">
            </div>
            
            <div class="container">
            <hr>
                <label for="uname"><b>Email</b></label>
                <input type="email" placeholder="Enter Username" name="email" required>

                <label for="psw"><b>Password</b></label>
                <input type="password" placeholder="Enter Password" name="psw" required>
            <hr>        
                <button type="submit" name="login">Login</button>
                <?php
                  if($status==2)
                    echo "<p><b>User name o password errati. Se non ti sei ancora registrato:  <a href='register.php'>REGISTARTI</a></b></p>";
                ?>
            </div>
              
        </form>
            </div>
    <?php
      
    }
    else{
    ?>
    <h1>Mappa posti aereo</h1>
            <div id="legenda" >
        <p>Per prenotare un posto click sul posto desiderato,<br>
                per deselezionare il posto, fare doppio click</p>
            <ul>
                <li><input class="posto acquistato" type="button">  Acquistato </li>
                <li><input class="posto prenotato" type="button">  Prenotato</li>
                <li><input class="posto libero" type="button">    Libero</li>
            </ul>
            <hr>
            
            <h2>Per procedere con l'acquisto</h2>
            <hr>
            <button type="button" name="ACQUISTA" onclick="acquista_posti();">ACQUISTA POSTI</button>
            <button type="button" name="AGGIORNA" onclick="aggiorna_mappa();" >AGGIORNA MAPPA DEI POSTI</button>
              
        </div>
        <div id="schema">
        <div class="testa_aereo">
         
         <h2>Scegli un posto</h2>
         </div>
         <div id="mappa_login">
          <?php
            $esito=connect();
            stampa_tab_con_user_log($esito);
          ?>
          </div>
        </div>

    <?php
    }
    ?>
    </div>
</div>
<div class="footer">
<p>MANISI COSIMO s265598</p>
</div>

</body>
</html>
