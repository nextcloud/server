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
	 * User agent patterns per predefined client. A request "is" a client when
	 * any of its patterns matches.
	 *
	 * @var array<string, list<string>>
	 */
	private const CLIENT_PATTERNS = [
		'android' => [
			IRequest::USER_AGENT_CLIENT_ANDROID,
			IRequest::USER_AGENT_CLIENT_ANDROID_WEBVIEW,
			IRequest::USER_AGENT_CLIENT_ANDROID_NO_VERSION,
		],
		'ios' => [
			IRequest::USER_AGENT_CLIENT_IOS,
			IRequest::USER_AGENT_CLIENT_IOS_WEBVIEW,
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
