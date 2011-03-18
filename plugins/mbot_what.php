<?PHP
require_once('inc/ircplugin.php');
require_once('inc/ircsettings.php');
if(!class_exists('MbotWhat')) {
   class MbotWhat extends IrcPlugin {
      private $torrent_dir = '/home/taimy/torrents/MP3';
      private $urlstart = 'http://what.cd/torrents.php?';
      private $authkey = 'd017d6caba7cabfab8168999903054ff';
      private $torrent_pass = '1e901dcce9d1d8ab4809e3d20c8cc1f1';
      private $base_url = 'http://what.cd/torrents.php?action=download&id=%s&authkey=%s&torrent_pass=%s';
      public function OnWhat(&$channel, IrcPrivMsg $priv_msg, $argv) {
         if(substr($argv[0], 0, strlen($this->urlstart)) != $this->urlstart) {
            $channel->Send('Kusinen URL...');
            return;
         }
         $get_str = parse_url($argv[0], PHP_URL_QUERY);
         parse_str($get_str, $url_args);
         if(!isset($url_args['torrentid'])) {
            $channel->Send('Kusinen URL...');
            return;
         }
         $torrent_url = sprintf($this->base_url, $url_args['torrentid'], $this->authkey, $this->torrent_pass);
         $file_h = fopen($this->torrent_dir.'/'.$url_args['torrentid'].'.torrent', 'w');
         $www_h = fopen($torrent_url, 'r');
         echo $torrent_url;
         if(!$file_h || !$www_h) {
            $channel->Send('Mjoo koitappa kohta uusix ;);)');
            return;
         }
         if(stream_copy_to_stream($www_h, $file_h))
            $channel->Send('Ja lataa... ::smoke');
      }
   }
}
return new MbotWhat;
?>
