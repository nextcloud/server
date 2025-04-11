<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

/**
 * Interface IFileCheck
 *
 * @since 18.0.0
 */
interface IEntityCheck {
	/**
	 * Equips the check with a subject fitting the Entity. For instance, an
	 * entity of File will receive an instance of OCP\Files\Node, or a comment
	 * entity might get an IComment.
	 *
	 * The implementing check must be aware of the incoming type.
	 *
	 * If an unsupported subject is passed the implementation MAY throw an
	 * \UnexpectedValueException.
	 *
	 * @param IEntity $entity
	 * @param mixed $subject
	 * @throws \UnexpectedValueException
	 * @since 18.0.0
	 */
	public function setEntitySubject(IEntity $entity, $subject): void;
}
