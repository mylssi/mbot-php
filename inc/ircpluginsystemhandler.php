<?PHP
require_once('ircexception.php');
require_once('ircprivmsg.php');
require_once('ircprivmsghandler.php');
require_once('ircplugin.php');
/*!
* IrcPluginSystemHandler is a IrcPrivMsgHandler that parses and forwards messages to IrcPlugins.
* @see IrcPrivMsgHandler
* @author Teemu Eskelinen
*/
class IrcPluginSystemHandler implements IrcPrivMsgHandler {
	private $dir;
	private $magic_char = '!'; ///<the char that prefixes every plugin call. The "!" in "!help".
	private $pattern = 'mbot_*.php';  ///<the pattern is used to differiante between plugins and other files.

	/*!
	* Constructor
	* @param $dir Path to the directory where the plugins reside.
	*/
	public function __construct($dir) {
		$this->SetDir($dir);
		$this->LoadPlugins();
	}

	/*!
	* Loads and initializes the plugins in the $dir.
	*/
	public function LoadPlugins() {
		foreach(glob($this->GetDir().'/'.$this->GetPattern()) as $file) {
			$plugin = include($file);
			if($plugin instanceof IrcPlugin)
				$this->AddPlugin($plugin);
		}
	}

	/*!
	* Registers the plugin. Mainly called by LoadPlugins()
	* @param $plugin IrcPlugin object.
	*/
	public function AddPlugin(IrcPlugin $plugin) {
		$this->plugins[] = $plugin;
	}

	/*!
	* Implements the Handle function, that is required by all IrcPrivMsgHandlers
	* @param $channel Reference of the IrcChannel where the message was sent.
	* @param $priv_msg IrcPrivMsg that has the message.
	*/
	public function Handle(&$channel, IrcPrivMsg $priv_msg) {
		$line = $priv_msg->GetMessage();
		if($line[0] != $this->GetMagicChar())
			return;
		foreach($this->plugins as $plugin) {
			$func_array = array($plugin, 'On'.ucfirst(Parse::After(Parse::Before($line, ' '), $this->GetMagicChar())));
			if(is_callable($func_array))	
				call_user_func($func_array, $channel, $priv_msg, Parse::Arguments(Parse::After($line, ' ')));
		}
	}

	//setters

	/*!
	* Sets the dir where the plugins will be searched.
	* @param $dir Path to the directory.
	*/
	public function SetDir($dir) {
		if(!file_exists($dir))
			throw new IrcException('Tried to load plugins from a non-existant directory '.$dir, PLUGIN_INVALID_DIR);
		else
			$this->dir = $dir;
	}

	/*!
	* Sets the "magic char".
	* @see IrcPluginSystemHandler::$magic_char
	* @param $char The new magic char.
	* @return The magic char.
	*/
	public function SetMagicChar($char) {
		$this->magic_char = $char;
	}

	//getters

	/*!
	* Gets the plugin dir
	* @return Path to the plugin dir.
	*/
	public function GetDir() {
		return $this->dir;
	}

	/*!
	* Gets the pattern that is used to find the plugins. * is the wildcard.
	* @see $pattern
	* @return The pattern.
	*/
	public function GetPattern() {
		return $this->pattern;
	}

	/*!
	* Gets the magic char.
	* @see $magic_char
	* @return The magic char.
	*/
	public function GetMagicChar() {
		return $this->magic_char;
	}

	/*!
	* Gets the IrcPrivMsgHandler description.
	* @return The description.
	*/
	public function GetName() {
		return 'Plugin system handler';
	}
}
