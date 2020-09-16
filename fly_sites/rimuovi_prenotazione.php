<?php
session_start();
include 'funzioniphp.php';

if(userLoggedIn()!=false && isset($_GET['postoselected']) && ($_SERVER["REQUEST_METHOD"] == "GET")){
    // check che non si abbia inserito dei caratteri malevoli nel get della richiesta
    $_GET['postoselected'] = nl2br(htmlentities($_GET['postoselected']));
    list($riga,$colonna)=explode ('-',$_GET['postoselected']);

    if( ($riga>$GLOBALS["fila"]) || ($colonna>$GLOBALS["posto"]) || $riga<1 || $colonna<1 ){
        echo "NO";
    }
    else{
        $alfabeto =   array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
        $colonna=$alfabeto[$colonna-1];
        $colonna=strtoupper($colonna);
        $esito=rimuovi_posto($riga,$colonna,$_SESSION['utente']);
        echo $esito;
    }   
}

function rimuovi_posto($riga,$colonna,$utente)
{
    $conn=connect();
    
    if(!$conn){
        return false;
    }else{
        // cancello la entry nella tabella prenotazioni 
        mysqli_autocommit($conn,false);
        $query="DELETE FROM prenotazioni where posto='".$colonna."' AND user='".$utente."' AND fila='".$riga."'";
        $ris=mysqli_query($conn,$query);
        if($ris==false){
            mysqli_autocommit($conn,true);
            return false;
        }

        // ------------------------------------------  aggiunta modifica  ------------------------------------------------ 
        // per la base dati è acquistato , ma per l'utente e prenotato da lui , sprenoto e diventa acquistato
        $query="SELECT $colonna FROM posti where  ID='".$riga."'  FOR UPDATE";
        $ris=mysqli_query($conn,$query);
        if($ris==false)
        {
            mysqli_rollback($conn);
            mysqli_autocommit($conn,true);
            close($conn);
            return false;
        }
        $riga2=mysqli_fetch_array($ris,MYSQLI_NUM);
        if($riga2[0]>1)
        {
            mysqli_autocommit($conn,true);
            close($conn);
            return 4;
        }


        // fine aggiunta     ---------------------------------------------------

        // controllo se dello stesso posto è presente un altra prenotazioni 
        $query="SELECT * FROM prenotazioni where posto='".$colonna."' AND fila='".$riga."'  FOR UPDATE";
        $ris=mysqli_query($conn,$query);
        if($ris==false)
        {
            mysqli_rollback($conn);
            mysqli_autocommit($conn,true);
            close($conn);
            return false;

        }
        if(mysqli_num_rows($ris)<=0){
            $query="UPDATE posti SET ".$colonna."=0  WHERE ID=".$riga;
            $ris=mysqli_query($conn,$query);

            if($ris==false){
                mysqli_free_result($ris);
                mysqli_rollback($conn);
                mysqli_autocommit($conn,true);
                close($conn);
                return false;
            }
            mysqli_autocommit($conn,true);
            return 2;   // diventa posto libero 
        }
        else{
            mysqli_autocommit($conn,true);
            return 3;  // diventa posto prenotato
        }
        close($conn);

    }
};

?>
