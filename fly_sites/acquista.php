<?php
session_start();
include 'funzioniphp.php';

if(userLoggedIn()!=false && ($_SERVER["REQUEST_METHOD"] == "POST")){
    $ris=acquista_posto();
    echo $ris;
}

function acquista_posto()
{
    $conn=connect();
    
    if(!$conn){
        return 0;
    }else{
        mysqli_autocommit($conn,false);
        $query="SELECT posto,fila FROM prenotazioni where user='".$_SESSION['utente']."'  FOR UPDATE";
        $ris=mysqli_query($conn,$query);
        if($ris==false){
            mysqli_autocommit($conn,true);
            close($conn);
            return 0;
        }
        // ho almeno una tupla all'interno ;
        
        if(mysqli_num_rows($ris)>0){
            // super query per il test per verificare la presenza dei conflitti di posti;
            $query="SELECT posto,fila FROM prenotazioni as P
                    WHERE user='".$_SESSION['utente']."' 
                    AND posto in (SELECT posto from prenotazioni where posto=P.posto and fila=P.fila and user<>'".$_SESSION['utente']."' FOR UPDATE)
                    AND fila in (SELECT fila from prenotazioni where posto=P.posto and fila=P.fila and user<>'".$_SESSION['utente']."' FOR UPDATE) 
                    AND id <= (SELECT MAX(id) from prenotazioni where posto=P.posto and fila=P.fila and user<>'".$_SESSION['utente']."' FOR UPDATE ) 
                    FOR UPDATE";
            $conflitto=mysqli_query($conn,$query);
            if($conflitto==false){
                mysqli_autocommit($conn,true);
                close($conn);
                return 0;
            }
            if(mysqli_num_rows($conflitto)>0)  //ho trovato almeno un conflitto di posti  e rimuovo tutti i posti prenotai dall'utente
            {
                // libero i posti non i conflito , diventano liberi;
                $query="SELECT posto,fila FROM prenotazioni where user='".$_SESSION['utente']."' FOR UPDATE";
                $ris_aggiorna=mysqli_query($conn,$query);
                if($ris_aggiorna==false){
                    mysqli_autocommit($conn,true);
                    close($conn);
                    return 0;
                } //libero tutti i posti
                for(;($riga=mysqli_fetch_array($ris_aggiorna,MYSQLI_NUM))!=null;){
                    $query="UPDATE posti SET $riga[0]=0 WHERE ID=$riga[1]";
                    $ris2=mysqli_query($conn,$query);
                    if($ris2==false){
                        mysqli_rollback($conn);
                        mysqli_autocommit($conn,true);
                        close($conn);
                        return 0;
                    }
                }
                // rioccupo i posti in conflitto
                
                for(;($riga=mysqli_fetch_array($conflitto,MYSQLI_NUM))!=null;){
                    $query="UPDATE posti SET $riga[0]=1 WHERE ID=$riga[1]";
                    $ris2=mysqli_query($conn,$query);
                    if($ris2==false){
                        mysqli_rollback($conn);
                        mysqli_autocommit($conn,true);
                        close($conn);
                        return 0;
                    }
                }

                $query="DELETE FROM prenotazioni WHERE user='".$_SESSION['utente']."'";
                $ris=mysqli_query($conn,$query);   
                if($ris==false){
                    mysqli_rollback($conn);
                    mysqli_autocommit($conn,true);
                    close($conn);
                    return 0;
                }
                mysqli_autocommit($conn,true); close($conn);
                return 2;
            }

            for(;($riga=mysqli_fetch_array($ris,MYSQLI_NUM))!=null;){
                $query="SELECT $riga[0] FROM posti WHERE ID=$riga[1]   FOR UPDATE";
                $ris2=mysqli_query($conn,$query);

                if($ris2==false){
                    mysqli_rollback($conn);
                    mysqli_autocommit($conn,true);
                    close($conn);
                    return 0;
                }

                $riga2=mysqli_fetch_array($ris2,MYSQLI_NUM);
                if($riga2[0]>1)
                {
                    //mysqli_free_result($ris);
                    //mysqli_free_result($ris2);
                    //mysqli_rollback($conn);  ------------------------------------------------------------------------------
                    /** aggiunta gestione prenotazione posto prenotato da un altro utente */
                    $query="DELETE FROM prenotazioni WHERE posto='$riga[0]' and fila='$riga[1]'";
                    $ris=mysqli_query($conn,$query);
                    if($ris==false){
                        mysqli_rollback($conn);
                        mysqli_autocommit($conn,true);
                        close($conn);
                        return 0;
                    }
                    // --------------------------------------------------------------
                    mysqli_autocommit($conn,true);

                    close($conn);
                    return 3;
                }
            }
            //mysqli_free_result($ris2);
            //mysqli_free_result($ris);

            $query="SELECT posto,fila FROM prenotazioni where user='".$_SESSION['utente']."' FOR UPDATE";
            $ris=mysqli_query($conn,$query);
            if($ris==false){
                mysqli_rollback($conn);
                mysqli_autocommit($conn,true);
                close($conn);
                return 0;
            }

            for(;($riga=mysqli_fetch_array($ris,MYSQLI_NUM))!=null;){
                $query="UPDATE posti SET $riga[0]=2 WHERE ID=$riga[1]";
                $ris2=mysqli_query($conn,$query);
                if($ris2==false){
                    mysqli_rollback($conn);
                    mysqli_autocommit($conn,true);
                    close($conn);
                    return 0;
                }

                //mysqli_free_result($ris);
            }

            $query="DELETE FROM prenotazioni WHERE user='".$_SESSION['utente']."'";
            $ris=mysqli_query($conn,$query);
            if($ris==false){
                mysqli_rollback($conn);
                mysqli_autocommit($conn,true);
                close($conn);
                return 0;
            }

            //mysqli_free_result($ris);
        }
        else
        {
            //mysqli_free_result($ris);
            mysqli_autocommit($conn,true);
            close($conn);
            return 4;
        }
        mysqli_autocommit($conn,true);
        close($conn);
        return 1;
    }
};

?>
