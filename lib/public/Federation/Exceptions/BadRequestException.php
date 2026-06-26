<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation\Exceptions;

use OCP\HintException;

/**
 * Class BadRequestException
 *
 *
 * @since 14.0.0
 */
class BadRequestException extends HintException {
	/**
	 * @var string[] $parameterList
	 */
	private $parameterList;

	/**
	 * BadRequestException constructor.
	 *
	 * @since 14.0.0
	 *
	 * @param array $missingParameters
	 */
	public function __construct(array $missingParameters) {
		$l = \OCP\Util::getL10N('federation');
		$this->parameterList = $missingParameters;
		$parameterList = implode(',', $missingParameters);
		$message = 'Parameters missing in order to complete the request. Missing Parameters: ' . $parameterList;
		$hint = $l->t('Parameters missing in order to complete the request. Missing Parameters: "%s"', [$parameterList]);
		parent::__construct($message, $hint);
	}

	/**
	 * get array with the return message as defined in the OCM API
	 *
	 * @since 14.0.0
	 *
	 * @return array{message: string, validationErrors: array{message: string, name: string}[]}
	 */
	public function getReturnMessage() {
		$result = [
			'message' => 'RESOURCE_NOT_FOUND',
			'validationErrors' => [
			]
		];

		foreach ($this->parameterList as $missingParameter) {
			$result['validationErrors'][] = [
				'name' => $missingParameter,
				'message' => 'NOT_FOUND'
			];
		}

		return $result;
	}
}
