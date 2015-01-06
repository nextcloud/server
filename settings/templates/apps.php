<script id="categories-template" type="text/x-handlebars-template">
{{#each this}}
	<li id="app-category-{{id}}" data-category-id="{{id}}"><a>{{displayName}}</a></li>
{{/each}}

<?php if(OC_Config::getValue('appstoreenabled', true) === true): ?>
	<li>
		<a class="app-external" target="_blank" href="https://apps.owncloud.com/?xsortmode=high"><?php p($l->t('More apps'));?> …</a>
	</li>
	<li>
		<a class="app-external" target="_blank" href="https://owncloud.org/dev"><?php p($l->t('Add your app'));?> …</a>
	</li>
<?php endif; ?>
</script>

<script id="app-template" type="text/x-handlebars">
	<div class="section" id="app-{{id}}">
	{{#if preview}}
	<div class="app-image{{#if previewAsIcon}} app-image-icon{{/if}} hidden">
	</div>
	{{/if}}
	<h2 class="app-name"><a href="{{detailpage}}" target="_blank">{{name}}</a></h2>
	<div class="app-version"> {{version}}</div>
	<div class="app-author"><?php p($l->t('by')); ?> {{author}}
		{{#if licence}}
		({{licence}}-<?php p($l->t('licensed')); ?>)
		{{/if}}
	</div>
	{{#if score}}
	<div class="app-score">{{{score}}}</div>
	{{/if}}
	{{#if internalclass}}
	<div class="{{internalclass}} icon-checkmark">{{internallabel}}</div>
	{{/if}}
	<div class="app-detailpage"></div>
	<div class="app-description"><pre>{{description}}</pre></div>
	<!--<div class="app-changed">{{changed}}</div>-->
	{{#if documentation}}
	<p class="documentation">
		<?php p($l->t("Documentation:"));?>
		{{#if documentation.user}}
		<span class="userDocumentation appslink">
		<a id='userDocumentation' href='{{documentation.user}}' target="_blank"><?php p($l->t("User Documentation"));?></a>
		</span>
		{{/if}}

		{{#if documentation.admin}}
		<span class="adminDocumentation appslink">
		<a id='adminDocumentation' href='{{documentation.admin}}' target="_blank"><?php p($l->t("Admin Documentation"));?></a>
		</span>
		{{/if}}
	</p>
	{{/if}}
	{{#unless canInstall}}
	<div class="app-dependencies">
	<p><?php p($l->t('This app cannot be installed because the following dependencies are not fulfilled:')); ?></p>
	<ul class="missing-dependencies">
	{{#each missingDependencies}}
	<li>{{this}}</li>
	{{/each}}
	</ul>
	</div>
	{{/unless}}

	{{#if update}}
	<input class="update" type="submit" value="<?php p($l->t('Update to %s', array('{{update}}'))); ?>" data-appid="{{id}}" />
	{{/if}}
	{{#if active}}
	<input class="enable" type="submit" data-appid="{{id}}" data-active="true" value="<?php p($l->t("Disable"));?>"/>
	<input type="checkbox" class="groups-enable" id="groups_enable-{{id}}"/>
	<label for="groups_enable-{{id}}"><?php p($l->t('Enable only for specific groups')); ?></label>
	<br />
	<input type="hidden" id="group_select" title="<?php p($l->t('All')); ?>" style="width: 200px">
	{{else}}
	<input class="enable" type="submit" data-appid="{{id}}" data-active="false" {{#unless canInstall}}disabled="disabled"{{/unless}} value="<?php p($l->t("Enable"));?>"/>
	{{/if}}
	{{#if canUnInstall}}
	<input class="uninstall" type="submit" value="<?php p($l->t('Uninstall App')); ?>" data-appid="{{id}}" />
	{{/if}}

	<div class="warning hidden"></div>

	</div>
</script>

<div id="app-navigation" class="icon-loading">
	<ul id="apps-categories">

	</ul>
</div>
<div id="app-content">
	<div id="apps-list" class="icon-loading"></div>
</div>
