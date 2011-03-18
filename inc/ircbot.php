<?PHP
require_once('ircsockethandler.php');
/*!
* Basic implementation of an ircbot. This class ties everything together.
* @author Teemu Eskelinen
*/
class IrcBot {
	private $max_fail;
	private $socket_handler;
	private $quit = false;
	private $sleep_time = 1;
	private $services = array();
	/*!
	* The constructor
	*/
	public function __construct() {
		$this->socket_handler = new IrcSocketHandler;
	}

	/*!
	* This is the last thing to call. It doesn't return until the bot is shut down.
	* Calling this is really fires up the bot.
	*/
	public function MainLoop() {
		//the loop
		while(!$this->quit && !$this->socket_handler->GetQuit()) {
			//connect
			$this->socket_handler->ConnectAll();
			//allow server instances to send their cached data
			$this->socket_handler->SendBuffer();
			//handle incoming
			$this->socket_handler->Handle();
			//call our services
			$this->PollServices();
			//we are not in such hurry, lets sleep for a while and save some CPU cycles
			sleep($this->sleep_time);
		}
	}

	/*!
	* Adds server to this ircbot.
	* @param $server IrcServer instance of the server.
	*/
	public function AddServer(IrcServer $server) {
		$this->socket_handler->AddServer($server);
	}

	/*!
	* Adds a service, which is periodiacally polled, to this ircbot.
	* @param $service IrcService instance of the service.
	*/
	public function AddService(IrcService $service) {
		$this->services[] = $service;
	}

	/*!
	* Polls services that have been registered.
	*/
	public function PollServices() {
		foreach($this->services as $service) {
			$service->Poll($this->socket_handler);
		}
	}
}
?>
