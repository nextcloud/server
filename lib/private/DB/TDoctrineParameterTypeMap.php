<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use OCP\DB\QueryBuilder\IQueryBuilder;

trait TDoctrineParameterTypeMap {
	protected function convertParameterTypeToDoctrine(string|int|null $type): ArrayParameterType|ParameterType|string {
		return match($type) {
			IQueryBuilder::PARAM_DATE,
			IQueryBuilder::PARAM_JSON => $type,
			IQueryBuilder::PARAM_NULL => ParameterType::NULL,
			IQueryBuilder::PARAM_BOOL => ParameterType::BOOLEAN,
			IQueryBuilder::PARAM_INT => ParameterType::INTEGER,
			null,
			IQueryBuilder::PARAM_STR => ParameterType::STRING,
			IQueryBuilder::PARAM_LOB => ParameterType::LARGE_OBJECT,
			IQueryBuilder::PARAM_INT_ARRAY => ArrayParameterType::INTEGER,
			IQueryBuilder::PARAM_STR_ARRAY => ArrayParameterType::STRING,
		};
	}

	protected function convertParameterTypeToJsonSerializable(ArrayParameterType|ParameterType|string $type): string {
		return match($type) {
			ParameterType::NULL => 'null',
			ParameterType::BOOLEAN => 'boolean',
			ParameterType::INTEGER => 'integer',
			ArrayParameterType::INTEGER => 'integer[]',
			ParameterType::STRING => 'string',
			ArrayParameterType::STRING => 'string[]',
			ParameterType::LARGE_OBJECT => 'lob',
			IQueryBuilder::PARAM_DATE,
			IQueryBuilder::PARAM_JSON => $type,
			default => 'unsupported-type',
		};
	}
}
