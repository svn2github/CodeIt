#include <time.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <unistd.h>
#include <syslog.h>
#include <errno.h>
#include <fcntl.h>
#include <mysql/mysql.h>
#include <sys/wait.h>
#include <sys/stat.h>
#define bufsize 1024
#define LOCKFILE "/var/run/judged.pid"
#define LOCKMODE (S_IRUSR|S_IWUSR|S_IRGRP|S_IROTH)

static char host_name[bufsize];
static char user_name[bufsize];
static char password [bufsize];
static char db_name  [bufsize];
static int port_number;
static int max_running;
static int sleep_time;
static int sleep_tmp;
static int oj_tot;
static int oj_mod;

static MYSQL *conn;
static MYSQL_RES *res;
static MYSQL_ROW row; 
static FILE *fp_log;
static const char query[]="SELECT solution_id FROM solution WHERE result<2 ORDER BY result ASC,solution_id ASC LIMIT 0,30";

// read the configue file
void init_mysql_conf(){
	FILE *fp;
	char buf[bufsize];
	host_name[0]=0;
	user_name[0]=0;
	password[0]=0;
	db_name[0]=0;
	port_number=3306;
	max_running=3;
	sleep_time=3;
	oj_tot=1;
	oj_mod=0;
	fp=fopen("/home/hoj/etc/judge.conf","r");
	while (fgets(buf,bufsize-1,fp)){
		buf[strlen(buf)-1]=0;
		if (      strncmp(buf,"OJ_HOST_NAME",12)==0){
			strcpy(host_name,buf+13);
		}else if (strncmp(buf,"OJ_USER_NAME",12)==0){
			strcpy(user_name,buf+13);
		}else if (strncmp(buf,"OJ_PASSWORD",11)==0){
			strcpy(password, buf+12);
		}else if (strncmp(buf,"OJ_DB_NAME",10)==0){
			strcpy(db_name,buf+11);
		}else if (strncmp(buf,"OJ_PORT_NUMBER",14)==0){
			sscanf(buf+15,"%d",&port_number);
		}else if (strncmp(buf,"OJ_RUNNING",10)==0){
			sscanf(buf+11,"%d",&max_running);
			if (max_running<1) max_running=1;
			if (max_running>8) max_running=8;
		}else if (strncmp(buf,"OJ_SLEEP_TIME",13)==0){
			sscanf(buf+14,"%d",&sleep_time);
			if (sleep_time<1) sleep_time=1;
			if (sleep_time>20) sleep_time=20;
		}else if (strncmp(buf,"OJ_TOTAL",8)==0){
			sscanf(buf+9,"%d",&oj_tot);
			if (oj_tot<=0) oj_tot=1;
		}else if (strncmp(buf,"OJ_MOD",6)==0){
			sscanf(buf+7,"%d",&oj_mod);
			if (oj_mod<0 || oj_mod>=oj_tot) oj_mod=0;
		}
	}
	sleep_tmp=sleep_time;
}

void updatedb(int solution_id,int result,int time,int memory){
	char sql[bufsize];
	sprintf(sql,"UPDATE solution SET result=%d,time=%d,memory=%d,judgetime=NOW() WHERE solution_id=%d LIMIT 1"
			,result,time,memory,solution_id);
	if (mysql_real_query(conn,sql,strlen(sql))){
		syslog(LOG_ERR | LOG_DAEMON, "%s",mysql_error(conn));
	}
}

void run_client(int runid){
	char buf[2];
	buf[0]=runid+'0'; buf[1]=0;
	execl("/usr/bin/judge_client","/usr/bin/judge_client",row[0],buf,NULL);
	exit(0);
}

