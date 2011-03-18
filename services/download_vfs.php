<?PHP
require_once('inc/ircservice.php');
require_once('inc/ircformat.php');
require_once('downloads_inc/download_parser.php');
require_once('downloads_inc/imdbphp/imdb.class.php');
require_once('downloads_inc/imdbphp/imdbsearch.class.php');

class MbotDownloadVfs implements IrcService {
   private $last_poll;
   private $poll_time;
   private $vfs_dir;
   private $mode = 0755;
   private $initialized = false;
   private $channels = array();
   private $file_handles = array();
   private $buffer = 4096;

   private $basedir = '/mnt/illegal';
   private $banned_subdirs = array('XXX', 'RANDOM');
   private $strip_basedir = true;

   public function __construct($dir='vfs/downloads', $poll_time=2) {  
      $this->poll_time = $poll_time;
      $this->vfs_dir = $dir;
   }

   public function AddChannel($network, $channel) {
      $this->channels[$network][] = $channel;
   }

   private function CreateVfs(IrcSocketHandler $socket_handler) {
      if(file_exists($this->vfs_dir))
         throw new IrcException("Download VFS dir exists", 702);
      mkdir($this->vfs_dir, $this->mode);
      posix_mknod($this->vfs_dir.'/started', $this->mode);
      posix_mknod($this->vfs_dir.'/completed', $this->mode);
      $this->file_handles['started'] = fopen($this->vfs_dir.'/started', "r+");
      $this->file_handles['completed'] = fopen($this->vfs_dir.'/completed', "r+");
      $this->initialized = true;
   }

   private function GetChannels(IrcSocketHandler $h) {
      $ret = array();
      foreach($this->channels as $network => $channels) {
         $network_object = $h->GetServerByNetwork($network);
         if(!$network_object)
            continue;
         foreach($channels as $channel) {
            $channel_object = $network_object->GetChannelByName($channel);
            if(!$channel_object)
               continue;
            $ret[] = &$channel_object;
         }
      }
      return $ret;
   }

   private function BytesToHuman($bytes, $precision=2) {
      if($bytes >= 1073741824) {
         return round(($bytes/1073741824), $precision).' GB';
      }
      elseif($bytes >= 1048576) {
         return round(($bytes/1048576), $precision).' MB';
      }
      elseif($bytes >= 1024) {
         return round(($bytes/1024), $precision).' kB';
      }
      return $bytes. ' bytes';
   }

   private function GetTotalSize($path) {
      //open to attack, TODO fix this
      $res = explode("\t",exec("du -sb ".escapeshellarg($path)), 2);
      if($res[1] != $path)
         return false;
      return $res[0];
   }

   private function SendIMDBInfo(&$channel, $rls_name) {
      $data = DownloadParser::ParseMovie($rls_name);
      $search = new imdbsearch();
      $search->setsearchname($data['title']);
      $results = $search->results();
      foreach($results as $result) {
         if($result->year() == $data['year']) {
            $channel->Send('  '.IrcFormat::Green('IMDB Rating:').' '.IrcFormat::Bold($result->rating()).'/10.0');
            if(!empty($result->runtime()))
               $channel->Send('  '.IrcFormat::Green('Runtime:').' '.$result->runtime().'min');
            $channel->Send('  '.IrcFormat::Green('URL:').' '.$result->main_url());
            //$channel->Send($result->tagline());
            break;
         }
      }
   }

   private function VerifyPath($path) {
      if($this->basedir != substr($path, 0, strlen($this->basedir)))
         return false;
      foreach($this->banned_subdirs as $banned_dir) {
         $absolute_banned_dir = $this->basedir.'/'.$banned_dir;
         if(trim($absolute_banned_dir) === trim(substr($path, 0, strlen($absolute_banned_dir))))
            return false;
      }
      return true;
   }

   private function HandleStartedLine(IrcSocketHandler $h, $line) {
      if(!$this->VerifyPath($line))
         return;
      $s_pool = IrcSettings::GetInstance()->GetPool('download_vfs');
      $torrents = isset($s_pool->torrents) ? $s_pool->torrents : array();
      if(is_array($torrents[$line]))
         return;
      $torrents[$line] = array('start_time' => time());
      $s_pool->torrents = $torrents;
      IrcSettings::GetInstance()->SavePool('download_vfs');
      return;
   }
   
   private function HandleCompletedLine(IrcSocketHandler $h, $line) {
      $full_path = $line;
      $s_pool = IrcSettings::GetInstance()->GetPool('download_vfs');
      $torrents = isset($s_pool->torrents) ? $s_pool->torrents : array();
      if(!$this->VerifyPath($line))
         return;
      //strip basedir
      if($this->strip_basedir)
         $line = substr($line, strlen($this->basedir)+1);
      //get category and remove it from the path
      list($category, $line) = explode('/', $line, 2);
      //remove traling '/' from path
      if($line[strlen($line)-1] == '/')
         $line = substr($line, 0, strlen($line)-1);
      foreach($this->GetChannels($h) as $channel) {
         $channel->Send(IrcFormat::Bold('======================'));
         $channel->Send('New '.IrcFormat::Bold('completed').' download in '.IrcFormat::Red($category));
         $channel->Send($line);
         //IMDB info
         if($category == 'X264')
            $this->SendIMDBInfo($channel, $line);
         //size and speed info
         if(isset($torrents[$full_path])) {
            $started_ago = time() - $torrents[$full_path]['start_time'];
            echo $started_ago."\n";
            $channel->Send(IrcFormat::Green('Started:').' '.Parse::StrTime($started_ago).' ago');
            $size = $this->GetTotalSize($full_path);
            if($size !== false) {
               echo $size."\n";
               $channel->Send(IrcFormat::Green('Size:').' '.$this->BytesToHuman($size).' ('.$this->BytesToHuman($size/$started_ago).'/s)');
            }
         }
         $channel->Send(IrcFormat::Bold('======================'));
      }
   }

   public function Poll(IrcSocketHandler $h) {
      if((time() - $this->last_poll) <= $this->poll_time)
         return;
      if(!$this->initialized)
         return $this->CreateVfs($h);
      foreach(array('started', 'completed') as $m) {
         $handle = $this->file_handles[$m];
         //try to obtain lock non-blocking
         if(!flock($handle, LOCK_EX | LOCK_NB))
            continue;
         $buffer = stream_get_contents($handle);
         fseek($handle, 0);
         ftruncate($handle, 0);
         $lines = explode("\n", $buffer);
         foreach($lines as $line) {
            if(empty($line))
               continue;
            if($m == 'started')
               $this->HandleStartedLine($h, $line);
            elseif($m == 'completed')
               $this->HandleCompletedLine($h, $line);
         }
         //release lock
         flock($handle, LOCK_UN);
      }
      $this->last_poll = time();
   }
}
?>
