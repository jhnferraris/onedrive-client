<?php
namespace Kunnu\OneDrive;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as Guzzle;

class Client
{

    /**
     * OneDrive API Root URL
     */
    CONST BASE_PATH = "https://api.onedrive.com/v1.0";

    /**
     * The Guzzle Client
     * @var GuzzleHttp\Client
     *
     */
    private $guzzle;

    /**
     * OAuth2 Access Token
     * @var String
     */
    private $access_token;

    /**
     * Response Type for API Response
     * @var string
     */
    private $responseType = "application/json";


    /**
     * The Constructor
     * @param string $access_token The Access Token
     * @param Guzzle $guzzle       The Guzzle Client Object
     */
    public function __construct($access_token, Guzzle $guzzle)
    {
        //Set the access token
        $this->setAccessToken($access_token);
        //Set the Guzzle Client
        $this->guzzle = $guzzle;
    }

    /**
     * Get the API Base Path
     * @return string API Base Path
     */
    public function getBasePath(){
        return self::BASE_PATH;
    }

    /**
     * Get the Access Token
     * @return string Access Token
     */
    public function getAccessToken(){
        return $this->access_token;
    }

    /**
     * Set the Access Token
     * @param string $access_token Access Token
     */
    public function setAccessToken($access_token){
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * Set the Response Type
     * @param string $type 'application/json', 'application/xml'
     */
    public function setResponseType($type){
        $this->responseType = $type;
        return $this;
    }

    /**
     * Get the Response Type
     * @return string Response Type
     */
    public function getResponseType(){
        return $this->responseType;
    }

    /**
     * Get the Authorization Header with the Access Token
     * @return array Authorization Header
     */
    protected function getAuthHeader(){
        return ['Authorization' => "bearer " . $this->getAccessToken()];
    }

    /**
     * Get the Response Type Header
     * @return array Response Type Header
     */
    public function getResponseTypeHeader(){
        return ['Content-Type' => $this->getResponseType()];
    }

    /**
     * Get Default Headers
     * @return array Default Headers
     */
    protected function getDefaultHeaders(){
        return array_merge($this->getAuthHeader(), $this->getResponseTypeHeader());
    }

    /**
     * Build Headers for the API Request
     * @param  array  $headers Additional Headers
     * @return array          Merged additonal and default headers
     */
    protected function buildHeaders($headers = []){
        //Override the Default Response Type, if provided
        if(array_key_exists("Content-Type", $headers)){
            $this->setResponseType($headers['Content-Type']);
        }

        return array_merge($headers, $this->getDefaultHeaders());
    }

    /**
     * Make Request to the API using Guzzle
     * @param  string $method  Method Type [GET|POST|PUT|DELETE]
     * @param  null|string|UriInterface $uri    URI for the Request
     * @param  string|resource|StreamInterface $body    Message Body
     * @param  array  $headers Headers for the message
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function makeRequest($method, $uri, $body = null, $headers = []){
        //Build headers
        $headers = $this->buildHeaders($headers);

        //Create a new Request Object
        $request = new Request($method, $uri, $headers, $body);

        try{
            //Send the Request
            return $this->guzzle->send($request);
        }catch(\Exception $e){
            echo $e->getMessage();
            exit();
        }
    }

    /**
     * Get Drive MetaData
     * @param  null|string $drive_id ID of the Drive to fetch. Null for Default Drive.
     * @return Object
     */
    public function getDrive($drive_id = null){
        $path = is_null($drive_id) ? "/drive" : "/drives/{$drive_id}";
        $uri = $this->getBasePath() . urlencode($path);

        $response = $this->makeRequest("GET", $uri);
        $responseContent = json_decode((string) $response->getBody());

        return $responseContent;
    }

    /**
     * Get the Default Drive
     * @return Object
     */
    public function getDefaultDrive(){
        return $this->getDrive();
    }

}