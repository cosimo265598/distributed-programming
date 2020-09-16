<?php
session_start();
include 'funzioniphp.php';
if(userLoggedIn()!=false && ($_SERVER["REQUEST_METHOD"] == "POST")){
    $esito=connect();
    stampa_tab_con_user_log($esito);
}
