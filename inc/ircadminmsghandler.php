<?PHP
require_once('ircprivmsghandler.php');
require_once('ircsettings.php');
require_once('parse.php');
/*! 
* Implements basic bot upkeep tools.
* @see IrcPrivMsgHandler
* @author Teemu Eskelinen
*/
class IrcAdminMsgHandler implements IrcPrivMsgHandler {
	/*!
	* The constructor
	*/
	public function __construct() {
		$this->SetStartTime(time());
	}

	/*!
	* The main handle function
	*/
	public function Handle(&$channel, IrcPrivMsg $priv_msg) {
		if($this->IsAdmin($priv_msg->GetSender())) {
			$p = explode(' ', $priv_msg->GetMessage());
			switch(strtolower($p[0])) {
				//op the sender
				case '!op':
					$user = $channel->GetUserByNick($priv_msg->GetNick());
					if(!$user)
						return;
					return $user->Op();
				//shutdown the bot
				case '!shutdown':
            case '!quit':
					return $channel->GetServer()->GetSocketHandler()->SetQuit();
				//get uptime
				case '!uptime':
					return $channel->Send('Uptime: '.Parse::StrTime(time() - $this->GetStartTime()));
            //rejoin channel
            case '!rejoin':
               $channel->Part();
               $channel->Join();
               return;
				//add new admin
				case '!addadmin':
					if(count($p) != 2)
						return;
					$this->AddAdmin($p[1]);
					return $channel->Send('Granted admin rights for "'.$p[1].'"');
            //get admins
            case '!getadmins':
               foreach($this->GetAdmins() as $admin) {
                  $channel->Send($admin);
               }
               return;
				//get channel's users
				case '!users':
					foreach($channel->GetUsers() as $user) {
						$channel->Send($user->GetNick());
					}
					return;
            //add autoop
            case '!autoop':
               $pool = IrcSettings::GetInstance()->GetPool('autoop');
               $channels = $pool->Get($channel->GetServer()->GetNetwork());
               $channels[$channel->GetName()][] = $p[1];
               $pool->Set($channel->GetServer()->GetNetwork(), $channels);
               $channel->Send('Added '.$p[1].' to '.$channel->GetName().'\'s auto-op list');
               return;
            //save settings
            case '!save':
               IrcSettings::GetInstance()->Save();
               return $channel->Send("Settings saved");
            //get setting
            case '!getsetting':
               $ins = IrcSettings::GetInstance();
               return $channel->Send($ins->GetPool($p[1])->Get($p[2]));
            //send raw data to the server
            case '!send':
               if($count($p) != 2)
                  return;
               return $channel->GetServer()->Send(Parse::After($priv_msg->GetMessage(), ' ', 1));
			}
		}
	}

	/*!
	* Give user admin privileges.
	* @param $host admin's hostmask, * can be used as a wildcard
	*/
	public function AddAdmin($host) {
      $settings_pool = IrcSettings::GetInstance()->GetPool('core');
      $admins = isset($settings_pool->admins) ? $settings_pool->admins : array();
      //no duplicates
      if(!in_array($host, $admins))
		   $admins[] = $host;
      $settings_pool->admins = $admins;
	}
	/*!
	* @return Array of admin's hostmasks
	*/
	public function GetAdmins() {
      $settings_pool = IrcSettings::GetInstance()->GetPool('core');
		return $settings_pool->Get('admins');
	}
	/*!
	* @param $host Hostname to check, whether it has admin privileges
	* @return TRUE if it's an admin, FALSE otherwise
	*/
	public function IsAdmin($host) {
		foreach($this->GetAdmins() as $admin) {
			if(Parse::WildcardMatch($admin, $host))
				return true;
		}
		return false;
	}

	//setters

	/*!
	* Set bot start time, that's used to report uptime.
	* @param $timestamp Unix timestamp.
	*/
	public function SetStartTime($timestamp) {
      $settings_pool = IrcSettings::GetInstance()->GetPool('core');
      $settings_pool->Set('start_time', $timestamp);
	}

   /*public function SetCallbacks($callbacks) {
      $this->callbacks = $callbacks;
      $this->callbacks->AddCallback('join', array($this, 'HandleJoin'));
   }*/

	//getters

	/*!
	* @return Unix timestamp of bot start time.
	*/
	public function GetStartTime() {
      return $settings_pool = IrcSettings::GetInstance()->GetPool('core')->Get('start_time');
	}

	/*!
	* @return Short description of this message handler.
	*/
	public function GetName() {
		return 'Tools for the bot owner';
	}
}
?>
