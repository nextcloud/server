<?php

namespace OCP\LanguageModel;

use RuntimeException;

final class SummaryTask extends AbstractLanguageModelTask {
	public const TYPE = 'summarize';

	/**
	 * @param ILanguageModelProvider&ISummaryProvider $provider
	 * @throws RuntimeException
	 * @return string
	 */
	public function visitProvider(ILanguageModelProvider $provider): string {
		if (!$provider instanceof ISummaryProvider) {
			throw new \RuntimeException('SummaryTask#visitProvider expects ISummaryProvider');
		}
		return $provider->summarize($this->getInput());
	}

	public function canUseProvider(ILanguageModelProvider $provider): bool {
		return $provider instanceof ISummaryProvider;
	}

	public function getType(): string {
		return self::TYPE;
	}
}
