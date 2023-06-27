<?php

namespace OCP\LanguageModel;

/**
 * @since 28.0.0
 * @template-extends AbstractLanguageModelTask<ILanguageModelProvider>
 */
final class FreePromptTask extends AbstractLanguageModelTask {
	/**
	 * @since 28.0.0
	 */
	public const TYPE = 'free_prompt';

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function visitProvider($provider): string {
		return $provider->prompt($this->getInput());
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function canUseProvider($provider): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function getType(): string {
		return self::TYPE;
	}
}
