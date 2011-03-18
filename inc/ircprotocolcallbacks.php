<?PHP
require_once('ircprotocolhandler.php');
require_once('parse.php');
/*!
* IrcProtocolCallbacks provides callback interface for events in the IRC protocol. A little more flexible way to access events than the class based handler system.
* @author Teemu Eskelinen
*/
class IrcProtocolCallbacks implements IrcServerMsgHandler {
	private $callback_functions = array('join'      => array(),
                                       'part'      => array(),
                                       'mode'        => array(),
                                       'privmsg'   => array()); ///valid events
   /*!
   * Add a event callback
   * @see $callback_functions
   * @param $function_array function array object to the callback
   * @param $event event type
   * @return true if success, false otherwise
   */
   public function AddCallback($function_array, $event) {
      if(!is_callable($function_array) || isset($callback_functions[$event]))
         return false;
      $this->callback_functions[$event][] = $function_array;
      return true;
   }

   private function Call($type, $args) {
      if(!isset($this->callback_functions[$type]))
         return;
      foreach($this->callback_functions[$type] as $func) {
         if(is_callable($func))
            call_user_func($func, $args);
      }
   }

   /*!
   * @todo Get rid of overlapping stuff with IrcProtocolHandler
	* @see IrcServerMsgHandler
	* Handles the message and dispatches events. See ircservermsghandler.php for the 
   * method definition.
	*/
	public function Handle(&$server, $line) {
      $p = explode(' ', $line);
		switch(strtoupper($p[1])) {
         case 'JOIN':
         case 'PART':
            $channel = Parse::After($p[2], ':');
            $user_and_host = Parse::After($p[0], ':');
            $this->Call(strtolower($p[1]), array('channel' => $channel, 'user' => $user_and_host));
            return;
      }
   }

   public function GetName() {
      return 'IRC protocol callbacks';
   }
}
