<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
namespace OCA\Theming\Themes;

use OCA\Theming\Util;

trait CommonThemeTrait {
	public Util $util;

	/**
	 * Generate primary-related variables
	 * This is shared between multiple themes because colorMainBackground and colorMainText
	 * will change in between.
	 */
	protected function generatePrimaryVariables(string $colorMainBackground, string $colorMainText): array {
		$colorPrimaryLight = $this->util->mix($this->primaryColor, $colorMainBackground, -80);
		$colorPrimaryElement = $this->util->elementColor($this->primaryColor);
		$colorPrimaryElementLight = $this->util->mix($colorPrimaryElement, $colorMainBackground, -80);

		// primary related colours
		return [
			'--color-primary' => $this->primaryColor,
			'--color-primary-default' => $this->defaultPrimaryColor,
			'--color-primary-text' => $this->util->invertTextColor($this->primaryColor) ? '#000000' : '#ffffff',
			'--color-primary-hover' => $this->util->mix($this->primaryColor, $colorMainBackground, 60),
			'--color-primary-light' => $colorPrimaryLight,
			'--color-primary-light-text' => $this->util->mix($this->primaryColor, $this->util->invertTextColor($colorPrimaryLight) ? '#000000' : '#ffffff', -20),
			'--color-primary-light-hover' => $this->util->mix($colorPrimaryLight, $colorMainText, 90),
			'--color-primary-text-dark' => $this->util->darken($this->util->invertTextColor($this->primaryColor) ? '#000000' : '#ffffff', 7),

			// used for buttons, inputs...
			'--color-primary-element' => $colorPrimaryElement,
			'--color-primary-element-text' => $this->util->invertTextColor($colorPrimaryElement) ? '#000000' : '#ffffff',
			'--color-primary-element-hover' => $this->util->mix($colorPrimaryElement, $colorMainBackground, 60),
			'--color-primary-element-light' => $colorPrimaryElementLight,
			'--color-primary-element-light-text' => $this->util->mix($colorPrimaryElement, $this->util->invertTextColor($colorPrimaryElementLight) ? '#000000' : '#ffffff', -20),
			'--color-primary-element-light-hover' => $this->util->mix($colorPrimaryElementLight, $colorMainText, 90),
			'--color-primary-element-text-dark' => $this->util->darken($this->util->invertTextColor($colorPrimaryElement) ? '#000000' : '#ffffff', 7),

			// to use like this: background-image: var(--gradient-primary-background);
			'--gradient-primary-background' => 'linear-gradient(40deg, var(--color-primary) 0%, var(--color-primary-hover) 100%)',
		];
	}
}
