<?php

namespace OCP\LanguageModel;

/**
 * @since 28.0.0
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
	public function visitProvider(ILanguageModelProvider $provider): string {
		if (!$provider instanceof IHeadlineProvider) {
			throw new \RuntimeException('SummaryTask#visitProvider expects IHeadlineProvider');
		}
		return $provider->findHeadline($this->getInput());
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function canUseProvider(ILanguageModelProvider $provider): bool {
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
