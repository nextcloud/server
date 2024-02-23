<?php

namespace OCP\SpeechToText;

/**
 * @since 28.0.0
 */
interface ISpeechToTextProviderWithId extends ISpeechToTextProvider {

	/**
	 * @since 28.0.0
	 */
	public function getId(): string;
}
