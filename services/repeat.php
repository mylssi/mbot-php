<?PHP
require_once('inc/ircservice.php');
class Repeat implements IrcService {
	private $network;
	private $channel;
	private $last_poll;
	private $poll_time = 60;
	private $string;
	public function __construct($network, $channel, $poll_time) {
		$this->network = $network;
		$this->channel = $channel;
		$this->poll_time = $poll_time;
	}
	public function SetString($str) {
		$this->string = $str;
	}
	private function GetChannel(IrcSocketHandler $h) {
		$server = $h->GetServerByNetwork($this->network);
		if(!$server)
			return false;
		$channel = $server->GetChannelByName($this->channel);
		if(!$channel)
			return false;
		return $channel;
	}
	public function Poll(IrcSocketHandler $h) {
		if(time() - $this->last_poll <= $this->poll_time)
			return;
		$channel = $this->GetChannel($h);
		if(!$channel)
			return;
		$channel->Send($this->string);
		$this->last_poll = time();
	}
}
?>
