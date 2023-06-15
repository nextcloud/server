<?php

namespace OCP\LanguageModel;

use RuntimeException;

class FreePromptTask extends AbstractLanguageModelTask {

	/**
	 * @param ILanguageModelProvider $provider
	 * @throws RuntimeException
	 * @return string
	 */
	public function visitProvider(ILanguageModelProvider $provider): string {
		$this->setStatus(self::STATUS_RUNNING);
		try {
			$output = $provider->prompt($this->getInput());
		} catch (RuntimeException $e) {
			$this->setStatus(self::STATUS_FAILED);
			throw $e;
		}
		$this->setStatus(self::STATUS_SUCCESSFUL);
		return $output;
	}
}
