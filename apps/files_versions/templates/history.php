<div id="history">

<?php

if( isset( $_['message'] ) ) {


	if( isset($_['path'] ) ) print_unescaped('<strong>File: '.OC_Util::sanitizeHTML($_['path'])).'</strong><br>';
	print_unescaped('<strong>'.OC_Util::sanitizeHTML($_['message']) ).'</strong><br>';

}else{

	if( isset( $_['outcome_stat'] ) ) {

		print_unescaped( '<div id="feedback-messages" class="'.OC_Util::sanitizeHTML($_['outcome_stat']).'"><h3>'.OC_Util::sanitizeHTML($_['outcome_msg']) ).'</h3></div><br>';

	}

	print_unescaped( '<strong>Versions of '.OC_Util::sanitizeHTML($_['path']) ).'</strong><br>';
	print_unescaped('<p><em>'.OC_Util::sanitizeHTML($l->t('Revert a file to a previous version by clicking on its revert button')).'</em></p><br />');

	foreach ( $_['versions'] as $v ) {
		p(' ');
		p(OCP\Util::formatDate( doubleval($v['version'])));
		print_unescaped(' <a href="'.OCP\Util::linkTo('files_versions', 'history.php',
			array('path' => $_['path'], 'revert' => $v['version'])) .'" class="button">Revert</a><br /><br />');
		if ( $v['cur'] ) {
			print_unescaped('  (<b>Current</b>)');
		}
		print_unescaped('<br /><br />');
	}

}

?>
</div>
