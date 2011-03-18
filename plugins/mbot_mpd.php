<?PHP
require_once('inc/ircplugin.php');
require_once('inc/ircformat.php');
require_once('mpd/mpd.class.php');
if(!class_exists('MbotMpd')) {
	class MbotMpd extends IrcPlugin {
		private $host = 'localhost';
		private $port = 6600;
		private $password = '';
		private $max_list = 10;
		private $last_search = array();
		public function OnMpd(&$channel, IrcPrivMsg $priv_msg, array $argv) {
			$this->Connect();
			switch(strtolower($argv[0])) {
			 	case 'np':
					return $this->HandleNp($channel);
				case 'pause':
					return $this->HandlePause();
				case 'search':
					return $this->HandleSearch($channel, $argv[1], $argv[2]);
				case 'play':
					return $this->HandlePlay($channel, $argv[1], $argv[2]);
			}
		}
		//a few shorthands
		public function OnSearch(&$channel, IrcPrivMsg $priv_msg, array $argv) {
			$this->Connect();
			return $this->HandleSearch($channel, 'title', $argv[0]);
		}
		public function OnPlay(&$channel, IrcPrivMsg $priv_msg, array $argv) {
			$this->Connect();
			return $this->HandlePlay($channel, 'title', $argv[0]);
		}
		public function OnList(&$channel, IrcPrivMsg $priv_msg, array $argv) {
			$this->Connect();
			return $this->PlayLastSearch($argv[0]);
		}
		private function HandleNp(&$channel) {
			$this->mpd->RefreshInfo();
			$current_track = $this->mpd->playlist[$this->mpd->current_track_id];
			$channel->Send($current_track['Artist'].' - '.$current_track['Title']);
		}
		private function HandlePause() {
			$this->mpd->Pause();
		}
		private function HandleSearch(&$channel, $type, $str) {
			$return = $this->SearchDB($type, $str);
			if(count($return) == 0) {
				$channel->Send("Nothing found");
			}
			elseif(count($return) <= $this->max_list) {
				$this->SearchResults(&$channel, $return);
			}
			else {
				$channel->Send('I\'m not going to list '.count($return).' songs');
			}
		}
		private function HandlePlay(&$channel, $type, $str) {
			if(strtolower($type) === 'list')
				return $this->PlayLastSearch($type);
			$return = $this->SearchDB($type, $str);
			if(count($return) == 1) {
				$this->Play($return[0]['file']);
				$channel->Send('Playing: '.$return[0]['Artist'].' - '.$return[0]['Title']);
			}
			elseif(count($return) == 0) {
				$channel->Send("Nothing found");
			}
			else { 
				$channel->Send('More than one song matches...');
				if(count($return) <= $this->max_list) {
					$this->SearchResults(&$channel, $return);
				}
				else
					$channel->Send('Actually there are too many matching songs that match even to list');
			}
		}
		private function Connect() {
			$this->mpd = empty($this->password) ? new mpd($this->host, $this->port) : new mpd($this->host, $this->port, $this->password);
		}
		private function SearchDB($type, $str) {
			$allowed_types = array('artist' => MPD_SEARCH_ARTIST, 'title' => MPD_SEARCH_TITLE, 'album' => MPD_SEARCH_ALBUM);
			if(!isset($allowed_types[strtolower($type)]))
				return;
			return $this->mpd->Search($allowed_types[strtolower($type)], $str);
		}
		private function Play($file) {
			//if song is already on the playlist, do not add it again
			$found = false;
			foreach($this->mpd->playlist as $idx => $song) {
				if($song['file'] == $file) {
					echo 'Song is already in the playlist'."\n";
					$this->mpd->SkipTo($idx);
					$found = true;
					break;
				}
			}
			if(!$found) {
				$this->mpd->PLAdd($file);	
				$this->mpd->RefreshInfo();
				$this->mpd->SkipTo(count($this->mpd->playlist)-1);
			}
		}
		private function PlayLastSearch($idx) {
			if(isset($this->last_search[$idx]))
				$this->Play($this->last_search[$idx]['file']);
		}
		private function SearchResults(&$channel, $songs) {
			$this->last_search = $songs;
			foreach($songs as $idx => $song) {	
				$channel->Send(IrcFormat::Bold($idx).'. '.$song['Artist'].' - '.$song['Title']);
			}
			$channel->Send('To play: '.IrcFormat::Green('!list #'));
		}
	}
}
return new MbotMpd;
?>
