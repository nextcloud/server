<?php

namespace OC\Cache;

class FileGlobalGC extends \OC\BackgroundJob\Job{
	public function run($argument){
		FileGlobal::gc();
	}
}
