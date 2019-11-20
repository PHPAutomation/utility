<?php
/**
 * lib/NeustarUltradnsClient.php.
 *
 * This class is a wrapper for JSON requests to the CloudFlare DNS API
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
 * @copyright 2015-2018 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 */
namespace Metaclassing;

class NeustarUltradnsClient
{
    private $account = '';
    private $username = '';
    private $password = '';

    //private $baseuri = 'https://mdns.verisign.com/mdns-web/api/v1';
    private $baseuri = 'https://api.ultradns.com';

    private $log = [];

    private $zones = [];
    private $zonerecords = [];

    private $accessToken = '';

    // User facing function to initialize the library
    public function __construct($account, $username, $password)
    {
        if (!$username) {
            throw new \Exception('Missing username');
        }
        if (!$password) {
            throw new \Exception('Missing password');
        }
        $this->username = $username;
        $this->password = $password;
        // Try to get our list of domains automatically
        $this->getZones();
//echo 'DEBUG found '.count($this->zones).' zones'.PHP_EOL;
    }

    // Return our log messages of requests and responses for debugging
    public function logs()
    {
        return $this->log;
    }

    // get an authorization token to talk to the API
    protected function authenticate()
    {
//echo 'DEBUG inside authenticate'.PHP_EOL;
        $uri = $this->baseuri . '/authorization/token';
        $body = [
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password,
        ];

        $this->log[] = [
            'request' => [
                'method' => 'post',
                'uri' => $uri,
            ],
        ];

        $response = \Httpful\Request::post($uri)
                                                ->sendsType(\Httpful\Mime::FORM)
                                                ->expects(\Httpful\Mime::JSON)
                                                ->parseWith(function ($body) {
                                                   return $body;
                                                   //return \Metaclassing\Utility::decodeJson($body);
                                                })
                                                ->body($body)
                                                ->send()
                                                ->body;
        $this->log[count($this->log) - 1]['response'] = $response;

        // parse our response as json hopefully...
        $results = \Metaclassing\Utility::decodeJson($response);

        // if we got an accessToken in the response and its non-empty then lets try to use that i guess...
        if (isset($results['accessToken']) && $results['accessToken']) {
            $this->accessToken = $results['accessToken'];
        }

        if (!$this->accessToken) {
            die('NEUSTAR DNS CLIENT DOES NOT HAVE A VALID ACCESS TOKEN!');
        }
//echo 'DEBUG got neustar access token'.PHP_EOL;
    }

    // Internal function for getting all the paged results
    protected function httpGet($uri)
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $results = [];

        // Start requesting at page 1
        $page = 0;
        $pageSize = 1000;
        // We dont know how many pages there are, so assume 1 until we know
        $pagecount = 0;
        while ($page <= $pagecount) {
            //echo('fetching page '.$page.' of '.$pagecount.' current result size is '.count($results).PHP_EOL);
            $this->log[] = [
                        'request' => [
                                    'method' => 'GET',
                                    'uri'    => $uri.'?offset='.$page.'&limit='.$pageSize,
                                    ],
                        ];
            $response = \Httpful\Request::get($uri.'?limit='.$pageSize)
                                                    ->addHeader('Authorization', 'Bearer '.$this->accessToken)
                                                    ->expectsType(\Httpful\Mime::JSON)
                                                    ->parseWith(function ($body) {
                                                        return \Metaclassing\Utility::decodeJson($body);
                                                    })
                                                    ->send()
                                                    ->body;
            $this->log[count($this->log) - 1]['response'] = $response;

            $resultKey = '';
            if (isset($response['zones'])) {
                $resultKey = 'zones';
            }
            if (isset($response['rrSets'])) {
                $resultKey = 'rrSets';
            }
            if (!$resultKey) {
                throw new \Exception('Unhandled results key, check dns client logs for details');
            }
            foreach ($response[$resultKey] as $result) {
                array_push($results, $result);
            }
            // Now that we KNOW the pagecount, update that and increment our page counter
/*
            if (isset($response['total_count'])) {
                $totalResults = $response['total_count'];
                $pagecount = ceil($totalResults / $pageSize);
            }
/**/
            // And get the next page ready for the next request
            $page++;
        }

//var_dump($this->log);
//die(PHP_EOL);

