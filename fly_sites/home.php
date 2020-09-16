<?php
include 'funzioniphp.php';
if(creaDB_table($GLOBALS['fila'],$GLOBALS['posto'])==1)
  echo "creazione nuova disposizione posti aereo nel DB";
include 'https_redirect.php';
session_start();
if(isset($_GET['logout'])){
  myDestroySession();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <script type="text/javascript" src="fun.js">    </script>
    <link type="text/css" rel="stylesheet" href="my_style.css">
    <script  type="text/javascript" src="jquery-2.1.1.min.js"></script>
    <noscript>
    <strong>
    You don't have javascript enabled.  Good luck with that.
   </strong>
    </noscript>
    <script> mostra_utente_ajax()</script>
    
</head>
<body id="my_body">
    <script type="text/javascript">
         cookie_enabled();
    </script>

  <div class="header">
  <h1>Benvenuto sul sito di prenotazione Voli</h1>
  <div id="mostrauser" class="nomeloginheader">
    
  </div>
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
  
  <h1>Info posti</h1> 
    <div id="legenda" >
    Visione generale della situazione posti in aereo<br>
      Leggenda:
        <ul>
            <li><input class="posto acquistato" type="button">  Acquistato </li>
            <li><input class="posto prenotato" type="button">  Prenotato</li>
            <li><input class="posto libero" type="button">    Libero</li>
        </ul>
        <hr>
        Sommario posti:
        <hr>
        <?php
        $esito=connect();
        nume_posti_lib_occ($esito);
        ?>

    </div>
    
        
        <div id="schema">
        <div class="testa_aereo">
         
         <h2>Scegli un posto</h2>
         </div>
            <?php
            $esito=connect();
            stampa_tab($esito);
            ?>
        </div>
    
  </div>
</div>

<div class="footer">
  <p> MANISI COSIMO s265598  </p>
</div>

</body>
</html>