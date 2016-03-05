<?php
/**
 * lib/ArubaActivate.php.
 *
 * This class is a wrapper for JSON queries and updates to the Aruba activate API
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
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
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */
namespace metaclassing;

class ArubaActivate
{
    public $options;
    public $curlopts;
    public $baseurl = 'https://activate.arubanetworks.com';
    private $username;
    private $password;

    public function __construct($OPTIONS = [])
    {
        $this->options = $OPTIONS;
        if (!isset($this->options['username'])) {
            $this->options['username'] = ARUBA_ACTIVATE_USER;
        }
        if (!isset($this->options['password'])) {
            $this->options['password'] = ARUBA_ACTIVATE_PASS;
        }

        $this->curlopts = [
                                // We will be sending POST requests
                                CURLOPT_POST              => true,
                                CURLOPT_HTTPHEADER        => ['Content-Type: application/json'],
                                // Generic client stuff
                                CURLOPT_RETURNTRANSFER    => true,
                                CURLOPT_FOLLOWLOCATION    => true,
                                CURLOPT_USERAGENT         => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
                                // Debugging
                                //CURLOPT_CERTINFO		=> true,
                                //CURLOPT_VERBOSE		=> true,
                                ];
    }

    public function authenticate()
    {
        $URL = "{$this->baseurl}/LOGIN";                    // I should do some error handling here for user/pass creds...
        $POST = [
                    'credential_0' => $this->options['username'],
                    'credential_1' => $this->options['password'],
                    ];
        $POSTARRAY = [];
        foreach ($POST as $KEY => $VALUE) {
            // This could be done in 1 line and alot simpler...

            array_push($POSTARRAY, "{$KEY}={$VALUE}");
        }
        $POSTSTRING = implode('&', $POSTARRAY);                // The final urlencoded string we post
        $CURL = curl_init($URL);                            // Create a CURL handle
        curl_setopt_array($CURL, $this->curlopts);            // Set our standard options
        curl_setopt($CURL, CURLOPT_POSTFIELDS, $POSTSTRING);    // Set our credentials as the post values
        curl_setopt($CURL, CURLOPT_HEADER, true);            // We need the header for the cookie requesting
        $RESPONSE = curl_exec($CURL);                        // Post to the login URL
        $REGEX = "/^Set-Cookie:\s*([^;]*)/mi";                // Multi-cookie matcher/grabber
        if (preg_match_all($REGEX, $RESPONSE, $MATCHES)) {
            // Look for any and all cookies that come back

            foreach ($MATCHES[1] as $OMNOMNOM) {
                // This lets us see all the cookies

                $this->curlopts[CURLOPT_COOKIE] = $OMNOMNOM; // Eventhough we only store the last one for now
            }
        }
        if (!isset($this->curlopts[CURLOPT_COOKIE])) {
            echo "AUTH ERROR: {$RESPONSE}\n";
        }

        return $this->curlopts[CURLOPT_COOKIE];
    }

    public function get_devices()
    {
        $URL = "{$this->baseurl}/api/ext/inventory.json?action=query";
        $CURL = curl_init($URL);                        // Create a CURL handle
        curl_setopt_array($CURL, $this->curlopts);        // Set our standard options
        $RESPONSE = curl_exec($CURL);                    // Get the response to our query (blank = everything)
        $INVENTORY = json_decode($RESPONSE, 1);            // Decode the reply to an assoc array
        return $INVENTORY['devices'];                    // Return the array of devices that came back
    }

    public function get_devices_by_mac($MACS)            // This should be an array of mac addresses...
    {
        $URL = "{$this->baseurl}/api/ext/inventory.json?action=query";
        $CURL = curl_init($URL);                        // Create a CURL handle
        curl_setopt_array($CURL, $this->curlopts);        // Set our standard options
        // If $MACS is a string, convert it to an array!
        $QUERY = [                                    // Build a query assoc array... This format
                        'devices' => $MACS,                // is dumb but json_converts properly
                        ];
        $JSON = 'json='.json_encode($QUERY);            // Encode the array into a JSON query.
        curl_setopt($CURL, CURLOPT_POSTFIELDS, $JSON);    // Attach our encoded JSON string to the POST fields.
        $RESPONSE = curl_exec($CURL);                    // Get the response to our query
        $INVENTORY = json_decode($RESPONSE, 1);            // Decode the reply to an assoc array
        return $INVENTORY['devices'];                    // Return the array of devices that came back
    }

    public function change_device_name($MAC, $NEWNAME)
    {
        $URL = "{$this->baseurl}/api/ext/inventory.json?action=update";
        $CURL = curl_init($URL);                        // Create a CURL handle
        curl_setopt_array($CURL, $this->curlopts);        // Set our standard options
        // We need some error handling here...
        $QUERY = [
                        'devices' => [
                                            [
                                                    'mac'        => "{$MAC}",
                                                    'deviceName' => "{$NEWNAME}",
                                                ],
                                            ],
                    ];
        $JSON = 'json='.json_encode($QUERY);            // Encode the array into a JSON query.
        curl_setopt($CURL, CURLOPT_POSTFIELDS, $JSON);    // Attach our encoded JSON string to the POST fields.
        $RESPONSE = curl_exec($CURL);                    // Get the response to our query
        $UPDATE = json_decode($RESPONSE, 1);                // Decode the reply to an assoc array
        return $UPDATE;                                    // Return the array of update information
    }

    public function change_device_folderid($MAC, $FOLDERID)
    {
        $URL = "{$this->baseurl}/api/ext/inventory.json?action=update";
        $CURL = curl_init($URL);                        // Create a CURL handle
        curl_setopt_array($CURL, $this->curlopts);        // Set our standard options
        // We need some error handling here...
        $QUERY = [
                        'devices' => [
                                            [
                                                    'mac'         => "{$MAC}",
                                                    'folderId'    => "{$FOLDERID}",
                                                ],
                                            ],
                    ];
        $JSON = 'json='.json_encode($QUERY);            // Encode the array into a JSON query.
        curl_setopt($CURL, CURLOPT_POSTFIELDS, $JSON);    // Attach our encoded JSON string to the POST fields.
        $RESPONSE = curl_exec($CURL);                    // Get the response to our query
        $UPDATE = json_decode($RESPONSE, 1);                // Decode the reply to an assoc array
        return $UPDATE;                                    // Return the array of update information
    }

    public function get_folders()
    {
        $URL = "{$this->baseurl}/api/ext/folder.json?action=query";
        $CURL = curl_init($URL);                        // Create a CURL handle
        curl_setopt_array($CURL, $this->curlopts);        // Set our standard options
        $RESPONSE = curl_exec($CURL);                    // Get the response to our query (empty query = all folders)
        $FOLDERS = json_decode($RESPONSE, 1);            // Decode the reply to an assoc array
        return $FOLDERS['folders'];                        // Return the array of update information
    }
}
