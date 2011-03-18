<?PHP
require_once('inc/ircservice.php');
class MbotVfs implements IrcService {
   private $last_poll;
   private $poll_time;
   private $vfs_dir;
   private $mode = 0755;
   private $initialized = false;
   private $file_handles = array();
   private $buffer = 4096;

   public function __construct($dir='vfs/networks', $poll_time=2) {  
      $this->poll_time = $poll_time;
      $this->vfs_dir = $dir;
   }

   private function CreateVfs(IrcSocketHandler $socket_handler) {
      if(file_exists($this->vfs_dir))
         throw new IrcException("VFS dir exists", 702);
      mkdir($this->vfs_dir, $this->mode);
      $servers = $socket_handler->GetServers();
      foreach($servers as $srv) {
         mkdir($this->vfs_dir.'/'.$srv->GetNetwork(), $this->mode);
         foreach($srv->GetChannels() as $channel) {
            posix_mknod($this->vfs_dir.'/'.$srv->GetNetwork().'/'.$channel->GetName(), $this->mode);
            $this->CreateFileHandle($srv->GetNetwork(), $channel->GetName());
         }
      }
      $this->initialized = true;
   }

   private function CreateFileHandle($network, $channel) {
      $fh = fopen($this->vfs_dir.'/'.$network.'/'.$channel, "r+");
      $this->file_handles[$network][$channel] = &$fh;
   }

   public function Poll(IrcSocketHandler $h) {
      if((time() - $this->last_poll) <= $this->poll_time)
         return;
      if(!$this->initialized)
         return $this->CreateVfs($h);
      foreach($this->file_handles as $network => $channels) {
         $network_object = $h->GetServerByNetwork($network);
         if(!$network_object)
            continue;
         foreach($channels as $channel => $handle) {
            $channel_object = $network_object->GetChannelByName($channel);
            if(!$channel_object)
               continue;
            //try to obtain lock non-blocking
            if(!flock($handle, LOCK_EX | LOCK_NB))
               continue;
            $buffer = stream_get_contents($handle);
            /*while(!feof($handle)) {
               $buffer .= fread($handle, $this->buffer);
            }*/
            fseek($handle, 0);
            ftruncate($handle, 0);
            $lines = explode("\n", $buffer);
            foreach($lines as $line) {
               if(empty($line))
                  continue;
               $channel_object->Send($line);
            }
            //release lock
            flock($handle, LOCK_UN);
         }
      }
      $this->last_poll = time();
   }

}
?>
