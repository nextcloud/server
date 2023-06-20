<?php

namespace OCP\LanguageModel;

use RuntimeException;

final class HeadlineTask extends AbstractLanguageModelTask {
	public const TYPE = 'headline';

	/**
	 * @param ILanguageModelProvider $provider
	 * @throws RuntimeException
	 * @return string
	 */
	public function visitProvider(ILanguageModelProvider $provider): string {
		if (!$provider instanceof IHeadlineProvider) {
			throw new \RuntimeException('SummaryTask#visitProvider expects IHeadlineProvider');
		}
		return $provider->findHeadline($this->getInput());
	}

	public function canUseProvider(ILanguageModelProvider $provider): bool {
		return $provider instanceof IHeadlineProvider;
	}

	public function getType(): string {
		return self::TYPE;
	}
}
