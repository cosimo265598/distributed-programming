<?php
include 'funzioniphp.php';
session_start();
if(($utente_connesso=userLoggedIn())!=false)
    echo '<img src="avatar_user.png" style="float:right;width: 3%;height: 3%;">Welcome <br> <strong>'.$utente_connesso.'</strong>';
?>
