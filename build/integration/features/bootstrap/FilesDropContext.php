<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client;

require __DIR__ . '/../../vendor/autoload.php';

class FilesDropContext implements Context, SnippetAcceptingContext {
	use WebDav;

	/**
	 * @When Dropping file :path with :content
	 */
	public function droppingFileWith($path, $content, $nickName = null) {
		$client = new Client();
		$options = [];
		if (count($this->lastShareData->data->element) > 0) {
			$token = $this->lastShareData->data[0]->token;
		} else {
			$token = $this->lastShareData->data[0]->token;
		}

		$base = substr($this->baseUrl, 0, -4);
		$fullUrl = str_replace('//', '/', $base . "/public.php/dav/files/$token/$path");

		$options['headers'] = [
			'X-REQUESTED-WITH' => 'XMLHttpRequest'
		];

		if ($nickName) {
			$options['headers']['X-NC-NICKNAME'] = $nickName;
		}

		$options['body'] = \GuzzleHttp\Psr7\Utils::streamFor($content);

		try {
			$this->response = $client->request('PUT', $fullUrl, $options);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}
		
		
	/**
	 * @When Dropping file :path with :content as :nickName
	 */
	public function droppingFileWithAs($path, $content, $nickName) {
		$this->droppingFileWith($path, $content, $nickName);
	}


	/**
	 * @When Creating folder :folder in drop
	 */
	public function creatingFolderInDrop($folder) {
		$client = new Client();
		$options = [];
		if (count($this->lastShareData->data->element) > 0) {
			$token = $this->lastShareData->data[0]->token;
		} else {
			$token = $this->lastShareData->data[0]->token;
		}

		$base = substr($this->baseUrl, 0, -4);
		$fullUrl = str_replace('//', '/', $base . "/public.php/dav/files/$token/$folder");

		$options['headers'] = [
			'X-REQUESTED-WITH' => 'XMLHttpRequest'
		];

		try {
			$this->response = $client->request('MKCOL', $fullUrl, $options);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}
}
