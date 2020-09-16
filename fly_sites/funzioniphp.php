<?php
$GLOBALS['fila']=10; // riga 
$GLOBALS['posto']=6;  // colonna
function connect(){
        $conn = mysqli_connect("localhost","root","","esonero");
        if(mysqli_connect_error())
        { 
           myRedirect("ERRORE di collegmaneto al DB");
        }
        return $conn;
};
function userLoggedIn() {
    if (isset($_SESSION['utente'])) {
        return $_SESSION['utente'];
    } else {
    return false;
    }
};
function myRedirect($msg="") {
    header('HTTP/1.1 307 temporary redirect');
    // L’URL relativo è accettato solo da HTTP/1.1
    header("Location: home.php?msg=".urlencode($msg));
    exit; // Necessario per evitare ulteriore
    // processamento della pagina
};

function myDestroySession() {
    $_SESSION=array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time()-3600*24,
        $params["path"],$params["domain"],
        $params["secure"], $params["httponly"]);
    }
    session_destroy(); // destroy session
};


function stampa_tab($conn){
        if(!$conn){
            echo "Connesione non eseguita";
            return;
        }
        mysqli_autocommit($conn,false);
        $query="SELECT * FROM posti FOR UPDATE";
        $ris=mysqli_query($conn,$query);
        if($ris==false)
             mysqli_autocommit($conn,true);
        
        echo "<table>";
        echo "<tr>";
       $num=mysqli_num_fields ($ris);
       $num=(int)($num/2);
       $num=$num+1;
       $giro=0;

        while ($property = mysqli_fetch_field($ris)) { 
            $giro++;
            if($property->name!="ID")
                echo "<th>".$property->name."</th>";
            if($giro==$num)
                echo "<th></th>";
        } 

        echo "</tr>";          
 
        for($j=1;($riga = mysqli_fetch_array($ris,MYSQLI_NUM))!=null;$j++) {
            
                echo "<tr>";
                for($i=1;$i<mysqli_num_fields($ris);$i++){
                    $pos=$j."-".($i);
                    $tipo_posto=tipo_posto($riga[$i]);
                    if($i==$num)
                    {
                        echo "<th>"." $j "."</th>";
                        echo "<td>"."<input disabled value='$riga[$i]' class='$tipo_posto' type='button' name='$pos'>"."</td>";
                    }else
                        echo "<td>"."<input disabled value='$riga[$i]' class='$tipo_posto' type='button' name='$pos'>"."</td>";
                }
                echo "</tr>";    
            }

        echo "</table>";
        mysqli_free_result($ris);
        mysqli_autocommit($conn,true);
        close($conn);
};
function close($conn)
{
    mysqli_close($conn);
};

function tipo_posto($val){
 
    if($val==0)
        return "posto libero";
    elseif($val==1){
        return "posto prenotato";
    }
    else
        return "posto acquistato";
}

function login_utente($user,$psw){
    $conn=connect();
    
    if(!$conn){
        return false;
    }else{

        
        //$psw = password_hash($psw, PASSWORD_DEFAULT);
        //$psw=md5($psw);
        
        $user = mysqli_real_escape_string($conn,$user);
        $user = filter_var($user, FILTER_SANITIZE_EMAIL); 

        if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
          return false;
        }
        
        $query="SELECT password FROM utenti where user='$user'  FOR UPDATE";
        $ris=mysqli_query($conn,$query);
       
        if($ris==false){
            return false;
        }
        else{
            $riga = mysqli_fetch_array($ris,MYSQLI_ASSOC);
            //
            $hash=$riga['password'];
            if(password_verify($psw, $hash)){    /// check password con algoritmo di hash 
            //
            //if($psw==$riga["password"]){
                //session_start();   commentato perchè già richiamato nel session.php
                $_SESSION['utente']=$user;
                return true;
            }
            else{
                myDestroySession(); // logout
                return 2;
            }
                

        }
        mysqli_free_result($ris);
    }
    close($conn);
}

