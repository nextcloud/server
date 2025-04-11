<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Service;

use OCA\WorkflowEngine\AppInfo\Application;
use OCA\WorkflowEngine\Helper\LogContext;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Log\IDataLogger;
use OCP\Log\ILogFactory;
use Psr\Log\LoggerInterface;

class Logger {
	protected ?LoggerInterface $flowLogger = null;

	public function __construct(
		protected LoggerInterface $generalLogger,
		private IConfig $config,
		private ILogFactory $logFactory,
	) {
		$this->initLogger();
	}

	protected function initLogger(): void {
		$default = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/flow.log';
		$logFile = trim((string)$this->config->getAppValue(Application::APP_ID, 'logfile', $default));
		if ($logFile !== '') {
			$this->flowLogger = $this->logFactory->getCustomPsrLogger($logFile);
		}
	}

	public function logFlowRequests(LogContext $logContext) {
		$message = 'Flow activation: rules were requested for operation {op}';
		$context = ['op' => $logContext->getDetails()['operation']['name'], 'level' => ILogger::DEBUG];

		$logContext->setDescription('Flow activation: rules were requested');

		$this->log($message, $context, $logContext);
	}

	public function logScopeExpansion(LogContext $logContext) {
		$message = 'Flow rule of a different user is legit for operation {op}';
		$context = ['op' => $logContext->getDetails()['operation']['name']];

		$logContext->setDescription('Flow rule of a different user is legit');

		$this->log($message, $context, $logContext);
	}

	public function logPassedCheck(LogContext $logContext) {
		$message = 'Flow rule qualified to run {op}, config: {config}';
		$context = [
			'op' => $logContext->getDetails()['operation']['name'],
			'config' => $logContext->getDetails()['configuration'],
			'level' => ILogger::DEBUG,
		];

		$logContext->setDescription('Flow rule qualified to run');

		$this->log($message, $context, $logContext);
	}

	public function logRunSingle(LogContext $logContext) {
		$message = 'Last qualified flow configuration is going to run {op}';
		$context = [
			'op' => $logContext->getDetails()['operation']['name'],
		];

		$logContext->setDescription('Last qualified flow configuration is going to run');

		$this->log($message, $context, $logContext);
	}

	public function logRunAll(LogContext $logContext) {
		$message = 'All qualified flow configurations are going to run {op}';
		$context = [
			'op' => $logContext->getDetails()['operation']['name'],
		];

		$logContext->setDescription('All qualified flow configurations are going to run');

		$this->log($message, $context, $logContext);
	}

	public function logRunNone(LogContext $logContext) {
		$message = 'No flow configurations is going to run {op}';
		$context = [
			'op' => $logContext->getDetails()['operation']['name'],
			'level' => ILogger::DEBUG,
		];

		$logContext->setDescription('No flow configurations is going to run');

		$this->log($message, $context, $logContext);
	}

	public function logEventInit(LogContext $logContext) {
		$message = 'Flow activated by event {ev}';

		$context = [
			'ev' => $logContext->getDetails()['eventName'],
			'level' => ILogger::DEBUG,
		];

		$logContext->setDescription('Flow activated by event');

		$this->log($message, $context, $logContext);
	}

	public function logEventDone(LogContext $logContext) {
		$message = 'Flow handling done for event {ev}';

		$context = [
			'ev' => $logContext->getDetails()['eventName'],
		];

		$logContext->setDescription('Flow handling for event done');

		$this->log($message, $context, $logContext);
	}

	protected function log(
		string $message,
		array $context,
		LogContext $logContext,
	): void {
		if (!isset($context['app'])) {
			$context['app'] = Application::APP_ID;
		}
		if (!isset($context['level'])) {
			$context['level'] = ILogger::INFO;
		}
		$this->generalLogger->log($context['level'], $message, $context);

		if (!$this->flowLogger instanceof IDataLogger) {
			return;
		}

		$details = $logContext->getDetails();
		$this->flowLogger->logData(
			$details['message'],
			$details,
			['app' => Application::APP_ID, 'level' => $context['level']]
		);
	}
}
