<?php
/**
* @package shorty an ownCloud url shortener plugin
* @category internet
* @author Christian Reiner
* @copyright 2011-2012 Christian Reiner <foss@christian-reiner.info>
* @license GNU Affero General Public license (AGPL)
* @link information 
* @link repository https://svn.christian-reiner.info/svn/app/oc/shorty
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the license, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.
* If not, see <http://www.gnu.org/licenses/>.
*
*/

/**
 * @file lib/query.php
 * Static catalog of sql queries
 * @author Christian Reiner
 */

/**
 * @class OC_Shorty_Query
 * @brief Static catalog of sql queries
 * These query templates are referenced by a OC_Shorty_Query::URL_...
 * They have to be prapared by adding an array of parameters
 * @access public
 * @author Christian Reiner
 */
class OC_Shorty_Query
{
  const URL_INSERT            = "INSERT INTO *PREFIX*shorty (id,status,favicon,title,source,target,user,until,created,notes) VALUES (:id,:status,:favicon,:title,:source,:target,:user,:until,CURRENT_DATE,:notes)";
  const URL_DELETE            = "DELETE FROM *PREFIX*shorty WHERE user=:user AND id=:id";
  const URL_REMOVE            = "DELETE FROM *PREFIX*shorty WHERE user=:user AND 'deleted'=status";
  const URL_UPDATE            = "UPDATE *PREFIX*shorty SET status=:status,title=:title,until=:until,notes=:notes WHERE user=:user AND id=:id";
  const URL_STATUS            = "UPDATE *PREFIX*shorty SET status=:status WHERE user=:user AND id=:id";
  const URL_CLICK             = "UPDATE *PREFIX*shorty SET accessed=CURRENT_TIMESTAMP, clicks=(clicks+1) WHERE id=:id";
  const URL_FORWARD           = "SELECT user,source,target,status,(until IS NOT NULL AND until!='' AND until<CURRENT_TIMESTAMP) AS expired FROM *PREFIX*shorty WHERE id=:id";
  const URL_SOURCE            = "SELECT id,source,target,status,(until IS NOT NULL AND until!='' AND until<CURRENT_TIMESTAMP) AS expired FROM *PREFIX*shorty WHERE source=:source";
  const URL_VERIFY            = "SELECT id,status,favicon,title,source,target,clicks,created,accessed,until,notes FROM *PREFIX*shorty WHERE user=:user AND id=:id LIMIT 1";
  const URL_LIST              = "SELECT id,status,favicon,title,source,target,clicks,created,accessed,until,notes FROM *PREFIX*shorty WHERE user=:user ORDER BY :sort";
  const URL_COUNT             = "SELECT count(*) AS sum_shortys,IFNULL(sum(clicks),0) AS sum_clicks FROM *PREFIX*shorty WHERE user=:user";
  const WIPE_SHORTYS          = "DELETE FROM *PREFIX*shorty WHERE user=:user";
  const WIPE_PREFERENCES      = "DELETE FROM *PREFIX*preferences WHERE user=:user";
} // class OC_Shorty_Query
?>
