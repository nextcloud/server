<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Attribute for mapping two properties together in a one-to-one relation.
 *
 * Example of a unidirectional relationship.
 *
 * ```php
 * #[Entity(name: 'product']
 * final class Product {
 *     #[Id]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?int $id = null;
 *
 *     #[OneToOne(targetEntity: Shipment::class)]
 *     #[JoinColumn(name: 'shipment_id', referencedColumnName: 'id')]
 *     public Shipment|null $shipment = null;
 * }
 *
 * #[Entity(name: 'shipment']
 * final class Shipment {
 *     #[Id]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?int $id = null;
 * }
 * ```
 *
 * Example of a bidirectional relationship where Cart owns the relationship.
 *
 * ```php
 * #[Entity(name: 'customer']
 * final class Customer {
 *     #[Id]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?int $id = null;
 *
 *     #[OneToOne(targetEntity: Cart::class, mappedBy: 'customer')]
 *     #[JoinColumn(name: 'cart_id', referencedColumnName: 'id')]
 *     public Cart|null $cart = null;
 * }
 *
 * #[Entity(name: 'cart']
 * final class Cart {
 *     #[Id]
 *     #[Column(name: 'id', type: ColumnType::Bigint)]
 *     public ?int $id = null;
 *
 *     #[OneToOne(targetEntity: Customer::class, invertedBy: 'cart')]
 *     #[JoinColumn(name: 'customer_id', referencedColumnName: 'id')]
 *     public Customer|null $customer;
 * }
 * ```
 *
 * @since 35.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '35.0.0')]
final readonly class OneToOne {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		/** @var class-string */
		public string $targetEntity,
		/** @var ?non-empty-string */
		public ?string $mappedBy = null,
		/** @var ?non-empty-string */
		public ?string $invertedBy = null,
	) {
	}
}
