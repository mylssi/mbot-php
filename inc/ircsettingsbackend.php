<?PHP
require_once('ircsettings.php');
interface IrcSettingsBackend {
   public function LoadPool($pool);
   public function Load();
   public function SavePool($pool);
   public function SetParent(&$parent);
}
?>
