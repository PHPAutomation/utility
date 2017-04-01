<?php

namespace Metaclassing\Curler;

class Netman extends Curler
{
    public $baseurl;

    public function __construct($baseurl, $clientcert, $cookiePath = '/tmp/curler.cookiejar')
    {
        $this->baseurl = $baseurl;
		$this->cookiePath = $cookiePath;

		// setup curl handle
        $this->curl = curl_init();
		// set curl cookie options
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookiePath);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookiePath);

        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_SSLCERT, $clientcert);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        // set curl user agent spoof
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7');
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_CERTINFO, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
//      curl_setopt($this->curl, CURLOPT_VERBOSE      , true);
    }

    public function getjson($url, $referer = '')
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_REFERER, $referer);

        $response = $this->curl_exec();
        if (!\Metaclassing\Utility::isJson($response)) {
            throw new \Exception('Did not get JSON response from web service url '.$url.', got '.$response);
        }
        $response = \Metaclassing\Utility::decodeJson($response);
		//\Metaclassing\Utility::dumper($response);
        return $response;
    }

    public function authenticate()
    {
        // eventually I might support auth with username and password, but NOT TODAY SATAN!
		$response = $this->get($this->baseurl);
    }

	public function search($query)
	{
		// Translate the search array into JSON
		$json = \Metaclassing\Utility::encodeJson($query);
		// Post the JSON
		$response = $this->post($this->baseurl.'/information/api/search/', $this->baseurl, $json);
		// return an assoc array with results
		return \Metaclassing\Utility::decodeJson($response);
	}

	public function retrieve($id)
	{
		$response = $this->getjson($this->baseurl.'/information/api/retrieve/?id='.$id);
		return $response;
	}
}
