<?php
/**
 * ownCloud
 *
 * @author Côme BERNIGAUD
 * @copyright 2011 Côme BERNIGAUD come.bernigaud@laposte.net
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once('base.php');

/**
 * This class manages the access to the database from apps. It basically is a wrapper for
 * OC_DB. It allows that apps doesn't contains SQL code.
 */
class OC_DB4App {
	/**
	 * @brief Store an object in the database
	 * @param $appname  Name of the application
	 * @param $table    Name of the database table
	 * @param $userid   Id of owner of the object
	 * @param $object   Object to save in the database
	 * @returns id of the object in the database
	 *
	 */
    static public function store($appname,$tablename,$userid,$object) {
        $table = $appname."_".$tablename;
        if(OC_DB::connect()) {
            $CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );
            if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' ){
                $idline = "id INTEGER PRIMARY KEY AUTOINCREMENT";
            } else {
                $idline = "id int NOT NULL AUTO_INCREMENT";
            }
            $query = "CREATE TABLE IF NOT EXISTS *PREFIX*$table ($idline,uid int REFERENCES user(id),object text)";
            OC_DB::query($query);
            $query = "INSERT INTO *PREFIX*$table (uid,object) VALUES ('$userid','".OC_DB::escape(base64_encode(serialize($object)))."')";
            OC_DB::query($query);
            return OC_DB::insertid();
        } else {
            die ("could not connect to database");
        }
    }
    
	/**
	 * @brief Get an object from the database
	 * @param $appname  Name of the application
	 * @param $table    Name of the database table
	 * @param $objectid Id of the object
	 * @returns the object
	 *
	 */
    static public function get_object($appname,$tablename,$objectid) {
        $table = $appname."_".$tablename;
        if(OC_DB::connect()) {
            $query = "SELECT object FROM *PREFIX*$table WHERE id='".OC_DB::escape($objectid)."'";
            $q = OC_DB::prepare($query);
            $result = $q->execute()->fetchOne();
            return unserialize(base64_decode($result));
        } else {
            die ("could not connect to database");
        }
        
    }
    
    static public function get_objects($appname,$tablename,$userid) {
        $table = $appname."_".$tablename;
        if(OC_DB::connect()) {
            $query = "SELECT id FROM *PREFIX*$table WHERE uid='".OC_DB::escape($userid)."'";
            $q = OC_DB::prepare($query);
            $result = $q->execute()->fetchAll();
            return $result;
        } else {
            die ("could not connect to database");
        }
    }
    
    static public function delete_object($appname,$tablename,$objectid) {
        $table = $appname."_".$tablename;
        if(OC_DB::connect()) {
            $query = "DELETE FROM *PREFIX*$table WHERE id='".OC_DB::escape($objectid)."'";
            $q = OC_DB::prepare($query);
            $q->execute();
            return true;
        } else {
            die ("could not connect to database");
        }
        
    }
    
    static public function drop($appname,$tablename) {
        $table = $appname."_".$tablename;
        if(OC_DB::connect()) {
            $query = "DROP TABLE *PREFIX*$table";
            $q = OC_DB::prepare($query);
            $q->execute();
            return true;
        } else {
            die ("could not connect to database");
        }
        
    }
}
?>
