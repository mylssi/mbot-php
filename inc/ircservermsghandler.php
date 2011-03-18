<?PHP
/*!
* Interface that every IrcServerMsgHandler must implement.
* @author Teemu Eskelinen
*/
interface IrcServerMsgHandler {
	/*!
	* The handler.
	* @param $server Reference to IrcServer the message was sent to
	* @param $line The line sent by the server.
	*/
	public function Handle(&$server, $line);
	
	/*!
	* @return The name or short description of the handler.
	*/
	public function GetName();
}
?>

