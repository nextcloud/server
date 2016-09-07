<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div id="<?php p($_['appid']); ?>" class="section workflowengine">
	<h2 class="inlineblock"><?php p($_['heading']); ?></h2>

	<?php if (!empty($_['docs'])): ?>
		<a target="_blank" rel="noreferrer" class="icon-info svg"
		   title="<?php p($l->t('Open documentation'));?>"
		   href="<?php p(link_to_docs($_['docs'])); ?>">
		</a>
	<?php endif; ?>

	<?php if (!empty($_['description'])): ?>
		<p><?php p($_['description']); ?></p>
	<?php endif; ?>

	<script type="text/template" id="operations-template">
		<div class="operations"></div>
		<button class="button-add-operation"><?php p($l->t('Add rule group')); ?></button>
	</script>

	<script type="text/template" id="operation-template">
		<div class="operation{{#if hasChanged}} modified{{/if}}">
			<input type="text" class="operation-name" placeholder="<?php p($l->t('Short rule description')); ?>" value="{{operation.name}}">
			{{! delete only makes sense if the operation is already saved }}
			{{#if operation.id}}
			<span class="button-delete pull-right icon-delete"></span>
			{{/if}}
			<input type="text" class="pull-right operation-operation" value="{{operation.operation}}">

			<div class="checks">
				{{#each operation.checks}}
				<div class="check" data-id="{{@index}}">
					<select class="check-class">
						{{#each ../classes}}
						<option value="{{class}}" {{selectItem class ../class}}>{{name}}</option>
						{{/each}}
					</select>
					<select class="check-operator">
						{{#each (getOperators class)}}
						<option value="{{operator}}" {{selectItem operator ../operator}}>{{name}}</option>
						{{/each}}
					</select>
					<input type="text" class="check-value" value="{{value}}">
					<span class="button-delete-check pull-right icon-delete"></span>
				</div>
				{{/each}}
			</div>
			<button class="button-add"><?php p($l->t('Add rule')); ?></button>
			{{#if hasChanged}}
				{{! reset only makes sense if the operation is already saved }}
				{{#if operation.id}}
					<button class="button-reset pull-right"><?php p($l->t('Reset')); ?></button>
				{{/if}}
				<button class="button-save pull-right"><?php p($l->t('Save')); ?></button>
			{{/if}}
			{{#if saving}}
				<span class="icon-loading-small pull-right"></span>
				<span class="pull-right"><?php p($l->t('Saving…')); ?></span>
			{{else}}{{#if message}}
				<span class="msg pull-right {{#if errorMessage}}error{{else}}success{{/if}}">
					{{message}}{{#if errorMessage}} {{errorMessage}}{{/if}}
				</span>
			{{/if}}{{/if}}
		</div>
	</script>

	<div class="rules"><span class="icon-loading-small"></span> <?php p($l->t('Loading…')); ?></div>
</div>
