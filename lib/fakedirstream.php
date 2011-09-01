<?php
global $FAKEDIRS;
$FAKEDIRS=array();

class fakeDirStream{
	private $name;
	private $data;
	private $index;

	public function dir_opendir($path,$options){
		global $FAKEDIRS;
		$url=parse_url($path);
		$this->name=substr($path,strlen('fakedir://'));
		$this->index=0;
		if(isset($FAKEDIRS[$this->name])){
			$this->data=$FAKEDIRS[$this->name];
		}else{
			$this->data=array();
		}
		return true;
	}

	public function dir_readdir(){
		if($this->index>=count($this->data)){
			return false;
		}
		$filename=$this->data[$this->index];
		$this->index++;
		return $filename;
	}

	public function dir_closedir() {
		$this->data=false;
		$this->name='';
		return true;
	}

	public function dir_rewinddir() {
		$this->index=0;
		return true;
	}
}
 
stream_wrapper_register("fakedir", "fakeDirStream");

