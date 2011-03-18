O<?PHP
require_once('parse.php');
require_once('ircservermsghandler.php');
require_once('ircsettings.php');

class IrcAutoOp implements IrcServerMsgHandler { 
	public function Handle(&$server, $line) {
      $p = explode(' ', $line);
		if(strtoupper($p[1]) == 'JOIN') {
         $channel = $server->GetChannelByName(Parse::After($p[2], ':'));
         $this->HandleJoin($channel, Parse::After($p[0], ':'));
      }
   }

   private function HandleJoin(&$channel, $user) {
      if(!$channel)
         return;
      if($this->IsOppable($channel->GetServer()->GetNetwork(), $channel->GetName(), $user)) {
         $user = $channel->GetUserByNick(Parse::Before($user, '!'));
		   if(!$user)
			   return;
			return $user->Op();
      }
   }
   
   private function IsOppable($network, $channel, $user) {
      $ins = IrcSettings::GetInstance();
      $autoop_pool = $ins->GetPool('autoop');
      $core_pool = $ins->GetPool('core');
      $channels = $autoop_pool->Get($network);
      $admins = $core_pool->Get('admins');
      //admins
      foreach($admins as $to_op) {
         if(Parse::WildcardMatch($to_op, $user))
            return true;
      }
      if(!is_array($channels[$channel]))
         return false;
      //auto ops
      foreach($channels[$channel] as $to_op) {
         if(Parse::WildcardMatch($to_op, $user))
            return true;
      }
      return false;
   }

   public function GetName() {
      return "Auto Opper";
   }
}
         
         

