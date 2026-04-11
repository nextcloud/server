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
		$token = $this->lastShareData->data[0]->token;
		$fullUrl = $this->getDavBaseUrl() . "public.php/dav/files/$token/$path";
		$client = $this->getGuzzleClient(null);
		$options['headers'] = [ 'X-REQUESTED-WITH' => 'XMLHttpRequest' ];
		$options['body'] = \GuzzleHttp\Psr7\Utils::streamFor($content);
		if ($nickname) {
			$options['headers']['X-NC-NICKNAME'] = $nickname;
		}
		$this->response = $client->request('PUT', $fullUrl, $options);		
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
		$token = $this->lastShareData->data[0]->token;
		$fullUrl = $this->getDavBaseUrl() . "public.php/dav/files/$token/$folder";
		$client = $this->getGuzzleClient(null);		
		$options['headers'] = [ 'X-REQUESTED-WITH' => 'XMLHttpRequest' ];
		if ($nickname) {
			$options['headers']['X-NC-NICKNAME'] = $nickname;
		}
		$this->response = $client->request('MKCOL', $fullUrl, $options);
	}


	/**
	 * @When Creating folder :folder in drop as :nickName
	 */
	public function creatingFolderInDropWithNickname($folder, $nickname) {
		return $this->creatingFolderInDrop($folder, $nickname);
	}
}
