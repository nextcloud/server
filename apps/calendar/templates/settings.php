<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<form id="calendar">
        <fieldset class="personalblock">
	<strong><?php echo $l->t('Calendar'); ?></strong>
        <table class="nostyle">
            <tr><td><label for="timezone" class="bold"><?php echo $l->t('Timezone');?></label></td><td><select style="display: none;" id="timezone" name="timezone">
                <?php
                $continent = '';
                foreach($_['timezones'] as $timezone):
                    $ex=explode('/', $timezone, 2);//obtain continent,city
                    if (!isset($ex[1])) {
                            $ex[1] = $ex[0];
                            $ex[0] = "Other";
                    }
                    if ($continent!=$ex[0]):
                        if ($continent!="") echo '</optgroup>';
                        echo '<optgroup label="'.$ex[0].'">';
                    endif;
					$city=strtr($ex[1], '_', ' ');
                    $continent=$ex[0];
                    echo '<option value="'.$timezone.'"'.($_['timezone'] == $timezone?' selected="selected"':'').'>'.$city.'</option>';
                endforeach;?>
            </select><input type="checkbox" name="timezonedetection" id="timezonedetection"><label for="timezonedetection"><?php echo $l->t('Check always for changes of the timezone'); ?></label></td></tr>

            <tr><td><label for="timeformat" class="bold"><?php echo $l->t('Timeformat');?></label></td><td>
                <select style="display: none;" id="timeformat" title="<?php echo "timeformat"; ?>" name="timeformat">
                    <option value="24" id="24h"><?php echo $l->t("24h"); ?></option>
                    <option value="ampm" id="ampm"><?php echo $l->t("12h"); ?></option>
                </select>
            </td></tr>

        </table>

        <?php echo $l->t('Calendar CalDAV syncing address:');?>
        <?php echo OC_Helper::linkTo('apps/calendar', 'caldav.php', null, true); ?><br />
        </fieldset>
</form>
