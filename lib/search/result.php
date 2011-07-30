<?php
/**
 * a result of a search
 */
class OC_Search_Result{
	public $name;
	public $text;
	public $link;
	public $type;

	/**
	 * create a new search result
	 * @param string $name short name for the result
	 * @param string $text some more information about the result
	 * @param string $link link for the result
	 * @param string $type the type of result as human readable string ('File', 'Music', etc)
	 */
	public function __construct($name,$text,$link,$type){
		$this->name=$name;
		$this->text=$text;
		$this->link=$link;
		$this->type=$type;
	}
}
