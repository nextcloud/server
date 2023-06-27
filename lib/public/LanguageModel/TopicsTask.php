<?php

namespace OCP\LanguageModel;

/**
 * This LanguageModel Task represents topics synthesis
 * which outputs comma-separated topics for the passed text
 * @since 28.0.0
 * @template-extends AbstractLanguageModelTask<ITopicsProvider>
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
	public function visitProvider($provider): string {
		if (!$this->canUseProvider($provider)) {
			throw new \RuntimeException('TopicsTask#visitProvider expects ITopicsProvider');
		}
		return $provider->findTopics($this->getInput());
	}

	/**
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function canUseProvider($provider): bool {
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
