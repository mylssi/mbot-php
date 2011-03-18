<?PHP
require_once('ircexception.php');
require_once('ircservermsghandler.php');
require_once('ircprotocolhandler.php');
require_once('ircchannel.php');
/*!
* IrcServer is what you would expect.
* @author Teemu Eskelinen
*/
class IrcServer {
	private $server_host;
	private $server_port;
	private $network;
	private $channels = array();
	private $nick = 'mbot-89'; ///<the default nick
	private $real_name = 'mbot'; ///<the default "real name"
	private $quit_message = '...'; ///<the default quit message
	private $num_reconnect = 0;
	private $max_reconnect = 50; ///<max number of reconnections
	private $handlers;
	private $socket_handler;
	private $ignored_socket_errors = array(0, 98, 10048, 10049); ///<these errors returned by socket_last_errors are discarded
	private $lines_per_send = 1;
	private $time_between_sends = 1;
	private $last_send = 0;
	private $queue = array();
	protected $socket;

	/*!
	* The constructor
	* @param $host The server's host.
	* @param $port The server's port.
	* @param $network The name of the irc network the server is part of.
	*/
	public function __construct($host, $port, $network) {
		$this->SetHost($host);
		$this->SetPort($port);
		$this->SetNetwork($network);
		//add basic IRC protocol handler
		$this->AddHandler(new IrcProtocolHandler);
	}
	
	/*!
	* The destructor
	*/
	public function __destruct() {
		$this->Disconnect();
	}

	/*!
	* Connects to the server and sends the user info.
	* @see IrcSocketHandler
	* @param $handler The server's IrcSocketHandler.
	*/
	public function Connect(IrcSocketHandler $handler) {
		if($this->IsConnected())
			return;
		if($this->max_reconnect < $this->num_reconnect++)
			throw new IrcException('Too many reconnections', SERVER_TOO_MANY_RECONNECTIONS);
		$this->SetSocketHandler($handler);
		$this->CreateSocket();
		$this->Write('NICK '.$this->GetNick());
		$this->Write('USER '.$this->GetNick().' 3 ooo :'.$this->GetRealName());

	}
	
	/*!
	* Disconnects from the server, leaves all channels and sends the quit message.
	*/
	public function Disconnect() {
		foreach($this->channels as $channel) {
			$channel->Part();
		}
		$this->Write('QUIT '.$this->GetQuitMessage());
	}

	/*!
	* Creates the network socket
	*/
	protected function CreateSocket() {
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(!$this->socket)
			throw new IrcException('Function socket_create failed', SOCKET_CANT_CREATE);
		if(!socket_connect($this->socket, $this->GetHost(), $this->GetPort()))
			throw new IrcException('Function socket_connect failed', SOCKET_CANT_CONNECT);
	}

	/*!
	* Unbuffered socket write. Used only internally.
	* @param $line A line of message.
	*/
	protected function Write($line) {
		socket_write($this->GetSocket(), $line."\r\n");
	}

	/*!
	* Buffered socket write. Moves to the message to a queue that will be sent when SendBuffer is called.
	* @param $line A line of message.
	*/
	public function Send($line) {
		$this->queue[] = $line;
	}

	/*!
	* @see IrcServer::Send($line)
	* Sends the queue built by Send($line), callback for the main loop, for example.
	*/
	public function SendBuffer() {
		if(time() - $this->last_send < $this->time_between_sends)
			return;
		for($i = 0; $i < $this->lines_per_send; $i++) {
			$line = array_shift($this->queue);
			$this->Write($line);
		}
		$this->last_send = time();
	}

	//channel related

	/*!
	* Adds channel to the server.
	* @param $channel IrcChannel object of the channel to be added.
	*/
	public function AddChannel(IrcChannel $channel) {
		if($this->GetChannelByName($channel->GetName()))
			throw new IrcException('Server already has a channel named '.$channel->GetName(), SERVER_DUPE_CHANNEL);
		$this->channels[] = $channel;
		$channel->SetServer($this);
	}

	/*!
	* Creates a IrcChannel object and adds it to the server. Mainly a callback for protocol handlers.
	* @param $name Channels name.
	*/
	public function JoinChannel($name) {
		$this->AddChannel(new Channel($name));
	}

	/*!
	* Leaves channel
	* @param $name Channels name.
	*/
	public function PartChannel($name) {
		$channel = $this->GetChannelByName($name);
		if(!$channel)
			throw new IrcException('Tried to part an non-existant channel named '.$channel->GetName(), SERVER_NO_CHANNEL);
		$channel->Part();
	}

