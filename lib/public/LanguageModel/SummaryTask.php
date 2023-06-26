<?php

namespace OCP\LanguageModel;

/**
 * This LanguageModel Task represents summarization
 * which sums up the passed text.
 * @since 28.0.0
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
	public function visitProvider(ILanguageModelProvider $provider): string {
		if (!$provider instanceof ISummaryProvider) {
			throw new \RuntimeException('SummaryTask#visitProvider expects ISummaryProvider');
		}
		return $provider->summarize($this->getInput());
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function canUseProvider(ILanguageModelProvider $provider): bool {
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
