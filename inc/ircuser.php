<?PHP
require_once('ircexception.php');
/*!
* IrcUser is what you would expect.
* IrcUser objects are channel specific.
* @author Teemu Eskelinen.
*/
class IrcUser {
	private $nick;
	private $modes;

	/*!
	* The constructor.
	* @param $nick User's nick with or without the mode prefix(@,+ etc.).
	*/
	public function __construct($nick) {
		//check for Op and voice etc signs
		$parsed_nick = substr($nick, 1);
		if($nick[0] == '+') 
			$this->SetMode('v');
		else if($nick[0] == '@')
			$this->SetMode('@');
		//some idiot mode, i don't its name so i'll just call it Modulo
		else if($nick[0] == '%')
			$this->SetMode('Modulo');
		else
			$parsed_nick = $nick;
		$this->SetNick($parsed_nick);
	}
	
	//user mode shorthands
	
	//voice

	/*!
	* Voices user.
	*/
	public function Voice() {
		$this->SendMode('v');
	}
	
	/*!
	* Devoices user.
	*/
	public function DeVoice() {
		$this->SendMode('v', false);
	}

	/*!
	* Checks whether user is voiced.
	* @return TRUE if user is voiced, FALSE otherwise.
	*/
	public function IsVoice() {
		if($this->GetMode('v') === true)
			return true;
		return false;
	}

	//operator

	/*!
	* Gives user operator privileges.
	*/
	public function Op() {
		$this->SendMode('o');
	}

	/*!
	* Removes user's operator privileges.
	*/
	public function DeOp() {
		$this->SendMode('o', false);
	}

	/*!
	* Checks whether user has operator privileges.
	* @return TRUE if user has operator privileges, FALSE otherwise.
	*/
	public function IsOp() {
		if($this->GetMode('o') === true)
			return true;
		return false;
	}

	/*!
	* The user mode sender.
	* @param $mode The mode to be changed. 
	* @param $value If boolean, TRUE means +mode and FALSE -mode.
	*/
	public function SendMode($mode, $value=true) {
		//on/off type of mode
		if(is_bool($value)) {
			if($value == true)
				$mode = '+'.$mode;
			else if($value == false)
				$mode = '-'.$mode;
			$this->GetChannel()->GetServer()->Send('MODE '.$this->GetChannel()->GetName().' '.$mode.' '.$this->GetNick());
		}
		//!@TODO string/number type of mode (some networks might have these(?))
		else {
			//$this->GetChannel()->GetServer()->Write('MODE
		}
	}

	//setters

	/*!
	* Sets user's channel.
	* @param $channel IrcChannel object.
	*/
	public function SetChannel(IrcChannel $channel) {
		$this->channel = $channel;
	}

	/*!
	* Sets user's nick.
	* @param $nick User's nick.
	*/
	public function SetNick($nick) {
		$this->nick = $nick;
	}

	/*!
	* Set user's mode internally. Note that this function doesn't actually send any modes to the server but is used as a callback from protocol handlers.
	* @param $mode Mode to change. 
	* @param $value The boolean value of the mode.
	*/
	public function SetMode($mode, $value=true) {
		$this->modes[$mode] = $value;
	}

	//getters

	/*!
	* Gets user's channel.
	* @return User's IrcChannel.
	*/
	public function GetChannel() {
		if(empty($this->channel))
			throw new IrcException('Tried to access non-existant channel in GetChannel()', USER_NO_CHANNEL);
		return $this->channel;
	}

	/*!
	* Get's user's nick.
	* @return User's nick.
	*/
	public function GetNick() {
		return $this->nick;
	}

	/*!
	* Get user's mode.
	* @param $mode Mode to get.
	* @return Mode's value.
	*/
	public function GetMode($mode) {
		return $this->modes[$mode];
	}
}
?>
