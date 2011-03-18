<?PHP
require_once("ircexception.php");
require_once("ircsettingsbackend.php");
require_once("ircsettingspool.php");
class IrcSettingsFileBackend implements IrcSettingsBackend {
   private $file_path;
   private $parent;

   public function __construct($file_path) {
      $this->SetFilePath($file_path);
   }

   public function Load() {
      $ret_array = array();
      //load data in serialized string
      $str_data = file_get_contents($this->GetFilePath());
      if($str_data === false)
         throw new IrcException("Can't read settings file", SETTINGS_CANT_LOAD);
      //unserialize (and hope for the best)
      $array_data = unserialize($str_data);
      //if($array_data === false)
      //   throw new IrcException("Badly formatted settings file", SETTINGS_BADLY_FORMATTED);
      //check that the array contains only IrcSettingsPools
      foreach($array_data as $key => $object) {
         if($object instanceof IrcSettingsPool) {
            $ret_array[$key] = $object;
            //set the parent
            $ret_array[$key]->SetParent($this->GetParent());
         }
      }
      return $ret_array;
   }

   public function LoadPool($pool) {
      $data = $this->Load();
      if(!isset($data[$pool]) || !($data[$pool] instanceof IrcSettingsPool))
         return IrcSettingsPool($this->GetParent(), $pool);
      return $data[$pool];
   }
   
   public function SavePool($pool) {
      $data = $this->Load();
      $pool_data = $this->GetParent()->GetPool($pool);
      $data[$pool] = $pool_data;
      $this->Save($data);
   }

   private function Save($data) {
      $str_data = serialize($data);
      if($str_data === false)
         throw new IrcException("Can't serialize", SETTINGS_CANT_SAVE);
      $ret = file_put_contents($this->GetFilePath(), $str_data);
      if($ret === false)
         throw new IrcException("Can't write to the file", SETTINGS_CANT_SAVE);
   }

   //setters and getters

   public function SetParent(&$parent) {
      $this->parent = $parent;
   }

   public function GetParent() {
      return $this->parent;
   }

   protected function SetFilePath($path) {
      $this->file_path = $path;
   }

   protected function GetFilePath() {
      return $this->file_path;
   }
}
