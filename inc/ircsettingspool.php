<?PHP
require_once('ircsettings.php');
class IrcSettingsPool {
   private $parent;
   private $pool_name;
   private $write_on_set = false;
   private $data = array();

   public function __construct(IrcSettings &$parent, $pool) {
      $this->SetParent($parent);
      $this->SetName($pool);
   }
   
   public function Get($key) {
      return $this->data[$key];
   }

   public function GetKeys() {
      return array_keys($this->data);
   }

   public function Set($key, $value) {
      $this->data[$key] = $value;
      //save it permanently if write_on_set is on
      if($this->GetWriteOnSet())
         $this->GetParent()->SavePool($this->GetName());
   }

   public function SetData(array $data) {
      $this->data = $data;
   }

   //Arraylike overloading
   
   public function __get($key) {
      return $this->Get($key);
   }

   public function __set($key, $value) {
      $this->Set($key, $value);
   }

   public function __isset($key) {
      return isset($this->data[$key]);
   }

   //setters and getters
   
   public function SetParent(&$parent) {
      $this->parent = $parent;
   }

   public function GetParent() {
      return $this->parent;
   }
   
   public function SetName($name) {
      $this->name = $name;
   }

   public function GetName() {
      return $this->name;
   }

   public function SetWriteOnSet($value = true) {
      $this->write_on_set = $value;
   }

   public function GetWriteOnSet() {
      return $this->write_on_set;
   }
}
?>
