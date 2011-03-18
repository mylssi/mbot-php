<?PHP
require_once('ircprotocolhandler.php');
require_once('ircuser.php');
require_once('parse.php');
/*!
* IrcProtocolHandler handles and parses everything that has to do with the very basic IRC protocol.
* Anything fancier should be implemented as a separate IrcServerMsgHandler.
* @author Teemu Eskelinen
*/
class IrcProtocolHandler implements IrcServerMsgHandler {
	/*!
	* @see IrcServerMsgHandler
	* Handles the message. See ircservermsghandler.php for the method definition.
	*/
	public function Handle(&$server, $line) {
		$p = explode(' ', $line);
		//identify message
		switch(strtoupper($p[0])) {
			case 'PING':
				return $this->HandlePing($server, $p);
		}
		switch(strtoupper($p[1])) {
			case '353':
				//Channel user list
				return $this->HandleChannelUserList($server, $p);
			case '376':
				//Message of the day end
				return $this->HandleMOTD($server);
			case '433':
				//Nickname in use
				return $this->HandleNickInUse($server);
			case 'JOIN':
				//someone joined a channel
				return $this->HandleJoin($server, $p);
			case 'PART':
				//someone left a channel
				return $this->HandlePart($server, $p);
			case 'PRIVMSG':
				//got channel/private message
				$channel = $server->GetChannelByName($p[2]);
				if($channel === false)
					return;
				$priv_msg = new IrcPrivMsg($line);
				return $channel->Handle($priv_msg);
		}
	}

	//specific handlers

	/*!
	* Handles PING messages
	*/
	private function HandlePing($server, $p) {
		$server->Write($p[1]);
	}

	/*!
	* Handles the user list sent by server when a channel is joined.
	*/
	private function HandleChannelUserList($server, $p) {
		//find channel
		if(count($p) < 5)
			return;
		$channel = $server->GetChannelByName($p[4]);
		if(!$channel)
			return;
		$nicklist = Parse::After(join(' ', $p), ':', 2);
		$nicks = explode(' ', $nicklist);
		foreach($nicks as $nick) {
			try {
				$channel->AddUser(new IrcUser($nick));
			}		
			catch(IrcException $e) {
				//ignore user already on list messages, since they pose no problem to us
				//TODO maybe parse user modes for those users and update them, just in case
				if($e->getCode() != CHANNEL_DUPE_USER) {
					//forward the exception
					throw new IrcException($e, $e->getCode());
				}
			}
		}
	}

	/*!
	* Handles the end of Message Of The Day
	*/
	private function HandleMOTD($server) {
		//it's all good to join now
		$server->JoinAllChannels();
	}

	/*!
	* Handles the message sent by the server when the wanted nick is in use.
	*/
	private function HandleNickInUse($server) {
		$server->SetNick($server->GetNick().'-');
	}
	
	/*!
	* Handles the message sent by the server when someone joins a channel and adds it to the channel's user list.
	*/
	private function HandleJoin($server, $p) {
		$channel = $server->GetChannelByName(Parse::After($p[2], ':'));
		if(!$channel)
			return;
		$user = Parse::Before(Parse::After($p[0], ':'), '!');
		try {
			$channel->AddUser(new IrcUser($user));
		}
		catch(IrcException $e) {
			//ignore dupe user messages, since userlist could be desynced in case of netsplit etc.
			if($e->getCode() != CHANNEL_DUPE_USER) {
				//forward the exception
				throw new IrcException($e, $e->getCode());
			}
		}
	}

	/*! 
	* Handles the message sent by the server when someone leaves a channel and removes it from the channel's user list.
	*/
	private function HandlePart($server, $p) {
		$channel = $server->GetChannelByName($p[2]);
		if(!$channel)
			return;
		$user = Parse::Before(Parse::After($p[0], ':'), '!');
		$channel->RemoveUserByNick($user);
	}
	
	//getters
	
	/*!
	* @see IrcServerMsgHandler
	* @return The handlers name.
	*/
	public function GetName() {
		return 'IRC Protocol Handler';
	}

}
?>

