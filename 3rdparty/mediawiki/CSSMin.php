<?php
/**
 * Minification of CSS stylesheets.
 *
 * Copyright 2010 Wikimedia Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * 		http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed
 * under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS
 * OF ANY KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 *
 * @file
 * @version 0.1.1 -- 2010-09-11
 * @author Trevor Parscal <tparscal@wikimedia.org>
 * @copyright Copyright 2010 Wikimedia Foundation
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

/**
 * Transforms CSS data
 *
 * This class provides minification, URL remapping, URL extracting, and data-URL embedding.
 */
class CSSMin {

	/* Constants */

	/**
	 * Maximum file size to still qualify for in-line embedding as a data-URI
	 *
	 * 24,576 is used because Internet Explorer has a 32,768 byte limit for data URIs,
	 * which when base64 encoded will result in a 1/3 increase in size.
	 */
	const EMBED_SIZE_LIMIT = 24576;
	const URL_REGEX = 'url\(\s*[\'"]?(?P<file>[^\?\)\'"]*)(?P<query>\??[^\)\'"]*)[\'"]?\s*\)';

	/* Protected Static Members */

	/** @var array List of common image files extensions and mime-types */
	protected static $mimeTypes = array(
		'gif' => 'image/gif',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'png' => 'image/png',
		'tif' => 'image/tiff',
		'tiff' => 'image/tiff',
		'xbm' => 'image/x-xbitmap',
	);

	/* Static Methods */

	/**
	 * Gets a list of local file paths which are referenced in a CSS style sheet
	 *
	 * @param $source string CSS data to remap
	 * @param $path string File path where the source was read from (optional)
	 * @return array List of local file references
	 */
	public static function getLocalFileReferences( $source, $path = null ) {
		$files = array();
		$rFlags = PREG_OFFSET_CAPTURE | PREG_SET_ORDER;
		if ( preg_match_all( '/' . self::URL_REGEX . '/', $source, $matches, $rFlags ) ) {
			foreach ( $matches as $match ) {
				$file = ( isset( $path )
					? rtrim( $path, '/' ) . '/'
					: '' ) . "{$match['file'][0]}";

				// Only proceed if we can access the file
				if ( !is_null( $path ) && file_exists( $file ) ) {
					$files[] = $file;
				}
			}
		}
		return $files;
	}

