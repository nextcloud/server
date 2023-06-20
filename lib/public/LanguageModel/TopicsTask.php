<?php

namespace OCP\LanguageModel;

/**
 * @since 28.0.0
 */
final class TopicsTask extends AbstractLanguageModelTask {
	/**
	 * @since 28.0.0
	 */
	public const TYPE = 'topics';

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function visitProvider(ILanguageModelProvider $provider): string {
		if (!$provider instanceof ITopicsProvider) {
			throw new \RuntimeException('SummaryTask#visitProvider expects IHeadlineProvider');
		}
		return $provider->findTopics($this->getInput());
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function canUseProvider(ILanguageModelProvider $provider): bool {
		return $provider instanceof ITopicsProvider;
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function getType(): string {
		return self::TYPE;
	}
}
