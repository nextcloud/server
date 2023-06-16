<?php

namespace OCP\LanguageModel;

use RuntimeException;

final class FreePromptTask extends AbstractLanguageModelTask {
	public const TYPE = 'free_prompt';

	/**
	 * @param ILanguageModelProvider $provider
	 * @throws RuntimeException
	 * @return string
	 */
	public function visitProvider(ILanguageModelProvider $provider): string {
		return $provider->prompt($this->getInput());
	}

	public function canUseProvider(ILanguageModelProvider $provider): bool {
		return true;
	}

	public function getType(): string {
		return self::TYPE;
	}
}
