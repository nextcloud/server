<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Wrapper that provides callbacks for url actions such as fopen, unlink, rename
 *
 * Usage:
 *
 * $path = UrlCallBack('/path/so/source', function(){
 *    echo 'fopen';
 * }, function(){
 *    echo 'opendir';
 * }, function(){
 *    echo 'mkdir';
 * }, function(){
 *    echo 'rename';
 * }, function(){
 *    echo 'rmdir';
 * }, function(){
 *    echo 'unlink';
 * }, function(){
 *    echo 'stat';
 * });
 *
 * mkdir($path);
 * ...
 *
 * All callbacks are called after the operation is executed on the source stream
 */
class UrlCallback extends Wrapper implements Url {

	/**
	 * @param string $source
	 * @param callable $fopen
	 * @param callable $opendir
	 * @param callable $mkdir
	 * @param callable $rename
	 * @param callable $rmdir
	 * @param callable $unlink
	 * @param callable $stat
	 * @return \Icewind\Streams\Path
	 *
	 * @throws \BadMethodCallException
	 * @throws \Exception
	 */
	public static function wrap($source, $fopen = null, $opendir = null, $mkdir = null, $rename = null, $rmdir = null,
								$unlink = null, $stat = null) {
		$options = array(
			'source' => $source,
			'fopen' => $fopen,
			'opendir' => $opendir,
			'mkdir' => $mkdir,
			'rename' => $rename,
			'rmdir' => $rmdir,
			'unlink' => $unlink,
			'stat' => $stat
		);
		return new Path('\Icewind\Streams\UrlCallBack', $options);
	}

	protected function loadContext($url) {
		list($protocol) = explode('://', $url);
		$options = stream_context_get_options($this->context);
		return $options[$protocol];
	}

	protected function callCallBack($context, $callback) {
		if (is_callable($context[$callback])) {
			call_user_func($context[$callback]);
		}
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = $this->loadContext($path);
		$this->callCallBack($context, 'fopen');
		$this->setSourceStream(fopen($context['source'], $mode));
		return true;
	}

	public function dir_opendir($path, $options) {
		$context = $this->loadContext($path);
		$this->callCallBack($context, 'opendir');
		$this->setSourceStream(opendir($context['source']));
		return true;
	}

	public function mkdir($path, $mode, $options) {
		$context = $this->loadContext($path);
		$this->callCallBack($context, 'mkdir');
		return mkdir($context['source'], $mode, $options & STREAM_MKDIR_RECURSIVE);
	}

	public function rmdir($path, $options) {
		$context = $this->loadContext($path);
		$this->callCallBack($context, 'rmdir');
		return rmdir($context['source']);
	}

	public function rename($source, $target) {
		$context = $this->loadContext($source);
		$this->callCallBack($context, 'rename');
		list(, $target) = explode('://', $target);
		return rename($context['source'], $target);
	}

	public function unlink($path) {
		$context = $this->loadContext($path);
		$this->callCallBack($context, 'unlink');
		return unlink($context['source']);
	}

	public function url_stat($path, $flags) {
		throw new \Exception('stat is not supported due to php bug 50526');
	}
}
