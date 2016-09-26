<?php
/**

 *
 * @author David Toledo <dtoledo@solidgear.es>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sergio Bertolin <sbertolin@solidgear.es>
 * @author Sergio Bertolín <sbertolin@solidgear.es>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
use GuzzleHttp\Client as GClient;
use GuzzleHttp\Message\ResponseInterface;
use Sabre\DAV\Client as SClient;

require __DIR__ . '/../../vendor/autoload.php';


trait WebDav {
	use Sharing;

	/** @var string*/
	private $davPath = "remote.php/webdav";
	/** @var ResponseInterface */
	private $response;

	/**
	 * @Given /^using dav path "([^"]*)"$/
	 */
	public function usingDavPath($davPath) {
		$this->davPath = $davPath;
	}

	/**
	 * @return string
	 */
	public function getFilesPath() {
		if ($this->davPath === 'remote.php/dav') {
			$basePath = '/files/' . $this->currentUser . '/';
		} else {
			$basePath = '/';
		}
		return $basePath;
	}

	public function makeDavRequest($user, $method, $path, $headers, $body = null){
		$fullUrl = substr($this->baseUrl, 0, -4) . $this->davPath . "$path";
		$client = new GClient();
		$options = [];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}
		$request = $client->createRequest($method, $fullUrl, $options);
		if (!is_null($headers)){
			foreach ($headers as $key => $value) {
				$request->addHeader($key, $value);
			}
		}

		if (!is_null($body)) {
			$request->setBody($body);
		}


		return $client->send($request);
	}

	/**
	 * @Given /^User "([^"]*)" moved (file|folder|entry) "([^"]*)" to "([^"]*)"$/
	 * @param string $user
	 * @param string $fileSource
	 * @param string $fileDestination
	 */
	public function userMovedFile($user, $entry, $fileSource, $fileDestination){
		$fullUrl = substr($this->baseUrl, 0, -4) . $this->davPath;
		$headers['Destination'] = $fullUrl . $fileDestination;
		$this->response = $this->makeDavRequest($user, "MOVE", $fileSource, $headers);
		PHPUnit_Framework_Assert::assertEquals(201, $this->response->getStatusCode());
	}

	/**
	 * @When /^User "([^"]*)" moves (file|folder|entry) "([^"]*)" to "([^"]*)"$/
	 * @param string $user
	 * @param string $fileSource
	 * @param string $fileDestination
	 */
	public function userMovesFile($user, $entry, $fileSource, $fileDestination){
		$fullUrl = substr($this->baseUrl, 0, -4) . $this->davPath;
		$headers['Destination'] = $fullUrl . $fileDestination;
		$this->response = $this->makeDavRequest($user, "MOVE", $fileSource, $headers);
	}

	/**
	 * @When /^User "([^"]*)" copies file "([^"]*)" to "([^"]*)"$/
	 * @param string $user
	 * @param string $fileSource
	 * @param string $fileDestination
	 */
	public function userCopiesFileTo($user, $fileSource, $fileDestination) {
		$fullUrl = substr($this->baseUrl, 0, -4) . $this->davPath;
		$headers['Destination'] = $fullUrl . $fileDestination;
		try {
			$this->response = $this->makeDavRequest($user, 'COPY', $fileSource, $headers);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx and 5xx responses cause an exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When /^Downloading file "([^"]*)" with range "([^"]*)"$/
	 * @param string $fileSource
	 * @param string $range
	 */
	public function downloadFileWithRange($fileSource, $range){
		$headers['Range'] = $range;
		$this->response = $this->makeDavRequest($this->currentUser, "GET", $fileSource, $headers);
	}

	/**
	 * @When /^Downloading last public shared file with range "([^"]*)"$/
	 * @param string $range
	 */
	public function downloadPublicFileWithRange($range){
		$token = $this->lastShareData->data->token;
		$fullUrl = substr($this->baseUrl, 0, -4) . "public.php/webdav";
		$headers['Range'] = $range;

		$client = new GClient();
		$options = [];
		$options['auth'] = [$token, ""];

		$request = $client->createRequest("GET", $fullUrl, $options);
		$request->addHeader('Range', $range);

		$this->response = $client->send($request);
	}

	/**
	 * @When /^Downloading last public shared file inside a folder "([^"]*)" with range "([^"]*)"$/
	 * @param string $range
	 */
	public function downloadPublicFileInsideAFolderWithRange($path, $range){
		$token = $this->lastShareData->data->token;
		$fullUrl = substr($this->baseUrl, 0, -4) . "public.php/webdav" . "$path";
		$headers['Range'] = $range;

		$client = new GClient();
		$options = [];
		$options['auth'] = [$token, ""];

		$request = $client->createRequest("GET", $fullUrl, $options);
		$request->addHeader('Range', $range);

		$this->response = $client->send($request);
	}

	/**
	 * @Then /^Downloaded content should be "([^"]*)"$/
	 * @param string $content
	 */
	public function downloadedContentShouldBe($content){
		PHPUnit_Framework_Assert::assertEquals($content, (string)$this->response->getBody());
	}

	/**
	 * @Then /^Downloaded content when downloading file "([^"]*)" with range "([^"]*)" should be "([^"]*)"$/
	 * @param string $fileSource
	 * @param string $range
	 * @param string $content
	 */
	public function downloadedContentWhenDownloadindShouldBe($fileSource, $range, $content){
		$this->downloadFileWithRange($fileSource, $range);
		$this->downloadedContentShouldBe($content);
	}

	/**
	 * @When Downloading file :fileName
	 * @param string $fileName
	 */
	public function downloadingFile($fileName) {
		$this->response = $this->makeDavRequest($this->currentUser, 'GET', $fileName, []);
	}

	/**
	 * @Then The following headers should be set
	 * @param \Behat\Gherkin\Node\TableNode $table
	 * @throws \Exception
	 */
	public function theFollowingHeadersShouldBeSet(\Behat\Gherkin\Node\TableNode $table) {
		foreach($table->getTable() as $header) {
			$headerName = $header[0];
			$expectedHeaderValue = $header[1];
			$returnedHeader = $this->response->getHeader($headerName);
			if($returnedHeader !== $expectedHeaderValue) {
				throw new \Exception(
					sprintf(
						"Expected value '%s' for header '%s', got '%s'",
						$expectedHeaderValue,
						$headerName,
						$returnedHeader
					)
				);
			}
		}
	}

	/**
	 * @Then Downloaded content should start with :start
	 * @param int $start
	 * @throws \Exception
	 */
	public function downloadedContentShouldStartWith($start) {
		if(strpos($this->response->getBody()->getContents(), $start) !== 0) {
			throw new \Exception(
				sprintf(
					"Expected '%s', got '%s'",
					$start,
					$this->response->getBody()->getContents()
				)
			);
		}
	}

	/**
	 * @Then /^as "([^"]*)" gets properties of folder "([^"]*)" with$/
	 * @param string $user
	 * @param string $path
	 * @param \Behat\Gherkin\Node\TableNode|null $propertiesTable
	 */
	public function asGetsPropertiesOfFolderWith($user, $path, $propertiesTable) {
		$properties = null;
		if ($propertiesTable instanceof \Behat\Gherkin\Node\TableNode) {
			foreach ($propertiesTable->getRows() as $row) {
				$properties[] = $row[0];
			}
		}
		$this->response = $this->listFolder($user, $path, 0, $properties);
	}

	/**
	 * @Then /^as "([^"]*)" the (file|folder|entry) "([^"]*)" does not exist$/
	 * @param string $user
	 * @param string $path
	 * @param \Behat\Gherkin\Node\TableNode|null $propertiesTable
	 */
	public function asTheFileOrFolderDoesNotExist($user, $entry, $path) {
		$client = $this->getSabreClient($user);
		$response = $client->request('HEAD', $this->makeSabrePath($path));
		if ($response['statusCode'] !== 404) {
			throw new \Exception($entry . ' "' . $path . '" expected to not exist (status code ' . $response['statusCode'] . ', expected 404)');
		}

		return $response;
	}

	/**
	 * @Then /^as "([^"]*)" the (file|folder|entry) "([^"]*)" exists$/
	 * @param string $user
	 * @param string $path
	 * @param \Behat\Gherkin\Node\TableNode|null $propertiesTable
	 */
	public function asTheFileOrFolderExists($user, $entry, $path) {
		$this->response = $this->listFolder($user, $path, 0);
	}

	/**
	 * @Then the single response should contain a property :key with value :value
	 * @param string $key
	 * @param string $expectedValue
	 * @throws \Exception
	 */
	public function theSingleResponseShouldContainAPropertyWithValue($key, $expectedValue) {
		$keys = $this->response;
		if (!array_key_exists($key, $keys)) {
			throw new \Exception("Cannot find property \"$key\" with \"$expectedValue\"");
		}

		$value = $keys[$key];
		if ($value != $expectedValue) {
			throw new \Exception("Property \"$key\" found with value \"$value\", expected \"$expectedValue\"");
		}
	}

	/**
	 * @Then the response should contain a share-types property with
	 */
	public function theResponseShouldContainAShareTypesPropertyWith($table)
	{
		$keys = $this->response;
		if (!array_key_exists('{http://owncloud.org/ns}share-types', $keys)) {
			throw new \Exception("Cannot find property \"{http://owncloud.org/ns}share-types\"");
		}

		$foundTypes = [];
		$data = $keys['{http://owncloud.org/ns}share-types'];
		foreach ($data as $item) {
			if ($item['name'] !== '{http://owncloud.org/ns}share-type') {
				throw new \Exception('Invalid property found: "' . $item['name'] . '"');
			}

			$foundTypes[] = $item['value'];
		}

		foreach ($table->getRows() as $row) {
			$key = array_search($row[0], $foundTypes);
			if ($key === false) {
				throw new \Exception('Expected type ' . $row[0] . ' not found');
			}

			unset($foundTypes[$key]);
		}

		if ($foundTypes !== []) {
			throw new \Exception('Found more share types then specified: ' . $foundTypes);
		}
	}

	/**
	 * @Then the response should contain an empty property :property
	 * @param string $property
	 * @throws \Exception
	 */
	public function theResponseShouldContainAnEmptyProperty($property) {
		$properties = $this->response;
		if (!array_key_exists($property, $properties)) {
			throw new \Exception("Cannot find property \"$property\"");
		}

		if ($properties[$property] !== null) {
			throw new \Exception("Property \"$property\" is not empty");
		}
	}

	/*Returns the elements of a propfind, $folderDepth requires 1 to see elements without children*/
	public function listFolder($user, $path, $folderDepth, $properties = null){
		$client = $this->getSabreClient($user);
		if (!$properties) {
			$properties = [
				'{DAV:}getetag'
			];
		}

		$response = $client->propfind($this->makeSabrePath($path), $properties, $folderDepth);

		return $response;
	}

	public function makeSabrePath($path) {
		return $this->encodePath($this->davPath . '/' . ltrim($path, '/'));
	}

	public function getSabreClient($user) {
		$fullUrl = substr($this->baseUrl, 0, -4);

		$settings = array(
			'baseUri' => $fullUrl,
			'userName' => $user,
		);

		if ($user === 'admin') {
			$settings['password'] = $this->adminUser[1];
		} else {
			$settings['password'] = $this->regularUser;
		}

		return new SClient($settings);
	}

	/**
	 * @Then /^user "([^"]*)" should see following elements$/
	 * @param string $user
	 * @param \Behat\Gherkin\Node\TableNode|null $expectedElements
	 */
	public function checkElementList($user, $expectedElements){
		$elementList = $this->listFolder($user, '/', 3);
		if ($expectedElements instanceof \Behat\Gherkin\Node\TableNode) {
			$elementRows = $expectedElements->getRows();
			$elementsSimplified = $this->simplifyArray($elementRows);
			foreach($elementsSimplified as $expectedElement) {
				$webdavPath = "/" . $this->davPath . $expectedElement;
				if (!array_key_exists($webdavPath,$elementList)){
					PHPUnit_Framework_Assert::fail("$webdavPath" . " is not in propfind answer");
				}
			}
		}
	}

	/**
	 * @When User :user uploads file :source to :destination
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 */
	public function userUploadsAFileTo($user, $source, $destination)
	{
		$file = \GuzzleHttp\Stream\Stream::factory(fopen($source, 'r'));
		try {
			$this->response = $this->makeDavRequest($user, "PUT", $destination, [], $file);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 4xx and 5xx responses cause an exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When User :user adds a file of :bytes bytes to :destination
	 * @param string $user
	 * @param string $bytes
	 * @param string $destination
	 */
	public function userAddsAFileTo($user, $bytes, $destination){
		$filename = "filespecificSize.txt";
		$this->createFileSpecificSize($user, $filename, $bytes);
		PHPUnit_Framework_Assert::assertEquals(1, file_exists("data/$filename"));
		$this->userUploadsAFileTo($user, "data/$filename", $destination);
		$this->removeFile("data/", $filename);
	}

	/**
	 * @When User :user uploads file with content :content to :destination
	 */
	public function userUploadsAFileWithContentTo($user, $content, $destination)
	{
		$file = \GuzzleHttp\Stream\Stream::factory($content);
		try {
			$this->response = $this->makeDavRequest($user, "PUT", $destination, [], $file);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 4xx and 5xx responses cause an exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When User :user deletes file :file
	 * @param string $user
	 * @param string $file
	 */
	public function userDeletesFile($user, $file)  {
		try {
			$this->response = $this->makeDavRequest($user, 'DELETE', $file, []);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 4xx and 5xx responses cause an exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Given User :user created a folder :destination
	 * @param string $user
	 * @param string $destination
	 */
	public function userCreatedAFolder($user, $destination) {
		try {
			$this->response = $this->makeDavRequest($user, "MKCOL", $this->getFilesPath() . ltrim($destination, $this->getFilesPath()), []);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 4xx and 5xx responses cause an exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Given user :user uploads chunk file :num of :total with :data to :destination
	 * @param string $user
	 * @param int $num
	 * @param int $total
	 * @param string $data
	 * @param string $destination
	 */
	public function userUploadsChunkFileOfWithToWithChecksum($user, $num, $total, $data, $destination)
	{
		$num -= 1;
		$data = \GuzzleHttp\Stream\Stream::factory($data);
		$file = $destination . '-chunking-42-'.$total.'-'.$num;
		$this->makeDavRequest($user, 'PUT', $file, ['OC-Chunked' => '1'], $data);
	}

	/**
	 * @Given user :user creates a new chunking upload with id :id
	 */
	public function userCreatesANewChunkingUploadWithId($user, $id)
	{
		$destination = '/uploads/'.$user.'/'.$id;
		$this->makeDavRequest($user, 'MKCOL', $destination, []);
	}

	/**
	 * @Given user :user uploads new chunk file :num with :data to id :id
	 */
	public function userUploadsNewChunkFileOfWithToId($user, $num, $data, $id)
	{
		$data = \GuzzleHttp\Stream\Stream::factory($data);
		$destination = '/uploads/'.$user.'/'.$id.'/'.$num;
		$this->makeDavRequest($user, 'PUT', $destination, [], $data);
	}

	/**
	 * @Given user :user moves new chunk file with id :id to :dest
	 */
	public function userMovesNewChunkFileWithIdToMychunkedfile($user, $id, $dest)
	{
		$source = '/uploads/'.$user.'/'.$id.'/.file';
		$destination = substr($this->baseUrl, 0, -4) . $this->davPath . '/files/'.$user.$dest;
		$this->makeDavRequest($user, 'MOVE', $source, [
			'Destination' => $destination
		]);
	}


	/**
	 * @Given /^Downloading file "([^"]*)" as "([^"]*)"$/
	 */
	public function downloadingFileAs($fileName, $user) {
		try {
			$this->response = $this->makeDavRequest($user, 'GET', $fileName, []);
		} catch (\GuzzleHttp\Exception\ServerException $ex) {
			$this->response = $ex->getResponse();
		}
	}

	/**
	 * URL encodes the given path but keeps the slashes
	 *
	 * @param string $path to encode
	 * @return string encoded path
	 */
	private function encodePath($path) {
		// slashes need to stay
		return str_replace('%2F', '/', rawurlencode($path));
	}

	/**
	 * @When user :user favorites element :path
	 */
	public function userFavoritesElement($user, $path){
		$this->response = $this->changeFavStateOfAnElement($user, $path, 1, 0, null);
	}

	/**
	 * @When user :user unfavorites element :path
	 */
	public function userUnfavoritesElement($user, $path){
		$this->response = $this->changeFavStateOfAnElement($user, $path, 0, 0, null);
	}

	/*Set the elements of a proppatch, $folderDepth requires 1 to see elements without children*/
	public function changeFavStateOfAnElement($user, $path, $favOrUnfav, $folderDepth, $properties = null){
		$fullUrl = substr($this->baseUrl, 0, -4);
		$settings = array(
			'baseUri' => $fullUrl,
			'userName' => $user,
		);
		if ($user === 'admin') {
			$settings['password'] = $this->adminUser[1];
		} else {
			$settings['password'] = $this->regularUser;
		}
		$client = new SClient($settings);
		if (!$properties) {
			$properties = [
				'{http://owncloud.org/ns}favorite' => $favOrUnfav
			];
		}

		$response = $client->proppatch($this->davPath . '/' . ltrim($path, '/'), $properties, $folderDepth);
		return $response;
	}

	/**
	 * @Then /^as "([^"]*)" gets properties of file "([^"]*)" with$/
	 * @param string $user
	 * @param string $path
	 * @param \Behat\Gherkin\Node\TableNode|null $propertiesTable
	 */
	public function asGetsPropertiesOfFileWith($user, $path, $propertiesTable) {
		$this->asGetsPropertiesOfFolderWith($user, $path, $propertiesTable);
	}
}
