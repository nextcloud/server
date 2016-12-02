<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use GuzzleHttp\Client;

require __DIR__ . '/../../vendor/autoload.php';

class FilesDropContext implements Context, SnippetAcceptingContext {
	use WebDav;

	/**
	 * @When Dropping file :path with :content
	 */
	public function droppingFileWith($path, $content) {
		$client = new Client();
		$options = [];
		if (count($this->lastShareData->data->element) > 0){
			$token = $this->lastShareData->data[0]->token;
		} else {
			$token = $this->lastShareData->data[0]->token;
		}

		$base = substr($this->baseUrl, 0, -4);
		$fullUrl = $base . '/public.php/webdav' . $path;

		$options['auth'] = [$token, ''];
		$options['headers'] = [
			'X-REQUESTED-WITH' => 'XMLHttpRequest'
		];

		$request = $client->createRequest('PUT', $fullUrl, $options);
		$file = \GuzzleHttp\Stream\Stream::factory($content);
		$request->setBody($file);

		try {
			$this->response = $client->send($request);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When Creating folder :folder in drop
	 */
	public function creatingFolderInDrop($folder) {
		$client = new Client();
		$options = [];
		if (count($this->lastShareData->data->element) > 0){
			$token = $this->lastShareData->data[0]->token;
		} else {
			$token = $this->lastShareData->data[0]->token;
		}

		$base = substr($this->baseUrl, 0, -4);
		$fullUrl = $base . '/public.php/webdav/' . $folder;

		$options['auth'] = [$token, ''];
		$options['headers'] = [
			'X-REQUESTED-WITH' => 'XMLHttpRequest'
		];

		$request = $client->createRequest('MKCOL', $fullUrl, $options);

		try {
			$this->response = $client->send($request);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}
}
