<?PHP
require_once('ircserver.php');
/*!
* This class handles all connections to the server.
* @author Teemu Eskelinen
*/
class IrcSocketHandler {
	protected $servers = array();
	private $quit;

	/*!
	* The destructor. Disconnects from the servers.
	*/
	public function __destruct() {
		foreach($this->servers as $server) {
			$server->Disconnect();
		}
	}

	/*!
	* Adds new server to the socket handler
	* @param $server IrcServer object to add.
	*/
	public function AddServer(IrcServer $server) {
		$this->servers[] = $server;
	}

	/*!
	* Connects to all servers that the socket handler has been registered with.
	*/
	public function ConnectAll() {
		foreach($this->servers as $key => $server) {
			try {
				$server->Connect($this);
			}
			catch(IrcException $e) {
				if($e->getCode() == SERVER_TOO_MANY_RECONNECTS)
					unset($this->servers[$key]);
				else
					throw new IrcException($e, $e->getCode());
			}
		}
	}

	/*!
	* Calls every servers SendBuffer() that causes them to send their queues.
	* Used as a callback.
	*/
	public function SendBuffer() {
		foreach($this->servers as $server) {
			$server->SendBuffer();
		}
	}
	
	/*!
	* Reads socket and handles incoming data. Callback for the main loop.
	*/
	public function Handle() {
		foreach($this->servers as $server) {
			$sockets[] = $server->GetSocket();
		}
		$changed_sockets = $sockets;
		$num_changed_sockets = socket_select($changed_sockets, $write = NULL, $except = NULL, 0, 15);
		if($num_changed_sockets < 1)
			return;
		foreach($changed_sockets as $socket) {
			$socket_name = array_search($socket, $sockets);
			$recieved = socket_recv($socket, $buffer, 2048, 0);
			if(empty($buffer))
				continue;
			$buffer = trim($buffer);
			//split the buffer into lines
			$lines = explode("\r\n", $buffer);
			foreach($lines as $line) {
				//dispatch message
				$this->servers[$socket_name]->Handle($line);
			}
		}
	}

	//getters

	/*!
	* Gets whether the main loop should break or not.
	* @return If the main loop should break TRUE, FALSE otherwise.
	*/
	public function GetQuit() {
		return $this->quit;
	}
	
   /*!
	* Gets all servers.
	* @return Reference to the servers array.
	*/
	public function GetServers() {
		return $this->servers;
	}

	/*!
	* Searches for server by irc network.
	* @param $network Irc networks name.
	* @return IrcServer object if found, FALSE otherwise.
	*/
	public function GetServerByNetwork($network) {
		foreach($this->servers as $server) {
			if(strtolower($network) === strtolower($server->GetNetwork()))
				return $server;
		}
		return false;
	}

	//setters

	/*!
	* Sets whether the main loop should break.
	* @param $value TRUE if main loop should break, FALSE otherwise.
	*/
	public function SetQuit($value = true) {
		$this->quit = $value;
	}
}
?>
