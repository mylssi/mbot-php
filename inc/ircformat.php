<?PHP
/*!
* IrcFormat gives basic tools to format irc messages. 
* All functions are static so they can be called without initializing an instance and still remain in their own namespace.
* @author Teemu Eskelinen
*/
class IrcFormat {
	/*!
	* Bolds message.
	* @param $msg String to be bolded
	* @return Bolded string
	*/
	static public function Bold($msg) {
		return chr(2).$msg.chr(2);
	}

	/*!
	* Colors message.
	* @param $msg The string to be colored. 
	* @param $color_code The color's ASCII code.
	* @return Colored message.
	*/
	static public function Color($msg, $color_code) {
		return chr(3).$color_code.(is_numeric($msg[0]) ? ' ' : '').$msg.chr(3);
	}

	/*!
	* Whitens the message.
	* @param $msg String to color white.
	* @return White string.
	*/
	static public function White($msg) {
		return self::Color($msg, 0);
	}

	/*!
	* Reds the message
	* @param $msg String to color red.
	* @return Red string.
	*/
	static public function Red($msg) {
		return self::Color($msg, 2);
	}

	/*!
	* Greens the message.
	* @param $msg String to color green.
	* @return Green string.
	*/
	static public function Green($msg) {
		return self::Color($msg, 6);
	}

	/*!
	* Blues the message.
	* @param $msg String to color blue.
	* @return Blue string.
	*/
	static public function Blue($msg) {
		return self::Color($msg, ':');
	}
}
?>
