<?php

namespace Metaclassing\Curler;

class Certbot extends Curler
{
    public $token;
    public $baseurl;
    public $accounttypes = ['acme', 'ca'];
    public $accounts = [];

    public function __construct($baseurl, $clientcert)
    {
        $this->baseurl = $baseurl;

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_SSLCERT, $clientcert);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt($this->curl, CURLOPT_VERBOSE      , true);

        // Get the list of accounts we can access and certs in each
        $this->authenticate();
        $this->listAccounts();
        $this->listCertificates();
    }

    public function getjson($url, $referer = '')
    {
        if ($this->token) {
            $url .= '?token='.$this->token;
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_REFERER, $referer);

        $response = $this->curl_exec();
        if (!\Metaclassing\Utility::isJson($response)) {
            throw new \Exception('Did not get JSON response from web service, got '.$response);
        }

        $response = \Metaclassing\Utility::decodeJson($response);
        if (isset($response['status_code']) && $response['status_code'] != 200) {
            throw new \Exception('Did not get a 200 response from web service for last call,'.
                                 ' url was '.$url.' response was '.\Metaclassing\Utility::dumperToString($response));
        }

        return $response;
    }

    public function authenticate()
    {
        // get our json web token for authentication
        $url = $this->baseurl.'authenticate';
        $result = $this->getjson($url);
        if (!$result['token']) {
            throw new \Exception('Could not authenticate to web service and get JSON web token');
        }
        $this->token = $result['token'];
    }

    public function listAccounts()
    {
        foreach ($this->accounttypes as $type) {
            $url = $this->baseurl.$type.'/account';
            $response = $this->getjson($url);
            $accounts = $response['accounts'];
            foreach ($accounts as $account) {
                $this->accounts[$type][$account['id']] = $account;
            }
        }

        return $this->accounts;
    }

    public function listCertificates()
    {
        foreach ($this->accounts as $type => $accounts) {
            foreach ($accounts as $index => $account) {
                $url = $this->baseurl.$type.'/accounts/'.$account['id'].'/certificates';
                $response = $this->getjson($url);
                $certificates = $response['certificates'];
                foreach ($certificates as $certificate) {
                    $this->accounts[$type][$index]['certificates'][$certificate['id']] = $certificate;
                }
            }
        }

        return $this->accounts;
    }

    // Really only necessary until I implement a direct API route to search for certificates...
    public function findCertificate($name)
    {
        foreach ($this->accounttypes as $type) {
            foreach ($this->accounts[$type] as $account) {
                foreach ($account['certificates'] as $certificate) {
                    if ($certificate['name'] == $name) {
                        $certificate['accounttype'] = $type;
                        $certificate['pemurl'] = $this->baseurl.$type.'/accounts/'.$certificate['account_id'].'/certificates/'.$certificate['id'].'/pem';

                        return $certificate;
                    }
                }
            }
        }
    }

    public function getCertificate($name)
    {
        $certificate = $this->findCertificate($name);
        if (!$certificate) {
            throw new \Exception('Could not find a certificate with the name of '.$name);
        }

        return $this->get($certificate['pemurl'].'?token='.$this->token);
    }
}
