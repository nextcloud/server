<?php

/**
* ownCloud - ATNotes plugin
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

/**
 * This class manages atnote db. 
 */
class OC_ATNotes {

	/**
	 * Save note
	 * @param $title Title of the note
	 * @param $content Content of the note
	 * @return $id Id of the saved note
	 */
	public static function saveNote($id,$title,$content){
		if($id == 0){
			$query = OCP\DB::prepare('INSERT INTO *PREFIX*atnotes (oc_uid,create_ts,update_ts,note_title,note_content) VALUES (?,?,?,?,?)');
			$query->execute(Array(OCP\User::getUser(),time(),time(),$title,$content));
			$id = OCP\DB::insertid('*PREFIX*atnotes');
		}else{
			$query = OCP\DB::prepare('UPDATE *PREFIX*atnotes SET update_ts=?,note_title=?,note_content=? WHERE note_id=?');
			$query->execute(Array(time(),$title,$content,$id));
		}
		return $id;
	}
	
	/**
	 * Get list
	 * @return Array()
	 */
	public static function getNotesList(){
		$query = OCP\DB::prepare('SELECT * FROM *PREFIX*atnotes WHERE oc_uid=? AND is_deleted=? ORDER BY update_ts DESC');
		$results = $query->execute(Array(OCP\User::getUser(),0))->fetchAll();
		return $results;
	}
	
	/**
	 * Get note
	 * @param $id The note db id
	 * @return Array if note found or Boolean FALSE
	 */
	public static function getNote($id){
		$query = OCP\DB::prepare('SELECT * FROM *PREFIX*atnotes WHERE oc_uid=? AND is_deleted=? AND note_id=?');
		$result = $query->execute(Array(OCP\User::getUser(),0,$id))->fetchRow();
		if($result){
			return $result;
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Delete note
	 * @param $id The note db id
	 * @return Boolean
	 */
	public static function deleteNote($id){
		if(self::getNote($id)){
			$query = OCP\DB::prepare('DELETE FROM *PREFIX*atnotes WHERE oc_uid=? AND note_id=?');
			$query->execute(Array(OCP\User::getUser(),$id));
			if(!self::getNote($id)){
				return 0;
			}
		}
		return 1;
	}
	
}
