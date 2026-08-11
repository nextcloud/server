<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Attribute for mapping the owning side of a many-to-one relation: several rows of this
 * entity can point at the same row of the target entity.
 *
 * ```php
 * #[Entity(name: 'order']
 * final class Order {
 *     #[Id]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?int $id = null;
 *
 *     #[ManyToOne(targetEntity: Customer::class)]
 *     #[JoinColumn(name: 'customer_id', referencedColumnName: 'id')]
 *     public Customer|null $customer = null;
 * }
 *
 * #[Entity(name: 'customer']
 * final class Customer {
 *     #[Id]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?int $id = null;
 * }
 * ```
 *
 * Unlike OneToOne, the join column has no uniqueness constraint: several Order rows are
 * allowed to reference the same Customer.
 *
 * @since 35.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '35.0.0')]
final readonly class ManyToOne {
	/** @since 35.0.0 */
	public function __construct(
		/** @var class-string */
		public string $targetEntity,
	) {
	}
}
