<?PHP
require_once('ircexception.php');
require_once('ircprivmsg.php');
require_once('ircprivmsghandler.php');
/*!
* IrcChannel is pretty much what you would expect.
* @author Teemu Eskelinen
*/
class IrcChannel {
	private $server;
	private $users;
	private $name;
	private $part_message = '...';
	
	/*!
	* Channel constructor
	* @param $name Channel name
	*/
	public function __construct($name) {
		$this->SetName($name);
	}

	/*!
	* Actually join the channel
	*/
	public function Join() {
		$this->GetServer()->Send('JOIN '.$this->GetName());
	}

	/*!
	* Part the channel and send the part message
	*/
	public function Part() {
		$this->GetServer()->Send('PART '.$this->GetName().' :'.$this->GetPartMessage());
	}

	/*!
	* Send a message to the channel
	* @param $line Complete line of message. Can't be called with an array.
	*/
	public function Send($line) {
		if(!empty($line))
			$this->GetServer()->Send('PRIVMSG '.$this->GetName().' :'.$line);
	}

	/*!
	* Register a message handler
	* @param $handler Instance of IrcPrivMsgHandler to be registered with this channel
	*/
	public function AddHandler(IrcPrivMsgHandler $handler) {
		$this->handlers[] = $handler;
	}

	/*!
	* Callback for the IrcServer.
	* Every line that's sent to this channel comes through this function, that forwards them to registered message handlers.
	* @param $priv_msg Instance of IrcPrivMsg
	*/
	public function Handle(IrcPrivMsg $priv_msg) {
		foreach($this->handlers as $handler) {
			if($handler->Handle($this, $priv_msg) === true)
				break;
		}
	}
	
	/*!
	* Add user to this channel. If someone joins, AddUser will be called.
	* @param $user User object
	*/
	public function AddUser(IrcUser $user) {
		if($this->GetUserByNick($user->GetNick()))
			throw new IrcException('Channel already has a user named '.$user->GetNick(), CHANNEL_DUPE_USER);
		$user->SetChannel($this);
		$this->users[] = $user;
	}

	/*!
	* Searchs user by nick and removes found user from channels userlist.
	* @param $nick Nick to search for
	* @return FALSE if no such nick exists, none otherwise
	*/
	public function RemoveUserByNick($nick) {
		if(empty($this->users))
			return false;
		foreach($this->users as $key => $user) {
			if(strtolower($nick) === strtolower($user->GetNick()))
				unset($this->users[$key]);
		}
	}
	
	/*!
	* Searchs for user by nick.
	* @param $nick Nick to search for
	* @return User object if nick was found, false otherwise
	*/
	public function GetUserByNick($nick) {
		if(empty($this->users))
			return false;
		foreach($this->users as $user) {
			if(strtolower($nick) === strtolower($user->GetNick()))
				return $user;
		}
		return false;
	}

	//setters

	/*!
	* Set channel name.
	* @param $name New channel name.
	*/
	public function SetName($name) {
		$this->name = $name;
	}

	/*!
	* Set part message.
	* @param $msg New part message.
	*/
	public function SetPartMessage($msg) {
		$this->part_message = $msg;
	}

	/*!
	* Set the server that this channel part of. Basically callback for IrcServer.
	* @param $server Server instance
	*/
	public function SetServer(IrcServer $server) {
		$this->server = $server;
	}

	//getters

	/*!
	* Get the channel name.
	* @return channel name.
	*/
	public function GetName() {
		return $this->name;
	}	
	
	/*!
	* Get the channel's part message.
	* @return part message.
	*/
	public function GetPartMessage() {
		return $this->part_message;
	}
	
	/*!
	* Get the list of users.
	* @return array containing all user objects.
	*/
	public function GetUsers() {
		return $this->users;
	}

	/*!
	* Get the server this channel is registered with.
	* @return IrcServer object.
	*/
	public function GetServer() {
		if(empty($this->server))
			throw new IrcException('Tried to access non-existant server in GetServer()', CHANNEL_NO_SERVER);
		return $this->server;
	}
}
?>
