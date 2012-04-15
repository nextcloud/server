<?php
/**
 * Tag - provides a 'tag' object
 *
 * PHP version 5.3
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */

namespace Granite\Git;

/**
 * Tag represents a full tag object
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Tag
{

    public function __construct($path, $sha)
    {
      $this->sha = $sha;
    }

    public function sha()
    {
      return $this->sha;
    }

}
