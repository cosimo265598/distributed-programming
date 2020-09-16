/*
function creaVista() {
    start=document.getElementById("schema");
    tabella="<table>";
    riga1="<tr><th>A</th><th>B</th><th>C</th><th>   </th><th>D</th><th>E</th><th>F</th></tr>";
    tabella=tabella+riga1;
    pulsante="<input type='button' name='posto'>";
    for(i=0;i<10;i++)
    {
        tabella=tabella+"<tr>";
        for(j=0;j<7;j++)
        {
            if(j!=3)
                tabella=tabella+"<td>"+pulsante+"</td>";
            else
                tabella=tabella+"<td>"+(i+1)+"</td>";
        }
        tabella=tabella+"</tr>";
    }
    tabella=tabella+"</table>";
    start.innerHTML=tabella;
};
*/
function change_status_posto(elemento){
    // jquery animazione pressione pulsante
    $(elemento).animate({height:35,width:35},"fast");
    $(elemento).animate({height:30,width:30},"fast");

    posizioneposto=elemento.getAttribute("name");

    prec_class=elemento.className;
    if( elemento.value==0){
        elemento.value=1
        elemento.className="posto prenotato_me";
        req = ajaxRequest();
        req.onreadystatechange = function (){
            if (req.readyState==4 && (req.status== 0 || req.status==200)) {
                var x=req.responseText;
                console.log(x);
                if(x=="NO")
                {
                    elemento.className=prec_class;
                    window.alert("Impossibile prenotare il posto.Il posto non è nel range della mappa posti visualizzata");
                }
                 if(x==2) 
                    window.alert("HAI GIA' PRENOTATO QUESTO POSTO!");
                 if(x=="acquistato"){
                    window.alert("Posto non piu acquistabili");
                    elemento.className="posto acquistato";
                 }
                if(x==true)
                    window.alert("POSTO PRENOTATO CORRETTAMENTE");
              }
        };
        req.open("GET","prenota.php?postoselected="+posizioneposto, true);
        req.send();
    }
    else if(elemento.value==1)
    {
        if(elemento.className=="posto prenotato_me")
        {
            req = ajaxRequest();
            req.onreadystatechange = function (){
                if (req.readyState==4 && (req.status== 0 || req.status==200)) {
                    var x=req.responseText;
                    console.log(x);
                    if(x=="NO")
                    {
                        elemento.className=prec_class;
                        window.alert("Impossibile togliere la prenotazione di un posto non presente sulla mappa.");    
                    }
                     if(x==2){
                        elemento.className="posto libero";
                        elemento.value=0;
                     }
                     if(x==false){
                        window.alert("Qualcosa è andato storto");
                     }
                    if(x==3){
                        elemento.className="posto prenotato";
                        window.alert("Posto liberato.");
                    }
                    if(x==4)
                    {
                        elemento.className="posto acquistato";
                        elemento.value=2;
                    }
                  }
            };
            req.open("GET","rimuovi_prenotazione.php?postoselected="+posizioneposto, true);
            req.send();
    
        }

        if(elemento.className=="posto prenotato")
        {
            elemento.className="posto prenotato_me";
            req = ajaxRequest();
            req.onreadystatechange = function (){
                if (req.readyState==4 && (req.status== 0 || req.status==200)) {
                    var x=req.responseText;
                    console.log(x);
                    if(x=="acquistato")
                        elemento.className="posto acquistato";
                     if(x==false){
                        window.alert("ATTENZIONE: Impossibile portare al termine la prenotazione");
                     }
                    if(x==true)
                        window.alert("POSTO PRENOTATO CORRETTAMENTE");
                  }
            };
            req.open("GET","prenota.php?postoselected="+posizioneposto, true);
            req.send();
    
        }
    }
}

function acquista_posti()
{
    req = ajaxRequest();
    req.onreadystatechange = function (){
        if (req.readyState==4 && (req.status== 0 || req.status==200)) {
            var x=req.responseText;
            console.log(x);
             if(x==0) 
                window.alert("Qualcosa è andato storto!")
             if(x==3){
                //elemento.className="posto acquistato";  // aggiunta 
                window.alert("Alcuni posti da te selezionanti non sono piu acquistabili. AGGIORNA LA PAGINA per vedere il nuovo stato");
            }
             if(x==1)
             {
                window.alert("Posti/o acquistati correttamente!");
                window.location.reload();
            }
            if(x==2){
                window.alert("Conflitto di posti!.I tuoi posti prenotati sono stati cancellati");
                aggiorna_mappa();
            }
            if(x==4) 
                window.alert("NON HAI SELEZIONATO NESSUN POSTO");
            
          }
    };
    req.open("POST","acquista.php", true);
    req.send();
};

function cookie_enabled(){
    var c=navigator.cookieEnabled;
    if(c==false)
    {
        $("#my_body").hide();
        window.alert("Cookie non abilitati!");
    }
}

var req;
var req1;

function ajaxRequest() {
    try { // Non IE Browser? 
        var request = new XMLHttpRequest()
    } catch(e1){ // No
        try { // IE 6+?
            request = new ActiveXObject("Msxml2.XMLHTTP")
        } catch(e2){ // No
               try { // IE 5?
                   request = new ActiveXObject("Microsoft.XMLHTTP")
               } catch(e3){ // No AJAX Support
                request = false
               }
          }
       }
       return request
}

function aggiorna_mappa() {
    req = ajaxRequest();
    req.onreadystatechange = function (){
        if (req.readyState==4 &&  (req.status== 0 || req.status==200)) {
           document.getElementById("mappa_login").innerHTML=req.responseText; 
        }
     };
    req.open("POST","stampa_posti.php", true);
    req.send();
}


function mostra_utente_ajax() {
    req = ajaxRequest();
    req.onreadystatechange = function (){
        if (req.readyState==4 && (req.status== 0 || req.status==200)) {
             document.getElementById("mostrauser").
             innerHTML=req.responseText; 
          }
    };
    req.open("POST","header.php", true);
    req.send();
};

  
    
