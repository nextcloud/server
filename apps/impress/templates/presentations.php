<?php



// show toolbar
echo('<div id="controls">	
	<a href="'.\OCP\Util::linkToAbsolute('impress','documentation.php').'" class="button docu">'.$l->t('Documentation').'</a>
	</div>
');


if(empty($_['list'])) {
	
    echo('<div id="emptyfolder">No Impress files are found in your ownCloud. Please upload a .impress file.</div>');
			
}else{

	echo('<table class="impresslist" >');
	foreach($_['list'] as $entry) {

		echo('<tr><td width="1"><a target="_blank" href="'.\OCP\Util::linkToAbsolute('impress','player.php').'&file='.urlencode($entry['url']).'&name='.urlencode($entry['name']).'"><img align="left" src="'.\OCP\Util::linkToAbsolute('impress','img/impressbig.png').'"></a></td><td><a target="_blank" href="'.\OCP\Util::linkToAbsolute('impress','player.php').'&file='.urlencode($entry['url']).'&name='.urlencode($entry['name']).'">'.$entry['name'].'</a></td><td>'.\OCP\Util::formatDate($entry['mtime']).'</td><td>'.\OCP\Util::humanFileSize($entry['size']).'</td></tr>');

	}
	echo('</table>');
	
	
	
}


