<?php
if ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ) {
    // richiesta gia fatta in https;
}else 
{
        
    // Redirect su HTTPS
    // eventuale distruzione sessione e cookie relativo
    echo "<script>window.alert('Verrai indirizzato alla nuova pagina con il supporto HTTPS.')</script>";
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}

?>