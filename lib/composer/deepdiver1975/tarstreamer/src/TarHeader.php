<?php

namespace ownCloud\TarStreamer;

class TarHeader {
	private $name = '';
	
	private $mode = '777';
	
	private $uid = '0';
	
	private $gid = '0';
	
	private $size;
	
	private $mtime = '';
	
	private $checksum;
	
	private $typeflag;
	
	private $linkname = '';
	
	private $magic = 'ustar';
	
	private $version = '00';
	
	private $uname = '';
	
	private $gname = '';
	
	private $devmajor = '';
	
	private $devminor = '';
	
	private $prefix = '';
	
	private $reserved = '';
	
	public function setName($name){
		$this->name = $name;
		return $this;
	}
	
	public function setSize($size){
		$this->size = $size;
		return $this;
	}
	
	public function setMtime($mtime){
		$this->mtime = $mtime;
		return $this;
	}
	
	public function setTypeflag($typeflag){
		$this->typeflag = $typeflag;
		return $this;
	}
	
	public function setPrefix($prefix){
		$this->prefix = $prefix;
		return $this;
	}

	public function getHeader(){
		$fields = [
			['a100', substr($this->name, 0, 100)],
			['a8', str_pad($this->mode, 7, '0', STR_PAD_LEFT)],
			['a8', decoct(str_pad($this->uid, 7, '0', STR_PAD_LEFT))],
			['a8', decoct(str_pad($this->gid, 7, '0', STR_PAD_LEFT))],
			['a12', str_pad(decoct($this->size), 11, '0', STR_PAD_LEFT)],
			['a12', str_pad(decoct($this->mtime), 11, '0', STR_PAD_LEFT)],
			// We calculate checksum later
			['a8', ''],
			['a1', $this->typeflag],
			['a100', $this->linkname],
			['a6', $this->magic],
			['a2', $this->version],
			['a32', $this->uname],
			['a32', $this->gname],
			['a8', $this->devmajor],
			['a8', $this->devminor],
			['a155', substr($this->prefix, 0, 155)],
			['a12', $this->reserved],
		];
		
		// pack fields and calculate "total" length
		$header = $this->packFields($fields);
		
		// Compute header checksum
		$checksum = str_pad(decoct($this->computeUnsignedChecksum($header)), 6, "0", STR_PAD_LEFT);
		for ($i = 0; $i < 6; $i++){
			$header[(148 + $i)] = substr($checksum, $i, 1);
		}
		$header[154] = chr(0);
		$header[155] = chr(32);
		
		return $header;
	}
	
	/**
	 * Create a format string and argument list for pack(), then call pack() and return the result.
	 *
	 * @param array $fields key being the format string and value being the data to pack
	 * @return string binary packed data returned from pack()
	 */
	protected function packFields($fields){
		list ($fmt, $args) = ['', []];

		// populate format string and argument list
		foreach ($fields as $field){
			$fmt .= $field[0];
			$args[] = $field[1];
		}

		// prepend format string to argument list
		array_unshift($args, $fmt);

		// build output string from header and compressed data
		return call_user_func_array('pack', $args);
	}
	
	/**
	 * Generate unsigned checksum of header
	 *
	 * @param string $header
	 * @return string unsigned checksum
	 */
	protected function computeUnsignedChecksum($header){
		$unsignedChecksum = 0;
		for ($i = 0; $i < 512; $i++){
			$unsignedChecksum += ord($header[$i]);
		}
		for ($i = 0; $i < 8; $i++){
			$unsignedChecksum -= ord($header[148 + $i]);
		}
		$unsignedChecksum += ord(" ") * 8;

		return $unsignedChecksum;
	}
}
