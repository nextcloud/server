<?php

namespace OCP\LanguageModel;

use RuntimeException;

final class TopicsTask extends AbstractLanguageModelTask {
	public const TYPE = 'topics';

	/**
	 * @param ILanguageModelProvider $provider
	 * @throws RuntimeException
	 * @return string
	 */
	public function visitProvider(ILanguageModelProvider $provider): string {
		if (!$provider instanceof ITopicsProvider) {
			throw new \RuntimeException('SummaryTask#visitProvider expects IHeadlineProvider');
		}
		return $provider->findTopics($this->getInput());
	}

	public function canUseProvider(ILanguageModelProvider $provider): bool {
		return $provider instanceof ITopicsProvider;
	}

	public function getType(): string {
		return self::TYPE;
	}
}
