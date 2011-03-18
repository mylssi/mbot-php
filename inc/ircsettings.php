<?PHP
require_once('ircsettingsbackend.php');
require_once('ircsettingspool.php');
class IrcSettings {
   static private $instance;
   private $pools = array();
   private $backend;
   private $save_on_quit = false;
   
   public function __destruct() {
      if($this->GetSaveOnQuit())
         $this->Save();
   }
   
   //singleton
   static public function GetInstance() {
      if(!(IrcSettings::$instance instanceof IrcSettings))
         IrcSettings::$instance = new IrcSettings;
      return IrcSettings::$instance;
   }

   public function LoadPool($pool) {
      if(isset($this->backend))
         $this->pools[$pool] = $this->backend->LoadPool($pool);
   }

   public function Load() {
      if(isset($this->backend))
         $this->pools = $this->backend->Load();
   }

   public function GetPool($pool) {
      if(!isset($this->pools[$pool]))
         $this->pools[$pool] = new IrcSettingsPool($this, $pool);
      return $this->pools[$pool];
   }
   
   public function SavePool($pool) {
      if(isset($this->backend))
         $this->backend->SavePool($pool);
   }

   public function Save() {
      foreach($this->pools as $pool => $data) {
         $this->SavePool($pool);
      }
   }

   //array emulation
   public function __get($name) {
      return $this->GetPool($name);
   }

   public function __set($name, $value) {
      //ignore
      return;
   }

   //setters and getters
   
   public function SetBackend(IrcSettingsBackend &$backend) {
      $this->backend = $backend;
      $this->backend->SetParent($this);
      //load data
      $this->Load();
   }

   public function SetSaveOnQuit($value = true) {
      $this->save_on_quit = $value;
   }
   
   public function GetSaveOnQuit() {
      return $this->save_on_quit;
   }
}
