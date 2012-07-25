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
 * @file templates/tmpl_dlg_verify.php
 * Dialog popup to validate a configured static backend base
 * @access public
 * @author Christian Reiner
 */
?>

<!-- a (usually hidden) dialog used for verification of the correct setup of the 'static' backend -->
<div id="dialog-verification" style="display:none;" title="<?php echo $l->t("'Static' backend: base url verification"); ?>">
  <!-- verification-in-progress -->
  <div id="hourglass">
    <img src="<?php echo OCP\Util::imagePath('shorty', 'loading-disk.gif'); ?>">
  </div>
  <!-- success -->
  <div id="success" style="display:none;">
    <fieldset>
      <legend>
        <img class="shorty-status" src="<?php echo OCP\Util::imagePath('shorty','status/good.png'); ?>" alt="<?php $l->t('Success') ?>" title="<?php $l->t('Verification successful') ?>">
        <span id="title" class="title"><strong>Verification successful !</strong></span>
      </legend>
      <?php echo $l->t("<p>Great, your setup appears to be working fine ! </p>".
                       "<p>Requests to the configured base url '%s' are mapped to this ownClouds shorty module at '%1\$s'</p>".
                       "<p>Usage of that static backend is fine and safe as long as this setup is not altered.</p>",
                       array('<a id="verification-target" style="font-family:Monospace;"></a>',OCP\Util::linkToAbsolute('shorty','index.php')) );?>
    </fieldset>
  </div>
  <!-- failure -->
  <div id="failure" style="display:none;">
    <fieldset>
      <legend>
        <img class="shorty-status" src="<?php echo OCP\Util::imagePath('shorty','status/bad.png'); ?>" alt="<?php $l->t('Success') ?>" title="<?php $l->t('Verification successful') ?>">
        <span id="title" class="title"><strong>Verification failed !</strong></span>
      </legend>
      <?php echo $l->t("Sorry, but your setup appears not be be working correctly yet.<p>".
                       "Please check your setup and make sure that the configured base url '%1\$s' is indeed correct ".
                       "and that all requests to it are somehow mapped to ownClouds shorty module at '%2\$s'.",
                       array('<a id="verification-target" style="font-family:Monospace;"></a>',OCP\Util::linkToAbsolute('shorty','index.php')) );?>
    </fieldset>
  </div>
</div>
<!-- end of verification dialog -->
