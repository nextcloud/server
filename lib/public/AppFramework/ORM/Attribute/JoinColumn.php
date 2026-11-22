<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Attribute for mapping a property in an entity to a database column.
 *
 * ```php
 * #[Entity(name: 'repository_customer')]
 * final class Customer {
 *     #[Id]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?int $id = null;
 *
 *     #[OneToOne(targetEntity: Cart::class, mappedBy: 'customer')]
 *     #[JoinColumn(name: 'cart_id', referencedColumnName: 'id')]
 *     public ?Cart $cart = null;
 *
 *     #[Column(name: 'name', type: Types::STRING, nullable: false)]
 *     public string $name;
 * }
 *
 * #[Entity(name: 'repository_cart')]
 * final class Cart {
 *     #[Id]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?int $id = null;
 *
 *     #[OneToOne(targetEntity: Customer::class, invertedBy: 'cart')]
 *     #[JoinColumn(name: 'customer_id', referencedColumnName: 'id')]
 *     public ?Customer $customer;
 * }
 * ```
 *
 * @since 35.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '35.0.0')]
final readonly class JoinColumn {
	/** @since 35.0.0 */
	public function __construct(
		/** @var non-empty-lowercase-string The name of the column in the database. */
		public string $name,
		/** @var non-empty-lowercase-string The name of the column in the other table */
		public string $referencedColumnName,
		/** @var bool Whether the column is nullable in the database */
		public bool $nullable = false,
		/** @var 'CASCADE'|null The action what happen when deleting the foreign entity */
		public ?string $onDelete = null,
	) {
	}
}
