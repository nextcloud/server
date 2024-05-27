<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\TwoFactorAuth;

use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;

class Base extends \OC\Core\Command\Base {
	public function __construct(
		?string $name,
		protected IUserManager $userManager,
	) {
		parent::__construct($name);
	}

	/**
	 * Return possible values for the named option
	 *
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeOptionValues($optionName, CompletionContext $context) {
		return [];
	}

	/**
	 * Return possible values for the named argument
	 *
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'uid') {
			return array_map(function (IUser $user) {
				return $user->getUID();
			}, $this->userManager->search($context->getCurrentWord(), 100));
		}
		return [];
	}
}
