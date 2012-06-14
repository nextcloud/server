<?php 
$id = $_['id'];
$tmp_path = $_['tmp_path'];
$requesttoken = $_['requesttoken'];
OCP\Util::writeLog('contacts','templates/part.cropphoto.php: tmp_path: '.$tmp_path.', exists: '.file_exists($tmp_path), OCP\Util::DEBUG);
?>
<script language="Javascript">
	jQuery(function($) {
		$('#cropbox').Jcrop({
			onChange:	showCoords,
			onSelect:	showCoords,
			onRelease:	clearCoords,
			maxSize:	[399, 399],
			bgColor:	'black',
			bgOpacity:	.4,
			boxWidth: 	400,
			boxHeight:	400,
			setSelect:	[ 100, 130, 50, 50 ]//,
			//aspectRatio: 0.8
		});
	});
	// Simple event handler, called from onChange and onSelect
	// event handlers, as per the Jcrop invocation above
	function showCoords(c) {
		$('#x1').val(c.x);
		$('#y1').val(c.y);
		$('#x2').val(c.x2);
		$('#y2').val(c.y2);
		$('#w').val(c.w);
		$('#h').val(c.h);
	};

	function clearCoords() {
		$('#coords input').val('');
	};
	/*
	$('#coords').submit(function() {
		alert('Handler for .submit() called.');
		return true;
	});*/
</script>
<img id="cropbox" src="<?php echo OCP\Util::linkToAbsolute('contacts', 'dynphoto.php'); ?>?tmp_path=<?php echo urlencode($tmp_path); ?>" />
<form id="cropform"
	class="coords"
	method="post"
	enctype="multipart/form-data"
	target="crop_target"
	action="<?php echo OCP\Util::linkToAbsolute('contacts', 'ajax/savecrop.php'); ?>">

	<input type="hidden" id="id" name="id" value="<?php echo $id; ?>" />
	<input type="hidden" id="requesttoken" name="requesttoken" value="<?php echo $requesttoken; ?>" />
	<input type="hidden" id="tmp_path" name="tmp_path" value="<?php echo $tmp_path; ?>" />
	<fieldset id="coords">
	<input type="hidden" id="x1" name="x1" value="" />
	<input type="hidden" id="y1" name="y1" value="" />
	<input type="hidden" id="x2" name="x2" value="" />
	<input type="hidden" id="y2" name="y2" value="" />
	<input type="hidden" id="w" name="w" value="" />
	<input type="hidden" id="h" name="h" value="" />
	</fieldset>
	<iframe name="crop_target" id='crop_target' src=""></iframe>
</form>


