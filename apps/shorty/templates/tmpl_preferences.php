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
 * @file templates/tmpl_preferences.php
 * Dialog to change user preferences, to be included in the clouds preferences page.
 * @access public
 * @author Christian Reiner
 */
?>

<form id="shorty">
  <fieldset class="personalblock">
    <div id="title" class="title">
      <img class="" src="<?php echo OCP\Util::imagePath("shorty","shorty.png"); ?> ">
      <strong>Shorty</strong>
    </div>
    <div id="settings">
      <!-- shortlet -->
      <label for="shortlet" class="aspect"><?php echo $l->t("Shortlet").":";?></label>
      <span id="shortlet">
        <a class="shortlet"
           href="javascript:(function(){url=encodeURIComponent(location.href);window.open('<?php echo OCP\Util::linkToAbsolute('shorty', 'index.php'); ?>&url='+url, 'owncloud-shorty')%20})()">
          <?php echo $l->t("Add page as 'Shorty' to ownCloud"); ?>
        </a>
      </span>
      <p>
      <span class="explain"><em><?php echo $l->t("Drag this to your browser bookmarks.");
                                      echo $l->t("Click it, for whatever site you want to create a Shorty."); ?></em></span>
      </p>
      <p>
      <!-- backend selection -->
      <label for="backend-type" class="aspect"><?php echo $l->t("Backend").":";?></label>
      <!-- list of available backend types -->
      <span style="margin-right:1em;">
        <select id="backend-type" name="backend-type" style="width:11em;" class="chosen"
                placeholder="<?php echo $l->t("Choose service…"); ?>" >
          <?php
            foreach ( $_['backend-types'] as $value=>$display )
              echo sprintf ( "        <option value=\"%s\" %s>%s</option>\n",
                             $value,
                             ($value==$_['backend-type']?'selected':''),
                             $l->t($display) );
          ?>
        </select>
      </span>
      <!-- some additional fields: input, explanation and example -->
      <!-- depending on the chosen backend-type above only one of the following span tags will be displayed -->
      <span id="backend-none" class="backend-supplement" style="display:none;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':';?></label>
          <span id="example" class="example"><?php echo sprintf('http://%s%s<em>&lt;shorty id&gt;</em>',$_SERVER['SERVER_NAME'],OCP\Util::linkTo('shorty','',false)) ?></span>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo sprintf('%s<br />%s<br />%s',
                             $l->t("No backend is used, direct links pointing to your ownCloud are generated."),
                             $l->t("Such links are most likely longer than those generated when using a backend."),
                             $l->t("However this option does not rely on any third party service and keeps your shortys under your control.") ); ?>
        </span>
      </span>
      <!-- backend -static- -->
      <span id="backend-static" class="backend-supplement" style="display:none;">
        <label for="backend-static-base" class="aspect"><?php echo $l->t("Base url").':';?></label>
        <input id="backend-static-base" type="text" name="backend-static-base"
               readonly disabled value="<?php echo $_['backend-static-base']; ?>"
               placeholder="<?php echo $l->t("Specify a backend base url…"); ?>" style="width:25em;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':';?></label>
          <a id="example" class="example" title="<?php echo $l->t("Verification by click");?>">
            <?php echo sprintf('http://%s/<em>&lt;service&gt;</em>/<em>&lt;shorty id&gt;</em>',$_SERVER['SERVER_NAME']) ?>
          </a>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo sprintf("%s<br />\n%s<br />\n%s<br />\n%s",
                             $l->t("A static, rule-based backend is used, shorty links are generated relative to a given base url."),
                             $l->t("Since this setup depends on server based configuration rules the base url can only be specified in the 'Admin' section of the configuration."),
                             $l->t("Have a try with the example link provided, click it, it should result in a confirmation that your setup is working."),
                             $l->t("Only use this backend, if you can provide a short base url that is mapped the described way. Your shorties won't work otherwise.") ); ?>
        </span>
      </span>
      <!-- backend bit.ly -->
      <span id="backend-bitly" class="backend-supplement" style="display:none;">
        <label for="backend-bitly-user" class="aspect"><?php echo $l->t("bit.ly user").':';?></label>
        <input id="backend-bitly-user" type="text" name="backend-bitly-user" value="<?php echo $_['backend-bitly-user']; ?>"
               maxlength="256" placeholder="<?php echo $l->t("bit.ly user name");?>" style="width:10em;">
        <label for="backend-bitly-key" class="aspect"><?php echo $l->t("bit.ly key").':';?></label>
        <input id="backend-bitly-key" type="text" name="backend-bitly-key" value="<?php echo $_['backend-bitly-key']; ?>"
               maxlength="256" placeholder="<?php echo $l->t("bit.ly users key");?>" style="width:18em;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':';?></label>
          <span id="example" class="example"><?php echo sprintf('http://bitly.com/<em>&lt;shorty id&gt;</em>') ?></span>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo sprintf("%s<br />\n%s<br />\n%s",
                             $l->t("The external 'bitly.com' service is used to register a short url for each generated shorty."),
                             $l->t("The service requires you to authenticate yourself by providing a valid bit.ly user name and an '%s'.",
                                   sprintf('<a class="external" href="http://bitly.com/a/your_api_key" target="_blank">%s</a>',$l->t("API access key")) ),
                             $l->t("This means you have to '%s' at their site first.", 
                                   sprintf('<a class="external" href="http://bitly.com/a/sign_up" target="_blank">%s</a>',$l->t("register an account")) ) ); ?>
        </span>
      </span>
      <!-- backend cligs -->
      <span id="backend-cligs" class="backend-supplement" style="display:none;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':';?></label>
          <span id="example" class="example"><?php echo sprintf('http://cli.gs/<em>&lt;shorty id&gt;</em>') ?></span>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo $l->t("The external 'cli.gs' service is used to register a short url for each generated shorty.");?>
        </span>
      </span>
      <!-- backend is.gd -->
      <span id="backend-isgd" class="backend-supplement" style="display:none;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':';?></label>
          <span id="example" class="example"><?php echo sprintf('http://is.gd/<em>&lt;shorty id&gt;</em>') ?></span>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo $l->t("The external 'is.gd' service is used to register a short url for each generated shorty.");?>
        </span>
      </span>
      <!-- backend google -->
      <span id="backend-google" class="backend-supplement" style="display:none;">
        <label for="backend-google-key" class="aspect"><?php echo $l->t("API key").':';?></label>
        <input id="backend-google-key" type="text" name="backend-google-key" value="<?php echo $_['backend-google-key']; ?>"
               maxlength="256" placeholder="<?php echo $l->t("Google API key");?>" style="width:24em;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':';?></label>
          <span id="example" class="example"><?php echo sprintf('http://goo.gl/<em>&lt;shorty id&gt;</em>') ?></span>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo sprintf("%s<br />\n%s<br />\n%s<br />\n%s",
                             $l->t("Googles external 'goo.gl service' is used to register a short url for each generated shorty."),
                             $l->t("You must provide a valid '%s' to use this service.",
                                   sprintf('<a class="external" href="https://code.google.com/apis/console/" target="_blank">%s</a>',$l->t("Google API key")) ),
                             $l->t("This means you require a 'Google API console account'."),
                             $l->t("Register a new '%s' at their pages.", array(
                                   sprintf('<a class="external" href="https://code.google.com/apis/console/" target="_blank">%s</a>',$l->t("Google API account")) ) ) );?>
        </span>
      </span>
      <!-- backend tinycc -->
      <span id="backend-tinycc" class="backend-supplement" style="display:none;">
        <label for="backend-tinycc-user" class="aspect"><?php echo $l->t("tiny.cc user").':'; ?></label>
        <input id="backend-tinycc-user" type="text" name="backend-tinycc-user" value="<?php echo $_['backend-tinycc-user']; ?>"
               maxlength="256" placeholder="<?php echo $l->t("tiny.cc user name"); ?>" style="width:10em;">
        <label for="backend-tinycc-key" class="aspect"><?php echo $l->t("tiny.cc key").':'; ?></label>
        <input id="backend-tinycc-key" type="text" name="backend-tinycc-key" value="<?php echo $_['backend-tinycc-key']; ?>"
               maxlength="256" placeholder="<?php echo $l->t("tiny.cc user key"); ?>" style="width:19em;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':';?></label>
          <span id="example" class="example"><?php echo sprintf('http://tiny.cc/<em>&lt;shorty id&gt;</em>') ?></span>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo sprintf ( "%s<br />\n%s<br />\n%s",
                               $l->t("The external 'tiny.cc' service is used to register a short url for each generated shorty."),
                               $l->t("The service requires you to authenticate yourself by providing a valid tiny.cc user name and an api access key."),
                               $l->t("This means you have to register an '%s' at their site first.", array (
                                     sprintf('<a class="external" href="http://tiny.ccc/" target="_blank">%s</a>', $l->t("account") ) ) ) ); ?>
        </span>
      </span>
      <!-- backend tinyURL -->
      <span id="backend-tinyurl" class="backend-supplement" style="display:none;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':'; ?></label>
          <span id="example" class="example"><?php echo sprintf('http://ti.ny/<em>&lt;shorty id&gt;</em>') ?></span>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo $l->t("The external 'ti.ny' service is used to register a short url for each generated shorty.");?>
        </span>
      </span>
      <!-- backend turl -->
      <span id="backend-turl" class="backend-supplement" style="display:none;">
        <br/>
        <label for="backend-example" class="aspect"> </label>
        <span id="backend-example">
          <label for="example" class="aspect"><?php echo $l->t("Example").':'; ?></label>
          <span id="example" class="example"><?php echo sprintf('http://turl.ca/<em>&lt;shorty id&gt;</em>') ?></span>
        </span>
        <br/>
        <span id="explain" class="explain">
          <?php echo $l->t("The external 'turl' service is used to register a short url for each generated shorty."); ?>
        </span>
      </span>
      </p>
      <p>
      <!-- sms -->
      <label for="sms" class="aspect"><?php echo $l->t("SMS").":";?></label>
      <span id="sms" style="margin-right:1em;">
        <select id="sms-control" name="sms-control" style="width:11em;" class="chosen">
          <?php echo sprintf("<option value=\"disabled\" %s>%s</option>\n",
                             ('enabled'!=$_['sms-control']?'selected':''),
                             $l->t('disabled') ); ?>
          <?php echo sprintf("<option value=\"enabled\" %s>%s</option>\n",
                             ('enabled'==$_['sms-control']?'selected':''),
                             $l->t('enabled')); ?>
        </select>
        <em><?php echo $l->t("Enabling the SMS option will offer sending a Shorty via SMS."); ?></em>
      </span>
      <p>
      <span class="explain"><em><?php echo $l->t("Unfortunately support for 'SMS url handling' is usually only found on mobile devices like smart phones.")."<br>\n";
                                      echo $l->t("In addition, the implementations found in Android or iOS are minimalistic, buggy and differ from system to system.")."<br>\n";
                                      echo $l->t("In short: this might not work for you, therefore you can disable it…")."<br>\n";?></em></span>
      </p>
    </div>
    <!-- a (usually hidden) dialog used for verification of the correct setup of the 'static' backend -->
    <?php require_once('tmpl_dlg_verify.php'); ?>
  </fieldset>
</form>
