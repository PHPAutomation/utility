<?php
/**
 * lib/Utility.php.
 *
 * This class contains a bunch of public static member functions with various uses
 * and have been collected from various sources over the years
 *
 * PHP version 7
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
        Check the last JSON encode or decode error and throw exceptions if there is a problem
    */
    public static function testJsonError()
    {
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

        return false;
    }

    /*
        encode data into json and throw exceptions if there is a problem
    */
    public static function encodeJson($data)
    {
        // decode json into an array
        $result = json_encode($data, true);
        // handle possible json errors and throw exceptions
        self::testJsonError();

        return $result;
    }

    /*
        decode json into an array and throw exceptions if there is a problem
    */
    public static function decodeJson($string)
    {
        // decode json into an array
        $result = json_decode($string, true);
        // handle possible json errors and throw exceptions
        self::testJsonError();

        return $result;
    }

	/*
		This converts a big ugly long line of XML to something human readable
	*/
	function xmlPrettyPrint($xml)
	{
		$dom = new \DOMDocument;
		$dom->preserveWhiteSpace = FALSE;
		$dom->loadXML($xml);
		$dom->formatOutput = TRUE;
		return $dom->saveXml();
	}

    /*
        Some stolen stackoverflow code to safely convert multidimensional complex arrays into UTF8 safe stuff to json_encode
    */
    public static function encodeArrayUTF8($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = \metaclassing\Utility::encodeArrayUTF8($value);
            } else {
                $array[$key] = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
            }
        }

        return $array;
    }

    /*
        quick and dirty function to create folders if they dont exist and set permissions
    */
    public static function safeMakeDirectory($directory, $permissions = 0755)
    {
        // make the directory if it doesnt exist
        if (!is_dir($directory)) {
            echo "Directory {$directory} does not exist, attempting to create...\n";
            mkdir($directory, $permissions, true);
            chmod($directory, $permissions);
            // or pitch a fit if that fails
            if (!is_dir($directory)) {
                throw new \Exception("Failed to create {$directory} directory");
            }

            return true;
        }

        return false;
    }

    public static function getDirectoryFiles($directory)
    {
        return array_filter(scandir($directory), function ($item) {
            return !is_dir('../pages/'.$item);
        });
    }

    /********************
    * Ryan's Var Dumper *
    ********************/
    public static function dumper($var)
    {
        if (php_sapi_name() != 'cli') { // DONT PRE THE CLI!
            echo "<pre>\n";
        }
        print_r($var);
        if (php_sapi_name() != 'cli') { // DONT PRE THE CLI!
            echo "</pre><br>\n";
        }
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
        new \dBug($debug);
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
        $ticks = explode(' ', microtime());
        // Return the sum of the two numbers (double precision number)
        return $ticks[0] + $ticks[1];
    }

    public static function checkValidEmail($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, '@');
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } elseif ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } elseif ($local[0] == '.' || $local[$localLen - 1] == '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } elseif (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
            $isValid = false;
            } elseif (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } elseif (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } elseif (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace('\\\\', '', $local))) {
                // character not valid in local part unless
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/',
                     str_replace('\\\\', '', $local))) {
                    $isValid = false;
                }
            }
            if ($isValid && !checkdnsrr($domain, 'MX')) {
                // domain not found in DNS
                $isValid = false;
            }
        }

        return $isValid;
    }

    public static function tcpProbe($host, $port, $timeout = 1)
    {
        if (false == ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
            return false;
        }
        if (false == (socket_set_nonblock($socket))) {
            return 0;
        }
        $time = time();
        while (!@socket_connect($socket, $host, $port)) {
            $err = socket_last_error($socket);
            if ($err == 115 || $err == 114) {
                if ((time() - $time) >= $timeout) {
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

    public static function assocRange($START, $END, $STEP = 1)
    {
        $RETURN = [];
        $RANGE = range($START, $END, $STEP);
        foreach ($RANGE as $KEY) {
            $RETURN["$KEY"] = "$KEY";
        }

        return $RETURN;
    }

    public static function assocArrayKeys($ARRAY)
    {
        $RETURN = [];
        $KEYS = array_keys($ARRAY);
        foreach ($KEYS as $KEY) {
            $RETURN["$KEY"] = $KEY;
        }

        return $RETURN;
    }

    public static function assocArrayField($ARRAY, $FIELD)
    {
        $RETURN = [];
        foreach ($ARRAY as $ELEMENT) {
            array_push($RETURN, $ELEMENT[$FIELD]);
        }

        return $RETURN;
    }

    // inline recursive function calling is so stupid is so stupid is so stupid is so stupid...
    public static function objectToArray($OBJ)
    {
        if (is_object($OBJ)) {
            // Gets the properties of the given object with get_object_vars function
            $OBJ = get_object_vars($OBJ);
        }
        if (is_array($OBJ)) {
            // Return array converted to object using __FUNCTION__ (Magic constant) for recursive call in THIS object class type
            return array_map([get_called_class(), __FUNCTION__], $OBJ);
        }

        return $OBJ;
    }

    public static function is_assoc($var)
    {
        return is_array($var) && array_diff_key($var, array_keys(array_keys($var)));
    }

    public static function parseNestedListToArray($LIST, $INDENTATION = ' ')
    {
        $RESULT = [];
        $PATH = [];

        $LINES = explode("\n", $LIST);

        foreach ($LINES as $LINE) {
            if ($LINE == '') {
                continue;
                echo "Skipped blank line\n";
            } // Skip blank lines, they dont need to be in our structure
        $DEPTH = strlen($LINE) - strlen(ltrim($LINE));
            $LINE = trim($LINE);
        // truncate path if needed
        while ($DEPTH < count($PATH)) {
            array_pop($PATH);
        }
        // keep label (at depth)
        $PATH[$DEPTH] = $LINE;
        // traverse path and add label to result
        $PARENT = &$RESULT;
            foreach ($PATH as $DEPTH => $KEY) {
                if (!isset($PARENT[$KEY])) {
                    $PARENT[$LINE] = [];
                    break;
                }
                $PARENT = &$PARENT[$KEY];
            }
        }
        $RESULT = \metaclassing\Utility::recursiveRemoveEmptyArray($RESULT);

        return $RESULT;
    }

    public static function recursiveRemoveEmptyArray($ARRAY)
    {
        $RETURN = [];
        foreach ($ARRAY as $KEY => $VALUE) {
            if (count($VALUE) == 0) {
                $RETURN[$KEY] = 1;
            } else {
                $RETURN[$KEY] = \metaclassing\Utility::recursiveRemoveEmptyArray($VALUE);
            }
        }

        return $RETURN;
    }

    public static function pregGrepKeys($pattern, $input, $flags = 0)
    {
        return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
    }

    public static function ifconfigInterfaces()
    {
        $COMMAND = '/sbin/ifconfig';
        $IFCONFIG = shell_exec($COMMAND);

        return \metaclassing\Utility::parseIfconfig($IFCONFIG);
    }

    public static function parseIfconfig($IFCONFIG)
    {
        $INTERFACES = [];

        $LINES = explode("\n", $IFCONFIG);
        $INT = 'Unknown';
        // Parse through output and identify interfaces
        foreach ($LINES as $LINE) {
            // Match ($INT) Link encap:(something) (hardware info)
            $MATCH = "/^(\w+)\s+Link\s+encap:(\w+)\s+(.+)/";
            if (preg_match($MATCH, $LINE, $REG)) {
                $INT = $REG[1];                                         // We found a new interface!
                $INTERFACES[$INT] = [];
                $INTERFACES[$INT]['name'] = $INT;                       // Store the interface name
                $INTERFACES[$INT]['encapsulation'] = $REG[2];           // Encapsulation type
                $INTERFACES[$INT]['hardware'] = $REG[3];                // Hardware information
                $INTERFACES[$INT]['ipv4'] = [];                    // and precreate the array of any addresses
                $INTERFACES[$INT]['ipv6'] = [];                    // for ipv4 and ipv6!
            }

            // Match inet addr:(i.p.a.d) blah blah Mask:(255.128.0.0)
            $MATCH = "/^\s+inet addr:(\S+)\s+.*Mask:(\S+)/";
            if (preg_match($MATCH, $LINE, $REG)) {
                $ADDRESS = [];                                     // We found an address on the interface
                $ADDRESS['address'] = $REG[1];                          // IPv4 address
                $ADDRESS['mask'] = $REG[2];                             // Subnet mask
                array_push($INTERFACES[$INT]['ipv4'], $ADDRESS);         // Stuff this address onto the interface
            }

            // Match inet6 addr: (i:p:v:6:a:d) blah? Scope:(Global)
            $MATCH = "/^\s+inet6 addr: (\S+)\s+.*Scope:(\S+)/";
            if (preg_match($MATCH, $LINE, $REG)) {
                $ADDRESS = [];                                     // We found an address on the interface
                $ADDRESS['address'] = $REG[1];                          // IPv6 address/prefix in CIDR notation!
                $ADDRESS['scope'] = $REG[2];                            // Network scope
                array_push($INTERFACES[$INT]['ipv6'], $ADDRESS);         // Stuff this address onto the interface
            }
            // TODO: Consider adding MTU, flags, metrics, statistics?
        }
        // Remove the loopback interfaces, this just screws up some apps...
        if (isset($INTERFACES['lo'])) {
            unset($INTERFACES['lo']);
        }

        return $INTERFACES;
    }

    public static function recursiveStripTags($INPUT, $ALLOWED_TAGS = '')
    {
        //print "Running recursiveStripTags on "; dumper($INPUT); print "<br>\n"; john_flush();

        if (self::is_assoc($INPUT)) {
            // If this is an associative array, parse it as key => value.

            foreach ($INPUT as $KEY => $VALUE) {
                $INPUT[$KEY] = \metaclassing\Utility::recursiveStripTags($VALUE, $ALLOWED_TAGS);
            }
        } elseif (is_array($INPUT)) {
            // If this is a normal array, parse it as $value.

            foreach ($INPUT as &$VALUE) {
                $VALUE = \metaclassing\Utility::recursiveStripTags($VALUE, $ALLOWED_TAGS);
            }
        } elseif (is_string($INPUT)) {
            // If this is a string, run the global strip_tags function.

            $INPUT = @strip_tags($INPUT, $ALLOWED_TAGS);
        }                           // If we dont know wtf we are given, dont muck it up.
        return $INPUT;
    }

    public static function drawSmallStatus($TEXT = '', $STATUSCOLOR = 'black', $FONTCOLOR = 'black')
    {
        $FONT = BASEDIR.'/font/arial.ttf';                                // Set Path to Font File
        $box = @imagettfbbox(10, 0, $FONT, $TEXT);                                 // Cheap trick to figure out how big to make our image
        $textwidth = abs($box[4] - $box[0]);
        $textheight = abs($box[5] - $box[1]);
        $WIDTH = 11 + $textwidth;                                               // Now our images have a dynamic width based on text length!
        $HEIGHT = 2 + $textheight;                                              // and a dynamic height
        if ($HEIGHT < 10) {
            $HEIGHT = 13;
        }

        $IMAGE = imagecreatetruecolor($WIDTH, $HEIGHT);                          // Create our GD image object

        $COLORS = [];                                                      // Create a pallet of colors
        $COLORS['transparent'] = imagecolorallocate($IMAGE, 254, 254, 254);
        $COLORS['white'] = imagecolorallocate($IMAGE, 255, 255, 255);
        $COLORS['black'] = imagecolorallocate($IMAGE, 0, 0, 0);
        $COLORS['gray'] = imagecolorallocate($IMAGE, 127, 127, 127);
        $COLORS['red'] = imagecolorallocate($IMAGE, 255, 0, 0);
        $COLORS['green'] = imagecolorallocate($IMAGE, 0, 224, 0);
        $COLORS['blue'] = imagecolorallocate($IMAGE, 0, 0, 255);
        $COLORS['yellow'] = imagecolorallocate($IMAGE, 255, 255, 0);
        $COLORS['orange'] = imagecolorallocate($IMAGE, 255, 165, 0);

        imagefill($IMAGE, 0, 0, $COLORS['transparent']);                           // Fill the image with our transparent color
        imagefilledellipse($IMAGE, 6, 6, 10, 10, $COLORS[$STATUSCOLOR]);        // Print a filled ellipse
        imagefilledellipse($IMAGE, 6, 6, 7, 7, $COLORS['transparent']);       // Print a filled ellipse
        imagefilledellipse($IMAGE, 6, 6, 3, 3, $COLORS[$STATUSCOLOR]);        // Print a filled ellipse
        imagettftext($IMAGE, 10, 0, 13, 11, $COLORS[$FONTCOLOR], $FONT, $TEXT); // Print Text On Image
        imagecolortransparent($IMAGE, $COLORS['transparent']);                  // Create a transparent background

        ob_start();                                                             // start a new output buffer
            imagepng($IMAGE);                                                   // Send Image to the buffer
            $RETURN = ob_get_contents();                                        // Capture image contents from buffer
        ob_end_clean();                                                         // stop this output buffer
        imagedestroy($IMAGE);                                                   // Clean up the image
        return $RETURN;
    }

    // check if an array is associative
    public static function isAssoc($var)
    {
        return is_array($var) && array_diff_key($var, array_keys(array_keys($var)));
    }

    public static function flush()
    {
        if (php_sapi_name() != 'cli') { // DONT FLUSH THE CLI!
            //echo(str_repeat(' ',256));
            if (ob_get_length()) {
                @ob_flush();
                @flush();
                @ob_end_flush();
            }
            @ob_start();
        }
    }

    public static function strip($a)
    {
        if (get_magic_quotes_gpc()) {
            return stripslashes($a);
        } else {
            return $a;
        }
    }

    // Find if a character $NEEDLE is in a string $HAYSTACK defaulting to case sensitive!
    public static function inString($needle, $haystack, $insensitive = false)
    {
        if ($insensitive) {
            return false !== stristr($haystack, $needle);
        }

        return false !== strpos($haystack, $needle);
    }

    public static function recursiveArrayDiffAssoc($array1, $array2)
    {
        $difference = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = \metaclassing\Utility::recursiveArrayDiffAssoc($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }

    public static function recursiveArrayFindKeyValue($ARRAY, $SEARCH)
    {
        if (is_array($ARRAY)) {
            foreach ($ARRAY as $KEY => $VALUE) {
                if ($SEARCH === $KEY) {
                    return $VALUE;
                }
                if (is_array($VALUE)) {
                    $FOUND = \metaclassing\Utility::recursiveArrayFindKeyValue($VALUE, $SEARCH);
                    if ($FOUND) {
                        return $FOUND;
                    }
                }
            }
        }

        return 0;
    }

    public static function recursiveArrayTypeValueSearch($ARRAY, $SEARCH)
    {
        if (is_array($ARRAY)) {
            foreach ($ARRAY as $KEY => $VALUE) {
                if ($KEY === 'type' && $VALUE === $SEARCH) {
                    return $ARRAY['value'];
                }
                if (is_array($VALUE)) {
                    $FOUND = \metaclassing\Utility::recursiveArrayTypeValueSearch($VALUE, $SEARCH);
                    if ($FOUND) {
                        return $FOUND;
                    }
                }
            }
        }

        return 0;
    }

    public static function isBinary($str)
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
    }

    public static function recursiveArrayBinaryValuesToBase64($arr)
    {
        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    $arr[$key] = \metaclassing\Utility::recursiveArrayBinaryValuesToBase64($value);
                } elseif (\metaclassing\Utility::isBinary($value)) {
                    $arr[$key] = base64_encode($value);
                }
            }
        }

        return $arr;
    }
}
