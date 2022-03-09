<?php
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @copyright Mathieu Santostefano <msantostefano@protonmail.com>
 *
 * @license AGPL-3.0-or-later AND MIT
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Validator\Constraints;

class CssColor extends Constraint {
	public const HEX_LONG = 'hex_long';
	public const HEX_LONG_WITH_ALPHA = 'hex_long_with_alpha';
	public const HEX_SHORT = 'hex_short';
	public const HEX_SHORT_WITH_ALPHA = 'hex_short_with_alpha';
	public const BASIC_NAMED_COLORS = 'basic_named_colors';
	public const EXTENDED_NAMED_COLORS = 'extended_named_colors';
	public const SYSTEM_COLORS = 'system_colors';
	public const KEYWORDS = 'keywords';
	public const RGB = 'rgb';
	public const RGBA = 'rgba';
	public const HSL = 'hsl';
	public const HSLA = 'hsla';
	private string $message;

	private array $formats;

	/**
	 * @param array{formats?: string[], message?: string} $options
	 */
	public function __construct(array $options = []) {
		parent::__construct();
		$this->message = $options['message'] ?? $this->l10n->t('"{{ value }}" is not a valid email address');
		$this->formats = $options['formats'] ?? [
			self::HEX_LONG,
			self::HEX_LONG_WITH_ALPHA,
			self::HEX_SHORT,
			self::HEX_SHORT_WITH_ALPHA,
			self::BASIC_NAMED_COLORS,
			self::EXTENDED_NAMED_COLORS,
			self::SYSTEM_COLORS,
			self::KEYWORDS,
			self::RGB,
			self::RGBA,
			self::HSL,
			self::HSLA,
		];
	}

	public function getMessage(): string {
		return $this->message;
	}

	/**
	 * @return string[]
	 */
	public function getFormats(): array {
		return $this->formats;
	}
}
