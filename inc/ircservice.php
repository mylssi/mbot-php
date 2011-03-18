<?PHP
require_once('ircsockethandler.php');
/*!
* Interface that every IrcService must implement.
* IrcServices are addons that are periodiacally polled.
* @author Teemu Eskelinen
*/
interface IrcService {
	/*!
	* This function is the poll callback.
	* @param $socket_handler IrcBot's IrcSocketHandler.
	*/
	public function Poll(IrcSocketHandler $socket_handler);
}
?>
