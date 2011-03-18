<?PHP
class DownloadParser {
   static public function ParseMovie($rls_name) {
      $ret = array();
      $regex = '/(.+)\.([\d]+)\..*/';
      if(!preg_match($regex, $rls_name, $matches))
         return false;
      $ret['title'] = $matches[1];
      $ret['year'] = $matches[2];
      return $ret;
   }
}
?>
