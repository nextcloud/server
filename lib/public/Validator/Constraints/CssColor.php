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

use OCP\Validator\Violation;

/**
 * Constraint that validate that a value is a CSS3 compatible color.
 */
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

	private const PATTERN_HEX_LONG = '/^#[0-9a-f]{6}$/i';
	private const PATTERN_HEX_LONG_WITH_ALPHA = '/^#[0-9a-f]{8}$/i';
	private const PATTERN_HEX_SHORT = '/^#[0-9a-f]{3}$/i';
	private const PATTERN_HEX_SHORT_WITH_ALPHA = '/^#[0-9a-f]{4}$/i';
	// List comes from https://www.w3.org/wiki/CSS/Properties/color/keywords#Basic_Colors
	private const PATTERN_BASIC_NAMED_COLORS = '/^(black|silver|gray|white|maroon|red|purple|fuchsia|green|lime|olive|yellow|navy|blue|teal|aqua)$/i';
	// List comes from https://www.w3.org/wiki/CSS/Properties/color/keywords#Extended_colors
	private const PATTERN_EXTENDED_NAMED_COLORS = '/^(aliceblue|antiquewhite|aqua|aquamarine|azure|beige|bisque|black|blanchedalmond|blue|blueviolet|brown|burlywood|cadetblue|chartreuse|chocolate|coral|cornflowerblue|cornsilk|crimson|cyan|darkblue|darkcyan|darkgoldenrod|darkgray|darkgreen|darkgrey|darkkhaki|darkmagenta|darkolivegreen|darkorange|darkorchid|darkred|darksalmon|darkseagreen|darkslateblue|darkslategray|darkslategrey|darkturquoise|darkviolet|deeppink|deepskyblue|dimgray|dimgrey|dodgerblue|firebrick|floralwhite|forestgreen|fuchsia|gainsboro|ghostwhite|gold|goldenrod|gray|green|greenyellow|grey|honeydew|hotpink|indianred|indigo|ivory|khaki|lavender|lavenderblush|lawngreen|lemonchiffon|lightblue|lightcoral|lightcyan|lightgoldenrodyellow|lightgray|lightgreen|lightgrey|lightpink|lightsalmon|lightseagreen|lightskyblue|lightslategray|lightslategrey|lightsteelblue|lightyellow|lime|limegreen|linen|magenta|maroon|mediumaquamarine|mediumblue|mediumorchid|mediumpurple|mediumseagreen|mediumslateblue|mediumspringgreen|mediumturquoise|mediumvioletred|midnightblue|mintcream|mistyrose|moccasin|navajowhite|navy|oldlace|olive|olivedrab|orange|orangered|orchid|palegoldenrod|palegreen|paleturquoise|palevioletred|papayawhip|peachpuff|peru|pink|plum|powderblue|purple|red|rosybrown|royalblue|saddlebrown|salmon|sandybrown|seagreen|seashell|sienna|silver|skyblue|slateblue|slategray|slategrey|snow|springgreen|steelblue|tan|teal|thistle|tomato|turquoise|violet|wheat|white|whitesmoke|yellow|yellowgreen)$/i';
	// List comes from https://drafts.csswg.org/css-color/#css-system-colors
	private const PATTERN_SYSTEM_COLORS = '/^(Canvas|CanvasText|LinkText|VisitedText|ActiveText|ButtonFace|ButtonText|ButtonBorder|Field|FieldText|Highlight|HighlightText|SelectedItem|SelectedItemText|Mark|MarkText|GrayText)$/i';
	private const PATTERN_KEYWORDS = '/^(transparent|currentColor)$/i';
	private const PATTERN_RGB = '/^rgb\(\s*(0|255|25[0-4]|2[0-4]\d|1\d\d|0?\d?\d),\s*(0|255|25[0-4]|2[0-4]\d|1\d\d|0?\d?\d),\s*(0|255|25[0-4]|2[0-4]\d|1\d\d|0?\d?\d)\s*\)$/i';
	private const PATTERN_RGBA = '/^rgba\(\s*(0|255|25[0-4]|2[0-4]\d|1\d\d|0?\d?\d),\s*(0|255|25[0-4]|2[0-4]\d|1\d\d|0?\d?\d),\s*(0|255|25[0-4]|2[0-4]\d|1\d\d|0?\d?\d),\s*(0|0?\.\d+|1(\.0)?)\s*\)$/i';
	private const PATTERN_HSL = '/^hsl\(\s*(0|360|35\d|3[0-4]\d|[12]\d\d|0?\d?\d),\s*(0|100|\d{1,2})%,\s*(0|100|\d{1,2})%\s*\)$/i';
	private const PATTERN_HSLA = '/^hsla\(\s*(0|360|35\d|3[0-4]\d|[12]\d\d|0?\d?\d),\s*(0|100|\d{1,2})%,\s*(0|100|\d{1,2})%,\s*(0|0?\.\d+|1(\.0)?)\s*\)$/i';

	private const COLOR_PATTERNS = [
		CssColor::HEX_LONG => self::PATTERN_HEX_LONG,
		CssColor::HEX_LONG_WITH_ALPHA => self::PATTERN_HEX_LONG_WITH_ALPHA,
		CssColor::HEX_SHORT => self::PATTERN_HEX_SHORT,
		CssColor::HEX_SHORT_WITH_ALPHA => self::PATTERN_HEX_SHORT_WITH_ALPHA,
		CssColor::BASIC_NAMED_COLORS => self::PATTERN_BASIC_NAMED_COLORS,
		CssColor::EXTENDED_NAMED_COLORS => self::PATTERN_EXTENDED_NAMED_COLORS,
		CssColor::SYSTEM_COLORS => self::PATTERN_SYSTEM_COLORS,
		CssColor::KEYWORDS => self::PATTERN_KEYWORDS,
		CssColor::RGB => self::PATTERN_RGB,
		CssColor::RGBA => self::PATTERN_RGBA,
		CssColor::HSL => self::PATTERN_HSL,
		CssColor::HSLA => self::PATTERN_HSLA,
	];

	private array $formats;

	/**
	 * @param string|null $message The violation message displayed to the user
	 * @param array|null $formats The list of allowed color formats, by default all
	 */
	public function __construct(?string $message, ?array $formats) {
		parent::__construct();
		$this->message = $message ?? $this->l10n->t('"{{ value }}" is not a valid email address');
		$this->formats = $formats ?? [
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


	public function validate($value): array {
		if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
			throw new \RuntimeException('The CssColorValidator can only validate scalar values or object convertible to string.');
		}

		foreach ($this->getFormats() as $regex) {
			if (preg_match(self::COLOR_PATTERNS[$regex], (string)$value)) {
				return [];
			}
		}

		return [
			(new Violation($this->getMessage()))->addParameter('{{ value }}', (string)$value),
		];
	}
}
