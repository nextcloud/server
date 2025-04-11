<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Settings\Tests\Controller;

use OC\IntegrityCheck\Checker;
use OCA\Settings\Controller\CheckSetupController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheckManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class CheckSetupControllerTest
 *
 * @backupStaticAttributes
 * @package Tests\Settings\Controller
 */
class CheckSetupControllerTest extends TestCase {
	/** @var CheckSetupController | \PHPUnit\Framework\MockObject\MockObject */
	private $checkSetupController;
	/** @var IRequest | \PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var IConfig | \PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IURLGenerator | \PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;
	/** @var IL10N | \PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var LoggerInterface */
	private $logger;
	/** @var Checker|\PHPUnit\Framework\MockObject\MockObject */
	private $checker;
	/** @var ISetupCheckManager|MockObject */
	private $setupCheckManager;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message, array $replace) {
				return vsprintf($message, $replace);
			});
		$this->checker = $this->getMockBuilder('\OC\IntegrityCheck\Checker')
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->setupCheckManager = $this->createMock(ISetupCheckManager::class);
		$this->checkSetupController = $this->getMockBuilder(CheckSetupController::class)
			->setConstructorArgs([
				'settings',
				$this->request,
				$this->config,
				$this->urlGenerator,
				$this->l10n,
				$this->checker,
				$this->logger,
				$this->setupCheckManager,
			])
			->setMethods([
				'getCurlVersion',
				'isPhpOutdated',
				'isPHPMailerUsed',
			])->getMock();
	}

	public function testCheck(): void {
		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnMap([
				['files_external', 'user_certificate_scan', '', '["a", "b"]'],
				['dav', 'needs_system_address_book_sync', 'no', 'no'],
			]);
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['connectivity_check_domains', ['www.nextcloud.com', 'www.startpage.com', 'www.eff.org', 'www.edri.org'], ['www.nextcloud.com', 'www.startpage.com', 'www.eff.org', 'www.edri.org']],
				['memcache.local', null, 'SomeProvider'],
				['has_internet_connection', true, true],
				['appstoreenabled', true, false],
			]);

		$this->request->expects($this->never())
			->method('getHeader');

		$this->urlGenerator->method('linkToDocs')
			->willReturnCallback(function (string $key): string {
				if ($key === 'admin-performance') {
					return 'http://docs.example.org/server/go.php?to=admin-performance';
				}
				if ($key === 'admin-security') {
					return 'https://docs.example.org/server/8.1/admin_manual/configuration_server/hardening.html';
				}
				if ($key === 'admin-reverse-proxy') {
					return 'reverse-proxy-doc-link';
				}
				if ($key === 'admin-code-integrity') {
					return 'http://docs.example.org/server/go.php?to=admin-code-integrity';
				}
				if ($key === 'admin-db-conversion') {
					return 'http://docs.example.org/server/go.php?to=admin-db-conversion';
				}
				return '';
			});

		$this->urlGenerator->method('getAbsoluteURL')
			->willReturnCallback(function (string $url): string {
				if ($url === 'index.php/settings/admin') {
					return 'https://server/index.php/settings/admin';
				}
				if ($url === 'index.php') {
					return 'https://server/index.php';
				}
				return '';
			});

		$expected = new DataResponse(
			[
				'generic' => [],
			]
		);
		$this->assertEquals($expected, $this->checkSetupController->check());
	}

	public function testRescanFailedIntegrityCheck(): void {
		$this->checker
			->expects($this->once())
			->method('runInstanceVerification');
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('settings.AdminSettings.index')
			->willReturn('/admin');

		$expected = new RedirectResponse('/admin');
		$this->assertEquals($expected, $this->checkSetupController->rescanFailedIntegrityCheck());
	}

	public function testGetFailedIntegrityCheckDisabled(): void {
		$this->checker
			->expects($this->once())
			->method('isCodeCheckEnforced')
			->willReturn(false);

		$expected = new DataDisplayResponse('Integrity checker has been disabled. Integrity cannot be verified.');
		$this->assertEquals($expected, $this->checkSetupController->getFailedIntegrityCheckFiles());
	}


	public function testGetFailedIntegrityCheckFilesWithNoErrorsFound(): void {
		$this->checker
			->expects($this->once())
			->method('isCodeCheckEnforced')
			->willReturn(true);
		$this->checker
			->expects($this->once())
			->method('getResults')
			->willReturn([]);

		$expected = new DataDisplayResponse(
			'No errors have been found.',
			Http::STATUS_OK,
			[
				'Content-Type' => 'text/plain',
			]
		);
		$this->assertEquals($expected, $this->checkSetupController->getFailedIntegrityCheckFiles());
	}

	public function testGetFailedIntegrityCheckFilesWithSomeErrorsFound(): void {
		$this->checker
			->expects($this->once())
			->method('isCodeCheckEnforced')
			->willReturn(true);
		$this->checker
			->expects($this->once())
			->method('getResults')
			->willReturn([ 'core' => [ 'EXTRA_FILE' => ['/testfile' => []], 'INVALID_HASH' => [ '/.idea/workspace.xml' => [ 'expected' => 'f1c5e2630d784bc9cb02d5a28f55d6f24d06dae2a0fee685f3c2521b050955d9d452769f61454c9ddfa9c308146ade10546cfa829794448eaffbc9a04a29d216', 'current' => 'ce08bf30bcbb879a18b49239a9bec6b8702f52452f88a9d32142cad8d2494d5735e6bfa0d8642b2762c62ca5be49f9bf4ec231d4a230559d4f3e2c471d3ea094', ], '/lib/private/integritycheck/checker.php' => [ 'expected' => 'c5a03bacae8dedf8b239997901ba1fffd2fe51271d13a00cc4b34b09cca5176397a89fc27381cbb1f72855fa18b69b6f87d7d5685c3b45aee373b09be54742ea', 'current' => '88a3a92c11db91dec1ac3be0e1c87f862c95ba6ffaaaa3f2c3b8f682187c66f07af3a3b557a868342ef4a271218fe1c1e300c478e6c156c5955ed53c40d06585', ], '/settings/controller/checksetupcontroller.php' => [ 'expected' => '3e1de26ce93c7bfe0ede7c19cb6c93cadc010340225b375607a7178812e9de163179b0dc33809f451e01f491d93f6f5aaca7929685d21594cccf8bda732327c4', 'current' => '09563164f9904a837f9ca0b5f626db56c838e5098e0ccc1d8b935f68fa03a25c5ec6f6b2d9e44a868e8b85764dafd1605522b4af8db0ae269d73432e9a01e63a', ], ], ], 'bookmarks' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'dav' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'encryption' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'external' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'federation' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files_antivirus' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files_drop' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files_external' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files_pdfviewer' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files_sharing' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files_trashbin' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files_versions' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'files_videoviewer' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'firstrunwizard' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'gitsmart' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'logreader' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature could not get verified.', ], ], 'password_policy' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'provisioning_api' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'sketch' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'threatblock' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'two_factor_auth' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'user_ldap' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], 'user_shibboleth' => [ 'EXCEPTION' => [ 'class' => 'OC\\IntegrityCheck\\Exceptions\\InvalidSignatureException', 'message' => 'Signature data not found.', ], ], ]);

		$expected = new DataDisplayResponse(
			'Technical information
=====================
The following list covers which files have failed the integrity check. Please read
the previous linked documentation to learn more about the errors and how to fix
them.

Results
=======
- core
	- EXTRA_FILE
		- /testfile
	- INVALID_HASH
		- /.idea/workspace.xml
		- /lib/private/integritycheck/checker.php
		- /settings/controller/checksetupcontroller.php
- bookmarks
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- dav
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- encryption
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- external
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- federation
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_antivirus
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_drop
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_external
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_pdfviewer
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_sharing
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_trashbin
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_versions
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- files_videoviewer
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- firstrunwizard
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- gitsmart
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- logreader
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature could not get verified.
- password_policy
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- provisioning_api
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- sketch
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- threatblock
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- two_factor_auth
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- user_ldap
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.
- user_shibboleth
	- EXCEPTION
		- OC\IntegrityCheck\Exceptions\InvalidSignatureException
		- Signature data not found.

Raw output
==========
Array
(
    [core] => Array
        (
            [EXTRA_FILE] => Array
                (
                    [/testfile] => Array
                        (
                        )

                )

            [INVALID_HASH] => Array
                (
                    [/.idea/workspace.xml] => Array
                        (
                            [expected] => f1c5e2630d784bc9cb02d5a28f55d6f24d06dae2a0fee685f3c2521b050955d9d452769f61454c9ddfa9c308146ade10546cfa829794448eaffbc9a04a29d216
                            [current] => ce08bf30bcbb879a18b49239a9bec6b8702f52452f88a9d32142cad8d2494d5735e6bfa0d8642b2762c62ca5be49f9bf4ec231d4a230559d4f3e2c471d3ea094
                        )

                    [/lib/private/integritycheck/checker.php] => Array
                        (
                            [expected] => c5a03bacae8dedf8b239997901ba1fffd2fe51271d13a00cc4b34b09cca5176397a89fc27381cbb1f72855fa18b69b6f87d7d5685c3b45aee373b09be54742ea
                            [current] => 88a3a92c11db91dec1ac3be0e1c87f862c95ba6ffaaaa3f2c3b8f682187c66f07af3a3b557a868342ef4a271218fe1c1e300c478e6c156c5955ed53c40d06585
                        )

                    [/settings/controller/checksetupcontroller.php] => Array
                        (
                            [expected] => 3e1de26ce93c7bfe0ede7c19cb6c93cadc010340225b375607a7178812e9de163179b0dc33809f451e01f491d93f6f5aaca7929685d21594cccf8bda732327c4
                            [current] => 09563164f9904a837f9ca0b5f626db56c838e5098e0ccc1d8b935f68fa03a25c5ec6f6b2d9e44a868e8b85764dafd1605522b4af8db0ae269d73432e9a01e63a
                        )

                )

        )

    [bookmarks] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [dav] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [encryption] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [external] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [federation] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_antivirus] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_drop] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_external] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_pdfviewer] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_sharing] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_trashbin] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_versions] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [files_videoviewer] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [firstrunwizard] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [gitsmart] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [logreader] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature could not get verified.
                )

        )

    [password_policy] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [provisioning_api] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [sketch] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [threatblock] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [two_factor_auth] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [user_ldap] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

    [user_shibboleth] => Array
        (
            [EXCEPTION] => Array
                (
                    [class] => OC\IntegrityCheck\Exceptions\InvalidSignatureException
                    [message] => Signature data not found.
                )

        )

)
',
			Http::STATUS_OK,
			[
				'Content-Type' => 'text/plain',
			]
		);
		$this->assertEquals($expected, $this->checkSetupController->getFailedIntegrityCheckFiles());
	}
}
