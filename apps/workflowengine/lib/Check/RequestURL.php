<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OCP\IL10N;
use OCP\IRequest;

class RequestURL extends AbstractStringCheck {
	public const CLI = 'cli';

	/** @var ?string */
	protected $url;

	/**
	 * @param IL10N $l
	 * @param IRequest $request
	 */
	public function __construct(
		IL10N $l,
		protected IRequest $request,
	) {
		parent::__construct($l);
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value) {
		if (\OC::$CLI) {
			$actualValue = $this->url = RequestURL::CLI;
		} else {
			$actualValue = $this->getActualValue();
		}
		if (in_array($operator, ['is', '!is'])) {
			switch ($value) {
				case 'webdav':
					if ($operator === 'is') {
						return $this->isWebDAVRequest();
					} else {
						return !$this->isWebDAVRequest();
					}
			}
		}
		return $this->executeStringCheck($operator, $value, $actualValue);
	}

	/**
	 * @return string
	 */
	protected function getActualValue() {
		if ($this->url !== null) {
			return $this->url;
		}

		$this->url = $this->request->getServerProtocol() . '://';// E.g. http(s) + ://
		$this->url .= $this->request->getServerHost();// E.g. localhost
		$this->url .= $this->request->getScriptName();// E.g. /nextcloud/index.php
		$this->url .= $this->request->getPathInfo();// E.g. /apps/files_texteditor/ajax/loadfile

		return $this->url; // E.g. https://localhost/nextcloud/index.php/apps/files_texteditor/ajax/loadfile
	}

	protected function isWebDAVRequest(): bool {
		if ($this->url === RequestURL::CLI) {
			return false;
		}
		return substr($this->request->getScriptName(), 0 - strlen('/remote.php')) === '/remote.php' && (
			$this->request->getPathInfo() === '/webdav'
			|| str_starts_with($this->request->getPathInfo() ?? '', '/webdav/')
			|| $this->request->getPathInfo() === '/dav/files'
			|| str_starts_with($this->request->getPathInfo() ?? '', '/dav/files/')
		);
	}
}
