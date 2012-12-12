<!DOCTYPE html>
<html>
	<head>
		<title>ownCloud</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="<?php echo image_path('', 'favicon.png'); ?>" /><link rel="apple-touch-icon-precomposed" href="<?php echo image_path('', 'favicon-touch.png'); ?>" />
		<?php foreach($_['cssfiles'] as $cssfile): ?>
			<link rel="stylesheet" href="<?php echo $cssfile; ?>" type="text/css" media="screen" />
		<?php endforeach; ?>
		<script type="text/javascript">
			var oc_debug = <?php echo (defined('DEBUG') && DEBUG) ? 'true' : 'false'; ?>;
			var oc_webroot = '<?php echo OC::$WEBROOT; ?>';
			var oc_appswebroots = <?php echo $_['apps_paths'] ?>;
			var oc_requesttoken = '<?php echo $_['requesttoken']; ?>';
			var datepickerFormatDate = <?php echo json_encode($l->l('jsdate', 'jsdate')) ?>;
			var dayNames = <?php echo json_encode(array((string)$l->t('Sunday'), (string)$l->t('Monday'), (string)$l->t('Tuesday'), (string)$l->t('Wednesday'), (string)$l->t('Thursday'), (string)$l->t('Friday'), (string)$l->t('Saturday'))) ?>;
			var monthNames = <?php echo json_encode(array((string)$l->t('January'), (string)$l->t('February'), (string)$l->t('March'), (string)$l->t('April'), (string)$l->t('May'), (string)$l->t('June'), (string)$l->t('July'), (string)$l->t('August'), (string)$l->t('September'), (string)$l->t('October'), (string)$l->t('November'), (string)$l->t('December'))) ?>;
			var firstDay = <?php echo json_encode($l->l('firstday', 'firstday')) ?>;
		</script>
		<?php foreach($_['jsfiles'] as $jsfile): ?>
			<script type="text/javascript" src="<?php echo $jsfile; ?>"></script>
		<?php endforeach; ?>
	
		<?php foreach($_['headers'] as $header): ?>
			<?php
				echo '<'.$header['tag'].' ';
				foreach($header['attributes'] as $name=>$value) {
					echo "$name='$value' ";
				};
				echo '/>';
			?>
		<?php endforeach; ?>
	</head>

	<body id="body-login">
		<div id="login">
			<header><div id="header">
				<img src="<?php echo image_path('', 'logo.svg'); ?>" class="svg" alt="ownCloud" />
			</div></header>
			<?php echo $_['content']; ?>
		</div>
		<footer><p class="info"><a href="http://owncloud.org/">ownCloud</a> &ndash; <?php echo $l->t( 'web services under your control' ); ?></p></footer>
	</body>
</html>
