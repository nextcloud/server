<?php
/*
 * @copyright Copyright (c) 2024 Joas Schilling <coding@schilljs.com>
 */

declare(strict_types=1);
/*
 * @copyright Copyright (c) 2024 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OC\DB;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use OCP\DB\QueryBuilder\IQueryBuilder;

trait TDoctrineParameterTypeMap {
	protected function convertParameterTypeToDoctrine(string|int|null $type): ArrayParameterType|ParameterType|string {
		return match($type) {
			null,
			IQueryBuilder::PARAM_DATE,
			IQueryBuilder::PARAM_JSON => $type,
			IQueryBuilder::PARAM_NULL => ParameterType::NULL,
			IQueryBuilder::PARAM_BOOL => ParameterType::BOOLEAN,
			IQueryBuilder::PARAM_INT => ParameterType::INTEGER,
			IQueryBuilder::PARAM_STR => ParameterType::STRING,
			IQueryBuilder::PARAM_LOB => ParameterType::LARGE_OBJECT,
			IQueryBuilder::PARAM_INT_ARRAY => ArrayParameterType::INTEGER,
			IQueryBuilder::PARAM_STR_ARRAY => ArrayParameterType::STRING,
		};
	}

}
