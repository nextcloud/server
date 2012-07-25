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
 * @file templates/tmpl_settings.php
 * Dialog to change plugin settings, to be included in the clouds settings page.
 * @access public
 * @author Christian Reiner
 */
?>

<!-- settings of app 'shorty' -->
<form id="shorty">
  <fieldset class="personalblock">
    <legend>
      <span id="title" class="title">
        <img class="" src="<?php echo OCP\Util::imagePath("shorty","shorty.png"); ?> ">
        <strong>Shorty</strong>
      </span>
    </legend>
    <div id="backend-static" class="backend-supplement">
      <label for="backend-static-base" class="aspect"><?php echo $l->t("Base url").':';?></label>
      <input id="backend-static-base" type="text" name="backend-static-base"
            value="<?php echo $_['backend-static-base']; ?>"
            maxlength="256" placeholder="<?php echo $l->t('Specify a static base url…');?>" style="width:25em;">
      <br/>
      <label for="backend-example" class="aspect"> </label>
      <span id="backend-example">
        <label for="example" class="aspect"><?php echo $l->t("Example").':';?></label>
        <a id="example" class="example" title="<?php echo $l->t("Verification by click");?>">
          <?php echo sprintf('http://%s/<em>&lt;service&gt;</em>/<em>&lt;shorty id&gt;</em>',$_SERVER['SERVER_NAME']) ?>
        </a>
      </span>
      <br/>
      <span id="explain" class="explain"><?php echo sprintf("%s<br />\n%s<br />\n%s<br />\n%s",
        $l->t("Static, rule-based backend, generates shorty links relative to a given base url."),
        $l->t("You have to take care that any request to the url configured here is internally mapped to the 'shorty' module."),
        $l->t("Have a try with the example link provided, click it, it should result in a confirmation that your setup is working."),
        $l->t("Leave empty if you can't provide a short base url that is mapped the described way.") ); ?>
      </span>
    </div>

    <?php require_once('tmpl_dlg_verify.php'); ?>
  </fieldset>
</form>
