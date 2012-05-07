<form id="external">
	<fieldset class="personalblock">
		<strong>External Sites</strong><br />
		<ul class="external_sites">

		<?php
		$sites = OC_External::getSites();
		for($i = 0; $i < sizeof($sites); $i++) {
			echo '<li><input type="text" name="site_name[]" class="site_name" value="'.$sites[$i][0].'" placeholder="'.$l->t('Name').'" />
			<input type="text" class="site_url" name="site_url[]"  value="'.$sites[$i][1].'"  placeholder="'.$l->t('URL').'" />
			<img class="svg action delete_button" src="'.OCP\image_path("", "actions/delete.svg") .'" title="'.$l->t("Remove site").'" />
			</li>';
		}
		?>

		</ul>

        <input type="button" id="add_external_site" value="Add" />
		<span class="msg"></span>
	</fieldset>
</form>
