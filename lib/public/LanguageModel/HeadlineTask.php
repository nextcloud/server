<?php

namespace OCP\LanguageModel;

/**
 * This LanguageModel Task represents headline generation
 * which generates a headline for the passed text
 * @since 28.0.0
 * @template-extends AbstractLanguageModelTask<IHeadlineProvider>
 */
final class HeadlineTask extends AbstractLanguageModelTask {
	/**
	 * @since 28.0.0
	 */
	public const TYPE = 'headline';

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function visitProvider($provider): string {
		if (!$this->canUseProvider($provider)) {
			throw new \RuntimeException('HeadlineTask#visitProvider expects IHeadlineProvider');
		}
		return $provider->findHeadline($this->getInput());
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function canUseProvider($provider): bool {
		return $provider instanceof IHeadlineProvider;
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function getType(): string {
		return self::TYPE;
	}
}
