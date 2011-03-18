<?PHP
require_once('ircprivmsg.php');
/*!
* The interface that all IrcPrivMsgHandlers must implement.
* @author Teemu Eskelinen
*/
interface IrcPrivMsgHandler {
	/*!
	* This method handles the message.
	* @param $channel The reference of the IrcChannel that the message was sent to.
	* @param $priv_msg The IrcPrivMsg to handle.
	*/
	public function Handle(&$channel, IrcPrivMsg $priv_msg);

	/*!
	* @return The name or short description of the handler.
	*/
	public function GetName();
}
?>
