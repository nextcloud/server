<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Testing\TaskProcessing;

use OCA\Testing\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\TaskTypes\TextToTextChat;
use RuntimeException;

class FakeTextToTextChatProvider implements ISynchronousProvider {

	public function __construct(
		protected IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getId(): string {
		return Application::APP_ID . '-text2textchat';
	}

	#[\Override]
	public function getName(): string {
		return 'Fake text2text chat task processing provider';
	}

	#[\Override]
	public function getTaskTypeId(): string {
		return TextToTextChat::ID;
	}

	#[\Override]
	public function getExpectedRuntime(): int {
		return 1;
	}

	#[\Override]
	public function getInputShapeEnumValues(): array {
		return [];
	}

	#[\Override]
	public function getInputShapeDefaults(): array {
		return [];
	}

	#[\Override]
	public function getOptionalInputShape(): array {
		return [];
	}

	#[\Override]
	public function getOptionalInputShapeEnumValues(): array {
		return [];
	}

	#[\Override]
	public function getOptionalInputShapeDefaults(): array {
		return [];
	}

	#[\Override]
	public function getOptionalOutputShape(): array {
		return [];
	}

	#[\Override]
	public function getOutputShapeEnumValues(): array {
		return [];
	}

	#[\Override]
	public function getOptionalOutputShapeEnumValues(): array {
		return [];
	}

	#[\Override]
	public function process(?string $userId, array $input, callable $reportProgress): array {
		if ($this->appConfig->getAppValueBool('fail-' . $this->getId())) {
			throw new ProcessingException('Failing as set by AppConfig');
		}

		if (!isset($input['system_prompt']) || !is_string($input['system_prompt'])) {
			throw new RuntimeException('Invalid system prompt');
		}

		if (!isset($input['input']) || !is_string($input['input'])) {
			throw new RuntimeException('Invalid input message');
		}

		if (!isset($input['history']) || !is_array($input['history'])) {
			throw new RuntimeException('Invalid message history');
		}

		return [
			'output' => 'This is a fake response message: '
				. "\n\n- System prompt: " . $input['system_prompt']
				. "\n- Input message: " . $input['input']
				. "\n- Message history:\n" . count($input['history']) . ' messages',
		];
	}
}
