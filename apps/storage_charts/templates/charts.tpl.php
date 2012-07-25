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
* JS minified by http://fmarcia.info/jsmin/test.html
* 
*/

OCP\Util::addStyle('storage_charts', 'styles');
OCP\Util::addScript('storage_charts', 'highCharts-2.2.1/highcharts.min');
OCP\Util::addScript('3rdparty','chosen/chosen.jquery.min');
OCP\Util::addStyle('3rdparty','chosen');
OCP\Util::addScript('storage_charts', 'units.min');

?>

<script type="text/javascript">
    $(document).ready(function(){
    	$('#stc_sortable').sortable({
    		axis:'y',handle:'h3',placeholder:'ui-state-highlight',update:function(e,u){
    			$.ajax({
		        	type:'POST',
		        	url:OC.linkTo('storage_charts','ajax/config.php'),
		        	dataType:'json',
		        	data:{o:'set',k:'sc_sort',i:$('#stc_sortable').sortable('toArray')},
		        	async:true
		        });
    		}
    	});
    	$('#stc_sortable').disableSelection();
    });
</script>

<div id="storage-charts">
	<div class="personalblock topblock titleblock">
		DjazzLab Storage Charts<span><?php print($l->t('Drag\'N\'Drop on the chart title to re-order')); ?></span>
	</div>
</div>
<div id="stc_frame">
	<div id="stc_sortable">
		<?php foreach($_['sc_sort'] as $sc_sort){
			if(strcmp($sc_sort, 'cpie_rfsus') == 0){
				$sc_sort_title = 'Current ratio free space / used space';
			}elseif(strcmp($sc_sort, 'clines_usse') == 0){
				$sc_sort_title = 'Daily Used Space Evolution';
			}else{
				$sc_sort_title = 'Monthly Used Space Evolution';
			}
			if($_['c_disp'][$sc_sort]){ ?>
			<div id="<?php print($sc_sort); ?>" class="personalblock">
				<h3><img src="<?php print(OCP\Util::imagePath('storage_charts', 'move.png')); ?>" /><?php print($l->t($sc_sort_title).' '.$l->t('for')); ?> "<?php print(OC_Group::inGroup(OCP\User::getUser(), 'admin')?$l->t('all users'):OCP\User::getUser()); ?>"</h3>
				<div id="<?php print(substr($sc_sort, 1)); ?>" style="max-width:100%;height:400px;margin:0 auto"></div>
				<script type="text/javascript">$(document).ready(function(){<?php print(OC_DLStChartsLoader::loadChart($sc_sort, $l)); ?>});</script>
			</div>
			<?php }
		} ?>
	</div>
	<?php if($_['c_disp']['clines_usse']){print('<script type="text/javascript">$(document).ready(function(){getLinesUsseUnitsSelect('.$_['hu_size'].');});</script>');}
	if($_['c_disp']['chisto_us']){print('<script type="text/javascript">$(document).ready(function(){getHistoUsUnitsSelect(' . $_['hu_size_hus'] . ');});</script>');} ?>
</div>