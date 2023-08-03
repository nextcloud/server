<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
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
namespace OCA\Settings\Sections\Admin;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class ArtificialIntelligence implements IIconSection {

	/** @var IL10N */
	private $l;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IL10N $l, IURLGenerator $urlGenerator) {
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('settings', 'ai.svg');
	}

	public function getID(): string {
		return 'ai';
	}

	public function getName(): string {
		return $this->l->t('Artificial Intelligence');
	}

	public function getPriority(): int {
		return 40;
	}
}
