<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * This attribute can be used to define Frontpage routes on controller methods.
 *
 * It works in addition to the traditional routes.php method and has the same parameters
 * (except for the `name` parameter which is not needed).
 *
 * @since 29.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class FrontpageRoute extends Route {
	/**
	 * @inheritDoc
	 *
	 * @since 29.0.0
	 */
	public function __construct(
		protected string $verb,
		protected string $url,
		protected ?array $requirements = null,
		protected ?array $defaults = null,
		protected ?string $root = null,
		protected ?string $postfix = null,
	) {
		parent::__construct(
			Route::TYPE_FRONTPAGE,
			$verb,
			$url,
			$requirements,
			$defaults,
			$root,
			$postfix,
		);
	}
}
