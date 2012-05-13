<?php
/**
 * ownCloud
 *
 * @author Bart Visscher
 * @copyright 2011 Bart Visscher bartv@thisnet.nl
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

/**
 * This class provides a streamlined interface to the Sabre VObject classes
 */
class OC_VObject{
	/** @var Sabre_VObject_Component */
	protected $vobject;

	/**
	 * @returns Sabre_VObject_Component
	 */
	public function getVObject(){
		return $this->vobject;
	}

	/**
	 * @brief Parses the VObject
	 * @param string VObject as string
	 * @returns Sabre_VObject or null
	 */
	public static function parse($data){
		try {
			Sabre_VObject_Property::$classMap['LAST-MODIFIED'] = 'Sabre_VObject_Property_DateTime';
			$vobject = Sabre_VObject_Reader::read($data);
			if ($vobject instanceof Sabre_VObject_Component){
				$vobject = new OC_VObject($vobject);
			}
			return $vobject;
		} catch (Exception $e) {
			OC_Log::write('vobject', $e->getMessage(), OC_Log::ERROR);
			return null;
		}
	}

	/**
	 * @brief Escapes semicolons
	 * @param string $value
	 * @return string
	 */
	public static function escapeSemicolons($value){
		foreach($value as &$i ){
			$i = implode("\\\\;", explode(';', $i));
		}
		return implode(';',$value);
	}

	/**
	 * @brief Creates an array out of a multivalue property
	 * @param string $value
	 * @return array
	 */
	public static function unescapeSemicolons($value){
		$array = explode(';',$value);
		for($i=0;$i<count($array);$i++){
			if(substr($array[$i],-2,2)=="\\\\"){
				if(isset($array[$i+1])){
					$array[$i] = substr($array[$i],0,count($array[$i])-2).';'.$array[$i+1];
					unset($array[$i+1]);
				}
				else{
					$array[$i] = substr($array[$i],0,count($array[$i])-2).';';
				}
				$i = $i - 1;
			}
		}
		return $array;
	}

	/**
	 * Constuctor
	 * @param Sabre_VObject_Component or string
	 */
	public function __construct($vobject_or_name){
		if (is_object($vobject_or_name)){
			$this->vobject = $vobject_or_name;
		} else {
			$this->vobject = new Sabre_VObject_Component($vobject_or_name);
		}
	}

	public function add($item, $itemValue = null){
		if ($item instanceof OC_VObject){
			$item = $item->getVObject();
		}
		$this->vobject->add($item, $itemValue);
	}

	/**
	 * @brief Add property to vobject
	 * @param object $name of property
	 * @param object $value of property
	 * @param object $parameters of property
	 * @returns Sabre_VObject_Property newly created
	 */
	public function addProperty($name, $value, $parameters=array()){
		if(is_array($value)){
			$value = OC_VObject::escapeSemicolons($value);
		}
		$property = new Sabre_VObject_Property( $name, $value );
		foreach($parameters as $name => $value){
			$property->parameters[] = new Sabre_VObject_Parameter($name, $value);
		}

		$this->vobject->add($property);
		return $property;
	}

	public function setUID(){
		$uid = substr(md5(rand().time()),0,10);
		$this->vobject->add('UID',$uid);
	}

	public function setString($name, $string){
		if ($string != ''){
			$string = strtr($string, array("\r\n"=>"\n"));
			$this->vobject->__set($name, $string);
		}else{
			$this->vobject->__unset($name);
		}
	}

	/**
	 * Sets or unsets the Date and Time for a property.
	 * When $datetime is set to 'now', use the current time
	 * When $datetime is null, unset the property
	 *
	 * @param string property name
	 * @param DateTime $datetime
	 * @param int $dateType
	 * @return void
	 */
	public function setDateTime($name, $datetime, $dateType=Sabre_VObject_Property_DateTime::LOCALTZ){
		if ($datetime == 'now'){
			$datetime = new DateTime();
		}
		if ($datetime instanceof DateTime){
			$datetime_element = new Sabre_VObject_Property_DateTime($name);
			$datetime_element->setDateTime($datetime, $dateType);
			$this->vobject->__set($name, $datetime_element);
		}else{
			$this->vobject->__unset($name);
		}
	}

	public function getAsString($name){
		return $this->vobject->__isset($name) ?
			$this->vobject->__get($name)->value :
			'';
	}

	public function getAsArray($name){
		$values = array();
		if ($this->vobject->__isset($name)){
			$values = explode(',', $this->getAsString($name));
			$values = array_map('trim', $values);
		}
		return $values;
	}

	public function &__get($name){
		if ($name == 'children'){
			return $this->vobject->children;
		}
		$return = $this->vobject->__get($name);
		if ($return instanceof Sabre_VObject_Component){
			$return = new OC_VObject($return);
		}
		return $return;
	}

	public function __set($name, $value){
		return $this->vobject->__set($name, $value);
	}

	public function __unset($name){
		return $this->vobject->__unset($name);
	}

	public function __isset($name){
		return $this->vobject->__isset($name);
	}

	public function __call($function,$arguments){
		return call_user_func_array(array($this->vobject, $function), $arguments);
	}
}
