<form id="contacts-settings">
	<fieldset class="personalblock">
		<?php echo $l->t('CardDAV syncing addresses'); ?> (<a href="http://owncloud.org/synchronisation/" target="_blank"><?php echo $l->t('more info'); ?></a>)
		<dl>
		<dt><?php echo $l->t('Primary address (Kontact et al)'); ?></dt>
		<dd><code><?php echo OCP\Util::linkToRemote('carddav'); ?></code></dd>
		<dt><?php echo $l->t('iOS/OS X'); ?></dt>
		<dd><code><?php echo OCP\Util::linkToRemote('carddav'); ?>principals/<?php echo OCP\USER::getUser(); ?></code>/</dd>
		<dt class="hidden"><?php echo $l->t('Addressbooks'); ?></dt>
		<dd class="addressbooks-settings hidden">
			<table>
			<?php foreach($_['addressbooks'] as $addressbook) { ?>
			<tr class="addressbook" data-id="<?php echo $addressbook['id'] ?>" data-uri="<?php echo $addressbook['uri'] ?>">
				<td class="active">
					<input type="checkbox" <?php echo (($addressbook['active']) == '1' ? ' checked="checked"' : ''); ?> />
				</td>
				<td class="name"><?php echo $addressbook['displayname'] ?></td>
				<td class="description"><?php echo $addressbook['description'] ?></td>
				<td class="action">
					<a class="svg action globe" title="<?php echo $l->t('Show CardDav link'); ?>"></a>
				</td>
				<td class="action">
					<a class="svg action cloud" title="<?php echo $l->t('Show read-only VCF link'); ?>"></a>
				</td>
				<td class="action">
					<a class="svg action share" data-item-type="addressbook" data-item="<?php echo $addressbook['id'] ?>" title="<?php echo $l->t("Share"); ?>"></a>
				</td>
				<td class="action">
					<a class="svg action download" title="<?php echo $l->t('Download'); ?>"
						href="<?php echo OCP\Util::linkToRemote('carddav').'addressbooks/'.OCP\USER::getUser().'/'
						.rawurlencode($addressbook['uri']) ?>?export"></a>
				</td>
				<td class="action">
					<a class="svg action edit" title="<?php echo $l->t("Edit"); ?>"></a>
				</td>
				<td class="action">
					<a class="svg action delete" title="<?php echo $l->t("Delete"); ?>"></a>
				</td>
			</tr>
			<?php } ?>
			</table>
			<div class="actions" style="width: 100%;">
				<input class="active hidden" type="checkbox" />
				<button class="new"><?php echo $l->t('New Address Book') ?></button>
				<input class="name hidden" type="text" autofocus="autofocus" placeholder="<?php echo $l->t('Name'); ?>" />
				<input class="description hidden" type="text" placeholder="<?php echo $l->t('Description'); ?>" />
				<input class="link hidden" style="width: 80%" type="text" autofocus="autofocus" />
				<button class="save hidden"><?php echo $l->t('Save') ?></button>
				<button class="cancel hidden"><?php echo $l->t('Cancel') ?></button>
			</div>
		</dd>
		</dl>
		<div style="width: 100%; clear: both;">
			<button class="moreless"><?php echo $l->t('More...') ?></button>
		</div>
	</fieldset>
</form>
