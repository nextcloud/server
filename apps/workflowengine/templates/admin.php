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
/** @var OC_L10N $l */
?>
<div id="workflowengine" class="section workflowengine">
	<h2 class="inlineblock"><?php p($_['heading']); ?></h2>
	<script type="text/template" id="operations-template">
		<div class="operations"></div>
		<button class="button-add-operation">Add operation</button>
	</script>

	<script type="text/template" id="operation-template">
		<div class="operation{{#if hasChanged}} modified{{/if}}">
			<input type="text" class="operation-name" value="{{operation.name}}">
			{{! delete only makes sense if the operation is already saved }}
			{{#if operation.id}}
			<span class="button-delete pull-right icon-delete"></span>
			{{/if}}
			<span class="pull-right info">{{operation.class}} - ID: {{operation.id}} - operation: {{operation.operation}}</span>

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
						<option value="{{operator}}" {{selectItem this.operator ../operator}}>{{name}}</option>
						{{/each}}
					</select>
					<input type="text" class="check-value" value="{{value}}">
					<span class="button-delete-check pull-right icon-delete"></span>
				</div>
				{{/each}}
			</div>
			<button class="button-add">Add check</button>
			{{#if hasChanged}}
				{{! reset only makes sense if the operation is already saved }}
				{{#if operation.id}}
					<button class="button-reset pull-right">Reset</button>
				{{/if}}
				<button class="button-save pull-right">Save</button>
			{{/if}}
			{{#if saving}}
				<span class="icon-loading-small pull-right"></span>
				<span class="pull-right">Saving ...</span>
			{{else}}{{#if message}}
				<span class="msg pull-right {{#if errorMessage}}error{{else}}success{{/if}}">
					{{message}}{{#if errorMessage}} {{errorMessage}}{{/if}}
				</span>
			{{/if}}{{/if}}
		</div>
	</script>

	<div class="rules"><span class="icon-loading-small"></span> Loading ...</div>
</div>
