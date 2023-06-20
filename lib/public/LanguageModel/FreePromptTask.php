<?php

namespace OCP\LanguageModel;

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

	/**
	 * @inheritDoc
	 */
	public function canUseProvider(ILanguageModelProvider $provider): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getType(): string {
		return self::TYPE;
	}
}
