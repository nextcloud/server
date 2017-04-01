<?php
/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 *
 */


/*
 * Stupid simple code to merge all the default vendor javascript into 1
 */
$data = json_decode(file_get_contents(__DIR__.'/../core/js/core.json'), true);
$vendors = $data['vendor'];

$vendorjs = fopen(__DIR__.'/../core/vendor/core.js', 'w');
foreach($vendors as $vendor) {
	fwrite($vendorjs, file_get_contents(__DIR__.'/../core/vendor/'.$vendor));
	fwrite($vendorjs, PHP_EOL);
}
fclose($vendorjs);
