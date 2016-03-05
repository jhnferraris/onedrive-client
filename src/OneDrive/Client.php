<?php
namespace Kunnu\OneDrive;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as Guzzle;
use Psr\Http\Message\ResponseInterface;

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
     * Default options to send along a Request
     * @var array
     */
    private $defaultOptions = [];


    /**
     * Current Selected Drive
     * @var default
     */
    private $selectedDrive = "me";


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
     * @return array \Kunnu\OneDrive\Client
     */
    public function setAccessToken($access_token){
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * Set the Response Type
     * @param string $type 'application/json', 'application/xml'
     * @return array \Kunnu\OneDrive\Client
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
        return $this->defaultOptions;
    }

    /**
     * Set the Default Options
     * @param array \Kunnu\OneDrive\Client
     * @return array \Kunnu\OneDrive\Client
     */
    public function setDefaultOptions($type){
        $this->defaultOptions = $type;
        return $this;
    }

    /**
     * Get the Default Options
     * @return string The Default Options
     */
    public function getDefaultOptions(){
        return $this->defaultOptions;
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
     * Build URL for the Request
     * @param  string $path Relative API path or endpoint
     * @return string       The Full URL
     */
    protected function buildUrl($path = ""){
        $path = urlencode($path);
        return $this->getBasePath() . $path;
    }

    /**
     * Build Options
     * @param  array $options Additional Options
     * @return array          Merged Additional Options
     */
    protected function buildOptions($options){
        return array_merge($options, $this->getDefaultOptions());
    }

    /**
     * Make Request to the API using Guzzle
     * @param  string $method  Method Type [GET|POST|PUT|DELETE]
     * @param  null|string|UriInterface $uri    URI for the Request
     * @param array $params Options to send along the request
     * @param  string|resource|StreamInterface $body    Message Body
     * @param  array  $headers Headers for the message
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function makeRequest($method, $uri, $options = [], $body = null, $headers = []){
        //Build headers
        $headers = $this->buildHeaders($headers);

        //Create a new Request Object
        $request = new Request($method, $uri, $headers, $body);

        //Build Options
        $options = $this->buildOptions($options);

        try{
            //Send the Request
            return $this->guzzle->send($request, $options);
        }catch(\Exception $e){
            echo $e->getMessage();
            exit();
        }
    }

    /**
     * Decode the Response
     * @param  string|\Psr\Http\Message\ResponseInterface $response Response object or string to decode
     * @return string
     */
    protected function decodeResponse($response){
        $body = $response;
        if($response instanceof ResponseInterface){
            $body = $response->getBody();
        }

        return json_decode((string) $body);
    }

    /**
     * Get Drive Path
     * @param  string $drive_id ID of the Drive
     * @return string           Drive Path
     */
    public function getDrivePath($drive_id = null){
        $drive_id = is_null($drive_id) ? $this->getSelectedDrive() : $drive_id;
        return "/drives/{$drive_id}";
    }

    /**
     * Select a Drive to perform operations on
     * @param  string $drive Drive ID
     * @return \Kunnu\OneDrive\Client
     */
    public function selectDrive($drive_id){
        if(!empty($drive_id)){
            $this->selectedDrive = $drive_id;
        }
        return $this;
    }

    /**
     * Get the Seleted Drive
     * @return string Selected Drive ID
     */
    public function getSelectedDrive(){
        return $this->selectedDrive;
    }

    /**
     * List Drives
     * @param array $params Additional Query Parameters
     * @return Object
     */
    public function listDrives($params = array()){
        $uri = $this->buildUrl("/drives");

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

    /**
     * Get Drive MetaData
     * @param  null|string $drive_id ID of the Drive to fetch. Null for Default Drive.
     * @param array $params Additional Query Parameters
     * @return Object
     */
    public function getDrive($drive_id = null, $params = array()){
        $path = $this->getDrivePath($drive_id);
        $uri = $this->buildUrl($path);

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

    /**
     * Get the Default Drive
     * @param array $params Additional Query Parameters
     * @return Object
     */
    public function getDefaultDrive($params = array()){
        return $this->getDrive(null, $params);
    }

    /**
     * Get Drive Root
     * @param  null|string $drive_id ID of the Drive to fetch. Null for Default Drive.
     * @param array $params Additional Query Parameters
     * @return Object
     */
    public function getDriveRoot($drive_id = null, $params = array()){
        $path = $this->getDrivePath($drive_id);
        $path = "{$path}/root";
        $uri = $this->buildUrl($path);

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

}