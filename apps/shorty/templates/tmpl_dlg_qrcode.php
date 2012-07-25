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
 * @file templates/tmpl_dlg_qrcode.php
 * Dialog popup to visualize and offer an url as a QRCode (2D barcode)
 * @access public
 * @author Christian Reiner
 */
?>

<!-- additional (hidden) popup dialogs for specific usage actions -->
<fieldset id="dialog-qrcode" style="display:none;" class="" style="align:center;">
  <input id="qrcode-url" type="hidden" value="<?php echo $_['qrcode-url']; ?>">
  <span id='qrcode-img'>
    <?php echo $l->t("Click for qrcode url").":"; ?>
    <br>
  <img width="100%" class="shorty-status" border="1" alt="<?php echo $l->t("QRCode"); ?>"
       src="<?php echo OCP\Util::imagePath('shorty','loading-disk.gif'); ?>" >
  </span>
  <span id='qrcode-val' style="display:none;">
    <?php echo $l->t("Click for qrcode image").":"; ?>
    <br>
    <span class="shorty-framed"><a title="<?php echo $l->t("QRCode url").":"; ?>"></a></span>
  </span>
</fieldset>
<!-- end of qrcode dialog -->
