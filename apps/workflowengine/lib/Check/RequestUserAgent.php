<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Check;

use OCP\IL10N;
use OCP\IRequest;

class RequestUserAgent extends AbstractStringCheck {
	/**
	 * Web views the iOS app opens for office editors (OnlyOffice, Euro-Office,
	 * Whiteboard) send "(iPhone) Mobile" or "(iPad)" instead of "(iOS)".
	 * Note: {@see IRequest::USER_AGENT_CLIENT_IOS} is used for exempting CSRF
	 *   checks and they should apply to web views, so it cannot be included here.
	 */
	private const string USER_AGENT_CLIENT_IOS_WEBVIEW = '/^Mozilla\/5\.0 \((?:iPhone|iPad)\)(?: Mobile)? (?:ownCloud|Nextcloud)\-iOS\/([^ ]*).*$/';

	/**
	 * Web views the Android app opens for office editors send
	 * "(Android <os version>) Mobile" instead of "(Android)".
	 */
	private const string USER_AGENT_CLIENT_ANDROID_WEBVIEW = '/^Mozilla\/5\.0 \(Android [^)]*\) Mobile (?:ownCloud|Nextcloud)\-android\/([^ ]*).*$/';

	/**
	 * The Android app's image loader sends its user agent without a version.
	 */
	private const string USER_AGENT_CLIENT_ANDROID_NO_VERSION = '/^Mozilla\/5\.0 \(Android\) (?:ownCloud|Nextcloud)\-android$/';

	/**
	 * User agent patterns per predefined client. A request "is" a client when
	 * any of its patterns matches.
	 *
	 * @var array<string, list<string>>
	 */
	private const array CLIENT_PATTERNS = [
		'android' => [
			IRequest::USER_AGENT_CLIENT_ANDROID,
			self::USER_AGENT_CLIENT_ANDROID_WEBVIEW,
			self::USER_AGENT_CLIENT_ANDROID_NO_VERSION,
		],
		'ios' => [
			IRequest::USER_AGENT_CLIENT_IOS,
			self::USER_AGENT_CLIENT_IOS_WEBVIEW,
		],
		'desktop' => [
			IRequest::USER_AGENT_CLIENT_DESKTOP,
		],
		'mail' => [
			IRequest::USER_AGENT_OUTLOOK_ADDON,
			IRequest::USER_AGENT_THUNDERBIRD_ADDON,
		],
	];

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
	#[\Override]
	public function executeCheck($operator, $value) {
		$actualValue = $this->getActualValue();
		if (in_array($operator, ['is', '!is'], true) && isset(self::CLIENT_PATTERNS[$value])) {
			$isClient = false;
			foreach (self::CLIENT_PATTERNS[$value] as $pattern) {
				if ($this->executeStringCheck('matches', $pattern, $actualValue)) {
					$isClient = true;
					break;
				}
			}
			return $operator === 'is' ? $isClient : !$isClient;
		}
		return $this->executeStringCheck($operator, $value, $actualValue);
	}

	/**
	 * @return string
	 */
	#[\Override]
	protected function getActualValue() {
		return $this->request->getHeader('User-Agent');
	}

	#[\Override]
	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