	/**
	 * @param $file string
	 * @return bool|string
	 */
	protected static function getMimeType( $file ) {
		$realpath = realpath( $file );
		// Try a couple of different ways to get the mime-type of a file, in order of
		// preference
		if (
			$realpath
			&& function_exists( 'finfo_file' )
			&& function_exists( 'finfo_open' )
			&& defined( 'FILEINFO_MIME_TYPE' )
		) {
			// As of PHP 5.3, this is how you get the mime-type of a file; it uses the Fileinfo
			// PECL extension
			return finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $realpath );
		} elseif ( function_exists( 'mime_content_type' ) ) {
			// Before this was deprecated in PHP 5.3, this was how you got the mime-type of a file
			return mime_content_type( $file );
		} else {
			// Worst-case scenario has happened, use the file extension to infer the mime-type
			$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
			if ( isset( self::$mimeTypes[$ext] ) ) {
				return self::$mimeTypes[$ext];
			}
		}
		return false;
	}

	/**
	 * Remaps CSS URL paths and automatically embeds data URIs for URL rules
	 * preceded by an /* @embed * / comment
	 *
	 * @param $source string CSS data to remap
	 * @param $local string File path where the source was read from
	 * @param $remote string URL path to the file
	 * @param $embedData bool If false, never do any data URI embedding, even if / * @embed * / is found
	 * @return string Remapped CSS data
	 */
	public static function remap( $source, $local, $remote, $embedData = true ) {
		$pattern = '/((?P<embed>\s*\/\*\s*\@embed\s*\*\/)(?P<pre>[^\;\}]*))?' .
			self::URL_REGEX . '(?P<post>[^;]*)[\;]?/';
		$offset = 0;
		while ( preg_match( $pattern, $source, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
			// Skip fully-qualified URLs and data URIs
			$urlScheme = parse_url( $match['file'][0], PHP_URL_SCHEME );
			if ( $urlScheme ) {
				// Move the offset to the end of the match, leaving it alone
				$offset = $match[0][1] + strlen( $match[0][0] );
				continue;
			}
			// URLs with absolute paths like /w/index.php need to be expanded
			// to absolute URLs but otherwise left alone
			if ( $match['file'][0] !== '' && $match['file'][0][0] === '/' ) {
				// Replace the file path with an expanded (possibly protocol-relative) URL
				// ...but only if wfExpandUrl() is even available.
				// This will not be the case if we're running outside of MW
				$lengthIncrease = 0;
				if ( function_exists( 'wfExpandUrl' ) ) {
					$expanded = wfExpandUrl( $match['file'][0], PROTO_RELATIVE );
					$origLength = strlen( $match['file'][0] );
					$lengthIncrease = strlen( $expanded ) - $origLength;
					$source = substr_replace( $source, $expanded,
						$match['file'][1], $origLength
					);
				}
				// Move the offset to the end of the match, leaving it alone
				$offset = $match[0][1] + strlen( $match[0][0] ) + $lengthIncrease;
				continue;
			}
			// Shortcuts
			$embed = $match['embed'][0];
			$pre = $match['pre'][0];
			$post = $match['post'][0];
			$query = $match['query'][0];
			$url = "{$remote}/{$match['file'][0]}";
			$file = "{$local}/{$match['file'][0]}";
			// bug 27052 - Guard against double slashes, because foo//../bar
			// apparently resolves to foo/bar on (some?) clients
			$url = preg_replace( '#([^:])//+#', '\1/', $url );
			$replacement = false;
			if ( $local !== false && file_exists( $file ) ) {
				// Add version parameter as a time-stamp in ISO 8601 format,
				// using Z for the timezone, meaning GMT
				$url .= '?' . gmdate( 'Y-m-d\TH:i:s\Z', round( filemtime( $file ), -2 ) );
				// Embedding requires a bit of extra processing, so let's skip that if we can
				if ( $embedData && $embed ) {
					$type = self::getMimeType( $file );
					// Detect when URLs were preceeded with embed tags, and also verify file size is
					// below the limit
					if (
						$type
						&& $match['embed'][1] > 0
						&& filesize( $file ) < self::EMBED_SIZE_LIMIT
					) {
						// Strip off any trailing = symbols (makes browsers freak out)
						$data = base64_encode( file_get_contents( $file ) );
						// Build 2 CSS properties; one which uses a base64 encoded data URI in place
						// of the @embed comment to try and retain line-number integrity, and the
						// other with a remapped an versioned URL and an Internet Explorer hack
						// making it ignored in all browsers that support data URIs
						$replacement = "{$pre}url(data:{$type};base64,{$data}){$post};";
						$replacement .= "{$pre}url({$url}){$post}!ie;";
					}
				}
				if ( $replacement === false ) {
					// Assume that all paths are relative to $remote, and make them absolute
					$replacement = "{$embed}{$pre}url({$url}){$post};";
				}
			} elseif ( $local === false ) {
				// Assume that all paths are relative to $remote, and make them absolute
				$replacement = "{$embed}{$pre}url({$url}{$query}){$post};";
			}
			if ( $replacement !== false ) {
				// Perform replacement on the source
				$source = substr_replace(
					$source, $replacement, $match[0][1], strlen( $match[0][0] )
				);
				// Move the offset to the end of the replacement in the source
				$offset = $match[0][1] + strlen( $replacement );
				continue;
			}
			// Move the offset to the end of the match, leaving it alone
			$offset = $match[0][1] + strlen( $match[0][0] );
		}
		return $source;
	}

	/**
	 * Removes whitespace from CSS data
	 *
	 * @param $css string CSS data to minify
	 * @return string Minified CSS data
	 */
	public static function minify( $css ) {
		return trim(
			str_replace(
				array( '; ', ': ', ' {', '{ ', ', ', '} ', ';}' ),
				array( ';', ':', '{', '{', ',', '}', '}' ),
				preg_replace( array( '/\s+/', '/\/\*.*?\*\//s' ), array( ' ', '' ), $css )
			)
		);
	}
}
