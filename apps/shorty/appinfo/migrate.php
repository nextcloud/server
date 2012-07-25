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
 * @file appinfo/migrate.php
 * @brief OC migration support
 * @author Christian Reiner
 */

class OC_Migration_Provider_Shorty extends OC_Migration_Provider
{

  function export ( )
  {
    OCP\Util::writeLog ( 'migration','starting export for Shorty', OCP\Util::INFO );
    $options = array(
      'table'=>'shorty',
      'matchcol'=>'user',
      'matchval'=>$this->uid,
      'idcol'=>'id'
    );
    $ids = $this->content->copyRows( $options );
    $count = OC_Shorty_Tools::countShorties();
    // check for success
    if(   (is_array($ids) && is_array($count))
      && (count($ids)==$count['sum_shortys']) )
        return true;
    else return false;
  } // function export

  function import ( )
  {
    switch( $this->appinfo->version )
    {
      default:
      $query  = $this->content->prepare( "SELECT * FROM shorty WHERE user_id LIKE ?" );
      $result = $query->execute( array( $this->olduid ) );
      if (is_array(is_array($result)))
      {
        while( $row = $result->fetchRow() )
        {
          $param = array (
            'id'       => $row['id'],
            'status'   => $row['status'],
            'title'    => $row['title'],
            'favicon'  => $row['favicon'],
            'source'   => $row['source'],
            'target'   => $row['target'],
            'user'     => $row['user'],
            'until'    => $row['until'],
            'created'  => $row['created'],
            'accessed' => $row['accessed'],
            'clicks'   => $row['clicks'],
            'notes'    => $row['notes'],
          );
          // import each shorty one by one, no special treatment required, since no autoincrement id is used
          $query = OCP\DB::prepare( sprintf ( "INSERT INTO *PREFIX*shorty(%s) VALUES (%s)",
                                              implode(',',array_keys($param)),
                                              implode(',',array_fill(0,count($param),'?')) ) );
          $query->execute( $param );
        } // while
      } // if
      break;
    } // switch
    // check for success by counting the generated entries
    $count = OC_Shorty_Tools::countShorties();
    if(   (is_array($result) && is_array($count))
      && (count($result)==$count['sum_shortys']) )
        return true;
    else return false;
  } // function import

} // class OC_Migration_Provider_Shorty

// Load the provider
new OC_Migration_Provider_Shorty ( 'shortys' );

?>
