<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\BackgroundJobs;

use OC\Core\AppInfo\ConfigLexicon;
use OC\Core\BackgroundJobs\CheckCodeIntegrityJob;
use OC\IntegrityCheck\Checker;
use OC\Notification\Notification;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CheckCodeIntegrityJobTest extends TestCase {
	private const FAILED_RESULT = [
		'core' => [
			'EXTRA_FILE' => [
				'core/extra.php' => ['expected' => '', 'current' => 'abc'],
			],
		],
	];

	private Checker&MockObject $checker;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private IGroupManager&MockObject $groupManager;
	private INotificationManager&MockObject $notificationManager;
	private IMailer&MockObject $mailer;
	private IEMailTemplate&MockObject $template;
	private IL10N&MockObject $l10n;
	/** @var list<string> recipients collected by expectAdminNotifications() */
	private array $users = [];
	private CheckCodeIntegrityJob $job;

	protected function setUp(): void {
		parent::setUp();

		$this->checker = $this->createMock(Checker::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->mailer = $this->createMock(IMailer::class);

		$this->config->method('getSystemValueBool')
			->with('integrity.check.scheduled', true)
			->willReturn(true);
		$this->checker->method('isCodeCheckEnforced')->willReturn(true);

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);
		$this->l10n->method('n')->willReturnCallback(
			fn (string $singular, string $plural, int $count, array $parameters = []): string
				=> vsprintf(str_replace('%n', (string)$count, $count === 1 ? $singular : $plural), $parameters)
		);

		$this->template = $this->createMock(IEMailTemplate::class);
		$this->mailer->method('createEMailTemplate')->willReturn($this->template);
		$this->mailer->method('createMessage')->willReturn($this->createMock(IMessage::class));

		$this->notificationManager->method('createNotification')->willReturnCallback(
			fn (): INotification => new Notification($this->createMock(IValidator::class), $this->createMock(IRichTextFormatter::class))
		);

		$this->job = $this->createJob($this->checker, $this->config);
	}

	private function createJob(Checker $checker, IConfig $config): CheckCodeIntegrityJob {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-09-25 12:00:00'));
		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->method('get')->willReturn($this->l10n);

		return new CheckCodeIntegrityJob(
			$time,
			$checker,
			$config,
			$this->appConfig,
			$this->groupManager,
			$this->notificationManager,
			$this->mailer,
			$l10nFactory,
			$this->createMock(IURLGenerator::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	private function mockAdmins(): void {
		$withEmail = $this->createMock(IUser::class);
		$withEmail->method('getUID')->willReturn('admin1');
		$withEmail->method('getEMailAddress')->willReturn('admin1@example.com');
		$withoutEmail = $this->createMock(IUser::class);
		$withoutEmail->method('getUID')->willReturn('admin2');
		$withoutEmail->method('getEMailAddress')->willReturn(null);

		$group = $this->createMock(IGroup::class);
		$group->method('getUsers')->willReturn([$withEmail, $withoutEmail]);
		$this->groupManager->method('get')->with('admin')->willReturn($group);
	}

	private function fingerprint(array $results): string {
		return self::invokePrivate($this->job, 'fingerprint', [$results]);
	}

	/**
	 * Expects one notification per admin for the given fingerprint and summary
	 */
	private function expectAdminNotifications(string $fingerprint, array $summary): void {
		$this->notificationManager->expects($this->exactly(2))->method('notify')
			->willReturnCallback(function (INotification $notification) use ($fingerprint, $summary): void {
				$this->assertSame('core', $notification->getApp());
				$this->assertSame(CheckCodeIntegrityJob::NOTIFICATION_OBJECT_TYPE, $notification->getObjectType());
				$this->assertSame($fingerprint, $notification->getObjectId());
				$this->assertSame('code_integrity_changed', $notification->getSubject());
				$this->assertSame($summary, $notification->getSubjectParameters());
				$this->users[] = $notification->getUser();
			});
	}

	private function expectRemovedNotification(string $fingerprint): void {
		$this->notificationManager->expects($this->once())->method('markProcessed')
			->with($this->callback(fn (INotification $notification): bool
				=> $notification->getApp() === 'core'
				&& $notification->getObjectType() === CheckCodeIntegrityJob::NOTIFICATION_OBJECT_TYPE
				&& $notification->getObjectId() === $fingerprint));
	}

	private function expectStoredFingerprint(string $fingerprint): void {
		$this->appConfig->expects($this->once())->method('setValueString')
			->with('core', ConfigLexicon::INTEGRITY_CHECK_NOTIFIED_RESULT, $fingerprint);
	}

	public function testSkipsWhenDisabledByConfig(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')
			->with('integrity.check.scheduled', true)
			->willReturn(false);

		$this->checker->expects($this->never())->method('runInstanceVerification');

		self::invokePrivate($this->createJob($this->checker, $config), 'run', [null]);
	}

	public function testSkipsWhenCodeCheckIsNotEnforced(): void {
		$checker = $this->createMock(Checker::class);
		$checker->method('isCodeCheckEnforced')->willReturn(false);
		$checker->expects($this->never())->method('runInstanceVerification');

		self::invokePrivate($this->createJob($checker, $this->config), 'run', [null]);
	}

	public function testNotifiesAndMailsAdminsWhenCheckStartsFailing(): void {
		$this->mockAdmins();
		$this->checker->expects($this->once())->method('runInstanceVerification');
		$this->checker->method('getResults')->willReturn(self::FAILED_RESULT);
		$this->appConfig->method('getValueString')
			->with('core', ConfigLexicon::INTEGRITY_CHECK_NOTIFIED_RESULT)
			->willReturn('');

		$fingerprint = $this->fingerprint(self::FAILED_RESULT);
		$this->notificationManager->expects($this->never())->method('markProcessed');
		$this->expectAdminNotifications($fingerprint, ['files' => 1, 'unverified' => []]);
		$this->mailer->expects($this->once())->method('send');
		$this->template->expects($this->never())->method('addBodyText');
		$this->template->expects($this->once())->method('addBodyNote')->with(
			'1 file does not match the signed release. It may have been modified or added without authorization.',
			'',
			IEMailTemplate::NOTE_WARNING,
		);
		$this->template->expects($this->once())->method('addBodyButton');
		$this->expectStoredFingerprint($fingerprint);

		self::invokePrivate($this->job, 'run', [null]);

		$this->assertSame(['admin1', 'admin2'], $this->users);
	}

	public function testDoesNothingWhenResultIsUnchanged(): void {
		$this->checker->method('getResults')->willReturn(self::FAILED_RESULT);
		$this->appConfig->method('getValueString')->willReturn($this->fingerprint(self::FAILED_RESULT));

		$this->notificationManager->expects($this->never())->method('markProcessed');
		$this->notificationManager->expects($this->never())->method('notify');
		$this->mailer->expects($this->never())->method('send');
		$this->appConfig->expects($this->never())->method('setValueString');

		self::invokePrivate($this->job, 'run', [null]);
	}

	public function testReplacesNotificationWhenFailureChanges(): void {
		$this->mockAdmins();
		$changed = self::FAILED_RESULT;
		$changed['core']['EXTRA_FILE']['core/another.php'] = ['expected' => '', 'current' => 'def'];
		$this->checker->method('getResults')->willReturn($changed);
		$previous = $this->fingerprint(self::FAILED_RESULT);
		$this->appConfig->method('getValueString')->willReturn($previous);

		$fingerprint = $this->fingerprint($changed);
		$this->expectRemovedNotification($previous);
		$this->expectAdminNotifications($fingerprint, ['files' => 2, 'unverified' => []]);
		$this->mailer->expects($this->once())->method('send');
		$this->expectStoredFingerprint($fingerprint);

		self::invokePrivate($this->job, 'run', [null]);
	}

	public function testMailPutsEverySentenceInOneWarningNote(): void {
		$this->mockAdmins();
		$results = self::FAILED_RESULT;
		$results['files'] = ['EXCEPTION' => ['class' => \Exception::class, 'message' => 'Signature data not found.']];
		$this->checker->method('getResults')->willReturn($results);
		$this->appConfig->method('getValueString')->willReturn('');

		$this->template->expects($this->once())->method('addBodyNote')->with(
			"1 file does not match the signed release. It may have been modified or added without authorization.\n"
			. 'The signature of files is missing or invalid, so it could not be verified.',
			'',
			IEMailTemplate::NOTE_WARNING,
		);

		self::invokePrivate($this->job, 'run', [null]);
	}

	public function testClearsNotificationWhenCheckPassesAgain(): void {
		$this->checker->method('getResults')->willReturn([]);
		$previous = $this->fingerprint(self::FAILED_RESULT);
		$this->appConfig->method('getValueString')->willReturn($previous);

		$this->expectRemovedNotification($previous);
		$this->notificationManager->expects($this->never())->method('notify');
		$this->mailer->expects($this->never())->method('send');
		$this->expectStoredFingerprint('');

		self::invokePrivate($this->job, 'run', [null]);
	}

	public function testMailFailureDoesNotPreventStoringTheResult(): void {
		$this->mockAdmins();
		$this->checker->method('getResults')->willReturn(self::FAILED_RESULT);
		$this->appConfig->method('getValueString')->willReturn('');
		$this->mailer->method('send')->willThrowException(new \Exception('SMTP down'));

		$fingerprint = $this->fingerprint(self::FAILED_RESULT);
		$this->expectAdminNotifications($fingerprint, ['files' => 1, 'unverified' => []]);
		$this->expectStoredFingerprint($fingerprint);

		self::invokePrivate($this->job, 'run', [null]);
	}

	public function testFingerprintIgnoresOrder(): void {
		$result = [
			'core' => ['EXTRA_FILE' => ['b.php' => ['current' => '2'], 'a.php' => ['current' => '1']]],
			'myapp' => ['INVALID_HASH' => ['x.php' => ['current' => 'c']]],
		];
		$reordered = [
			'myapp' => ['INVALID_HASH' => ['x.php' => ['current' => 'c']]],
			'core' => ['EXTRA_FILE' => ['a.php' => ['current' => '1'], 'b.php' => ['current' => '2']]],
		];

		$this->assertSame($this->fingerprint($result), $this->fingerprint($reordered));
	}

	public function testFingerprintChangesWhenFailingFileChangesAgain(): void {
		$changedAgain = self::FAILED_RESULT;
		$changedAgain['core']['EXTRA_FILE']['core/extra.php']['current'] = 'def';

		$this->assertNotSame($this->fingerprint(self::FAILED_RESULT), $this->fingerprint($changedAgain));
	}

	public function testSummarizeSeparatesFilesFromUnverifiedScopes(): void {
		$result = [
			'myapp' => ['EXTRA_FILE' => ['extra1.php' => [], 'extra2.php' => []], 'INVALID_HASH' => ['lib/Changed.php' => []]],
			'files' => ['EXCEPTION' => ['class' => 'X', 'message' => 'Signature data not found.']],
			'core' => ['EXCEPTION' => ['class' => 'X', 'message' => 'Certificate is not valid.']],
		];

		$this->assertSame(
			['files' => 3, 'unverified' => ['core', 'files']],
			self::invokePrivate($this->job, 'summarize', [$result]),
		);
	}

	public static function dataFormatSummary(): array {
		return [
			'files only' => [['files' => 2, 'unverified' => []], ['2 files do not match the signed release. They may have been modified or added without authorization.']],
			'unverified only' => [['files' => 0, 'unverified' => ['core']], ['The signature of core is missing or invalid, so it could not be verified.']],
			'both' => [['files' => 1, 'unverified' => ['core', 'files']], [
				'1 file does not match the signed release. It may have been modified or added without authorization.',
				'The signatures of core, files are missing or invalid, so they could not be verified.',
			]],
			'missing parameters' => [[], []],
		];
	}

	#[DataProvider('dataFormatSummary')]
	public function testFormatSummary(array $summary, array $expected): void {
		$this->assertSame($expected, CheckCodeIntegrityJob::formatSummary($this->l10n, $summary));
	}
}
