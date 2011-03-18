<?PHP
// NOTES:
// THIS DOESN'T WORK YET
// Handles servers' incoming messages in separate threads.
// As far as I know, pcntl functions work only on Linux and other unices. On win32 use the non-forking implementation.
require_once('ircsockethandler.php');
require_once('ircexception.php');
declare(ticks=1);
class ForkingIrcSocketHandler extends IrcSocketHandler {
	private $num_threads = 0;
	public function __construct() {
		pcntl_signal(SIGCHLD, array($this, 'SignalHandler'));
	}
	public function SignalHandler($signo) {
		if($signo == SIGCHLD) {
			$this->num_threads--;
			echo 'Bye'."\r\n";
		}
	}
	public function Handle() {
		echo 'Handling'."\r\n";
		foreach($this->servers as $server) {
			$sockets[] = $server->GetSocket();
		}
		$changed_sockets = $sockets;
		$num_changed_sockets = socket_select($changed_sockets, $write = NULL, $except = NULL, 0, 15);
		if($num_changed_sockets < 1)
			return;
		$servers = $this->servers;
		foreach($changed_sockets as $socket) {
			//the fork
			echo 'Forking'."\r\n";
			$id = pcntl_fork();
			if($id == -1)
				throw new IrcException('pcntl_fork() failed', SOCKET_HANDLER_CANT_FORK);
			else if($id)
				//we are the parent thread
				continue;
			$this->num_threads++;
			$socket_name = array_search($socket, $sockets);
			$recieved = socket_recv($socket, $buffer, 2048, 0);
			if(empty($buffer))
				exit;
			$buffer = trim($buffer);
			//split the buffer into lines
			$lines = explode("\r\n", $buffer);
			foreach($lines as $line) {
				//dispatch message
				$servers[$socket_name]->Handle($line);
			}
			$this->servers = $servers;
			exit('Imma outta here '.$id."\r\n");
		}
		echo 'Lets see'."\r\n";
		//wait until the threads exit
		while($this->num_threads > 0) {
			echo 'Waiting'.$this->num_threads."\r\n";
			//pcntl_wait($status);
			//$this->num_threads--;
		}
	}
}

