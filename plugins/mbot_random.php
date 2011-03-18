<?PHP
require_once('inc/ircplugin.php');
if(!class_exists('MbotRandom')) {
	class MbotRandom extends IrcPlugin {
		public function OnRandom(&$channel, IrcPrivMsg $priv_msg, array $argv) {
			$channel->Send('tä');
		}
	}
}
return new MbotRandom;
?>
