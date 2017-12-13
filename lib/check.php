<?php
/**
 * Allow to include additional checks using a file convention:
 * Files started by 'check_' are included and processed.
 * 
 * @copyright Copyright (c) 2017, Nextcloud, Inc.
 *
 * @author Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
$checks = scandir(__DIR__);
foreach($checks as $check){
	if (substr($check,0,6) == 'check_' ){
		include $check;
	}
}