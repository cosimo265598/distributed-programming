/*
 * TEMPLATE 
 */
#include <stdio.h>
#include <stdlib.h>
#include <errno.h>
#include <ctype.h>
#include <stdio.h>
#include <time.h>


#include <sys/types.h>
#include <sys/socket.h>
#include <netdb.h>
#include <arpa/inet.h>
#include <netinet/in.h>

#include <string.h>

#include "../sockwrap.h"
#include "../errlib.h"

#define MAXCHAR 1024
#define MAX_NOME 30
#define MAX_ATTESA 15
#define MAX_LETTURA  1048576  // 1 MByte 
#define MESSAGGIO_OK "+OK\r\n"

char *prog_name;

int main (int argc, char *argv[])
{
	char buffer_file[MAX_LETTURA];
	int byte_file_letti;
	struct sockaddr_in connectToTcp;
	char buffer[MAXCHAR];
	char nomifiles[MAX_NOME];
	int s;
	//int s_port;
	int r_select;
	struct  timeval tval;
	fd_set cset;		
	uint32_t size_byte;
	uint32_t time_stamp;
	int num_file_to_transf;
	int check_read;
	int check_connect;
	struct addrinfo *list=NULL;
	struct sockaddr_in *solvedaddr=NULL;
	int err_code=0;

	prog_name=argv[0];
	
	if(argc <= 3)  // almeno ho un file da traferire
		return printf("%s <dest_host> <port_dest> [ .. file.txt .. ]\n",argv[0] );
	
	num_file_to_transf=3;  // tre perchè nella posizione tre ho il primo file da trasferire!


	err_code=getaddrinfo(argv[1], argv[2], NULL, &list);
	if (err_code!=0) {
		printf("(%s)\tImpossibile risolvere il nome o ip errato.\n",argv[0]);
		return 0;
	}


	solvedaddr = (struct sockaddr_in *)list->ai_addr;


	//Socket(PF_INET,SOCK_STREAM,IPPROTO_TCP);
	if ( (s = socket(PF_INET,SOCK_STREAM,IPPROTO_TCP)) < 0){
		printf ("(%s)\tImpossbile aprire il socket", argv[0]);
		freeaddrinfo(list);
		return 0;
	}

	printf("(%s)\tSocket creato.\n",argv[0]);

	memset(&connectToTcp, 0, sizeof(connectToTcp));
	connectToTcp.sin_family=AF_INET;
	//s_port=atoi(argv[2]);
	//connectToTcp.sin_port=htons(s_port);
	connectToTcp.sin_port= solvedaddr->sin_port;
	connectToTcp.sin_addr.s_addr=solvedaddr->sin_addr.s_addr;
	//Inet_aton(argv[1],(struct in_addr*)&connectToTcp.sin_addr );


	/** istaurazione connessione con il server TCP **/
	printf("(%s)\tConnessione verso...  %s : %s\n",argv[0],argv[1],argv[2] );

	//CONNESIONE VERSO IL SERVER TCP 
	
	check_connect=connect(s,(struct sockaddr *)&connectToTcp,sizeof(connectToTcp));
	if(check_connect<0){
		printf("(%s)\tErrore nella connessione verso  %s : %s\n",argv[0],argv[1],argv[2] );
		Close(s);
		freeaddrinfo(list);
		return 0;
	}
	
	// pulizia buffer prima dell'uso;
	strcpy(buffer,"");
	
	for(int j=num_file_to_transf; j<argc; j++){
		sprintf(nomifiles,"GET %s\r\n",argv[j]);
		if(strlen(nomifiles)+strlen(buffer)<= sizeof(buffer)-1)  // check se ho ancora spazio nella stringa per poter inserire i nomi dei file che voglio ricevere
			strcat(buffer,nomifiles);
		else
		{
			printf("\nAlcuni file che hai richiesto non verrano inviati , causa superamento buffer STDIN %d caratteri.\n",MAXCHAR);  // guarda MAX_CHAR 
			argc=j;
			break;
		}
		
	}

	if (send(s,(void *)buffer,strlen(buffer),0) != (ssize_t)strlen(buffer)){
		printf ("(%s)\tNon è stato possibile effettuare la send()\n", argv[0]);
		Close(s);
		freeaddrinfo(list);
		return 0;
	}
		

	// settaggio parametri per la gestione del timeout 15 secondi
	FD_ZERO(&cset);
	FD_SET(s,&cset);
	tval.tv_sec=(int)MAX_ATTESA;
	tval.tv_usec=0; 

	r_select=select(FD_SETSIZE,&cset,NULL,NULL,&tval);
	if(r_select>0){

		// gestione del traferimento per piu file 
		for(; num_file_to_transf<argc; num_file_to_transf++){


			//strcpy(buffer,"");
			// ciclo di tentativi di connessione al serve  // funzione già testata per il ritorno del valor -1
			int i=0;
			char c;
			// leggo la prima parte della stringa inviata dal server 
			// esito +OK o -ERR
			do {
				check_read=read(s, &c, sizeof(char));
				if(check_read<=0){
					Close(s);
					freeaddrinfo(list);
					return -1;
				}
				buffer[i++]=c;
			} while (c != '\n' && i < MAXCHAR-1);
			buffer[i]='\0';
				

			if(strncmp(buffer,MESSAGGIO_OK,6)==0)   // test se effettivamente ricevo +OK\r\n senza nessuna combinazione di essi
			{
				// leggo la dimensione del file e lo converto in host
				check_read=read(s,(void*)&size_byte,sizeof(uint32_t));
				if(check_read<=0){
					Close(s);
					freeaddrinfo(list);
					return -2;
				}
				// tiro fuori tutto il file 
				//                   |    100 byte nel caso esempio 
				
				size_byte=ntohl(size_byte);
				//size_byte=size_byte+100;
				//size_byte=size_byte+20;
				// Soluzione un carattere alla volta puo essere una alternativa , si e scelti un buffer da un byte 
				//  controlli per evitare di leggere pochi caratteri o troppi caratteri del previsto  
				FILE *fp=NULL;
				//strcpy(buffer_file,"");
				if((fp=fopen(argv[num_file_to_transf],"wb")) !=NULL)
				{
					byte_file_letti=0;
					for(i=0;i<size_byte;)
					{
						//printf("\nPrima della read");
						if( (size_byte - i) >= MAX_LETTURA){
							// sono rimasti ancora byte da inviari pari ad un numero maggiore della capienza massima, quindi li prendi il piu possibile
							byte_file_letti=read(s,buffer_file, sizeof(buffer_file) );
							if(byte_file_letti<=0){
								Close(s);
								freeaddrinfo(list);
								return -3;
							}
							//printf("\n IF byte letti :%d \n",byte_file_letti);
							i= i + byte_file_letti;
							fwrite(buffer_file, byte_file_letti, 1, fp);
						}
						else
						{
							// scrivo i byte rimasti inferiori alla capienza massima
							byte_file_letti=read( s, buffer_file,(size_byte -i) ) ;
							if(byte_file_letti<=0){
								Close(s);
								freeaddrinfo(list);

								return -4;
							}
							//printf("\nELSE byte letti :%d \n",byte_file_letti);
							i= i + byte_file_letti;
							fwrite(buffer_file, byte_file_letti, 1, fp);
							//printf("\nSuperato la dimensione del file\n");

						}
						
					}
					fclose(fp);
					
				}
				else
				{
					printf("Impossibile aprire il file %s",argv[num_file_to_transf]);
					return 0;
				}

				// Recupero il timestamp
				check_read=read(s,(void*)&time_stamp,sizeof(time_stamp));
				if(check_read<=0){
					Close(s);
					freeaddrinfo(list);

					return -5;
				}
				//printf("%u\t%u",time_stamp[0],time_stamp[1]);
				time_stamp=ntohl(time_stamp);
				// mi appoggio ad una varibile time_t per ottenere il reverse dell time stamp
				time_t time_file=time_stamp;
				//  ------------------
				printf("###### INFO FILE DOWNLOADED ######  (Remaining:%d file)\n",argc-num_file_to_transf-1);
				printf("# Nome file = \t\t%s\n# Dimensione del file = %d Bytes\n# Timestamp converted = %s# Timestamp = \t\t%u\n\n",argv[num_file_to_transf],size_byte,ctime(&time_file),time_stamp);
				//printf("############################\n");

			}
			else{
				printf("Errore, istruzione illegale o file successivo inesistente!\n");
				break;
			}
		}
	}
	else
		printf("(%s)---Nessuna risposta da parte del server entro %d secondi.Connesione Chiusa!\n",argv[0],MAX_ATTESA );
	
	//sleep(10);
	//printf("SONO QUI");
	
	FD_ZERO(&cset);
	FD_SET(s,&cset);
	tval.tv_sec=0.5;
	tval.tv_usec=0; 

	r_select=select(FD_SETSIZE,&cset,NULL,NULL,&tval);
	if(r_select>0){
	char d;
	int nr = read (s, &d, sizeof(char));
	if(nr>0)
		printf("\nATTENZIONE Sono stati trasferiti piu/meno byte di quelli richiesti, il/i file potrebbero essere incompleti e il suo time stamp errato.\n");
	}
	
	Close(s);
	
	freeaddrinfo(list);

	return 0;
}
