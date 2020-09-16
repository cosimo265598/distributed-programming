<?php
session_start();
include 'funzioniphp.php';

if(userLoggedIn()!=false && isset($_GET['postoselected']) && ($_SERVER["REQUEST_METHOD"] == "GET")){
    // check che non si abbia inserito dei caratteri malevoli nel get della richiesta
    $_GET['postoselected'] = nl2br(htmlentities($_GET['postoselected']));

   list($riga,$colonna)=explode ('-',$_GET['postoselected']);
   // controllo 
   if( ($riga>$GLOBALS["fila"]) || ($colonna>$GLOBALS["posto"]) || $riga<1 || $colonna<1 ){
       echo "NO";
   }
   else{
    $alfabeto =   array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
    $colonna=$alfabeto[$colonna-1];
    $colonna=strtoupper($colonna);
    $esito=memorizza_posto($riga,$colonna,$_SESSION['utente']);
    echo $esito;
   }
}

function memorizza_posto($riga,$colonna,$utente)
{
    $conn=connect();
    
    if(!$conn){
        return false;
    }else{
        mysqli_autocommit($conn,false);
        // aggiunta 

        // fine 
        $query="SELECT * FROM prenotazioni where posto='".$colonna."' AND user='".$utente."' AND fila='".$riga."'  FOR UPDATE";
        $ris=mysqli_query($conn,$query);
                
        if(mysqli_num_rows($ris)<=0){

            $query="SELECT $colonna FROM posti where ID=$riga FOR UPDATE";
            $ris=mysqli_query($conn,$query);
            $riga_1= mysqli_fetch_array($ris,MYSQLI_NUM);
            if($riga_1[0]<2)
            {
                $query="UPDATE posti SET ".$colonna."=1  WHERE ID=".$riga;
                $ris=mysqli_query($conn,$query);
                if($ris==false){
                    mysqli_free_result($ris);
                    //mysqli_rollback($conn);
                    mysqli_autocommit($conn,true);

                    close($conn);
                    return false;
                }
                else{
                    $user=$_SESSION['utente'];
                    $query="INSERT INTO prenotazioni (user,posto,fila,stato)  VALUES ('".$user."','".$colonna."',".$riga.",'prenotato')";
                    $ris=mysqli_query($conn,$query);
                    if($ris==false)
                        mysqli_rollback($conn);
                    //mysqli_free_result($ris);
                    
                    mysqli_autocommit($conn,true);

                    close($conn);
                    return true;
                }
            }
            else{
                mysqli_autocommit($conn,true);
                mysqli_free_result($ris);
                return "acquistato";
            }
        }
        else
        {
            mysqli_autocommit($conn,true);
            return 2;   
        }
    }
    close($conn);
};

?>
