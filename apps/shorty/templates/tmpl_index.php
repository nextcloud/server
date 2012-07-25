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
?>

<?php
/**
 * @file templates/tmpl_index.php
 * The general html environment where specific templates are bedded into. 
 * @access public
 * @author Christian Reiner
 */
?>

<!-- central notification area -->
<div id='notification'></div>

<!-- top control bar -->
<div id="controls" class="controls shorty-controls" data-referrer="<?php if (array_key_exists('shorty-referrer',$_)) echo $_['shorty-referrer']; ?>">
  <!-- button to add a new entry to list -->
  <input type="button" id="add" value="<?php echo OC_Shorty_L10n::t('New Shorty'); ?>"/>
  <!-- display label: number of entries in list -->
  <span>
        <a class="shorty-prompt"><?php echo OC_Shorty_L10n::t('Number of entries') ?>:</a>
        <a id="sum_shortys" class="shorty-value">
        <img src="<?php echo OCP\Util::imagePath('core', 'loading.gif'); ?>" /></a>
  </span>
  <!-- display label: total of clicks in list -->
  <span>
        <a class="shorty-prompt"><?php echo OC_Shorty_L10n::t('Total of clicks') ?>:</a>
        <a id="sum_clicks" class="shorty-value">
        <img src="<?php echo OCP\Util::imagePath('core', 'loading.gif'); ?>" /></a>
  </span>
  <!-- the dialogs, hidden by default --> 
<?php require_once('tmpl_url_add.php'); ?>
<?php require_once('tmpl_url_edit.php'); ?>
<?php require_once('tmpl_url_show.php'); ?>
<?php require_once('tmpl_url_share.php'); ?>
</div>

<!-- the "desktop where the action takes place -->
<div id="desktop" class="right-content shorty-desktop">
<?php require_once('tmpl_url_list.php'); ?>
</div>