	/*!
	* Joins all channels currently registered to the server instance.
	*/
	public function JoinAllChannels() {
		foreach($this->channels as $channel) {
			$channel->Join();
		}
	}

	//handlers

	/*!
	* Handles incoming messages from the server and forwards them to IrcServerMsgHandlers.
	* @see IrcServerMsgHandler
	* @param $line Message sent by the server.
	*/
	public function Handle($line) {
		foreach($this->handlers as $handler) {
			if($handler->Handle($this, $line) === true)
				break;
		}
	}

	/*!
	* Registers a handler with the server.
	* @see IrcServerMsgHandler
	* @param $handler IrcServerMsgHandler object.
	*/
	public function AddHandler(IrcServerMsgHandler $handler) {
		$this->handlers[] = $handler;
	}
	
	//setters
	
	/*!
	* Sets the server's host.
	* @param $host Server's new host.
	*/
	public function SetHost($host) {
		$this->server_host = $host;
	}

	/*!
	* Sets the server's port.
	* @param $port Server's new port.
	*/
	public function SetPort($port) {
		$this->server_port = $port;
	}

	/*!
	* Sets the nick used in the server.
	* @param $nick Bot's new nick.
	*/
	public function SetNick($nick) {
		$this->nick = $nick;
		if($this->IsConnected()) {
			$this->Send('NICK '.$nick);
		}
	}

	/*!
	* Sets the real name used in the server.
	* @param $name Bot's new real name.
	*/
	public function SetRealName($name) {
		$this->real_name = $name;
	}

	/*!
	* Sets the server's irc network.
	* @param $network Server's new irc network.
	*/
	public function SetNetwork($network) {
		$this->network = $network;
	}

	/*!
	* Sets the quit message used in the server.
	* @param $msg Bot's new quit message.
	*/
	public function SetQuitMessage($msg) {
		$this->quit_message = $msg;
	}

	/*!
	* Sets the server's IrcSocketHandler.
	* @param $handler IrcSocketHandler object.
	*/
	public function SetSocketHandler(IrcSocketHandler $handler) {
		$this->socket_handler = $handler;
	}

	//getters

	/*!
	* Checks if we are connected or not.
	* @return TRUE if connected, FALSE otherwise.
	*/
	public function IsConnected() {
		if(empty($this->socket))
			return false;
		if(!in_array($this->GetErrorCode(), $this->ignored_socket_errors))
			return false;
		return true;
	}

	/*!
	* Gets the server's socket.
	* @return Server's network socket.
	*/
	public function GetSocket() {
		if(empty($this->socket))
			throw new IrcException('Tried to access a non-existant socket in $this->GetSocket()', SERVER_NO_SOCKET);
		return $this->socket;
	}

	/*!
	* Gets the error code of server's network socket.
	* @return The socket error code.
	*/
	public function GetErrorCode() {
		return socket_last_error($this->socket);
	}

	/*!
	* Gets the network socket's error string.
	* @return The network socket's error string.
	*/
	public function GetError() {
		return socket_strerror($this->GetErrorCode());
	}
	
   /*!
	* Gets server's channels.
	* @return Reference to channels array.
	*/
	public function GetChannels() {
		return $this->channels;
	}

	/*!
	* Searches channel by name.
	* @param $name Channel name to look for.
	* @return IrcChannel if it's found, FALSE otherwise.
	*/
	public function GetChannelByName($name) {
		foreach($this->channels as $channel) {
			if(strtolower($channel->GetName()) == strtolower($name)) {
				return $channel;
			}
		}
		return false;
	}

	/*!
	* Gets the server's network host.
	* @return Server's network host.
	*/
	public function GetHost() {
		return $this->server_host;
	}

	/*!
	* Gets the server's netowk port.
	* @return Server's network port.
	*/
	public function GetPort() {
		return $this->server_port;
	}

	/*!
	* Gets the nick used in the server.
	* @return Bot's nick.
	*/
	public function GetNick() {
		return $this->nick;
	}

	/*!
	* Gets the real name used in the server.
	* @return Bot's real name.
	*/
	public function GetRealName() {
		return $this->real_name;
	}

	/*!
	* Gets the quit message used in the server.
	* @return Bot's quit message.
	*/
	public function GetQuitMessage() {
		return $this->quit_message;
	}

	/*!
	* Gets the server's irc network.
	* @return Server's irc network.
	*/
	public function GetNetwork() {
		return $this->network;
	}

	/*!
	* Gets the server's socket handler.
	* @return Server's IrcSocketHandler.
	*/
	public function GetSocketHandler() {
		return $this->socket_handler;
	}
}
