<?php

namespace OCP\LanguageModel;

use RuntimeException;

/**
 * @since 28.0.0
 */
final class FreePromptTask extends AbstractLanguageModelTask {
	/**
	 * @since 28.0.0
	 */
	public const TYPE = 'free_prompt';

	/**
	 * @inheritDoc
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
