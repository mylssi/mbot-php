<?PHP
require_once('inc/ircplugin.php');
require_once('inc/ircsettings.php');
if(!class_exists('MbotDownloads')) {
	class MbotDownloads extends IrcPlugin {
		public function OnGet(&$channel, IrcPrivMsg $priv_msg, array $argv) {
			$channel->Send('juu');
		}
	}
}
return new MbotDownloads;
?>
