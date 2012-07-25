<?php
/**
* @package shorty an ownCloud url shortener plugin
* @category internet
* @author Christian Reiner
* @copyright 2011-2012 Christian Reiner <foss@christian-reiner.info>
* @license GNU Affero General Public license (AGPL)
* @link information 
* @link repository https://svn.christian-reiner.info/svn/app/oc/shorty
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the license, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.
* If not, see <http://www.gnu.org/licenses/>.
*
*/

/**
 * @file lib/meta.php
 * Routines to retrieve meta information about a remote url
 * @author Christian Reiner
 */

/**
 * @class OC_Shorty_Meta
 * @brief Static 'namespace' class for url meta information retrieval
 * ownCloud propagates to use static classes as namespaces instead of OOP.
 * This 'namespace' defines routines for the retrieval of meta information about remote urls. 
 * @access public
 * @author Christian Reiner
 */ 
class OC_Shorty_Meta
{

  /**
   * @method OC_Shorty_Meta::fetchMetaData
   * @brief Retrieves the meta information to a given remote url
   * @param url decoded target url for which meta information if requested
   * @returns associative array holding the requested meta data
   * @access public
   * @author Christian Reiner
   */
  static function fetchMetaData ( $url )
  {
    $url_token = parse_url ( $url );
    // some sane fallback values, in case we cannot get the meta data
    $meta = array();
    $meta['target']    = $url;
    $meta['title']     = strtolower ( $url_token['host'] );
    $meta['scheme']    = strtolower ( $url_token['scheme'] );
    $meta['mimetype']  = 'application/octet-stream';
    $meta['schemicon'] = self::selectIcon ( 'scheme', strtolower($url_token['scheme']) );
    // we wont bother retrieving data about other protocols than http or ftp
    if ( ! in_array(strtolower($url_token['scheme']),array('http','https','ftp','ftps')) )
      return $meta;
    // to fetch meta data we rely on curl being installed
    if ( ! function_exists('curl_init') )
      return $meta;
    // try to retrieve the meta data
    $handle = curl_init ( );
    curl_setopt ( $handle, CURLOPT_URL, $url );
    curl_setopt ( $handle, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $handle, CURLOPT_FOLLOWLOCATION, TRUE );
    curl_setopt ( $handle, CURLOPT_MAXREDIRS, 10 );
    if ( FALSE!==($page=curl_exec($handle)) )
    {
      // try to extract title from page
      preg_match ( "/<head>.*<title>(.*)<\/title>.*<\/head>/si", $page, $match );
      $meta['title']    = htmlspecialchars_decode ( $match[1] );
      $meta['staticon'] = self::selectIcon ( 'state', TRUE );
      // final url after a possible redirection
      $meta['final']       = curl_getinfo ( $handle, CURLINFO_EFFECTIVE_URL );
      // try to extract favicon from page
      preg_match ( '/<[^>]*link[^>]*(rel=["\']icon["\']|rel=["\']shortcut icon["\']) .*href=["\']([^>]*)["\'].*>/iU', $page, $match );
      if (1<sizeof($match))
      {
        // the specified uri might be an url, an absolute or a relative path
        // we have to turn it into an url to be able to display it out of context
        $favicon = htmlspecialchars_decode ( $match[2] );
        // test for an url
        if (parse_url($favicon,PHP_URL_SCHEME))
        {
          $meta['favicon'] = $favicon;
        }
        // test for an absolute path
        elseif ( 0===strpos(parse_url($favicon,PHP_URL_PATH),'/') )
        {
          $url_token = parse_url($meta['final']);
          $meta['favicon'] = sprintf( '%s://%s/%s', $url_token['scheme'], $url_token['host'], $favicon );
        }
        // so it appears to be a relative path
        else
        {
          $url_token = parse_url($meta['final']);
          $meta['favicon'] = sprintf( '%s://%s%s%s', $url_token['scheme'], $url_token['host'], dirname($url_token['path']), $favicon );
        }
      }
      $meta['mimetype']    = preg_replace ( '/^([^;]+);.*/i', '$1', curl_getinfo($handle,CURLINFO_CONTENT_TYPE) );
      $meta['mimicon']     = self::selectIcon ( 'mimetype', $meta['mimetype'] );
      $meta['code']        = curl_getinfo ( $handle, CURLINFO_HTTP_CODE );
      $meta['status']      = OC_Shorty_L10n::t ( self::selectCode('status',$meta['code']) );
      $meta['explanation'] = OC_Shorty_L10n::t ( self::selectCode('explanation',$meta['code']) );
    }
    curl_close ( $handle );
    // that's it !
    return $meta;
  } // function fetchMetaData

