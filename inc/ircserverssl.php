<?PHP
//THIS DOESN'T WORK YET
require_once('ircserver.php');
class IrcServerSSL extends IrcServer {
	protected function CreateSocket() {
		echo 'wat';
		$this->socket = fsockopen('ssl://'.$this->GetHost(), $this->GetPort(), $errno, $errstr, 30);
		if(!$this->socket)
			throw new IrcException('Function fsockopen failed', SOCKET_CANT_CREATE);
	}
	protected function Write($line) {
		fwrite($this->GetSocket(), $line."\r\n");
	}
}
?>
