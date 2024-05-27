<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Authentication\Login;

use OC\Authentication\Login\ALoginCommand;
use OC\Authentication\Login\LoginData;
use OCP\IRequest;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

abstract class ALoginCommandTest extends TestCase {
	/** @var IRequest|MockObject */
	protected $request;

	/** @var string */
	protected $username = 'user123';

	/** @var string */
	protected $password = '123456';

	/** @var string */
	protected $redirectUrl = '/apps/contacts';

	/** @var string */
	protected $timezone = 'Europe/Vienna';

	protected $timeZoneOffset = '2';

	/** @var IUser|MockObject */
	protected $user;

	/** @var ALoginCommand */
	protected $cmd;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->user = $this->createMock(IUser::class);
	}

	protected function getBasicLoginData(): LoginData {
		return new LoginData(
			$this->request,
			$this->username,
			$this->password
		);
	}

	protected function getInvalidLoginData(): LoginData {
		return new LoginData(
			$this->request,
			$this->username,
			$this->password
		);
	}

	protected function getFailedLoginData(): LoginData {
		$data = new LoginData(
			$this->request,
			$this->username,
			$this->password
		);
		$data->setUser(false);
		return $data;
	}

	protected function getLoggedInLoginData(): LoginData {
		$basic = $this->getBasicLoginData();
		$basic->setUser($this->user);
		return $basic;
	}

	protected function getLoggedInLoginDataWithRedirectUrl(): LoginData {
		$data = new LoginData(
			$this->request,
			$this->username,
			$this->password,
			$this->redirectUrl
		);
		$data->setUser($this->user);
		return $data;
	}

	protected function getLoggedInLoginDataWithTimezone(): LoginData {
		$data = new LoginData(
			$this->request,
			$this->username,
			$this->password,
			null,
			$this->timezone,
			$this->timeZoneOffset
		);
		$data->setUser($this->user);
		return $data;
	}
}