  /**
   * @method OC_Shorty_Meta::selectCode
   * @brief Some helper utility used to resolve numeric http status codes into human readable strings
   * @param aspect a string indicating a section/pool a code is to be resolved in
   * @param identifier a string indicating a specific code to be resolved
   * @returns a human readable string resolving the specified numeric status code
   * @throws OC_Shorty_Exception in case of an undefined code to be resolved
   * @access public
   * @author Christian Reiner
   */
  static function selectCode ( $aspect, $identifier )
  {
    // map of official http status codes
    $_code_map = array
    (
      'status' => OC_Shorty_Type::$HTTPCODE,
      'explanation' => array
      (
        200 => 'Target url is valid and resolved.',
        201 => 'The request has been fulfilled and created a new ressource.',
        202 => 'The request has been accepted.',
        203 => 'The request yielded in non-authorative information.',
        204 => 'The request has been fulfilled but not produced any content.',
        205 => 'The request has been fulfilled and the view should be reset.',
        206 => 'The request has been fulfilled partially.',
      )
    );
    // resolve specified code against map or provide some fallback content
    if ( key_exists($aspect,$_code_map) && key_exists($identifier,$_code_map[$aspect]) )
      return $_code_map[$aspect][$identifier];
    else
    {
      switch ( $aspect )
      {
        case 'status':      return sprintf("Status %s [unknown]",$identifier);
        case 'explanation': return sprintf("[Undefined status code '%s']",$identifier);
        default: throw new OC_Shorty_Exception ( "unknown aspect '%s' requested to resolve code '%s'",
                                                 array($aspect,$identifier) );
      } // switch
    }
  } // function selectCode

  /**
   * @method OC_Shorty_Meta::selectIcon
   * @brief Some helper utility for the easy integrate of icon references into templates and alike
   * @param aspect a string indicating a section/pool an icon is to be chosen from
   * @param identifier a string indicating a specific icon to be referenced
   * @returns a hyper reference to an icon in form of a string
   * @access public
   * @author Christian Reiner
   */
  static function selectIcon ( $aspect, $identifier )
  {
    switch ( $aspect )
    {
      case 'state':
        switch ($identifier)
        {
          case TRUE:  return OCP\Util::imagePath('shorty', 'status/good.png');
          case FALSE: return OCP\Util::imagePath('shorty', 'status/bad.png');
          default:    return OCP\Util::imagePath('shorty', 'status/neutral.png');
        } // switch identifier
      case 'scheme':
        switch ($identifier)
        {
          case 'http':
          case 'https':   return OCP\Util::imagePath('shorty', 'scheme/H.png');
          case 'ftp':
          case 'ftps':    return OCP\Util::imagePath('shorty', 'scheme/F.png');
          case 'sftp':    return OCP\Util::imagePath('shorty', 'scheme/S.png');
          case 'mailto':  return OCP\Util::imagePath('shorty', 'scheme/M.png');
          case 'gopher':  return OCP\Util::imagePath('shorty', 'scheme/G.png');
          case 'webdav':
          case 'webdavs': return OCP\Util::imagePath('shorty', 'scheme/W.png');
          default:        return OCP\Util::imagePath('shorty', 'blank.png');
        } // switch identifier
      case 'mimetype':
        $identifier = explode('/',$identifier);
        switch ($identifier[0])
        {
          case 'audio':       return OCP\Util::imagePath('core', 'filetypes/audio.png');
          case 'text':        return OCP\Util::imagePath('core', 'filetypes/text.png');
          case 'video':       return OCP\Util::imagePath('core', 'filetypes/video.png');
          case 'application':
            switch ($identifier[1])
            {
              case 'pdf':     return OCP\Util::imagePath('core', 'filetypes/application-pdf.png');
              default:        return OCP\Util::imagePath('shorty', 'blank.png');
            } // switch identifier[1]
          default:            return OCP\Util::imagePath('shorty', 'blank.png');
        } // switch identifier[0]
    } // switch aspect
  } // function selectIcon

} // class OC_Shorty_Meta
?>
