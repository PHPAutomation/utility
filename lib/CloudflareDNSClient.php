<?php
/**
 * lib/CloudflareDNSClient.php
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
 * @copyright 2015-2016 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 */
namespace metaclassing;

class CloudflareDNSClient
{
	private $email = '';
	private $apikey = '';

	private $baseuri = 'https://api.cloudflare.com/client/v4';

	private $log = [];

	private $zones = [];
	private $zonerecords = [];

	// User facing function to initialize the library
	public function __construct($email, $apikey)
	{
		if (!$email) { throw new \Exception("Missing account email address"); }
		if (!$apikey) { throw new \Exception("Missing account API key"); }
		$this->email = $email;
		$this->apikey = $apikey;
		// Try to get our list of domains automatically
		$this->getZones();
	}

	// Return our log messages of requests and responses for debugging
	public function logs()
	{
		return $this->log;
	}

	// Internal function for getting all the paged results
	protected function httpGet($uri)
	{
		$results = [];
		// Start requesting at page 1
		$page = 1;
		// We dont know how many pages there are, so assume 1 until we know
		$pagecount = 1;
		while($page <= $pagecount) {
			$this->log[] = [
						"request" => [
									"method" => "GET",
									"uri" => $uri . '?page=' . $page,
									],
						];
			$response = \Httpful\Request::get($uri . '?page=' . $page)
													->addHeader('X-Auth-Email', $this->email)
													->addHeader('X-Auth-Key', $this->apikey)
													->expectsType(\Httpful\Mime::JSON)
													->parseWith(function ($body) {
														return \metaclassing\Utility::decodeJson($body);
													})
													->send()
													->body;
			$this->log[ count($this->log) - 1 ]["response"] = $response;
			if ($response["success"] != true) {
				throw new \Exception("get {$uri} unsuccessful" . \metaclassing\Utility::encodeJson($response) );
			}
			foreach($response["result"] as $result) {
				array_push($results, $result);
			}
			// Now that we KNOW the pagecount, update that and increment our page counter
			$pagecount = $response["result_info"]["total_pages"];
			// And get the next page ready for the next request
			$page++;
		}
		return $results;
	}

	// Internal function to post json back to the API
	protected function httpPostJson($uri, $body)
	{
		$this->log[] = [
						"request" => [
									"method" => "POST",
									"uri" => $uri,
									"body" => $body,
									],
						];
		$response = \Httpful\Request::post($uri)
												->addHeader('X-Auth-Email', $this->email)
												->addHeader('X-Auth-Key', $this->apikey)
												->sendsAndExpects(\Httpful\Mime::JSON)
												->parseWith(function ($body) {
													return \metaclassing\Utility::decodeJson($body);
												})
												->body($body)
												->send()
												->body;
		$this->log[ count($this->log) - 1 ]["response"] = $response;
		if ($response["success"] != true) {
			throw new \Exception("post to {$uri} unsuccessful");
		}
		return $response["result"];
	}

	// Internal function to delete records in the API
	protected function httpDelete($uri)
	{
		$this->log[] = [
						"request" => [
									"method" => "DELETE",
									"uri" => $uri,
									],
						];
		$response = \Httpful\Request::delete($uri)
												->addHeader('X-Auth-Email', $this->email)
												->addHeader('X-Auth-Key', $this->apikey)
												->expects(\Httpful\Mime::JSON)
												->parseWith(function ($body) {
													return \metaclassing\Utility::decodeJson($body);
												})
												->send()
												->body;
		$this->log[ count($this->log) - 1 ]["response"] = $response;
		if ($response["success"] != true) {
			throw new \Exception("delete to {$uri} unsuccessful");
		}
		return $response["result"];
	}

	// Public handler for MOST zone types we would want to add
	public function addZoneRecord($zone, $type, $name, $content, $ttl = 120)
	{
		// List of supported record types to add
		$types = ["A", "AAAA", "TXT", "CNAME"];
		if (!in_array($type, $types)) { throw new \Exception("record type {$type} not in list of supported types: " . implode(",", $types)); }
		// Make sure we know what zone to target
		if (!isset($this->zones[$zone])) { throw new \Exception("unknown zone {$zone} did you call getZones()?"); }
		$zoneid = $this->zones[$zone]["id"];
		if (!$zoneid) { throw new \Exception("could not determine zone id for {$zone}"); }
		// build our postable body
		$request = [
					"type" => $type,
					"name" => $name,
					"content" => $content,
					"ttl" => $ttl,
				];
		$uri = $this->baseuri . '/zones/' . $zoneid . '/dns_records';
		$result = $this->httpPostJson($uri, $request);
		// Save the resulting new zone into our cache of zones
		$this->zonerecords[$zone][] = $result;
		return $result;
	}

	// Semi public function to delete zone records by ID because I dont have a strong enough selector... you can have 3 A records that are identical other than content...
	public function delZoneRecord($zone, $recordid)
	{
		if (!$recordid) { throw new \Exception("missing record id"); }
		// Make sure we know what zone to target
		if (!isset($this->zones[$zone])) { throw new \Exception("unknown zone {$zone} did you call getZones()?"); }
		$zoneid = $this->zones[$zone]["id"];
		if (!$zoneid) { throw new \Exception("could not determine zone id for {$zone}"); }
		$uri = $this->baseuri . '/zones/' . $zoneid . '/dns_records/' . $recordid;
		$result = $this->httpDelete($uri);
		return $result;
	}

	// Internal function to fetch all zones from the API and map into a useful data structure
	protected function fetchZones()
	{
		$uri = $this->baseuri . '/zones';
		$results = $this->httpGet($uri);
		foreach($results as $zone) {
			// TODO add some zone parsing error handling
			$this->zones[$zone["name"]] = $zone;
		}
	}

	// User facing function to retrieve and cache zone information
	public function getZones()
	{
		if (!count($this->zones)) { $this->fetchZones(); }
		return $this->zones;
	}

	// User facing function to just spit back the names of all known zones
	public function listZones()
	{
		return array_keys($this->zones);
	}

	// Internal function to get all the records for one zone and cache the data
	protected function fetchRecords($zoneid)
	{
		if (!$zoneid) { throw new \Exception("fetchRecords called with blank zone ID"); }
		$uri = $this->baseuri . '/zones/' . $zoneid . '/dns_records';
		$result = $this->httpGet($uri);
		return $result;
	}

	// User facing function to return possibly cached records for a zone by name
	public function getRecords($zone)
	{
		if (!isset($this->zones[$zone])) { throw new \Exception("Unknown zone {$zone} requested, did you call getZones() first?"); }
		if (!isset($this->zonerecords[$zone])) {
			$this->zonerecords[$zone] = $this->fetchRecords($this->zones[$zone]["id"]);
		}
		return $this->zonerecords[$zone];
	}

}
