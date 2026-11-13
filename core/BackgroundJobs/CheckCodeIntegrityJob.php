<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Core\AppInfo\ConfigLexicon;
use OC\IntegrityCheck\Checker;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Notification\IManager as INotificationManager;
use Psr\Log\LoggerInterface;

/**
 * Re-runs the code integrity check daily and notifies and emails admins whenever
 * its result differs from the one they were last notified about.
 * Disabled by setting `integrity.check.scheduled` to false.
 */
class CheckCodeIntegrityJob extends TimedJob {
	public const NOTIFICATION_OBJECT_TYPE = 'code_integrity';

	public function __construct(
		ITimeFactory $time,
		private readonly Checker $checker,
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly IGroupManager $groupManager,
		private readonly INotificationManager $notificationManager,
		private readonly IMailer $mailer,
		private readonly IFactory $l10nFactory,
		private readonly IURLGenerator $urlGenerator,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);

		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
		$this->setAllowParallelRuns(false);
	}

	#[\Override]
	protected function run($argument): void {
		if (!$this->config->getSystemValueBool('integrity.check.scheduled', true)
			|| !$this->checker->isCodeCheckEnforced()) {
			return;
		}

		$this->checker->runInstanceVerification();
		$results = $this->checker->getResults() ?? [];

		$fingerprint = $this->fingerprint($results);
		$notifiedFingerprint = $this->appConfig->getValueString('core', ConfigLexicon::INTEGRITY_CHECK_NOTIFIED_RESULT, lazy: true);
		if ($fingerprint === $notifiedFingerprint) {
			return;
		}

		if ($notifiedFingerprint !== '') {
			$notification = $this->notificationManager->createNotification();
			$notification->setApp('core')
				->setObject(self::NOTIFICATION_OBJECT_TYPE, $notifiedFingerprint);
			$this->notificationManager->markProcessed($notification);
		}

		if ($fingerprint !== '') {
			$this->notifyAdmins($fingerprint, $this->summarize($results));
		}

		$this->appConfig->setValueString('core', ConfigLexicon::INTEGRITY_CHECK_NOTIFIED_RESULT, $fingerprint, lazy: true);
	}

	/**
	 * Stable hash of the check result, or an empty string when the check passed
	 */
	private function fingerprint(array $results): string {
		$entries = [];
		foreach ($results as $scope => $scopeResult) {
			foreach ($scopeResult as $type => $details) {
				if ($type === 'EXCEPTION') {
					$entries[] = json_encode([$scope, $type, $details['message'] ?? ''], JSON_THROW_ON_ERROR);
					continue;
				}
				foreach ($details as $file => $hashes) {
					$entries[] = json_encode([$scope, $type, $file, $hashes['current'] ?? ''], JSON_THROW_ON_ERROR);
				}
			}
		}

		if ($entries === []) {
			return '';
		}

		// Scope and file order depend on filesystem iteration order
		sort($entries);
		return hash('sha256', implode("\n", $entries));
	}

	/**
	 * Number of files that failed, and the scopes (core or app ids) whose
	 * signature could not be verified at all
	 *
	 * @return array{files: int, unverified: list<string>}
	 */
	private function summarize(array $results): array {
		$files = 0;
		$unverified = [];
		foreach ($results as $scope => $scopeResult) {
			foreach ($scopeResult as $type => $entries) {
				if ($type === 'EXCEPTION') {
					$unverified[] = (string)$scope;
				} elseif (is_array($entries)) {
					$files += count($entries);
				}
			}
		}
		sort($unverified);
		return ['files' => $files, 'unverified' => $unverified];
	}

	/**
	 * Human readable sentences for a summary, shared by the notification and the email
	 *
	 * @param array{files?: int, unverified?: list<string>} $summary
	 * @return list<string>
	 */
	public static function formatSummary(IL10N $l, array $summary): array {
		$files = (int)($summary['files'] ?? 0);
		$unverified = $summary['unverified'] ?? [];

		$sentences = [];
		if ($files > 0) {
			$sentences[] = $l->n(
				'%n file does not match the signed release. It may have been modified or added without authorization.',
				'%n files do not match the signed release. They may have been modified or added without authorization.',
				$files,
			);
		}
		if ($unverified !== []) {
			$sentences[] = $l->n(
				'The signature of %s is missing or invalid, so it could not be verified.',
				'The signatures of %s are missing or invalid, so they could not be verified.',
				count($unverified),
				[implode(', ', $unverified)],
			);
		}
		return $sentences;
	}

	/**
	 * @param array{files: int, unverified: list<string>} $summary
	 */
	private function notifyAdmins(string $fingerprint, array $summary): void {
		$admins = $this->groupManager->get('admin')?->getUsers() ?? [];
		if ($admins === []) {
			return;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('core')
			->setDateTime($this->time->getDateTime())
			->setObject(self::NOTIFICATION_OBJECT_TYPE, $fingerprint)
			->setSubject('code_integrity_changed', $summary);

		foreach ($admins as $admin) {
			$notification->setUser($admin->getUID());
			$this->notificationManager->notify($notification);
			$this->sendMail($admin, $summary);
		}
	}

	/**
	 * @param array{files: int, unverified: list<string>} $summary
	 */
	private function sendMail(IUser $admin, array $summary): void {
		$email = $admin->getEMailAddress();
		if ($email === null || $email === '') {
			return;
		}

		$l = $this->l10nFactory->get('core', $this->l10nFactory->getUserLanguage($admin));

		$template = $this->mailer->createEMailTemplate('core.CodeIntegrityChanged', $summary);
		$template->setSubject($l->t('The code integrity check result has changed'));
		$template->addHeader();
		$template->addHeading($l->t('The code integrity check result has changed'));
		foreach (self::formatSummary($l, $summary) as $sentence) {
			$template->addBodyText($sentence);
		}
		$template->addBodyButton(
			$l->t('Review integrity check results'),
			$this->urlGenerator->linkToRouteAbsolute('settings.AdminSettings.index', ['section' => 'overview']),
		);
		$template->addFooter();

		$message = $this->mailer->createMessage();
		$message->setTo([$email => $admin->getDisplayName()]);
		$message->useTemplate($template);

		try {
			$this->mailer->send($message);
		} catch (\Exception $e) {
			$this->logger->error('Could not send code integrity email to ' . $admin->getUID(), ['exception' => $e]);
		}
	}
}
