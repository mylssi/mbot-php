<?PHP
require_once('parse.php');
/*!
* IrcPrivMsg is used to pass channel messages around. It can do some basic parsing on the message.
* @author Teemu Eskelinen
*/
class IrcPrivMsg {
	private $line;

	/*!
	* The constructor.
	* @param $msg The whole PRIVMSG line sent by the server.
	*/
	public function __construct($msg) {
		$this->SetLine($msg);
	}

	//setters
	
	/*!
	* Sets the PRIVMSG line used that's parsed.
	* @param $line The whole PRIVMSG line sent by the server.
	*/
	public function SetLine($line) {
		$this->line = $line;
	}

	//getters
	
	/*!
	* Gets the PRIVMSG line.
	* @return The PRIVMSG line.
	*/
	public function GetLine() {
		return $this->line;
	}

	/*!
	* Gets the message senders nick!hostname that's sent by the server.
	* @return The senders nick!hostname.
	*/
	public function GetSender() {
		$p = explode(' ', $this->GetLine());
		return Parse::After($p[0], ':');
	}

	/*!
	* Gets the message senders host.
	* @return The senders host.
	*/
	public function GetHost() {
		return Parse::After($this->GetSender(), '!');
	}

	/*!
	* Gets the senders nick.
	* @return The senders nick.
	*/
	public function GetNick() {
		return Parse::Before($this->GetSender(), '!');
	}

	/*!
	* Gets the channel's name, that the message was sent to.
	* @return The channel name.
	*/
	public function GetChannelName() {
		$p = explode(' ', $this->GetLine());
		return $p[2];
	}

	/*!
	* Gets the actual message.
	* @return The message.
	*/
	public function GetMessage() {
		return Parse::After($this->GetLine(), ':', 2);
	}
}
?>
