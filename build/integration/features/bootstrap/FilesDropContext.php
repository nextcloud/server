<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client;

require __DIR__ . '/autoload.php';

class FilesDropContext implements Context, SnippetAcceptingContext {
	use WebDav;

	/**
	 * @When Dropping file :path with :content
	 */
	public function droppingFileWith($path, $content, $nickname = null) {
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
			'X-REQUESTED-WITH' => 'XMLHttpRequest',
		];

		if ($nickname) {
			$options['headers']['X-NC-NICKNAME'] = $nickname;
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
	public function droppingFileWithAs($path, $content, $nickname) {
		$this->droppingFileWith($path, $content, $nickname);
	}


	/**
	 * @When Creating folder :folder in drop
	 */
	public function creatingFolderInDrop($folder, $nickname = null) {
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
			'X-REQUESTED-WITH' => 'XMLHttpRequest',
		];

		if ($nickname) {
			$options['headers']['X-NC-NICKNAME'] = $nickname;
		}

		try {
			$this->response = $client->request('MKCOL', $fullUrl, $options);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}


	/**
	 * @When Creating folder :folder in drop as :nickName
	 */
	public function creatingFolderInDropWithNickname($folder, $nickname) {
		return $this->creatingFolderInDrop($folder, $nickname);
	}
}