function stampa_tab_con_user_log($conn){
    if(!$conn){
        echo "Connesione non eseguita";
        return;
    }

    $query="SELECT * FROM posti  FOR UPDATE";
    $ris=mysqli_query($conn,$query);

    echo "<table>";
    echo "<tr>";
    $num=mysqli_num_fields ($ris);
    $num=(int)($num/2);
    $num=$num+1;
    $giro=0;
    
    while ($property = mysqli_fetch_field($ris)) { 
        $giro++;
        if($property->name!="ID")
            echo "<th>".$property->name."</th>";
        if($giro==$num)
            echo "<th></th>";
    } 

    echo "</tr>";      
    /*    
        AGGIUNTA    
    */    
    $posti=array();

    $utente_log=userLoggedIn();
    //$utente=userLoggedIn();
    /*
    $query="SELECT posto,fila FROM prenotazioni where user='".$utente_log."' 
            AND id <= (SELECT MAX(ID) from prenotazioni where user<>'".$utente_log."' FOR UPDATE) FOR UPDATE";
    $ris2=mysqli_query($conn,$query);
    if($ris2!=false)
    {
        for($i=0;($riga = mysqli_fetch_array($ris2,MYSQLI_NUM))!=null;$i++) {
            $name=$riga[0]."-".$riga[1];
            $posti[$i]=$name;
        } 
    }*/
    // MODIFICA PER VISUALIZZARE CHI PRENOTATO PER ULTIMO.
    $query="SELECT posto,fila FROM prenotazioni as P
    WHERE user='".$utente_log."'
    AND id >= (SELECT MAX(id) from prenotazioni where posto=P.posto and fila=P.fila and user<>'".$utente_log."'   )";
    $ris2=mysqli_query($conn,$query);
    if($ris2!=false)
    {
        for($i=0;($riga = mysqli_fetch_array($ris2,MYSQLI_NUM))!=null;$i++) {
            $name=$riga[0]."-".$riga[1];
            $posti[$i]=$name;
        } 
    }
    $query="SELECT posto,fila FROM prenotazioni as P
    WHERE user='".$utente_log."'
    AND posto NOT in (SELECT posto from prenotazioni where posto=P.posto and fila=P.fila and user<>'".$utente_log."'  )
    AND fila NOT in (SELECT fila from prenotazioni where posto=P.posto and fila=P.fila and user<>'".$utente_log."' )";
    $ris2=mysqli_query($conn,$query);
    if($ris2!=false)
    {
        for(;($riga = mysqli_fetch_array($ris2,MYSQLI_NUM))!=null;$i++) {
            $name=$riga[0]."-".$riga[1];
            $posti[$i]=$name;
        } 
    }

    /*
        fine 
    */
    for($j=1;($riga = mysqli_fetch_array($ris,MYSQLI_NUM))!=null;$j++) {
        
            echo "<tr>";
            for($i=1;$i<mysqli_num_fields($ris);$i++){
                $pos=$j."-".($i);
                $tipo_posto=tipo_posto($riga[$i]);
                ////
                // verifica che quel sia dell'utente.
                $pos2=formNUMtoLetter($i)."-".($j);
                if(in_array($pos2, $posti)==true){
                    $tipo_posto="posto prenotato_me";
                }
                
                ///
                if($i==$num)
                {
                    echo "<td>"." $j "."</td>";
                    echo "<td>"."<input value='$riga[$i]' onclick='change_status_posto(this);'  class='$tipo_posto' type='button' name='$pos'>"."</td>";
                }else
                    echo "<td>"."<input value='$riga[$i]' onclick='change_status_posto(this);'  class='$tipo_posto' type='button' name='$pos'>"."</td>";
            }
            echo "</tr>";    
        }

    echo "</table>";
    mysqli_free_result($ris);
    close($conn);
};

function logout_utente(){
    session_destroy();
}

