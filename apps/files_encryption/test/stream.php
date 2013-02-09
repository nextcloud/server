// <?php
// /**
//  * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
//  * This file is licensed under the Affero General Public License version 3 or
//  * later.
//  * See the COPYING-README file.
//  */
//  
// namespace OCA\Encryption;
// 
// class Test_Stream extends \PHPUnit_Framework_TestCase {
// 
// 	function setUp() {
// 	
// 		\OC_Filesystem::mount( 'OC_Filestorage_Local', array(), '/' );
// 	
// 		$this->empty = '';
// 	
// 		$this->stream = new Stream();
// 		
// 		$this->dataLong = file_get_contents( realpath( dirname(__FILE__).'/../lib/crypt.php' ) );
// 		$this->dataShort = 'hats';
// 		
// 		$this->emptyTmpFilePath = \OCP\Files::tmpFile();
// 		
// 		$this->dataTmpFilePath = \OCP\Files::tmpFile();
// 		
// 		file_put_contents( $this->dataTmpFilePath, "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean ut gravida lorem. Ut turpis felis, pulvinar a semper sed, adipiscing id dolor. Pellentesque auctor nisi id magna consequat sagittis. Curabitur dapibus enim sit amet elit pharetra tincidunt feugiat nisl imperdiet. Ut convallis libero in urna ultrices accumsan. Donec sed odio eros. Donec viverra mi quis quam pulvinar at malesuada arcu rhoncus. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In rutrum accumsan ultricies. Mauris vitae nisi at sem facilisis semper ac in est." );
// 	
// 	}
// 	
// 	function testStreamOpen() {
// 		
// 		$stream1 = new Stream();
// 		
// 		$handle1 = $stream1->stream_open( $this->emptyTmpFilePath, 'wb', array(), $this->empty );
// 		
// 		// Test that resource was returned successfully
// 		$this->assertTrue( $handle1 );
// 		
// 		// Test that file has correct size
// 		$this->assertEquals( 0, $stream1->size );
// 		
// 		// Test that path is correct
// 		$this->assertEquals( $this->emptyTmpFilePath, $stream1->rawPath );
// 		
// 		$stream2 = new Stream();
// 		
// 		$handle2 = $stream2->stream_open( 'crypt://' . $this->emptyTmpFilePath, 'wb', array(), $this->empty );
// 		
// 		// Test that protocol identifier is removed from path
// 		$this->assertEquals( $this->emptyTmpFilePath, $stream2->rawPath );
// 
// 		// "Stat failed error" prevents this test from executing
// // 		$stream3 = new Stream();
// // 		
// // 		$handle3 = $stream3->stream_open( $this->dataTmpFilePath, 'r', array(), $this->empty );
// // 		
// // 		$this->assertEquals( 0, $stream3->size );
// 	
// 	}
// 	
// 	function testStreamWrite() {
// 		
// 		$stream1 = new Stream();
// 		
// 		$handle1 = $stream1->stream_open( $this->emptyTmpFilePath, 'r+b', array(), $this->empty );
// 		
// 		# what about the keymanager? there is no key for the newly created temporary file!
// 		
// 		$stream1->stream_write( $this->dataShort );
// 	
// 	}
// 
// // 	function getStream( $id, $mode, $size ) {
// // 	
// // 		if ( $id === '' ) {
// // 			
// // 			$id = uniqid();
// // 		}
// // 		
// // 		
// // 		if ( !isset( $this->tmpFiles[$id] ) ) {
// // 		
// // 			// If tempfile with given name does not already exist, create it
// // 			
// // 			$file = OCP\Files::tmpFile();
// // 			
// // 			$this->tmpFiles[$id] = $file;
// // 		
// // 		} else {
// // 		
// // 			$file = $this->tmpFiles[$id];
// // 		
// // 		}
// // 		
// // 		$stream = fopen( $file, $mode );
// // 		
// // 		Stream::$sourceStreams[$id] = array( 'path' => 'dummy' . $id, 'stream' => $stream, 'size' => $size );
// // 		
// // 		return fopen( 'crypt://streams/'.$id, $mode );
// // 	
// // 	}
// // 
// // 	function testStream(  ){
// // 
// // 		$stream = $this->getStream( 'test1', 'w', strlen( 'foobar' ) );
// // 
// // 		fwrite( $stream, 'foobar' );
// // 
// // 		fclose( $stream );
// // 
// // 
// // 		$stream = $this->getStream( 'test1', 'r', strlen( 'foobar' ) );
// // 
// // 		$data = fread( $stream, 6 );
// // 
// // 		fclose( $stream );
// // 
// // 		$this->assertEquals( 'foobar', $data );
// // 
// // 
// // 		$file = OC::$SERVERROOT.'/3rdparty/MDB2.php';
// // 
// // 		$source = fopen( $file, 'r' );
// // 
// // 		$target = $this->getStream( 'test2', 'w', 0 );
// // 
// // 		OCP\Files::streamCopy( $source, $target );
// // 
// // 		fclose( $target );
// // 
// // 		fclose( $source );
// // 
// // 
// // 		$stream = $this->getStream( 'test2', 'r', filesize( $file ) );
// // 
// // 		$data = stream_get_contents( $stream );
// // 
// // 		$original = file_get_contents( $file );
// // 
// // 		$this->assertEquals( strlen( $original ), strlen( $data ) );
// // 
// // 		$this->assertEquals( $original, $data );
// // 
// // 	}
// 
// }
// 
// // class Test_CryptStream extends PHPUnit_Framework_TestCase {
// // 	private $tmpFiles=array();
// // 	
// // 	function testStream(){
// // 		$stream=$this->getStream('test1','w',strlen('foobar'));
// // 		fwrite($stream,'foobar');
// // 		fclose($stream);
// // 
// // 		$stream=$this->getStream('test1','r',strlen('foobar'));
// // 		$data=fread($stream,6);
// // 		fclose($stream);
// // 		$this->assertEquals('foobar',$data);
// // 
// // 		$file=OC::$SERVERROOT.'/3rdparty/MDB2.php';
// // 		$source=fopen($file,'r');
// // 		$target=$this->getStream('test2','w',0);
// // 		OCP\Files::streamCopy($source,$target);
// // 		fclose($target);
// // 		fclose($source);
// // 
// // 		$stream=$this->getStream('test2','r',filesize($file));
// // 		$data=stream_get_contents($stream);
// // 		$original=file_get_contents($file);
// // 		$this->assertEquals(strlen($original),strlen($data));
// // 		$this->assertEquals($original,$data);
// // 	}
// // 
// // 	/**
// // 	 * get a cryptstream to a temporary file
// // 	 * @param string $id
// // 	 * @param string $mode
// // 	 * @param int size
// // 	 * @return resource
// // 	 */
// // 	function getStream($id,$mode,$size){
// // 		if($id===''){
// // 			$id=uniqid();
// // 		}
// // 		if(!isset($this->tmpFiles[$id])){
// // 			$file=OCP\Files::tmpFile();
// // 			$this->tmpFiles[$id]=$file;
// // 		}else{
// // 			$file=$this->tmpFiles[$id];
// // 		}
// // 		$stream=fopen($file,$mode);
// // 		OC_CryptStream::$sourceStreams[$id]=array('path'=>'dummy'.$id,'stream'=>$stream,'size'=>$size);
// // 		return fopen('crypt://streams/'.$id,$mode);
// // 	}
// // 
// // 	function testBinary(){
// // 		$file=__DIR__.'/binary';
// // 		$source=file_get_contents($file);
// // 
// // 		$stream=$this->getStream('test','w',strlen($source));
// // 		fwrite($stream,$source);
// // 		fclose($stream);
// // 
// // 		$stream=$this->getStream('test','r',strlen($source));
// // 		$data=stream_get_contents($stream);
// // 		fclose($stream);
// // 		$this->assertEquals(strlen($data),strlen($source));
// // 		$this->assertEquals($source,$data);
// // 
// // 		$file=__DIR__.'/zeros';
// // 		$source=file_get_contents($file);
// // 
// // 		$stream=$this->getStream('test2','w',strlen($source));
// // 		fwrite($stream,$source);
// // 		fclose($stream);
// // 
// // 		$stream=$this->getStream('test2','r',strlen($source));
// // 		$data=stream_get_contents($stream);
// // 		fclose($stream);
// // 		$this->assertEquals(strlen($data),strlen($source));
// // 		$this->assertEquals($source,$data);
// // 	}
// // }
