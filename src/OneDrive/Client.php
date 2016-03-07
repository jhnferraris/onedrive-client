<?php
namespace Kunnu\OneDrive;

use GuzzleHttp\Psr7\Stream;
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
    private $contentType = "application/json";

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
     * Available Conflict Behaviours
     * @var array
     */
    private $defaultconflictBehaviors = ["rename", "fail", "replace"];


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
    public function getBasePath()
    {
        return self::BASE_PATH;
    }

    /**
     * Set the Default Options
     * @param array \Kunnu\OneDrive\Client
     * @return array \Kunnu\OneDrive\Client
     */
    public function setDefaultOptions(array $options = array())
    {
        $this->defaultOptions = $options;
        return $this;
    }

    /**
     * Get the Default Options
     * @return string The Default Options
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * Get the Access Token
     * @return string Access Token
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * Set the Access Token
     * @param string $access_token Access Token
     * @return array \Kunnu\OneDrive\Client
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * Set the Content Type
     * @param string $type 'application/json', 'application/xml'
     * @return array \Kunnu\OneDrive\Client
     */
    public function setContentType($type)
    {
        $this->contentType = $type;
        return $this;
    }

    /**
     * Get the Content Type
     * @return string Content Type
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Get the Authorization Header with the Access Token
     * @return array Authorization Header
     */
    protected function getAuthHeader()
    {
        return ['Authorization' => "bearer " . $this->getAccessToken()];
    }

    /**
     * Get the Content Type Header
     * @return array Content Type Header
     */
    public function getContentTypeHeader()
    {
        return ['Content-Type' => $this->getContentType()];
    }

    /**
     * Get Default Headers
     * @return array Default Headers
     */
    protected function getDefaultHeaders()
    {
        return array_merge($this->getAuthHeader(), $this->getContentTypeHeader());
    }

    /**
     * Build Headers for the API Request
     * @param  array  $headers Additional Headers
     * @return array          Merged additonal and default headers
     */
    protected function buildHeaders($headers = [])
    {
        //Override the Default Response Type, if provided
        if (array_key_exists("Content-Type", $headers)) {
            $this->setContentType($headers['Content-Type']);
        }

        return array_merge($headers, $this->getDefaultHeaders());
    }

    /**
     * Build URL for the Request
     * @param  string $path Relative API path or endpoint
     * @return string       The Full URL
     */
    protected function buildUrl($path = "")
    {
        $path = urlencode($path);
        return $this->getBasePath() . $path;
    }

    /**
     * Build Options
     * @param  array $options Additional Options
     * @return array          Merged Additional Options
     */
    protected function buildOptions($options)
    {
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
    protected function makeRequest($method, $uri, $options = [], $body = null, $headers = [])
    {
        //Build headers
        $headers = $this->buildHeaders($headers);

        //Create a new Request Object
        $request = new Request($method, $uri, $headers, $body);

        //Build Options
        $options = $this->buildOptions($options);

        try {
            //Send the Request
            return $this->guzzle->send($request, $options);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
        }
    }

    /**
     * Decode the Response
     * @param  string|\Psr\Http\Message\ResponseInterface $response Response object or string to decode
     * @return string
     */
    protected function decodeResponse($response)
    {
        $body = $response;
        if ($response instanceof ResponseInterface) {
            $body = $response->getBody();
        }

        return json_decode((string) $body);
    }

    /**
     * Get Drive Path
     * @param  string $drive_id ID of the Drive
     * @return string           Drive Path
     */
    public function getDrivePath($drive_id = null)
    {
        $drive_id = is_null($drive_id) ? $this->getSelectedDrive() : $drive_id;
        return "/drives/{$drive_id}";
    }

    /**
     * Select a Drive to perform operations on
     * @param  string $drive Drive ID
     * @return \Kunnu\OneDrive\Client
     */
    public function selectDrive($drive_id)
    {
        if (!empty($drive_id)) {
            $this->selectedDrive = $drive_id;
        }
        return $this;
    }

    /**
     * Get the Seleted Drive
     * @return string Selected Drive ID
     */
    public function getSelectedDrive()
    {
        return $this->selectedDrive;
    }

    /**
     * List Drives
     * @param array $params Additional Query Parameters
     * @return Object
     */
    public function listDrives($params = array())
    {
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
    public function getDrive($drive_id = null, $params = array())
    {
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
    public function getDefaultDrive($params = array())
    {
        return $this->getDrive(null, $params);
    }

    /**
     * Get Drive Root
     * @param  null|string $drive_id ID of the Drive to fetch. Null for Default Drive.
     * @param array $params Additional Query Parameters
     * @return Object
     */
    public function getDriveRoot($drive_id = null, $params = array())
    {
        $path = $this->getDrivePath($drive_id);
        $path = "{$path}/root";
        $uri = $this->buildUrl($path);

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

    /**
     * List Children of the specified Item ID
     * @param  null|string $item_id ID of the Item to list children of.
     * @param array $params Additional Query Parameters
     * @return Object
     */
    public function listChildren($item_id = null, $params = array())
    {
        $path = is_null($item_id) ? "/root" : "/items/{$item_id}";
        $path = $this->getDrivePath() . "{$path}/children";
        $uri = $this->buildUrl($path);

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

    /**
     * Get an Item by ID
     * @param  string  $item_id      ID of the Item
     * @param  boolean $withChildren Get the Item along with it's children
     * @param  array   $params       Additional Query Params
     * @return Object
     */
    public function getItem($item_id, $withChildren = false, $params = array())
    {
        if ($item_id == "") {
            echo "A valid Item ID is required!";
            return false;
        }
        $path = $this->getDrivePath() . "/items/{$item_id}";
        $uri = $this->buildUrl($path);

        if ($withChildren) {
            //User has passed an expand param
            if (array_key_exists('expand', $params))  {
                //Expand doesn't contain children param
                if ((!strpos($params['expand'], "children"))){
                    //Append children param into expand
                    $params['expand'] = "{$params['expand']},children";
                }
            }else{
                //No expand param given
                $params['expand'] = "children";
            }
        }

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

    /**
     * Search
     * @param string Search Query
     * @param  null|string $item_id ID of the Item(Folder) to search inside of.
     * @param array $params Additional Query Parameters
     * @return Object
     */
    public function search($query, $item_id = null, $params = array())
    {
        $path = is_null($item_id) ? "/root" : "/items/{$item_id}";
        $path = $this->getDrivePath() . "{$path}/view.search";
        $uri = $this->buildUrl($path);

        $params['q'] = stripslashes(trim($query));

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

    /**
     * Get Thumbnails of an Item
     * @param  string  $item_id      ID of the Item
     * @param  array   $params       Additional Query Params
     * @return Object
     */
    public function getItemThumbnails($item_id, $params = array())
    {
        if ($item_id == "") {
            echo "A valid Item ID is required!";
            return false;
        }
        $path = $this->getDrivePath() . "/items/{$item_id}/thumbnails";
        $uri = $this->buildUrl($path);

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

    /**
     * Get a Single Thumbnail of an Item
     * @param  string  $item_id      ID of the Item
     * @param  string  $thumbnail_id ID of the thumbnail
     * @param  array   $params       Additional Query Params
     * @return Object
     */
    public function getItemThumbnail($item_id, $thumbnail_id = "0", $params = array())
    {
        if ($item_id == "" || $thumbnail_id == "") {
            echo "A valid Item ID and Thumbnail ID are required!";
            return false;
        }
        $path = $this->getDrivePath() . "/items/{$item_id}/thumbnails/{$thumbnail_id}";
        $uri = $this->buildUrl($path);

        $response = $this->makeRequest("GET", $uri, ["query" => $params]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

    /**
     * Download an Item
     * @param  string $item_id ID of the Item to download
     * @param  array  $params  Additional Query Params
     * @return string          Downloaded content
     */
    public function downloadItem($item_id, $params = array())
    {
        $item = $this->getItem($item_id);
        $downloadUrl = $item->{'@content.downloadUrl'};

        $file = fopen($downloadUrl, 'r');
        $stream = new Stream($file);

        $downloadedFile = fopen('php://temp', 'w+');

        if ($downloadedFile === false) {
            throw new \Exception('Error when saving the downloaded file');
        }

        while (!$stream->eof()) {
            $writeResult = fwrite($downloadedFile, $stream->read(8000));
            if ($writeResult === false) {
                throw new \Exception('Error when saving the downloaded file');
            }
        }

        $stream->close();
        rewind($downloadedFile);

        return $downloadedFile;
    }

    /**
     * Validate whether a given behavior is a valid Conflict Behavior
     * @param  string $conflictBehavior Behavior to validate
     * @return bool
     */
    protected function validateConflictBehavior($conflictBehavior)
    {
        $exists = in_array($conflictBehavior, $this->defaultconflictBehaviors);

        if (!$exists) {
            return false;
        }

        return true;
    }


    /**
     * Create a Folder Item
     * @param  string  $title            Name of the Folder
     * @param  string  $parent_id        ID of the Parent Folder. Empty for drive root.
     * @param  string  $behavior         Conflict Behavior
     * @param  array   $params           Additional Query Parameters
     * @return string                    Created Folder Item
     */
    public function createFolder($title, $parent_id = null, $behavior = "rename", $params = array())
    {

        //Drive Path
        $path = $this->getDrivePath();

        //If the parent id is not provided, use the drive root
        if (is_null($parent_id)) {
            $path .= "/root/children";
        } else {
            $path .= "/items/{$parent_id}/children";
        }

        $uri = $this->buildUrl($path);

        //Validate Conflict Behavior
        if (!$this->validateConflictBehavior($behavior)) {
            echo "Please enter a valid conflict behavior";
            exit();
        }

        $body = ["name" => $title, "@name.conflictBehavior" => $behavior, "folder" => new \StdClass()];

        //Json Encode Body
        $body = json_encode($body);

        $response = $this->makeRequest("POST", $uri, ["query" => $params, 'body' => $body]);
        $responseContent = $this->decodeResponse($response);

        return $responseContent;
    }

}