        return $results;
    }

    // Internal function to post json back to the API
    protected function httpPostJson($uri, $body)
    {
        $this->log[] = [
                        'request' => [
                                    'method' => 'POST',
                                    'uri'    => $uri,
                                    'body'   => $body,
                                    ],
                        ];
        if (!$this->accessToken) {
            $this->authenticate();
        }
        $response = \Httpful\Request::post($uri)
                                                ->addHeader('Authorization', 'Bearer '.$this->accessToken)
                                                ->sendsAndExpects(\Httpful\Mime::JSON)
                                                ->parseWith(function ($body) {
                                                   return $body;
                                                   //return \Metaclassing\Utility::decodeJson($body);
                                                })
                                                ->body($body)
                                                ->send()
                                                ->body;
        $this->log[count($this->log) - 1]['response'] = $response;
        //if ($response['success'] != true) {
        //    throw new \Exception("post to {$uri} unsuccessful");
        //}

        return $response;
    }

    // Internal function to delete records in the API
    protected function httpDelete($uri, $body)
    {
        $this->log[] = [
                        'request' => [
                                    'method' => 'DELETE',
                                    'uri'    => $uri,
                                    'body'   => $body,
                                    ],
                        ];
        $response = \Httpful\Request::delete($uri)
                                                ->addHeader('Authorization', 'Bearer '.$this->accessToken)
                                                ->sendsAndExpects(\Httpful\Mime::JSON)
                                                ->parseWith(function ($body) {
                                                   return $body;
                                                   //return \Metaclassing\Utility::decodeJson($body);
                                                })
                                                ->body($body)
                                                ->send()
                                                ->body;
        $this->log[count($this->log) - 1]['response'] = $response;
        //if ($response['success'] != true) {
        //    throw new \Exception("delete to {$uri} unsuccessful");
        //}

        return $response;
    }

    // Public handler for MOST zone types we would want to add
    public function addZoneRecord($zone, $type, $name, $content, $ttl = 120)
    {
//echo 'DEBUG inside addzonerecord'.PHP_EOL;
        // List of supported record types to add
        $types = ['TXT'];
        if (!in_array($type, $types)) {
            throw new \Exception("record type {$type} not in list of supported types: ".implode(',', $types));
        }
        // Make sure we know what zone to target
        if (!isset($this->zones[$zone])) {
            throw new \Exception("unknown zone {$zone} did you call getZones()?");
        }
        // VERISCUMs new api is REALLY DUMB about name.zone.tld with a missing DOT at the end.
        if(substr($name, -1) != '.') {
            $name = $name . '.';
        }
        // build our postable body
        $request = [
//                    'owner'   => $name,
//                    'type'    => $type,
                    'rdata'   => [$content],
                    'ttl'     => $ttl,
                ];
        $uri = $this->baseuri.'/zones/'.$zone.'/rrsets/txt/'.$name;
        $result = $this->httpPostJson($uri, $request);
        // Save the resulting new zone into our cache of zones
        $this->zonerecords[$zone][] = $result;

        return $result;
    }

    // Semi public function to delete zone records by ID because I dont have a strong enough selector... you can have 3 A records that are identical other than content...
    public function delZoneRecord($zone, $recordid)
    {
//echo 'DEBUG inside delzonerecord for zone '.$zone.' record '.$recordid.PHP_EOL;
        if (!$recordid) {
            throw new \Exception('missing record id');
        }
        // Make sure we know what zone to target
        if (!isset($this->zones[$zone])) {
            throw new \Exception("unknown zone {$zone} did you call getZones()?");
        }
        $uri = $this->baseuri.'/zones/'.$zone.'/rrsets/txt/'.$recordid;
        $body = ['comments' => 'automated api delete'];
        $result = $this->httpDelete($uri, $body);
//dd("OOF");
        return $result;
    }

    // Internal function to fetch all zones from the API and map into a useful data structure
    protected function fetchZones()
    {
//echo 'DEBUG inside fetchzones'.PHP_EOL;
        $uri = $this->baseuri.'/zones';
        $results = $this->httpGet($uri);
        foreach ($results as $zone) {
            // TODO add some zone parsing error handling
            $dumb = rtrim($zone['properties']['name'], '.');
            $this->zones[$dumb] = $zone;
        }
    }

    // User facing function to retrieve and cache zone information
    public function getZones()
    {
//echo 'DEBUG inside getzones'.PHP_EOL;
        if (!count($this->zones)) {
            $this->fetchZones();
        }
//dd($this->zones);
        return $this->zones;
    }

    // User facing function to just spit back the names of all known zones
    public function listZones()
    {
        return array_keys($this->zones);
    }

    // Internal function to get all the records for one zone and cache the data
    protected function fetchRecords($zone)
    {
        if (!$zone) {
            throw new \Exception('fetchRecords called with blank zone');
        }
        $uri = $this->baseuri.'/zones/'.$zone.'/rrsets/txt';
        $result = $this->httpGet($uri);
        foreach($result as $key => $value) {
            $result[$key]['type'] = 'TXT';
            $regex = '/\.'.$zone.'\.$/';
            $result[$key]['nameWithoutTld'] = preg_replace($regex, '', $result[$key]['ownerName']);
        }

        return $result;
    }

    // User facing function to return possibly cached records for a zone by name
    public function getRecords($zone, $force = false)
    {
//echo 'DEBUG inside getRecords for zone '.$zone.PHP_EOL;
        if (!isset($this->zones[$zone])) {
            throw new \Exception("Unknown zone {$zone} requested, did you call getZones() first?");
        }
        if (!isset($this->zonerecords[$zone]) || $force) {
            $this->zonerecords[$zone] = $this->fetchRecords($zone);
        }

        return $this->zonerecords[$zone];
    }
}

