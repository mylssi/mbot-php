<?PHP
/*!
* This handler just echoes everything sent by the server to the stdout.
* @see IrcServerMsgHandler
* @author Teemu Eskelinen
*/
class IrcServerDebugHandler implements IrcServerMsgHandler {
	public function Handle(&$server, $line) {
		if(!empty($line))
			echo $line."\r\n";
	}
	public function GetName() {
		return 'Server connection debugger';
	}
}
?>

