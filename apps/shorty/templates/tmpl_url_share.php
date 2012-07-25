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
 * @file templates/tmpl_url_share.php
 * A dialog offering control over an entries state and offers the source url
 * @access public
 * @author Christian Reiner
 */
?>

<!-- (hidden) dialog to share a shorty from the list -->
<form id="dialog-share" class="shorty-dialog shorty-embedded">
  <fieldset>
    <legend class="">
      <a id="close" class="shorty-close-button"
        title="<?php echo OC_Shorty_L10n::t('Close'); ?>">
        <img alt="<?php echo OC_Shorty_L10n::t('Close'); ?>"
            src="<?php echo OCP\Util::imagePath('apps/shorty','actions/shade.png');  ?>">
      </a>
      <?php echo OC_Shorty_L10n::t('Test and use').':'; ?>
    </legend>
    <input id="id" name="id" type="hidden" readonly data="" class="" readonly disabled />
    <label for="status"><?php echo OC_Shorty_L10n::t('Status').':'; ?></label>
    <select id="status" name="status" data="" class="" value="">
    <?php
      foreach ( OC_Shorty_Type::$STATUS as $status )
        if ( 'deleted'!=$status )
          echo sprintf ( "<option value=\"%s\">%s</option>\n", $status, OC_Shorty_L10n::t($status) );
    ?>
    </select>
    <br />
    <label for="source"><?php echo OC_Shorty_L10n::t('Source url').':'; ?></label>
    <a id="source" class="shorty-clickable" target="_blank"
       title="<?php echo OC_Shorty_L10n::t('Open source url'); ?>"
       href=""></a>
    <br />
    <label for="relay"><?php echo OC_Shorty_L10n::t('Relay url').':'; ?></label>
    <a id="relay" class="shorty-clickable" target="_blank"
       title="<?php echo OC_Shorty_L10n::t('Open relay url'); ?>"
       href=""></a>
    <br />
    <label for="target"><?php echo OC_Shorty_L10n::t('Target url').':'; ?></label>
    <a id="target" class="shorty-clickable" target="_blank"
       title="<?php echo OC_Shorty_L10n::t('Open target url'); ?>"
       href=""></a>
    <br />
    <img id="usage-email" name="usage-email" class="shorty-usage" alt="email"
         src="<?php echo OCP\Util::imagePath('apps/shorty','usage/64/email.png'); ?>"
         title="<?php echo OC_Shorty_L10n::t("Send by email"); ?>" />
    <img id="usage-sms" type="image" name="usage-sms" alt="sms"
         class="shorty-usage <?php echo $_['sms-control']; ?>"
         src="<?php echo OCP\Util::imagePath('apps/shorty','usage/64/sms.png'); ?>"
         title="<?php echo OC_Shorty_L10n::t("Send by SMS"); ?>" />
    <img id="usage-qrcode" type="image" name="usage-qrcode" class="shorty-usage" alt="qrcode"
         src="<?php echo OCP\Util::imagePath('apps/shorty','usage/64/qrcode.png'); ?>"
         title="<?php echo OC_Shorty_L10n::t("Show as QRCode"); ?>" />
    <img id="usage-clipboard" type="image" name="usage-clipboard" class="shorty-usage" alt="clipbaord"
         src="<?php echo OCP\Util::imagePath('apps/shorty','usage/64/clipboard.png'); ?>"
         title="<?php echo OC_Shorty_L10n::t("Copy to clipboard"); ?>" />
  </fieldset>
</form>

<?php require_once('tmpl_dlg_qrcode.php'); ?>
