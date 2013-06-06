<?php


class OC_Cache_FileGlobalGC extends \OC\BackgroundJob\Job{
	public function run($argument){
		OC_Cache_FileGlobal::gc();
	}
}
