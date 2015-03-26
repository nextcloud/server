<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

/**
 * This class does the dirty work.
 */
class OC_BackgroundJob{
	/**
	 * get the execution type of background jobs
	 * @return string
	 *
	 * This method returns the type how background jobs are executed. If the user
	 * did not select something, the type is ajax.
	 */
	public static function getExecutionType() {
		return OC_Appconfig::getValue( 'core', 'backgroundjobs_mode', 'ajax' );
	}

	/**
	 * sets the background jobs execution type
	 * @param string $type execution type
	 * @return false|null
	 *
	 * This method sets the execution type of the background jobs. Possible types
	 * are "none", "ajax", "webcron", "cron"
	 */
	public static function setExecutionType( $type ) {
		if( !in_array( $type, array('none', 'ajax', 'webcron', 'cron'))) {
			return false;
		}
		return OC_Appconfig::setValue( 'core', 'backgroundjobs_mode', $type );
	}
}
