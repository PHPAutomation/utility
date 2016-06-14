<?php
/**
 * lib/VerisignDNSClient.php.
 *
 * This class is a wrapper for SOAP requests to the Verisign MDNS API
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

class VerisignDNSClient
{
	private $wsdl = 'https://api.verisigndns.com/dnsa-ws/V2.0/dnsaapi?wsdl';
    private $wxsd = 'https://api.verisigndns.com/dnsa-ws/V2.0/dnsaapi?xsd=3';
	private $soap = null;

    private $log = [];

    private $zones = [];
    private $zonerecords = [];

    // User facing function to initialize the library
    public function __construct($username, $password)
    {
        if (!$username) {
            throw new \Exception('Missing api username');
        }
        if (!$password) {
            throw new \Exception('Missing api password');
        }

		ini_set("soap.wsdl_cache_enabled", "0");

		// Create our SOAP client to make calls
		$this->soap = new \SoapClient($this->wsdl,
									[
										"soap_version" => SOAP_1_2,
										"style" => SOAP_DOCUMENT,
										"encoding" => SOAP_LITERAL,
										"trace" => 1
									]
								);
		// Manual authentication header for requests, custom vendor namespace attributes
		$header_part = <<<END
<ns2:authInfo xmlns="urn:com:verisign:dnsa:messaging:schema:1" xmlns:ns2="urn:com:verisign:dnsa:auth:schema:1" xmlns:ns3="urn:com:verisign:dnsa:api:schema:1">
    <ns2:userToken>
        <ns2:userName>{$username}</ns2:userName>
        <ns2:password>{$password}</ns2:password>
    </ns2:userToken>
</ns2:authInfo>
END;
		$soap_var_header = new \SoapVar($header_part,
										XSD_ANYXML,
										null,
										null,
										null
									);
		$soap_header = new \SOAPHeader(	$this->wxsd,
										"authInfo",
										$soap_var_header
									);
		$this->soap->__setSoapHeaders($soap_header);

        // Try to get our list of domains automatically
        $this->getZones();
    }

    // Return our log messages of requests and responses for debugging
    public function logs()
    {
        return $this->log;
    }

    // Internal function for executing and logging soap requests
    protected function soapCall($action, $parameters = null)
    {
		// deep black magic
		$response = $this->soap->$action($parameters);
		$response = \metaclassing\Utility::encodeJson($response);
		$response = \metaclassing\Utility::decodeJson($response);
		$this->log[] = [
						'request' => [
									'action' => $action,
									'parameters' => $parameters,
									//'xml' => \metaclassing\Utility::xmlPrettyPrint($this->soap->__getLastRequest()),
									],
						'response' => [
									'response' => $response,
									//'xml' => \metaclassing\Utility::xmlPrettyPrint( $this->soap->__getLastResponse() ),
									],
						];
        return $response;
    }

	// Internal function for getting PAGED responses
	protected function pagedSoapCall($action, $parameters = [], $resultkey)
	{
		$pagesize = 100;
		$pagenumber = 0;
		/* example list call that has paged information
			<urn2:getZoneList>
				<!--Optional:-->
				<urn2:listPagingInfo>
					<urn2:pageNumber>1</urn2:pageNumber>
					<urn2:pageSize>80</urn2:pageSize>
				</urn2:listPagingInfo>
			</urn2:getZoneList>
		*/
		$results = [];
		do {
			$pagenumber++;
			$parameters["listPagingInfo"] = [
											"pageSize" => $pagesize,
											"pageNumber" => $pagenumber,
											];
			$response = $this->soapCall($action, $parameters);
			// Make sure our soap call was successful
			if(!$response["callSuccess"]) {
				throw new \Exception("SOAP callSuccess false");
			}
			// Save the results field in the response
			$results = array_merge($results, $response[$resultkey]);
		} while (count($results) < $response["totalCount"]);
		return $results;
	}

    // Internal function to fetch all zones from the API and map into a useful data structure
    protected function fetchZones()
    {
		$results = $this->pagedSoapCall('getZoneList', [], 'zoneInfo');
        foreach ($results as $zone) {
            $this->zones[$zone['domainName']] = $zone;
        }
    }

    // User facing function to retrieve and cache zone information
    public function getZones()
    {
        if (!count($this->zones)) {
            $this->fetchZones();
        }

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
		/* Sample get zone info call
			<urn2:getZoneInfo_V2>
				<!--You may enter ANY elements at this point-->
				<urn2:domainName>test.com</urn2:domainName>
			</urn2:getZoneInfo_V2>
		*/
		$response = $this->soapCall('getZoneInfo_V2', ['domainName' => $zone]);
		if(!$response["callSuccess"]) {
			throw new \Exception("SOAP callSuccess false");
		}
		$this->zones[$zone] = $response["primaryZoneInfo"];
		return $response["primaryZoneInfo"]["resourceRecord"];
    }

    // User facing function to return possibly cached records for a zone by name
    public function getRecords($zone)
    {
        if (!isset($this->zones[$zone])) {
            throw new \Exception("Unknown zone {$zone} requested, did you call getZones() first?");
        }
        if (!isset($this->zonerecords[$zone])) {
            $this->zonerecords[$zone] = $this->fetchRecords($zone);
        }

        return $this->zonerecords[$zone];
    }

    // Public handler for MOST zone types we would want to add
    public function addZoneRecord($zone, $type, $name, $content, $ttl = 120)
    {
        // List of supported record types to add
        $types = ['A', 'AAAA', 'TXT', 'CNAME'];
        if (!in_array($type, $types)) {
            throw new \Exception("record type {$type} not in list of supported types: " . implode(',', $types) );
        }
        // Make sure we know what zone to target
        if (!isset($this->zones[$zone])) {
            throw new \Exception("unknown zone {$zone} did you call getZones()?");
        }
		/*
			<urn2:createResourceRecords>
				<urn2:domainName>test.com</urn2:domainName>
				<urn2:resourceRecord allowanyIP="false">
					<urn2:resourceRecordId>?</urn2:resourceRecordId>
					<urn2:owner>_acme-challenge.three.test.com.</urn2:owner>
					<urn2:type>TXT</urn2:type>
					<urn2:ttl>120</urn2:ttl>
					<urn2:rData>i9x9C8DlEZmUuqaa90dchYN4C23zso</urn2:rData>
				</urn2:resourceRecord>
				<urn2:comments>LetsEncrypt AutoEnrollment</urn2:comments>
			</urn2:createResourceRecords>
		*/
		$owner = $name . '.';
		$params = [
					'domainName' => $zone,
					'resourceRecord' => [
											'owner' => $owner,
											'type' => $type,
											'ttl' => $ttl,
											'rData' => $content,
										],
					];
		$response = $this->soapCall('createResourceRecords', $params);
		if(!$response["callSuccess"]) {
			throw new \Exception("SOAP callSuccess false");
		}
        // Update our cache of zone resource records after adding one
        $this->zonerecords[$zone] = $this->fetchRecords($zone);
		/* Sample resource record added to a zone
	        [resourceRecordId] => 21042133
	        [owner] => 044-smtp-in-5.test.com.
	        [type] => MX
	        [ttl] => 14400
	        [rData] => 10 044-smtp-in-1e.test.com.
		*/
		foreach($this->zonerecords[$zone] as $record) {
			if ($record["owner"] == $owner
			&&	$record["type"] == $type
			&&	$record["ttl"] == $ttl
			&&	$record["rData"] = $content ) {
				return $record;
			}
		}
		throw new \Exception("Added record but could not identify new resource record");
    }

    // Semi public function to delete zone records by ID because I dont have a strong enough selector... you can have 3 A records that are identical other than content...
    public function delZoneRecord($zone, $recordid)
    {
        if (!$recordid) {
            throw new \Exception('missing record id');
        }
        // Make sure we know what zone to target
        if (!isset($this->zones[$zone])) {
            throw new \Exception("unknown zone {$zone} did you call getZones()?");
        }
		/*
			<urn2:deleteResourceRecords>
				<urn2:domainName>test.com</urn2:domainName>
				<urn2:resourceRecordId>40523414</urn2:resourceRecordId>
			</urn2:deleteResourceRecords>
		*/
		$response = $this->soapCall('deleteResourceRecords', ["domainName" => $zone, "resourceRecordId" => $recordid ]);
		if(!$response["callSuccess"]) {
			throw new \Exception("SOAP callSuccess false");
		}
        return $response;
    }

}
