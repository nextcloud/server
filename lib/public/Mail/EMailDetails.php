<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Mail;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Card describing an item (share, event, conversation...) in an email,
 * rendered by {@see IEMailTemplate::addBodyDetails()}.
 *
 * All values are plain text and escaped by the template.
 *
 * Example:
 *
 * $details = new EMailDetails('Q3 board review');
 * $details->setSubtitle('Thursday, October 15, 2026')
 *     ->setDateBadge('Oct', '15');
 * $details->addRow('Where')
 *     ->text('Meeting room 2')
 *     ->link('Join in Nextcloud Talk', 'https://cloud.example.com/call/abc');
 * $emailTemplate->addBodyDetails($details);
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class EMailDetails {
	private string $subtitle = '';
	private ?string $initialsName = null;
	/** @var array{month: string, day: string}|null */
	private ?array $dateBadge = null;
	/** @var list<EMailDetailsRow> */
	private array $rows = [];

	/**
	 * @since 36.0.0
	 */
	public function __construct(
		private string $title,
	) {
	}

	/**
	 * @since 36.0.0
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @since 36.0.0
	 */
	public function setSubtitle(string $subtitle): self {
		$this->subtitle = $subtitle;
		return $this;
	}

	/**
	 * @since 36.0.0
	 */
	public function getSubtitle(): string {
		return $this->subtitle;
	}

	/**
	 * Show an initials circle next to the title, replaces any date badge
	 *
	 * @param string $name Name the initials are computed from
	 * @since 36.0.0
	 */
	public function setInitials(string $name): self {
		$this->initialsName = $name;
		$this->dateBadge = null;
		return $this;
	}

	/**
	 * @since 36.0.0
	 */
	public function getInitialsName(): ?string {
		return $this->initialsName;
	}

	/**
	 * Show a calendar badge next to the title, replaces any initials circle
	 *
	 * @param string $month Localized short month name, e.g. "Oct"
	 * @param string $day Day of the month, e.g. "15"
	 * @since 36.0.0
	 */
	public function setDateBadge(string $month, string $day): self {
		$this->dateBadge = ['month' => $month, 'day' => $day];
		$this->initialsName = null;
		return $this;
	}

	/**
	 * @return array{month: string, day: string}|null
	 * @since 36.0.0
	 */
	public function getDateBadge(): ?array {
		return $this->dateBadge;
	}

	/**
	 * Add a labelled row, fill it through the returned row
	 *
	 * @since 36.0.0
	 */
	public function addRow(string $label): EMailDetailsRow {
		$row = new EMailDetailsRow($label);
		$this->rows[] = $row;
		return $row;
	}

	/**
	 * @return list<EMailDetailsRow>
	 * @since 36.0.0
	 */
	public function getRows(): array {
		return $this->rows;
	}
}
