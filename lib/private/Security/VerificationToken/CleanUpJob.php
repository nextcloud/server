<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\VerificationToken;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\Job;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Security\VerificationToken\InvalidTokenException;
use OCP\Security\VerificationToken\IVerificationToken;

class CleanUpJob extends Job {
	protected ?int $runNotBefore = null;
	protected ?string $userId = null;
	protected ?string $subject = null;
	protected ?string $pwdPrefix = null;

	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private IVerificationToken $verificationToken,
		private IUserManager $userManager,
	) {
		parent::__construct($time);
	}

	public function setArgument($argument): void {
		parent::setArgument($argument);
		$args = \json_decode($argument, true);
		$this->userId = (string)$args['userId'];
		$this->subject = (string)$args['subject'];
		$this->pwdPrefix = (string)$args['pp'];
		$this->runNotBefore = (int)$args['notBefore'];
	}

	protected function run($argument): void {
		try {
			$user = $this->userManager->get($this->userId);
			if ($user === null) {
				return;
			}
			$this->verificationToken->check('irrelevant', $user, $this->subject, $this->pwdPrefix);
		} catch (InvalidTokenException $e) {
			if ($e->getCode() === InvalidTokenException::TOKEN_EXPIRED) {
				// make sure to only remove expired tokens
				$this->config->deleteUserValue($this->userId, 'core', $this->subject);
			}
		}
	}

	public function start(IJobList $jobList): void {
		if ($this->time->getTime() >= $this->runNotBefore) {
			$jobList->remove($this, $this->argument);
			parent::start($jobList);
		}
	}
}
