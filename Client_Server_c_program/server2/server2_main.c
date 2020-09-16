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
#include <sys/stat.h>

#include <unistd.h>
#include<sys/wait.h>
#include <signal.h>
#include "../sockwrap.h"
#include "../errlib.h"


#define MAXCHAR 1024
#define MAX_NOME 50
#define MAX_ATTESA 15
#define MAX_CODA_RICHIESTA 10
#define DELIM "GET "
#define MESSAGGIO_OK "+OK\r\n"
#define MESSAGGIO_ERR "-ERR\r\n"
#define MAX_INVIO 1048576  // 1 MByte 

char *prog_name;

int num_figli;


void handler_sig_chld() {
	printf ("Figlio terminato\n");
	int status;
	int pid;
	while ( (pid = waitpid(-1, &status, WNOHANG) ) > 0) {
		num_figli--;
	}
	/*   
		-1  aspetto qualsiasi sia il processo, sganciato dal padre durante la fork()
		WNOHANG
		return immediately if no child has exited.
	*/
	return;
}

void invio_file_to_client(int socket){
	int r_read;
	char buffer[MAXCHAR]="";
	char nomefile[MAX_NOME]="";
	char *ptr=NULL;
	int j=0;
	int size;
	uint32_t dim_file;
	uint32_t ultima_modifica;
	int byte_file_letti;
	char buffer_file[MAX_INVIO]="";
	int check_write;
	int ok=1;

	//varbile per la gestione select
	struct  timeval tval;
	fd_set cset;		

	//select nel caso in cui il client si connettese ma non invia nessuna richiesta GET file\r\n

	FD_ZERO(&cset);
	FD_SET(socket,&cset);
	tval.tv_sec=(int)MAX_ATTESA;
	tval.tv_usec=0; 

	if(select(FD_SETSIZE,&cset,NULL,NULL,&tval)>0){

		while (1)
		{
			r_read=read(socket,(void *)buffer, sizeof(buffer)-1);
			if(r_read<=0)
				return;
			//printf("Il client ha richieto: %s\n",buffer);
			if(strncmp(buffer,DELIM,4)!=0){
				ok=0;
				check_write=write(socket,MESSAGGIO_ERR,strlen(MESSAGGIO_ERR));
				if(check_write!=strlen(MESSAGGIO_ERR))
					return ;
				return;
			}

			char* rest = buffer; 		
			while (((ptr = strtok_r(rest,DELIM, &rest))!=NULL) && ok ) {
				strcpy(nomefile,ptr);
				int i=strlen(nomefile);
				

				// check protoccollo corretto del \r\n oltre alla parola GET
				if(!(nomefile[i-2]=='\r' && nomefile[i-1]=='\n')){
					check_write=write(socket,MESSAGGIO_ERR,strlen(MESSAGGIO_ERR));
					if(check_write!=strlen(MESSAGGIO_ERR))
						return ;
					return;
				}

				for(j=0;j<i;j++)	
					if(nomefile[j]=='\r' || nomefile[j]=='\n')
						nomefile[j]='\0';

				//printf("-%s-\n",nomefile);
				FILE *fp=NULL;
				if((fp=fopen(nomefile,"rb")) !=NULL)
				{
					printf("|-->Invio file: %s\n",nomefile);
					struct stat info;
					stat(nomefile, &info);
					size=info.st_size;
					ultima_modifica=htonl(info.st_mtime);

					dim_file=htonl(size);
					check_write=write (socket, MESSAGGIO_OK, strlen(MESSAGGIO_OK));
					if(check_write!=strlen(MESSAGGIO_OK))
						return ;
					check_write=write (socket, &dim_file, sizeof(size));
					if(check_write!=sizeof(size))
						return ;

					byte_file_letti=0;
					for(i=0;i<size;)
					{
						//printf("\nPrima della read");
						if( (size - i) >= MAX_INVIO){
							byte_file_letti=fread(buffer_file,1, sizeof(buffer_file), fp);
							// sono rimasti ancora byte da inviari pari ad un numero maggiore della capienza massima, quindi li prendi il piu possibile
							//printf("\n IF byte letti :%d \n",byte_file_letti);
							i= i + byte_file_letti;
							check_write=write(socket, buffer_file, byte_file_letti);
							if(check_write!=byte_file_letti)
								return ;
						
						}
						else
						{
							// scrivo/leggo i byte rimasti inferiori alla capienza massima
							byte_file_letti=fread(&buffer_file,1, (size -i),fp);
							//printf("\nELSE byte letti :%d \n",byte_file_letti);
							i= i + byte_file_letti;
							check_write=write(socket, buffer_file, byte_file_letti);
							if(check_write!=byte_file_letti)
								return ;
							//printf("\nSuperato la dimensione del file\n");

						}
						
					}
					fclose(fp);
					check_write=write (socket, &ultima_modifica, sizeof(ultima_modifica));
					if(check_write!=sizeof(ultima_modifica))
						return ;



				}
				else
				{
					//printf("%s\n",MESSAGGIO_ERR);
					printf("|-->ERR file: %s\n",nomefile);
					check_write=write(socket,MESSAGGIO_ERR,strlen(MESSAGGIO_ERR));
					if(check_write!=strlen(MESSAGGIO_ERR))
						return ;
					return;
				}


			}

			
		}
	}
	else
	{
		printf("\nNessun dato dal client ricveuto entro i %d secondi\n",MAX_ATTESA);
	}

	return;
}


int main (int argc, char *argv[])
{
	struct sockaddr_in serverTCP,clientTCP;
	socklen_t clientaddr=sizeof(clientTCP);
	int s,s1;
	int s_port;
	int r_bind;
	time_t start_server;
	pid_t childpid;

	if(argc != 2)  // check se ho i parametri che mi interessano !
		return printf("%s <port_dest>\n",argv[0] );
	

	s=Socket(PF_INET,SOCK_STREAM,0);

	printf("(%s)\t-Socket creato.\n",argv[0]);

	memset(&serverTCP, 0, sizeof(serverTCP));
	serverTCP.sin_family=AF_INET;
	s_port=atoi(argv[1]);
	serverTCP.sin_port=htons(s_port);
	serverTCP.sin_addr.s_addr=  htonl(INADDR_ANY);

	r_bind=bind(s,(SA*)&serverTCP,sizeof(serverTCP));
	if(r_bind!=0){
		printf("(%s)\t-Impossibile essere in ascolto sulla porta %s.\n",argv[0],argv[1]);
		return 0;
	}
	Listen(s,MAX_CODA_RICHIESTA);
	start_server=time(NULL);
	printf("Started: %s\n",ctime(&start_server));
	num_figli=0;
	// gestione di cattura del segnale sigchild
	signal(SIGCHLD,handler_sig_chld);

	while (1)
	{
		s1=Accept(s,(SA*)&clientTCP,&clientaddr);
		// funzione che gestisce le richieste di trasmissione 
		// aggiunta per rendere il server concorrente.
		// una richiesta = un processo dedicato.

		if ((childpid = fork()) < 0)
			return printf("ERROR:  fork() failed\n");
		else if (childpid > 0)
		{
			/* processo padre */
			num_figli++;
			printf("N figli attivi: %d\n",num_figli);
			close(s1);
			//wait(&status);
		}
		else
		{
			/* processo figlio */
			close(s);
			invio_file_to_client(s1);
			exit(0);

		}

		close(s1);
	}
	

	return 0;
}
