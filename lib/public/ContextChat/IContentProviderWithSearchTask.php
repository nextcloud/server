<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\ContextChat;

/**
 * This interface defines methods to implement a Context Chat content provider
 * with a search task and data description.
 * @since 35.0.0
 */
interface IContentProviderWithSearchTask extends IContentProvider {
	/**
	 * A short description about the kind of data provided by this provider.
	 * This part is sent to the main model to make sense of the retrieved documents.
	 *
	 * Some examples can be:
	 * - "Email messages from the user's mailbox with sender, recipients, subject,
	 *    and date."
	 * - "Chat messages from group and private conversations, each prefixed with
	 *    author and timestamp. They are short and may be informal, and often only
	 *    make sense in sequence.
	 *
	 * Return an empty string to keep it empty.
	 *
	 * @return string
	 * @since 35.0.0
	 */
	public function getDataDescription(): string;

	/**
	 * A short (10-25 words) search task to be performed by context chat for the
	 * content provider.
	 * This is not shown to the user, only passed to the embedding model if the model
	 * is set to https://huggingface.co/intfloat/multilingual-e5-large-instruct
	 *
	 * Some examples can be:
	 * - "Given a question, retrieve email messages whose subject or body discusses
	 *    or answers it, including quoted replies inside threads."
	 * - "Given a question, retrieve chat messages that mention or answer it,
	 *    even when written informally or as short fragments."
	 *
	 * Return an empty string to use the generic default.
	 *
	 * @return string
	 * @since 35.0.0
	 */
	public function getSearchTaskDescription(): string;
}
