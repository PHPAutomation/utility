<?php
/**
 * lib/Utility.php
 *
 * This class contains a bunch of public static member functions with various uses
 * and have been collected from various sources over the years
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 *
 * @author    John Lavoie
 * @copyright 2009-2016 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 */
namespace metaclassing;

class Utility
{
    public function __construct()
    {
        throw new \Exception("Do not create instances of this object, call public static member functions like \metaclassing\Utility::someDumbThing(params)");
    }

	/*
		determine if a string is valid json to decode, return bool
	*/
    public static function isJson($string)
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

	/*
		decode json into an array and throw exceptions if there is a problem
	*/
	public static function decodeJson($string)
	{
	    // decode json into an array
	    $result = json_decode($string, true);

	    // handle possible json errors and throw exceptions
	    switch (json_last_error()) {
	        case JSON_ERROR_NONE:
	            break;
   	     case JSON_ERROR_DEPTH:
   	         throw new \Exception('The maximum stack depth has been exceeded');
   	     case JSON_ERROR_STATE_MISMATCH:
   	         throw new \Exception('Invalid or malformed JSON');
   	     case JSON_ERROR_CTRL_CHAR:
   	         throw new \Exception('Control character error, possibly incorrectly encoded');
   	     case JSON_ERROR_SYNTAX:
   	         throw new \Exception('Syntax error, malformed JSON');
   	     // PHP >= 5.3.3
    	    case JSON_ERROR_UTF8:
  	          throw new \Exception('Malformed UTF-8 characters, possibly incorrectly encoded');
      	  // PHP >= 5.5.0
       	 case JSON_ERROR_RECURSION:
       	     throw new \Exception('One or more recursive references in the value to be encoded');
       	 // PHP >= 5.5.0
       	 case JSON_ERROR_INF_OR_NAN:
       	     throw new \Exception('One or more NAN or INF values in the value to be encoded');
        case JSON_ERROR_UNSUPPORTED_TYPE:
      		      throw new \Exception('A value of a type that cannot be encoded was given');
        	default:
        	    throw new \Exception('Unknown JSON error occured');
    	}
    	return $result;
	}

	/*
		quick and dirty function to create folders if they dont exist and set permissions
	*/
	public static function safeMakeDirectory($directory, $permissions = 0755)
    {
        // make the directory if it doesnt exist
        if ( !is_dir($directory) ) {
            print "Directory {$directory} does not exist, attempting to create...\n";
            mkdir($directory, $permissions, true);
            chmod($directory, $permissions);
            // or pitch a fit if that fails
            if ( !is_dir($directory) ) {
                throw new \Exception("Failed to create {$directory} directory");
            }
            return true;
        }
        return false;
    }

	public static function getDirectoryFiles($directory)
	{
		return array_filter( scandir($directory), function($item) { return !is_dir('../pages/' . $item); } );
	}

	/********************
	* Ryan's Var Dumper *
	********************/
	public static function dumper($var)
	{
		print "<pre>\n";
		print_r($var);
		print "</pre><br>\n";
	}

	public static function dumperToString($var)
	{
		ob_start();
		\metaclassing\Utility::dumper($var);
		$result = ob_get_clean();
		return $result;
	}

	public static function dBugToString($debug)
	{
	    ob_start();
	    new dBug($debug);
	    $result = ob_get_clean();
	    return $result;
	}

	public static function lastStackCall($e)
	{
		$trace = reset($e->getTrace());
		$traceFile = basename($trace['file']);
		return "! IN {$traceFile} (line {$trace['line']}) function {$trace['function']}()\n";
	}

	public static function microtimeTicks()
	{
		// Turn microtime into an array (12345 0.7563262)
		$ticks = explode(" ", microtime() );
		// Return the sum of the two numbers (double precision number)
		return $ticks[0] + $ticks[1];
	}

