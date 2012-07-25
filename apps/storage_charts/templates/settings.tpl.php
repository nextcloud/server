<?php

/**
* ownCloud - DjazzLab Storage Charts plugin
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

?>
<form id="storage_charts" method="POST" action="<?php print(OC_Helper::linkTo('settings','personal.php')); ?>">
	<fieldset class="personalblock">
		<strong>DjazzLab Storage Charts</strong><span style="margin-left:10px;color:#BBB;font-style:italic;"><?php print($l->t('Uncheck charts you do not want to display')); ?></span>
		<?php foreach($_['displays'] as $chart => $is_enable){
			if(strcmp($chart, 'cpie_rfsus') == 0){
				$chart_title = 'Current ratio free space / used space';
			}elseif(strcmp($chart, 'clines_usse') == 0){
				$chart_title = 'Daily Used Space Evolution';
			}else{
				$chart_title = 'Monthly Used Space Evolution'; 
			} ?>
			<div><input type="checkbox" name="storage_charts_disp[]" id="<?php print($chart); ?>_e" style="margin-right:10px;"<?php print($is_enable?' checked':'') ?> value="<?php print($chart); ?>" /><?php print($l->t($chart_title)); ?></div>
		<?php } ?>
		<input type="submit" value="<?php print($l->t('Save')); ?>" /><span style="color:#00A220;"><?php if(isset($_['stc_save_ok'])){print($l->t('Save OK'));} ?></span>
	</fieldset>
</form>
