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
		$actualValue = $this->getActualValue();
		if (in_array($operator, ['is', '!is'], true)) {
			switch ($value) {
				case 'android':
					$operator = $operator === 'is' ? 'matches' : '!matches';
					$value = IRequest::USER_AGENT_CLIENT_ANDROID;
					break;
				case 'ios':
					$operator = $operator === 'is' ? 'matches' : '!matches';
					$value = IRequest::USER_AGENT_CLIENT_IOS;
					break;
				case 'desktop':
					$operator = $operator === 'is' ? 'matches' : '!matches';
					$value = IRequest::USER_AGENT_CLIENT_DESKTOP;
					break;
				case 'mail':
					if ($operator === 'is') {
						return $this->executeStringCheck('matches', IRequest::USER_AGENT_OUTLOOK_ADDON, $actualValue)
							|| $this->executeStringCheck('matches', IRequest::USER_AGENT_THUNDERBIRD_ADDON, $actualValue);
					}

					return $this->executeStringCheck('!matches', IRequest::USER_AGENT_OUTLOOK_ADDON, $actualValue)
						&& $this->executeStringCheck('!matches', IRequest::USER_AGENT_THUNDERBIRD_ADDON, $actualValue);
			}
		}
		return $this->executeStringCheck($operator, $value, $actualValue);
	}

	/**
	 * @return string
	 */
	protected function getActualValue() {
		return $this->request->getHeader('User-Agent');
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