	public static function checkValidEmail($email)
	{
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex)
		{
			$isValid = false;
		}
		else
		{
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64)
			{
				// local part length exceeded
				$isValid = false;
			}
			else if ($domainLen < 1 || $domainLen > 255)
			{
				// domain part length exceeded
				$isValid = false;
			}
			else if ($local[0] == '.' || $local[$localLen-1] == '.')
			{
				// local part starts or ends with '.'
				$isValid = false;
			}
			else if (preg_match('/\\.\\./', $local))
			{
			// local part has two consecutive dots
			$isValid = false;
			}
			else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
			{
				// character not valid in domain part
				$isValid = false;
			}
			else if (preg_match('/\\.\\./', $domain))
			{
				// domain part has two consecutive dots
				$isValid = false;
			}
			else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
			{
				// character not valid in local part unless
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/',
					 str_replace("\\\\","",$local)))
				{
					$isValid = false;
				}
			}
			if ( $isValid && !checkdnsrr($domain,"MX") )
			{
				// domain not found in DNS
				$isValid = false;
			}
		}
		return $isValid;
	}

    public static function tcpProbe($host,$port,$timeout = 1)
    {
        if ( false == ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) { return false; }
        if ( false == (socket_set_nonblock($socket))) { return 0; }
        $time = time();
        while (!@socket_connect($socket, $host, $port))
        {
            $err = socket_last_error($socket);
            if ($err == 115 || $err == 114)
            {
                if ((time() - $time) >= $timeout)
                {
                    socket_close($socket);
                    return false;
                }
                usleep(50000);  // Sleep for 50 ms! we run this loop 20 times before default timeout.
                continue;
            }
            return false;
        }
        socket_close($socket);
        return true;
    }

	public static function assocRange($START,$END,$STEP = 1)
    {
        $RETURN = array();
        $RANGE = range($START,$END,$STEP);
        foreach ($RANGE as $KEY)
        { $RETURN["$KEY"] = "$KEY"; }
        return $RETURN;
    }

    public static function assocArrayKeys($ARRAY)
    {
        $RETURN = array();
        $KEYS = array_keys($ARRAY);
        foreach ($KEYS as $KEY)
        { $RETURN["$KEY"] = $KEY; }
        return $RETURN;
    }

    public static function assocArrayField($ARRAY,$FIELD)
    {
        $RETURN = array();
        foreach ($ARRAY as $ELEMENT) { array_push($RETURN,$ELEMENT[$FIELD]); }
        return $RETURN;
    }

	// inline recursive function calling is so stupid is so stupid is so stupid is so stupid...
    public static function objectToArray($OBJ)
    {
        if ( is_object($OBJ) ) {
            // Gets the properties of the given object with get_object_vars function
            $OBJ = get_object_vars($OBJ);
        }
        if ( is_array($OBJ) ) {
            // Return array converted to object using __FUNCTION__ (Magic constant) for recursive call in THIS object class type
            return array_map([get_called_class(), __FUNCTION__], $OBJ);
        }
        return $OBJ;
    }

	public static function is_assoc($var)
	{
	        return is_array($var) && array_diff_key($var,array_keys(array_keys($var)));
	}

	public static function flush()
	{
	    if (php_sapi_name() != "cli") // DONT FLUSH THE FUCKING CLI!
	    {
	//      echo(str_repeat(' ',256));
	        if (ob_get_length())
	        {
	            @ob_flush();
	            @flush();
   	         @ob_end_flush();
   	     }
   	     @ob_start();
   	 }
	}


	function parseNestedListToArray($LIST, $INDENTATION = " ")
	{
    $RESULT = array();
    $PATH = array();

    $LINES = explode("\n",$LIST);

    foreach ($LINES as $LINE)
    {
        if ($LINE == "") { continue; print "Skipped blank line\n"; } // Skip blank lines, they dont need to be in our structure
        $DEPTH  = strlen($LINE) - strlen(ltrim($LINE));
        $LINE   = trim($LINE);
        // truncate path if needed
        while ($DEPTH < sizeof($PATH))
        {
            array_pop($PATH);
        }
        // keep label (at depth)
        $PATH[$DEPTH] = $LINE;
        // traverse path and add label to result
        $PARENT =& $RESULT;
        foreach ($PATH as $DEPTH => $KEY)
        {
            if (!isset($PARENT[$KEY]))
            {
                $PARENT[$LINE] = array();
                break;
            }
            $PARENT =& $PARENT[$KEY];
        }
    }
    $RESULT = recursive_remove_empty_array($RESULT);
    return $RESULT;
	}

	public static function recursiveRemove_empty_array($ARRAY)
	{
    $RETURN = array();
    foreach($ARRAY as $KEY => $VALUE)
    {
        if (count($VALUE) == 0)
        {
            $RETURN[$KEY] = 1;
        }else{
            $RETURN[$KEY] = recursive_remove_empty_array($VALUE);
        }
    }
    return $RETURN;
	}

	// Find if a character $NEEDLE is in a string $HAYSTACK defaulting to case sensitive!
	public static function inString($needle, $haystack, $insensitive = false) {
	    if ($insensitive) {
	        return false !== stristr($haystack, $needle);
	    } else {
		        return false !== strpos($haystack, $needle);
	    }
	}

	public static function pregGrepKeys($pattern, $input, $flags = 0)
	{
   		return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
	}

}
