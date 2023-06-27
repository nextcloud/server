<?php

namespace OCP\LanguageModel;

/**
 * This is an absctract LanguageModel Task represents summarization
 * which sums up the passed text.
 * @since 28.0.0
 * @template-extends AbstractLanguageModelTask<ISummaryProvider>
 */
final class SummaryTask extends AbstractLanguageModelTask {
	/**
	 * @since 28.0.0
	 */
	public const TYPE = 'summarize';

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function visitProvider($provider): string {
		if (!$this->canUseProvider($provider)) {
			throw new \RuntimeException('SummaryTask#visitProvider expects ISummaryProvider');
		}
		return $provider->summarize($this->getInput());
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function canUseProvider($provider): bool {
		return $provider instanceof ISummaryProvider;
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function getType(): string {
		return self::TYPE;
	}
}
