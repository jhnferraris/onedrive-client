OneDrive PHP Client
===================
An easy to use PHP Client for the [OneDrive API](https://dev.onedrive.com/).

<img src="https://cloud.githubusercontent.com/assets/893057/13648897/12ebf3de-e661-11e5-84e3-a86a829eba32.png">


## Installation
The OneDrive PHP Client can be installed via **Composer**.
```sh
$ php composer require kunalvarma05/onedrive-client
```

## Getting Started
- To get started quickly, get a [Sample Access Token](https://dev.onedrive.com/auth/msa_oauth.htm).
OR
- Use [Microsft OAuth Package](https://github.com/stevenmaguire/oauth2-microsoft) to obtain a Access Token.

## Initializing
```php 
use Kunnu\OneDrive\Client;
use GuzzleHttp\Client as Guzzle;

//Create a Guzzle Client
$guzzle = new Guzzle;

//Access Token
$accessToken = "abcd1234....";

//Initialize the OneDrive Client
$client = new Client($accessToken, $guzzle);
```

## Usage

### Working with Drives

#### List Drives
List the available drives
```php
$client->listDrives();
```

#### Select a Drive
Select the drive to work on. However, if you do not select a drive, the current selected drive defaults to your personal drive.
```php
$client->selectDrive($drive_id);
```

#### Get Selected Drive
If a drive is not selected using the `selectDrive()` method before calling this method, the selected drive defaults to your personal drive.
```php
$drive = $client->getDrive();
```

#### Get Default Drive
Get the Default OneDrive Drive (Your personal drive).
```php
$drive = $client->getDefaultDrive();
```

#### Switch Drives
```php
$defaultDrive = $client->getDrive();
//Switch Drive
$client->selectDrive("1234")
$drive2 = $client->getDrive();
```

#### Select Drive Root
Get the root folder of a drive
```php
$driveRoot = $client->getDriveRoot();
//OR
$driveRoot = $client->getDriveRoot($drive_id);
```

------------------

### Get Item
Fetch an item
```php
$item = $client->getItem($itemID);
```

#### Fetch an item along with it's children
```php
$withChildren = true;
$item = $client->getItem($itemID, $withChildren);
```

#### Fetch an item only with the specified properties
See: [OneDrive Selecting Properties](https://dev.onedrive.com/odata/optional-query-parameters.htm#selecting-properties)
```php
$item = $client->getItem("7D780E8525603004!1324", true, ["select" => "id,name,size"]);
```

#### Fetch an item and expand a children collection
See: [OneDrive Expanding Collections](https://dev.onedrive.com/odata/optional-query-parameters.htm#expanding-collections)
```php
$item = $client->getItem($itemID, $withChildren, ["expand" => "children(select=id,name,size)"]);
```

------------------

### Fetch Children of a folder
```php
$children = $client->listChildren($itemID);
```

#### Fetch Children with only specified properties
```php
$children = $client->listChildren($itemID, ['select' => "id,name"]);
```

#### Fetch Children with expanded children collection
```php
$children = $client->listChildren($itemID, ['expand' => "thumbnails(select=medium)"]);
```

------------------

### Search
```php
$query = "search query";
$search = $client->selectDrive($drive->id)->search($query);
```

#### Search inside a folder
```php
$query = "search query";
$parentFolderID = "1234";
$search = $client->selectDrive($drive->id)->search($query, $parentFolderID);
```

------------------

### Thumbnails
#### Get an Item's thumbnails
```php
$thumbnails = $client->getItemThumbnails($item->id);
```

#### Get an Item's thumbnails with specified fields and/or size
```php
$thumbnails = $client->getItemThumbnails($item->id, ['select' => "id,large"]);
```

#### Get a Single thumbnail
```php
$thumbnailID = "0";
$thumbnail = $client->getItemThumbnail($item->id, $thumbnailID);
```

------------------

### Download Item and Save it to a file
```php
$downloadedContent = $client->downloadItem($item->id);
file_put_contents("/path/to/file", $downloadedContent);
```

------------------

### Create Folder
Create a folder
```php
$folder = $client->createFolder("Test Folder");
```

#### Create Folder inside another folder
```php
$parentFolderID = "1234";
$folder = $client->createFolder("Test Folder", $parentFolderID);
```

------------------

### Create File
Create a file with contents
```php
$contents = "Hello World";
$item = $client->createFile("hello.txt", $contents);
```

------------------

### Upload File
```php
$file = "/path/to/file";
$createdFile = $client->uploadFile($file);
```

#### Upload file with a new name
```php
$file = "helloworld.txt";
$createdFile = $client->uploadFile($file, "hello.txt");
```

#### Upload file to a folder
```php
$file = "helloworld.txt";
$createdFile = $client->uploadFile($file, "hello.txt", $folderID);
```

------------------

### Conflict Behavior
The default Conflict Behavior if set to `fail`. Thus, if an item with the same name already exists, OneDrive will return an error.

#### Replace an Item
If Conflict Behavior is set to `replace` and an item with the same name already exists in the destination, the new item will override the existing one. 

```php
$folder = $client->createFolder("Test Folder", null, "replace");
```

#### Auto-Rename

If Conflict Behavior is set to `rename` and an item with the same name already exists in the destination, the new item name will be updated to be unique.OneDrive will append a number to the end of the item name (for files - before the extension).

For example, `hello.txt` would be renamed `hello 1.txt`. If `hello 1.txt` is taken, then the number would be incremented again until a unique filename is discovered.
```php
$file = "helloworld.txt";
$conflictBehavior = "replace";
$createdFile = $client->uploadFile($file, null, null, "rename");
```

------------------

### Update an Item's MetaData
```php
$metadata = array("name" => "new-name.txt");
$update = $client->updateMeta($itemID, $metadata);
```

#### Updating Item's Parent Reference
You can move an item to a new location via the `parentReference` property
```php
$metadata = array("parentReference" => ["id" => $parentID]);
$updatedItem = $client->updateMeta($itemID, $metadata);
```

------------------

### Copy an Item
```php
$itemCopy = $client->copy($itemID, $destinationID);
```

#### Copy an item with a new name
```php
$itemCopy = $client->copy($itemID, $destinationID, "new-name.txt");
```

------------------

### Move an Item
```php
$item = $client->move($itemID, $destinationID);
```

------------------

### Delete an Item
```php
$client->delete($itemID);
```

------------------

### Create a Sharing Link
```php
$type = "view"; //Read-only Permission (Default)
//OR
$type = "edit"; //Read-Write Permission

$client->createShareLink($itemID, $type);
```