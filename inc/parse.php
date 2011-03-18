<?PHP
/*!
* Parse gives basic tools to parse messages. 
* All functions are static so they can be called without initializing an instance and still remain in their own namespace.
* @author Teemu Eskelinen
*/
class Parse {
	/*!
	* Gets everything before a delimiter from a string.
	* @param $str The string. 
	* @param $delimiter The delimiter
	* @param $num How many delimiters are ignored.
	* @return Everything before the delimiter.
	*/
	static public function Before($str, $delimiter, $num=1) {
		for($i = 0; $i < $num; $i++) {
			$str = substr($str, 0, strpos($str, $delimiter)+strlen($delimiter)-1);
		}
		return $str;
	}

	/*!
	* Gets everything after a delimiter from a string.
	* @param $str The string. 
	* @param $delimiter The delimiter
	* @param $num How many delimiters are ignored.
	* @return Everything after the delimiter.
	*/
	static public function After($str, $delimiter, $num=1) {
		for($i = 0; $i < $num; $i++) {
			$str = substr($str, strpos($str, $delimiter)+strlen($delimiter));
		}
		return $str;
	}

	/*!
	* Splits line to "arguments" like argv in C. " and ' can be used to escape whitespaces.
	* @param $str Line to split to argv-like array.
	* @return Array of arguments.
	*/
	static public function Arguments($str) {
		$escaped = array('"', "'");
		$inside_e = false;
		$ret = array();
		$cur = '';
		for($i = 0; $i < strlen($str); $i++) {
			if($inside_e === $str[$i]) {
				if($i > 0 && $str[$i-1] == '\\' && strlen($cur) > 0)
					$cur[strlen($cur)-1] = $str[$i];
				else {
					$ret[] = $cur;
					$cur = '';
					$inside_e = false;
				}
			}
			elseif(!$inside_e && in_array($str[$i], $escaped))
				$inside_e = $str[$i];
			elseif($inside_e || $str[$i] !== ' ')
				$cur .= $str[$i];
			elseif($str[$i] === ' ') {
				if(strlen($cur))
					$ret[] = $cur;
				$cur = '';
			}
		}
		if(strlen($cur))
			$ret[] = $cur;
		return $ret;
	}

	/*!
	* Does a wildcard match. * is the wildcard.
	* @param $pattern The needle. 
	* @param $file The haystack.
	* @return TRUE if they match, FALSE if they don't.
	* @author Unknown
	*/
	static public function WildcardMatch($pattern, $file) {
		$lenpattern = strlen($pattern);
		$lenfile    = strlen($file);
		for($i=0 ; $i<$lenpattern ; $i++)
		{
			if($pattern[$i] == "*")
			{
				for($c=$i ; $c<max($lenpattern, $lenfile) ; $c++)
				{
					if(self::WildcardMatch(substr($pattern, $i+1), substr($file, $c))) return true;
				}
				return false;
			}
			if($pattern[$i] == "[")
			{
				$letter_set = array();
				for($c=$i+1 ; $c<$lenpattern ; $c++)
				{
					if($pattern[$c] != "]")
						array_push($letter_set, $pattern[$c]);
					else
						break;
				}
				foreach($letter_set as $letter)
				{
					if(self::WildcardMatch($letter.substr($pattern, $c+1), substr($file, $i)))
					return true;
				}
				return false;
			}
			if($pattern[$i] == "?") continue;
			if($pattern[$i] != $file[$i]) return false;
		}
		if(($lenpattern != $lenfile) && ($pattern[$i - 1] == "?")) return false;
		return true;
	}

	/*!
	* Literal version of time. For example: "1 hour and 22 minutes and 5 seconds".
	* @param $time Time span in seconds.
	* @return Literal version of the time span.
	*/
	static public function StrTime($time) {
		$time_units = array('year'   => 365*24*60*60,
                          'month'  => 30*24*60*60,
                          'day'    => 24*60*60,
                          'hour'   => 60*60,
                          'minute' => 60,
                          'second' => 1);
      $ret_arr = array();
      //loop and add to the array
      foreach($time_units as $name => $in_seconds) {
         $whole_num = floor($time/($in_seconds));
         if($whole_num > 0) {
            //Plurals just by adding 's'. :P
            //Works almost always :P
            $ret_arr[] = $whole_num.' '.$name.($whole_num > 1 ? 's' : '');
            $time = $time%$in_seconds;
         }
      }
      //create the string
      if(count($ret_arr) === 1)
         return $ret_arr[0];
      $ret_string = join(array_splice($ret_arr, 0, count($ret_arr)-1), ', ');
      $ret_string .= ' and '.$ret_arr[count($ret_arr)-1];
      return $ret_string;
	}
}
?>

