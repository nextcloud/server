<?php

namespace OCP\LanguageModel;

use RuntimeException;

/**
 * @since 28.0.0
 */
final class SummaryTask extends AbstractLanguageModelTask {
	/**
	 * @since 28.0.0
	 */
	public const TYPE = 'summarize';

	/**
	 * @inheritDoc
	 */
	public function visitProvider(ILanguageModelProvider $provider): string {
		if (!$provider instanceof ISummaryProvider) {
			throw new \RuntimeException('SummaryTask#visitProvider expects ISummaryProvider');
		}
		return $provider->summarize($this->getInput());
	}

	/**
	 * @inheritDoc
	 */
	public function canUseProvider(ILanguageModelProvider $provider): bool {
		return $provider instanceof ISummaryProvider;
	}

	/**
	 * @inheritDoc
	 */
	public function getType(): string {
		return self::TYPE;
	}
}
