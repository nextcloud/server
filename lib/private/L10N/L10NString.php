<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\L10N;

class L10NString implements \JsonSerializable {
	/**
	 * @param string|string[] $text
	 */
	public function __construct(
		protected L10N $l10n,
		protected string|array $text,
		protected array $parameters,
		protected int $count = 1,
	) {
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
