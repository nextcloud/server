<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Talk;

/**
 * @since 24.0.0
 */
interface IConversationOptions {
	/**
	 * Will the conversation be public?
	 *
	 * @return bool
	 * @since 24.0.0
	 */
	public function isPublic(): bool;

	/**
	 * Make the new conversation public
	 *
	 * @param bool $isPublic
	 *
	 * @return $this
	 * @since 24.0.0
	 */
	public function setPublic(bool $isPublic = true): self;

	/**
	 * Date of the meeting if the conversation is tied to a single meeting event
	 *
	 * This will be used by the Talk backend to expire the conversation after a
	 * reasonable amount of time after the meeting unless the conversation is
	 * being reused.
	 *
	 * @param \DateTimeInterface $meetingStartDate
	 * @param \DateTimeInterface $meetingEndDate
	 * @return $this
	 * @since 34.0.0
	 * @since 33.0.3
	 * @since 32.0.9
	 */
	public function setMeetingDate(\DateTimeInterface $meetingStartDate, \DateTimeInterface $meetingEndDate): self;

	/**
	 * Start date of the meeting
	 *
	 * @return ?\DateTimeInterface
	 * @since 34.0.0
	 * @since 33.0.3
	 * @since 32.0.9
	 */
	public function getMeetingStartDate(): ?\DateTimeInterface;

	/**
	 * End date of the meeting
	 *
	 * @return ?\DateTimeInterface
	 * @since 34.0.0
	 * @since 33.0.3
	 * @since 32.0.9
	 */
	public function getMeetingEndDate(): ?\DateTimeInterface;
}
