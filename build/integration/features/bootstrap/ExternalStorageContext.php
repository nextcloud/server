<?php


require __DIR__ . '/../../vendor/autoload.php';

use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

class ExternalStorageContext implements \Behat\Behat\Context\Context {
	

	/**
	 * @AfterScenario
	 */
	public static function removeFilesFromLocalStorage(){
		$dir = "./local_storage/";
		$di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
		$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
		foreach ( $ri as $file ) {
    		$file->isDir() ?  rmdir($file) : unlink($file);
		}
	}
}
