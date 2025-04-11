<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\L10N;

class L10NString implements \JsonSerializable {
	/** @var L10N */
	protected $l10n;

	/** @var string */
	protected $text;

	/** @var array */
	protected $parameters;

	/** @var integer */
	protected $count;

	/**
	 * @param L10N $l10n
	 * @param string|string[] $text
	 * @param array $parameters
	 * @param int $count
	 */
	public function __construct(L10N $l10n, $text, array $parameters, int $count = 1) {
		$this->l10n = $l10n;
		$this->text = $text;
		$this->parameters = $parameters;
		$this->count = $count;
	}

	public function __toString(): string {
		$translations = $this->l10n->getTranslations();
		$identityTranslator = $this->l10n->getIdentityTranslator();

		// Use the indexed version as per \Symfony\Contracts\Translation\TranslatorInterface
		$identity = $this->text;
		if (array_key_exists($this->text, $translations)) {
			$identity = $translations[$this->text];
		}

		if (is_array($identity)) {
			$pipeCheck = implode('', $identity);
			if (str_contains($pipeCheck, '|')) {
				return 'Can not use pipe character in translations';
			}

			$identity = implode('|', $identity);
		} elseif (str_contains($identity, '|')) {
			return 'Can not use pipe character in translations';
		}

		$beforeIdentity = $identity;
		$identity = str_replace('%n', '%count%', $identity);

		$parameters = [];
		if ($beforeIdentity !== $identity) {
			$parameters = ['%count%' => $this->count];
		}

		// $count as %count% as per \Symfony\Contracts\Translation\TranslatorInterface
		$text = $identityTranslator->trans($identity, $parameters);

		return vsprintf($text, $this->parameters);
	}

	public function jsonSerialize(): string {
		return $this->__toString();
	}
}