function nume_posti_lib_occ($conn){
    if(!$conn){
        echo "Connesione non eseguita";
        return;
    }

    $query="SELECT * FROM posti  FOR UPDATE";
    $ris=mysqli_query($conn,$query);
    
    $tot=0;
    $liberi=0;
    $prenotati=0;
    $occupati=0;        

    for(;($riga = mysqli_fetch_array($ris,MYSQLI_NUM))!=null;) {
            for($i=1;$i<mysqli_num_fields($ris);$i++){
                $tot++;
               if($riga[$i]==0)
                    $liberi++;
               if($riga[$i]==1)
                    $prenotati++;
               if($riga[$i]==2)
                    $occupati++;
            }
    }
    mysqli_free_result($ris);
    close($conn);

    echo "<div class='visnum'>Totali<h2>".$tot."</h2></div>";
    echo "<div class='visnum'>Liberi<h2>".$liberi."</h2></div>";
    echo "<div class='visnum'>Prenotati<h2>".$prenotati."</h2></div>";
    echo "<div class='visnum'>Occupati<h2>".$occupati."</h2></div>";
};
function registra_utente($user,$psw,$psw_rpt){
    $conn=connect();
    
    if(!$conn){
        return false;
    }else{
        if (!preg_match('/^[a-z]+[A-z0-9]+/',$psw)) {
            return 3;
        }
        if (!preg_match('/^[a-z]+[A-z0-9]+/',$psw_rpt)) {
            return 4;
        }

        //$psw=md5($psw);
        //$psw_rpt=md5($psw_rpt);
        if($psw != $psw_rpt)
            return 5;
        
        // sale sulla password 
        $psw = password_hash($psw, PASSWORD_BCRYPT);
        // password_bcrypt è l'algoritmo usato per la funzione di hash con il sale
        

        $user = mysqli_real_escape_string($conn,$user);
        $user = filter_var($user, FILTER_SANITIZE_EMAIL); 
        if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
          return false;
        }

        // verifica che l'utente non esiste gia nel database
        mysqli_autocommit($conn,false);
        $query="SELECT user FROM utenti where user='".$user."' FOR UPDATE";
        $ris=mysqli_query($conn,$query);
        if(mysqli_num_rows($ris)>0){
            mysqli_autocommit($conn,true);
            close($conn);
            return 6;
        }

        mysqli_free_result($ris);
        // inserisco l'utente nel database
        $query="INSERT INTO utenti (user,password) VALUES ('".$user."','".$psw."')";
        $ris=mysqli_query($conn,$query);
       
        if($ris==false){
            mysqli_autocommit($conn,true);
            close($conn);
    
            return false;
        }
        else{
            mysqli_autocommit($conn,true);
            mysqli_free_result($ris);
            close($conn);

            //login_utente($user,$psw);         // richiama la funzione log in ; cosi al nuovo reindirizzamento sono loggato
            // prima di abbandonare faccio start session e setto il $_SESSION utente , in modo che alla redicetion l'untente si 
            // ritrova gia collegato .
            session_start();
            $_SESSION['utente']=$user;
            return "OK";              
        }

    }
    
};


function creaDB_table($fila_new,$posto_new){

    $conn=connect();

    $fila_new=nl2br(htmlentities($fila_new));
    $posto_new=nl2br(htmlentities($posto_new));
    
    mysqli_autocommit($conn,true);
    $query="SELECT righe,colonne FROM dimensioni WHERE ID=1  FOR UPDATE";
    $ris=mysqli_query($conn,$query);
    $riga = mysqli_fetch_array($ris,MYSQLI_NUM);

    if($riga[0]==$fila_new && $riga[1]==$posto_new){
        mysqli_autocommit($conn,false);
        return 0;
    }
    else{       
        mysqli_free_result($ris);
        $query="UPDATE `dimensioni` SET `colonne`=$posto_new , `righe`=$fila_new  WHERE `ID`=1";
        $ris2=mysqli_query($conn,$query);
        if($ris==false)
        {
            mysqli_autocommit($conn,true);
            return ;
        }
        $posto=$posto_new;
        $fila=$fila_new;
    }

    $alfabeto =   array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
    
    // creazione tabella con i campi indicati;
    $sql = "CREATE TABLE posti (
        ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY";
    
    for($i=1;$i<=$posto;$i++)
    {
        $colonna=$alfabeto[$i-1];
        $colonna=strtoupper($colonna);
        $sql=$sql.", $colonna int(11) DEFAULT 0";
    }
        $sql=$sql.")";
        
        
        if (($c=mysqli_query($conn, $sql))) {
            if($c==false){
                mysqli_rollback($conn);
                mysqli_autocommit($conn,true);
                return;
            }
        } else {
            
            $sql2="DROP TABLE posti ";
            $a=mysqli_query($conn, $sql2);
            $b=mysqli_query($conn, $sql);
            if($a==false || $b==false){
                mysqli_rollback($conn);
                mysqli_autocommit($conn,true);
                return;
            }
        }
    // creazione tabella con il numero giusto di righe
        
        $sql="INSERT INTO posti () VALUES ()";
        for($i=0;$i<$fila;$i++)
        {
            if (mysqli_query($conn, $sql) ) {
            } else {
                echo "Error creating table: " . mysqli_error($conn);
                mysqli_rollback($conn);
                mysqli_autocommit($conn,true);
    
                exit;
            }
        }
        $sql="TRUNCATE TABLE prenotazioni ";
        if (mysqli_query($conn, $sql) ) {
        } else {
            echo "Error truncate posti: " . mysqli_error($conn);
            mysqli_rollback($conn);
            mysqli_autocommit($conn,true);

            exit;
        }

        mysqli_autocommit($conn,true);
        close($conn);

        return 1;
};


function formNUMtoLetter($colonna){
    $alfabeto =   array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
    $colonna=$alfabeto[$colonna-1];
    $colonna=strtoupper($colonna);
    return $colonna;
};


?>
