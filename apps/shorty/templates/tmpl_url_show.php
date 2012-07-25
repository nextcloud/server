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
 * @file templates/tmpl_url_show.php
 * A read-only dialog visualizing all aspects of a selected shorty.
 * @access public
 * @author Christian Reiner
 */
?>

<!-- (hidden) dialog to show a shorty from the list -->
<form id="dialog-show" class="shorty-dialog shorty-standalone">
  <fieldset>
    <legend class="">
      <a id="close" class="shorty-close-button"
        title="<?php echo OC_Shorty_L10n::t('Close'); ?>">
        <img alt="<?php echo OC_Shorty_L10n::t('Close'); ?>"
            src="<?php echo OCP\Util::imagePath('shorty','actions/shade.png');  ?>">
      </a>
      <?php echo OC_Shorty_L10n::t('Show details').':'; ?>
    </legend>
    <label for="source"><?php echo OC_Shorty_L10n::t('Source url').':'; ?></label>
    <input id="source" name="source" type="text" data="" class="" readonly disabled />
    <br />
    <label for="target"><?php echo OC_Shorty_L10n::t('Target url').':'; ?></label>
    <input id="target" name="target" data="" class="" readonly disabled />
    <br />
    <label for="meta">&nbsp;</label>
    <span id="meta">
      <img id="staticon"  class="shorty-icon" src="" data="<?php echo OCP\Util::imagePath('shorty', 'status/neutral.png'); ?>">
      <img id="schemicon" class="shorty-icon" src="" data="<?php echo OCP\Util::imagePath('shorty', 'blank.png'); ?>">
      <img id="favicon"   class="shorty-icon" src="" data="<?php echo OCP\Util::imagePath('shorty', 'blank.png'); ?>">
      <img id="mimicon"   class="shorty-icon" src="" data="<?php echo OCP\Util::imagePath('shorty', 'blank.png'); ?>">
      <a id="explanation" maxlength="80" data="" class="shorty-value"></a>
    </span>
    <br />
    <label for="title"><?php echo OC_Shorty_L10n::t('Shorty title').':'; ?></label>
    <input id="title" name="title" type="text" data="" class="" readonly disabled />
    <br />
    <label for="status"><?php echo OC_Shorty_L10n::t('Status').':'; ?></label>
    <input id="status" name="status" type="text" data="" class="" style="width:8em;" readonly disabled />
    <span class="label-line">
    <label for="until"><?php echo OC_Shorty_L10n::t('Expiration').':'; ?></label>
    <input id="until" name="until" type="text" data="" class="" style="width:12em;" readonly disabled />
    </span>
    <br />
    <label for="notes"><?php echo OC_Shorty_L10n::t('Notes').':'; ?></label>
    <input id="notes" name="notes" data="" class="" readonly disabled />
    <br />
    <span class="label-line">
    <label for="clicks"><?php echo OC_Shorty_L10n::t('Clicks').':'; ?></label>
    <input id="clicks" name="clicks" data="" type="textarea" class="" style="width:1em;" readonly disabled />
    <label for="created"><?php echo OC_Shorty_L10n::t('Creation').':'; ?></label>
    <input id="created" name="created" type="text" data="" class="" style="width:7em;" readonly disabled />
    <label for="accessed"><?php echo OC_Shorty_L10n::t('Access').':'; ?></label>
    <input id="accessed" name="accessed" type="text" data="" class="" style="width:10em;" readonly disabled />
    </span>
  </fieldset>
</form>