int work(){
	char buf[1024];
	int retcnt;
	int i;
	static pid_t ID[8]={0,0,0,0,0,0,0,0};
	static int workcnt=0;
	int runid;
	pid_t tmp_pid;
	retcnt=0;
	conn=mysql_init(NULL);		// init the database connection
	/* connect the database */
	if(!mysql_real_connect(conn,host_name,user_name,password,
			db_name,port_number,0,0)){
		syslog(LOG_ERR | LOG_DAEMON, "%s", mysql_error(conn));
		sleep_time=60;
		return 0;
	}
	if (mysql_real_query(conn,query,strlen(query))){
		syslog(LOG_ERR | LOG_DAEMON, "%s", mysql_error(conn));
		sleep_time=60;
		return 0;
	}
	sleep_time=sleep_tmp;
	/* get the database info */
	retcnt=0;
	res=mysql_store_result(conn);
	/* exec the submit */
	while (row=mysql_fetch_row(res)){
		runid=atoi(row[0]);
		if (runid%oj_tot!=oj_mod) continue;
		syslog(LOG_INFO | LOG_DAEMON, "Judging solution %d",runid);
		if (workcnt==max_running){		// if no more client can running
			tmp_pid=waitpid(-1,NULL,0);	// wait 4 one child exit
			for (i=0;i<max_running;i++)	// get the client id
				if (ID[i]==tmp_pid) break; // got the client id
		}else{							// have free client
			workcnt++;
			for (i=0;i<max_running;i++)	// find the client id
				if (ID[i]==0) break;	// got the client id
		}
		updatedb(atoi(row[0]),2,0,0);
		ID[i]=fork();					// start to fork
		if (ID[i]==0){
			run_client(i);	// if the process is the son, run it
			exit(0);	
		}
		retcnt++;
	}
	if (retcnt==0){
		while (workcnt>0){
			workcnt--;
			waitpid(-1,NULL,0);
		}
		memset(ID,0,sizeof(ID));
	}
	mysql_free_result(res);				// free the memory
	mysql_close(conn);					// close db
	return retcnt;
}

int lockfile(int fd)
{
	struct flock fl;
	fl.l_type = F_WRLCK;
	fl.l_start = 0;
	fl.l_whence = SEEK_SET;
	fl.l_len = 0;
	return (fcntl(fd,F_SETLK,&fl));
}

int already_running(){
	int fd;
	char buf[16];
	fd = open(LOCKFILE, O_RDWR|O_CREAT, LOCKMODE);
	if (fd < 0){
		syslog(LOG_ERR|LOG_DAEMON, "can't open %s: %s", LOCKFILE, strerror(errno));
		exit(1);
	}
	if (lockfile(fd) < 0){
		if (errno == EACCES || errno == EAGAIN){
			close(fd);
			return 1;
		}
		syslog(LOG_ERR|LOG_DAEMON, "can't lock %s: %s", LOCKFILE, strerror(errno));
		exit(1);
	}
	ftruncate(fd, 0);
	sprintf(buf,"%d", getpid());
	write(fd,buf,strlen(buf)+1);
	return (0);
}

void daemonize(){
	int i,fd0,fd1,fd2;
	pid_t pid;
	struct rlimit rl;
	struct sigaction sa;
	umask(0);
	if (getrlimit(RLIMIT_NOFILE, &rl)<0){
		syslog(LOG_DAEMON|LOG_ERR,"can't get file limit");
		exit(1);
	}
	if ((pid = fork()) < 0){
		syslog(LOG_ERR|LOG_DAEMON, "Can't fork!");
		printf("Could not daemonize!\n");
		exit(1);
	}
	else if (pid != 0) /* parent */
		exit(0);
	setsid();

	sa.sa_handler = SIG_IGN;
	sigemptyset(&sa.sa_mask);
	sa.sa_flags = 0;
	if (sigaction(SIGHUP, &sa, NULL) < 0){
		syslog(LOG_ERR|LOG_DAEMON, "can't ignore SIGHUP");
		exit(1);
	}

	if ((pid = fork()) < 0){
		syslog(LOG_ERR|LOG_DAEMON,"Can't fork(2)!");
		exit(1);
	}else if (pid != 0) exit(0);

	if (chdir("/") < 0){
		syslog(LOG_ERR|LOG_DAEMON,"can't change dirctory to /");
		exit(1);
	}
	if (rl.rlim_max == RLIM_INFINITY) rl.rlim_max=1024;
	if (rl.rlim_max>1024) rl.rlim_max=1024;
	for (i=0; i < rl.rlim_max; i++) close(i);
	fd0 = open("/dev/null", O_RDWR);
	fd1 = dup(0);
	fd2 = dup(0);
}

int main(){
	daemonize();
	if (already_running()){
		syslog(LOG_ERR|LOG_DAEMON, "This daemon program is already running!\n");
		return 1;
	}
	struct timespec final_sleep;
	final_sleep.tv_sec=0;
	final_sleep.tv_nsec=500000000;
	init_mysql_conf();	// set the database info
	chdir("/home/hoj");	// change the dir
	while (1){			// start to run
		if (work()==0){	// if nothing done 
			sleep(sleep_time);	// sleep
			syslog(LOG_ERR|LOG_DAEMON,"No WORK -- sleeping 1 second");
		}
		nanosleep(&final_sleep,NULL);
	}
	return 0;
}

