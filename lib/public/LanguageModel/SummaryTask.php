<?php

namespace OCP\LanguageModel;

use RuntimeException;

class SummaryTask extends AbstractLanguageModelTask {

	/**
	 * @param ILanguageModelProvider&ISummaryProvider $provider
	 * @throws RuntimeException
	 * @return string
	 */
	public function visitProvider(ILanguageModelProvider $provider): string {
		if (!$provider instanceof ISummaryProvider) {
			throw new \RuntimeException('SummaryTask#visitProvider expects ISummaryProvider');
		}
		$this->setStatus(self::STATUS_RUNNING);
		try {
			$output = $provider->summarize($this->getInput());
		} catch (RuntimeException $e) {
			$this->setStatus(self::STATUS_FAILED);
			throw $e;
		}
		$this->setStatus(self::STATUS_SUCCESSFUL);
		return $output;
	}
}
