(self.webpackChunk_nextcloud_app_core=self.webpackChunk_nextcloud_app_core||[]).push([["677"],{62839:function(e,i,t){"use strict";t.r(i),t.d(i,{default:()=>o});var n=t(34942),a=t.n(n),d=t(60278),r=t.n(d)()(a());r.push([e.id,`/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/*
* Ensure proper alignment of the vue material icons
*/
.material-design-icon[data-v-8e3b7bdd] {
  display: flex;
  align-self: center;
  justify-self: center;
  align-items: center;
  justify-content: center;
}
.input-field[data-v-8e3b7bdd] {
  --input-border-color: var(--color-border-maxcontrast);
  --input-border-radius: var(--border-radius-element);
  --input-border-width-offset: calc(var(--border-width-input-focused, 2px) - var(--border-width-input, 2px));
  --input-padding-start: var(--border-radius-element);
  --input-padding-end: var(--border-radius-element);
  position: relative;
  width: 100%;
  margin-block-start: 6px;
}
.input-field--disabled[data-v-8e3b7bdd] {
  opacity: 0.4;
  filter: saturate(0.4);
}
.input-field--label-outside[data-v-8e3b7bdd] {
  margin-block-start: 0;
}
.input-field--leading-icon[data-v-8e3b7bdd] {
  --input-padding-start: calc(var(--default-clickable-area) - var(--default-grid-baseline));
}
.input-field--trailing-icon[data-v-8e3b7bdd] {
  --input-padding-end: calc(var(--default-clickable-area) - var(--default-grid-baseline));
}
.input-field--pill[data-v-8e3b7bdd] {
  --input-border-radius: var(--border-radius-pill);
}
.input-field__main-wrapper[data-v-8e3b7bdd] {
  height: var(--default-clickable-area);
  padding: var(--border-width-input, 2px);
  position: relative;
}
.input-field__main-wrapper[data-v-8e3b7bdd]:not(:has([disabled])):has(input:focus), .input-field__main-wrapper[data-v-8e3b7bdd]:not(:has([disabled])):has(input:active) {
  padding: 0;
}
.input-field__input[data-v-8e3b7bdd] {
  background-color: var(--color-main-background);
  color: var(--color-main-text);
  border: none;
  border-radius: var(--input-border-radius);
  box-shadow: 0 -1px var(--input-border-color), 0 0 0 1px color-mix(in srgb, var(--input-border-color), 65% transparent);
  cursor: pointer;
  -webkit-appearance: textfield !important;
  -moz-appearance: textfield !important;
  appearance: textfield !important;
  font-size: var(--default-font-size);
  text-overflow: ellipsis;
  height: 100% !important;
  min-height: unset;
  width: 100%;
  padding-block: var(--input-border-width-offset);
  padding-inline: calc(var(--input-padding-start) + var(--input-border-width-offset)) calc(var(--input-padding-end) + var(--input-border-width-offset));
}
.input-field__input[data-v-8e3b7bdd]::placeholder {
  color: var(--color-text-maxcontrast);
}
.input-field__input[data-v-8e3b7bdd]::-webkit-search-cancel-button {
  display: none;
}
.input-field__input[data-v-8e3b7bdd]::-webkit-search-decoration, .input-field__input[data-v-8e3b7bdd]::-webkit-search-results-button, .input-field__input[data-v-8e3b7bdd]::-webkit-search-results-decoration, .input-field__input[data-v-8e3b7bdd]::-ms-clear {
  display: none;
}
.input-field__input[data-v-8e3b7bdd]:hover:not([disabled]) {
  box-shadow: 0 0 0 1px var(--input-border-color);
}
.input-field__input[data-v-8e3b7bdd]:active:not([disabled]), .input-field__input[data-v-8e3b7bdd]:focus:not([disabled]) {
  --input-border-color: var(--color-main-text);
  --input-border-width-offset: 0px;
  border: var(--border-width-input-focused, 2px) solid var(--input-border-color);
  box-shadow: 0 0 0 2px var(--color-main-background) !important;
}
.input-field__input:focus + .input-field__label[data-v-8e3b7bdd], .input-field__input:hover:not(:placeholder-shown) + .input-field__label[data-v-8e3b7bdd] {
  color: var(--color-main-text);
}
.input-field__input[data-v-8e3b7bdd]:focus {
  cursor: text;
}
.input-field__input[data-v-8e3b7bdd]:disabled {
  cursor: default;
}
.input-field__input[data-v-8e3b7bdd]:focus-visible {
  box-shadow: unset !important;
}
.input-field:not(.input-field--label-outside) .input-field__input[data-v-8e3b7bdd]:not(:focus)::placeholder {
  opacity: 0;
}
.input-field__label[data-v-8e3b7bdd] {
  --input-label-font-size: var(--default-font-size);
  font-size: var(--input-label-font-size);
  position: absolute;
  margin-inline: var(--input-padding-start) var(--input-padding-end);
  max-width: fit-content;
  inset-block-start: calc((var(--default-clickable-area) - 1lh) / 2);
  inset-inline: var(--border-width-input-focused, 2px);
  color: var(--color-text-maxcontrast);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  pointer-events: none;
  transition: height var(--animation-quick), inset-block-start var(--animation-quick), font-size var(--animation-quick), color var(--animation-quick), background-color var(--animation-quick) var(--animation-slow);
}
.input-field__input:focus + .input-field__label[data-v-8e3b7bdd], .input-field__input:not(:placeholder-shown) + .input-field__label[data-v-8e3b7bdd] {
  --input-label-font-size: 13px;
  line-height: 1.5;
  inset-block-start: calc(-1.5 * var(--input-label-font-size) / 2);
  font-weight: 500;
  border-radius: var(--default-grid-baseline) var(--default-grid-baseline) 0 0;
  background-color: var(--color-main-background);
  padding-inline: var(--default-grid-baseline);
  margin-inline: calc(var(--input-padding-start) - var(--default-grid-baseline)) calc(var(--input-padding-end) - var(--default-grid-baseline));
  transition: height var(--animation-quick), inset-block-start var(--animation-quick), font-size var(--animation-quick), color var(--animation-quick);
}
.input-field__icon[data-v-8e3b7bdd] {
  position: absolute;
  height: var(--default-clickable-area);
  width: var(--default-clickable-area);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0.7;
  inset-block-end: 0;
}
.input-field__icon--leading[data-v-8e3b7bdd] {
  inset-inline-start: 0px;
}
.input-field__icon--trailing[data-v-8e3b7bdd] {
  inset-inline-end: 0px;
}
.input-field__trailing-button[data-v-8e3b7bdd] {
  --button-size: calc(var(--default-clickable-area) - 2 * var(--border-width-input-focused, 2px)) !important;
  --button-radius: calc(var(--input-border-radius) - var(--border-width-input-focused, 2px));
}
.input-field__trailing-button.button-vue[data-v-8e3b7bdd] {
  position: absolute;
  top: var(--border-width-input-focused, 2px);
  inset-inline-end: var(--border-width-input-focused, 2px);
}
.input-field__trailing-button.button-vue[data-v-8e3b7bdd]:focus-visible {
  box-shadow: none !important;
}
.input-field__helper-text-message[data-v-8e3b7bdd] {
  padding-block: 4px;
  padding-inline: var(--border-radius-element);
  display: flex;
  align-items: center;
  color: var(--color-text-maxcontrast);
}
.input-field__helper-text-message__icon[data-v-8e3b7bdd] {
  margin-inline-end: 8px;
}
.input-field--error .input-field__helper-text-message[data-v-8e3b7bdd],
.input-field--error .input-field__icon--trailing[data-v-8e3b7bdd] {
  color: var(--color-text-error, var(--color-error));
}
.input-field--error .input-field__input[data-v-8e3b7bdd], .input-field__input[data-v-8e3b7bdd]:user-invalid {
  --input-border-color: var(--color-border-error, var(--color-error)) !important;
}
.input-field--error .input-field__input[data-v-8e3b7bdd]:focus-visible, .input-field__input[data-v-8e3b7bdd]:user-invalid:focus-visible {
  box-shadow: rgb(248, 250, 252) 0px 0px 0px 2px, var(--color-primary-element) 0px 0px 0px 4px, rgba(0, 0, 0, 0.05) 0px 1px 2px 0px;
}
.input-field--success .input-field__input[data-v-8e3b7bdd] {
  --input-border-color: var(--color-border-success, var(--color-success)) !important;
}
.input-field--success .input-field__input[data-v-8e3b7bdd]:focus-visible {
  box-shadow: rgb(248, 250, 252) 0px 0px 0px 2px, var(--color-primary-element) 0px 0px 0px 4px, rgba(0, 0, 0, 0.05) 0px 1px 2px 0px;
}
.input-field--success .input-field__helper-text-message__icon[data-v-8e3b7bdd] {
  color: var(--color-border-success, var(--color-success));
}
.input-field--legacy .input-field__input[data-v-8e3b7bdd] {
  box-shadow: 0 0 0 1px var(--input-border-color) inset;
}
.input-field--legacy .input-field__main-wrapper[data-v-8e3b7bdd]:hover:not(:has([disabled])) {
  padding: 0;
}
.input-field--legacy .input-field__main-wrapper:hover:not(:has([disabled])) .input-field__input[data-v-8e3b7bdd] {
  --input-border-color: var(--color-main-text);
  --input-border-width-offset: 0px;
  border: var(--border-width-input-focused, 2px) solid var(--input-border-color);
  box-shadow: 0 0 0 2px var(--color-main-background) !important;
}`,"",{version:3,sources:["webpack://./../node_modules/@nextcloud/dialogs/node_modules/@nextcloud/vue/dist/assets/NcInputField-DRt2ahWd.css"],names:[],mappings:"AAAA;;;EAGE;AACF;;;EAGE;AACF;;CAEC;AACD;EACE,aAAa;EACb,kBAAkB;EAClB,oBAAoB;EACpB,mBAAmB;EACnB,uBAAuB;AACzB;AACA;EACE,qDAAqD;EACrD,mDAAmD;EACnD,0GAA0G;EAC1G,mDAAmD;EACnD,iDAAiD;EACjD,kBAAkB;EAClB,WAAW;EACX,uBAAuB;AACzB;AACA;EACE,YAAY;EACZ,qBAAqB;AACvB;AACA;EACE,qBAAqB;AACvB;AACA;EACE,yFAAyF;AAC3F;AACA;EACE,uFAAuF;AACzF;AACA;EACE,gDAAgD;AAClD;AACA;EACE,qCAAqC;EACrC,uCAAuC;EACvC,kBAAkB;AACpB;AACA;EACE,UAAU;AACZ;AACA;EACE,8CAA8C;EAC9C,6BAA6B;EAC7B,YAAY;EACZ,yCAAyC;EACzC,sHAAsH;EACtH,eAAe;EACf,wCAAwC;EACxC,qCAAqC;EACrC,gCAAgC;EAChC,mCAAmC;EACnC,uBAAuB;EACvB,uBAAuB;EACvB,iBAAiB;EACjB,WAAW;EACX,+CAA+C;EAC/C,qJAAqJ;AACvJ;AACA;EACE,oCAAoC;AACtC;AACA;EACE,aAAa;AACf;AACA;EACE,aAAa;AACf;AACA;EACE,+CAA+C;AACjD;AACA;EACE,4CAA4C;EAC5C,gCAAgC;EAChC,8EAA8E;EAC9E,6DAA6D;AAC/D;AACA;EACE,6BAA6B;AAC/B;AACA;EACE,YAAY;AACd;AACA;EACE,eAAe;AACjB;AACA;EACE,4BAA4B;AAC9B;AACA;EACE,UAAU;AACZ;AACA;EACE,iDAAiD;EACjD,uCAAuC;EACvC,kBAAkB;EAClB,kEAAkE;EAClE,sBAAsB;EACtB,kEAAkE;EAClE,oDAAoD;EACpD,oCAAoC;EACpC,mBAAmB;EACnB,gBAAgB;EAChB,uBAAuB;EACvB,oBAAoB;EACpB,kNAAkN;AACpN;AACA;EACE,6BAA6B;EAC7B,gBAAgB;EAChB,gEAAgE;EAChE,gBAAgB;EAChB,4EAA4E;EAC5E,8CAA8C;EAC9C,4CAA4C;EAC5C,4IAA4I;EAC5I,mJAAmJ;AACrJ;AACA;EACE,kBAAkB;EAClB,qCAAqC;EACrC,oCAAoC;EACpC,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,YAAY;EACZ,kBAAkB;AACpB;AACA;EACE,uBAAuB;AACzB;AACA;EACE,qBAAqB;AACvB;AACA;EACE,0GAA0G;EAC1G,0FAA0F;AAC5F;AACA;EACE,kBAAkB;EAClB,2CAA2C;EAC3C,wDAAwD;AAC1D;AACA;EACE,2BAA2B;AAC7B;AACA;EACE,kBAAkB;EAClB,4CAA4C;EAC5C,aAAa;EACb,mBAAmB;EACnB,oCAAoC;AACtC;AACA;EACE,sBAAsB;AACxB;AACA;;EAEE,kDAAkD;AACpD;AACA;EACE,8EAA8E;AAChF;AACA;EACE,iIAAiI;AACnI;AACA;EACE,kFAAkF;AACpF;AACA;EACE,iIAAiI;AACnI;AACA;EACE,wDAAwD;AAC1D;AACA;EACE,qDAAqD;AACvD;AACA;EACE,UAAU;AACZ;AACA;EACE,4CAA4C;EAC5C,gCAAgC;EAChC,8EAA8E;EAC9E,6DAA6D;AAC/D",sourcesContent:["/**\n * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors\n * SPDX-License-Identifier: AGPL-3.0-or-later\n */\n/**\n * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors\n * SPDX-License-Identifier: AGPL-3.0-or-later\n */\n/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon[data-v-8e3b7bdd] {\n  display: flex;\n  align-self: center;\n  justify-self: center;\n  align-items: center;\n  justify-content: center;\n}\n.input-field[data-v-8e3b7bdd] {\n  --input-border-color: var(--color-border-maxcontrast);\n  --input-border-radius: var(--border-radius-element);\n  --input-border-width-offset: calc(var(--border-width-input-focused, 2px) - var(--border-width-input, 2px));\n  --input-padding-start: var(--border-radius-element);\n  --input-padding-end: var(--border-radius-element);\n  position: relative;\n  width: 100%;\n  margin-block-start: 6px;\n}\n.input-field--disabled[data-v-8e3b7bdd] {\n  opacity: 0.4;\n  filter: saturate(0.4);\n}\n.input-field--label-outside[data-v-8e3b7bdd] {\n  margin-block-start: 0;\n}\n.input-field--leading-icon[data-v-8e3b7bdd] {\n  --input-padding-start: calc(var(--default-clickable-area) - var(--default-grid-baseline));\n}\n.input-field--trailing-icon[data-v-8e3b7bdd] {\n  --input-padding-end: calc(var(--default-clickable-area) - var(--default-grid-baseline));\n}\n.input-field--pill[data-v-8e3b7bdd] {\n  --input-border-radius: var(--border-radius-pill);\n}\n.input-field__main-wrapper[data-v-8e3b7bdd] {\n  height: var(--default-clickable-area);\n  padding: var(--border-width-input, 2px);\n  position: relative;\n}\n.input-field__main-wrapper[data-v-8e3b7bdd]:not(:has([disabled])):has(input:focus), .input-field__main-wrapper[data-v-8e3b7bdd]:not(:has([disabled])):has(input:active) {\n  padding: 0;\n}\n.input-field__input[data-v-8e3b7bdd] {\n  background-color: var(--color-main-background);\n  color: var(--color-main-text);\n  border: none;\n  border-radius: var(--input-border-radius);\n  box-shadow: 0 -1px var(--input-border-color), 0 0 0 1px color-mix(in srgb, var(--input-border-color), 65% transparent);\n  cursor: pointer;\n  -webkit-appearance: textfield !important;\n  -moz-appearance: textfield !important;\n  appearance: textfield !important;\n  font-size: var(--default-font-size);\n  text-overflow: ellipsis;\n  height: 100% !important;\n  min-height: unset;\n  width: 100%;\n  padding-block: var(--input-border-width-offset);\n  padding-inline: calc(var(--input-padding-start) + var(--input-border-width-offset)) calc(var(--input-padding-end) + var(--input-border-width-offset));\n}\n.input-field__input[data-v-8e3b7bdd]::placeholder {\n  color: var(--color-text-maxcontrast);\n}\n.input-field__input[data-v-8e3b7bdd]::-webkit-search-cancel-button {\n  display: none;\n}\n.input-field__input[data-v-8e3b7bdd]::-webkit-search-decoration, .input-field__input[data-v-8e3b7bdd]::-webkit-search-results-button, .input-field__input[data-v-8e3b7bdd]::-webkit-search-results-decoration, .input-field__input[data-v-8e3b7bdd]::-ms-clear {\n  display: none;\n}\n.input-field__input[data-v-8e3b7bdd]:hover:not([disabled]) {\n  box-shadow: 0 0 0 1px var(--input-border-color);\n}\n.input-field__input[data-v-8e3b7bdd]:active:not([disabled]), .input-field__input[data-v-8e3b7bdd]:focus:not([disabled]) {\n  --input-border-color: var(--color-main-text);\n  --input-border-width-offset: 0px;\n  border: var(--border-width-input-focused, 2px) solid var(--input-border-color);\n  box-shadow: 0 0 0 2px var(--color-main-background) !important;\n}\n.input-field__input:focus + .input-field__label[data-v-8e3b7bdd], .input-field__input:hover:not(:placeholder-shown) + .input-field__label[data-v-8e3b7bdd] {\n  color: var(--color-main-text);\n}\n.input-field__input[data-v-8e3b7bdd]:focus {\n  cursor: text;\n}\n.input-field__input[data-v-8e3b7bdd]:disabled {\n  cursor: default;\n}\n.input-field__input[data-v-8e3b7bdd]:focus-visible {\n  box-shadow: unset !important;\n}\n.input-field:not(.input-field--label-outside) .input-field__input[data-v-8e3b7bdd]:not(:focus)::placeholder {\n  opacity: 0;\n}\n.input-field__label[data-v-8e3b7bdd] {\n  --input-label-font-size: var(--default-font-size);\n  font-size: var(--input-label-font-size);\n  position: absolute;\n  margin-inline: var(--input-padding-start) var(--input-padding-end);\n  max-width: fit-content;\n  inset-block-start: calc((var(--default-clickable-area) - 1lh) / 2);\n  inset-inline: var(--border-width-input-focused, 2px);\n  color: var(--color-text-maxcontrast);\n  white-space: nowrap;\n  overflow: hidden;\n  text-overflow: ellipsis;\n  pointer-events: none;\n  transition: height var(--animation-quick), inset-block-start var(--animation-quick), font-size var(--animation-quick), color var(--animation-quick), background-color var(--animation-quick) var(--animation-slow);\n}\n.input-field__input:focus + .input-field__label[data-v-8e3b7bdd], .input-field__input:not(:placeholder-shown) + .input-field__label[data-v-8e3b7bdd] {\n  --input-label-font-size: 13px;\n  line-height: 1.5;\n  inset-block-start: calc(-1.5 * var(--input-label-font-size) / 2);\n  font-weight: 500;\n  border-radius: var(--default-grid-baseline) var(--default-grid-baseline) 0 0;\n  background-color: var(--color-main-background);\n  padding-inline: var(--default-grid-baseline);\n  margin-inline: calc(var(--input-padding-start) - var(--default-grid-baseline)) calc(var(--input-padding-end) - var(--default-grid-baseline));\n  transition: height var(--animation-quick), inset-block-start var(--animation-quick), font-size var(--animation-quick), color var(--animation-quick);\n}\n.input-field__icon[data-v-8e3b7bdd] {\n  position: absolute;\n  height: var(--default-clickable-area);\n  width: var(--default-clickable-area);\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  opacity: 0.7;\n  inset-block-end: 0;\n}\n.input-field__icon--leading[data-v-8e3b7bdd] {\n  inset-inline-start: 0px;\n}\n.input-field__icon--trailing[data-v-8e3b7bdd] {\n  inset-inline-end: 0px;\n}\n.input-field__trailing-button[data-v-8e3b7bdd] {\n  --button-size: calc(var(--default-clickable-area) - 2 * var(--border-width-input-focused, 2px)) !important;\n  --button-radius: calc(var(--input-border-radius) - var(--border-width-input-focused, 2px));\n}\n.input-field__trailing-button.button-vue[data-v-8e3b7bdd] {\n  position: absolute;\n  top: var(--border-width-input-focused, 2px);\n  inset-inline-end: var(--border-width-input-focused, 2px);\n}\n.input-field__trailing-button.button-vue[data-v-8e3b7bdd]:focus-visible {\n  box-shadow: none !important;\n}\n.input-field__helper-text-message[data-v-8e3b7bdd] {\n  padding-block: 4px;\n  padding-inline: var(--border-radius-element);\n  display: flex;\n  align-items: center;\n  color: var(--color-text-maxcontrast);\n}\n.input-field__helper-text-message__icon[data-v-8e3b7bdd] {\n  margin-inline-end: 8px;\n}\n.input-field--error .input-field__helper-text-message[data-v-8e3b7bdd],\n.input-field--error .input-field__icon--trailing[data-v-8e3b7bdd] {\n  color: var(--color-text-error, var(--color-error));\n}\n.input-field--error .input-field__input[data-v-8e3b7bdd], .input-field__input[data-v-8e3b7bdd]:user-invalid {\n  --input-border-color: var(--color-border-error, var(--color-error)) !important;\n}\n.input-field--error .input-field__input[data-v-8e3b7bdd]:focus-visible, .input-field__input[data-v-8e3b7bdd]:user-invalid:focus-visible {\n  box-shadow: rgb(248, 250, 252) 0px 0px 0px 2px, var(--color-primary-element) 0px 0px 0px 4px, rgba(0, 0, 0, 0.05) 0px 1px 2px 0px;\n}\n.input-field--success .input-field__input[data-v-8e3b7bdd] {\n  --input-border-color: var(--color-border-success, var(--color-success)) !important;\n}\n.input-field--success .input-field__input[data-v-8e3b7bdd]:focus-visible {\n  box-shadow: rgb(248, 250, 252) 0px 0px 0px 2px, var(--color-primary-element) 0px 0px 0px 4px, rgba(0, 0, 0, 0.05) 0px 1px 2px 0px;\n}\n.input-field--success .input-field__helper-text-message__icon[data-v-8e3b7bdd] {\n  color: var(--color-border-success, var(--color-success));\n}\n.input-field--legacy .input-field__input[data-v-8e3b7bdd] {\n  box-shadow: 0 0 0 1px var(--input-border-color) inset;\n}\n.input-field--legacy .input-field__main-wrapper[data-v-8e3b7bdd]:hover:not(:has([disabled])) {\n  padding: 0;\n}\n.input-field--legacy .input-field__main-wrapper:hover:not(:has([disabled])) .input-field__input[data-v-8e3b7bdd] {\n  --input-border-color: var(--color-main-text);\n  --input-border-width-offset: 0px;\n  border: var(--border-width-input-focused, 2px) solid var(--input-border-color);\n  box-shadow: 0 0 0 2px var(--color-main-background) !important;\n}"],sourceRoot:""}]);let o=r},72758:function(e,i,t){var n=t(62839);n.__esModule&&(n=n.default),"string"==typeof n&&(n=[[e.id,n,""]]),n.locals&&(e.exports=n.locals),(0,t(18349).A)("3ae1b965",n,!0,{})},78950:function(e,i,t){"use strict";t.d(i,{_:()=>n});let n=(e,i)=>{let t=e.__vccOpts||e;for(let[e,n]of i)t[e]=n;return t}},59193:function(e,i,t){"use strict";t.d(i,{N:()=>C}),t(72758);var n=t(19632),a=t(75999),d=t(95425),r=t(88362),o=t(64917),l=t(21686),u=t(395);let p={class:"input-field__main-wrapper"},s=["id","aria-describedby","disabled","placeholder","type","value"],A=["for"],b={class:"input-field__icon input-field__icon--leading"},c={key:2,class:"input-field__icon input-field__icon--trailing"},v=["id"],f=(0,n.pM)({...{inheritAttrs:!1},__name:"NcInputField",props:(0,n.zz)({class:{default:""},inputClass:{default:""},id:{default:()=>(0,d.c)()},label:{default:void 0},labelOutside:{type:Boolean},type:{default:"text"},placeholder:{default:void 0},showTrailingButton:{type:Boolean},trailingButtonLabel:{default:void 0},success:{type:Boolean},error:{type:Boolean},helperText:{default:""},disabled:{type:Boolean},pill:{type:Boolean}},{modelValue:{required:!0},modelModifiers:{}}),emits:(0,n.zz)(["trailingButtonClick"],["update:modelValue"]),setup(e,{expose:i,emit:t}){let d=(0,n.fn)(e,"modelValue");i({focus:function(e){f.value.focus(e)},select:function(){f.value.select()}});let u=(0,n.OA)(),f=(0,n.rk)("input"),C=(0,n.EW)(()=>e.showTrailingButton||e.success),_=(0,n.EW)(()=>e.placeholder||(r.i?e.label:void 0)),h=(0,n.EW)(()=>{let i=e.label||e.labelOutside;return i||(0,n.R8)("You need to add a label to the NcInputField component. Either use the prop label or use an external one, as per the example in the documentation."),i}),x=(0,n.EW)(()=>{let i=[];return e.helperText&&i.push(`${e.id}-helper-text`),u["aria-describedby"]&&i.push(String(u["aria-describedby"])),i.join(" ")||void 0});function g(i){let t=i.target;d.value="number"===e.type&&"number"==typeof d.value?parseFloat(t.value):t.value}return(e,i)=>((0,n.uX)(),(0,n.CE)("div",{class:(0,n.C4)(["input-field",[{"input-field--disabled":e.disabled,"input-field--error":e.error,"input-field--label-outside":e.labelOutside||!h.value,"input-field--leading-icon":!!e.$slots.icon,"input-field--trailing-icon":C.value,"input-field--pill":e.pill,"input-field--success":e.success,"input-field--legacy":(0,n.R1)(r.i)},e.$props.class]])},[(0,n.Lk)("div",p,[(0,n.Lk)("input",(0,n.v6)(e.$attrs,{id:e.id,ref:"input","aria-describedby":x.value,"aria-live":"polite",class:["input-field__input",e.inputClass],disabled:e.disabled,placeholder:_.value,type:e.type,value:d.value.toString(),onInput:g}),null,16,s),!e.labelOutside&&h.value?((0,n.uX)(),(0,n.CE)("label",{key:0,class:"input-field__label",for:e.id},(0,n.v_)(e.label),9,A)):(0,n.Q3)("",!0),(0,n.bo)((0,n.Lk)("div",b,[(0,n.RG)(e.$slots,"icon",{},void 0,!0)],512),[[n.aG,!!e.$slots.icon]]),e.showTrailingButton?((0,n.uX)(),(0,n.Wv)((0,n.R1)(o.N),{key:1,class:"input-field__trailing-button","aria-label":e.trailingButtonLabel,disabled:e.disabled,variant:"tertiary-no-background",onClick:i[0]||(i[0]=e=>t("trailingButtonClick",e))},{icon:(0,n.k6)(()=>[(0,n.RG)(e.$slots,"trailing-button-icon",{},void 0,!0)]),_:3},8,["aria-label","disabled"])):e.success||e.error?((0,n.uX)(),(0,n.CE)("div",c,[e.success?((0,n.uX)(),(0,n.Wv)((0,n.R1)(l.N),{key:0,path:(0,n.R1)(a.d)},null,8,["path"])):((0,n.uX)(),(0,n.Wv)((0,n.R1)(l.N),{key:1,path:(0,n.R1)(a.e)},null,8,["path"]))])):(0,n.Q3)("",!0)]),e.helperText?((0,n.uX)(),(0,n.CE)("p",{key:0,id:`${e.id}-helper-text`,class:"input-field__helper-text-message"},[e.success?((0,n.uX)(),(0,n.Wv)((0,n.R1)(l.N),{key:0,class:"input-field__helper-text-message__icon",path:(0,n.R1)(a.d)},null,8,["path"])):e.error?((0,n.uX)(),(0,n.Wv)((0,n.R1)(l.N),{key:1,class:"input-field__helper-text-message__icon",path:(0,n.R1)(a.e)},null,8,["path"])):(0,n.Q3)("",!0),(0,n.eW)(" "+(0,n.v_)(e.helperText),1)],8,v)):(0,n.Q3)("",!0)],2))}}),C=(0,u._)(f,[["__scopeId","data-v-8e3b7bdd"]])},68532:function(e,i,t){"use strict";t.d(i,{_:()=>l});var n=t(19632),a=t(75999),d=t(75719),r=t(21686),o=t(59193);(0,d.r)();let l=(0,n.pM)({__name:"NcTextField",props:(0,n.zz)({class:{},inputClass:{},id:{},label:{},labelOutside:{type:Boolean},type:{},placeholder:{},showTrailingButton:{type:Boolean},trailingButtonLabel:{default:void 0},success:{type:Boolean},error:{type:Boolean},helperText:{},disabled:{type:Boolean},pill:{type:Boolean},trailingButtonIcon:{default:"close"}},{modelValue:{default:""},modelModifiers:{}}),emits:["update:modelValue"],setup(e,{expose:i}){let t=(0,n.fn)(e,"modelValue");i({focus:function(e){l.value.focus(e)},select:function(){l.value.select()}});let l=(0,n.rk)("inputField"),u={arrowEnd:(0,d.a)("Save changes"),close:(0,d.a)("Clear text"),undo:(0,d.a)("Undo changes")},p=new Set(Object.keys(o.N.props)),s=(0,n.EW)(()=>{let i=Object.fromEntries(Object.entries(e).filter(([e])=>p.has(e)));return i.trailingButtonLabel??=u[e.trailingButtonIcon],i});return(e,i)=>((0,n.uX)(),(0,n.Wv)((0,n.R1)(o.N),(0,n.v6)(s.value,{ref:"inputField",modelValue:t.value,"onUpdate:modelValue":i[0]||(i[0]=e=>t.value=e)}),(0,n.eX)({_:2},[e.$slots.icon?{name:"icon",fn:(0,n.k6)(()=>[(0,n.RG)(e.$slots,"icon")]),key:"0"}:void 0,"search"!==e.type?{name:"trailing-button-icon",fn:(0,n.k6)(()=>["arrowEnd"===e.trailingButtonIcon?((0,n.uX)(),(0,n.Wv)((0,n.R1)(r.N),{key:0,directional:"",path:(0,n.R1)(a.m)},null,8,["path"])):((0,n.uX)(),(0,n.Wv)((0,n.R1)(r.N),{key:1,path:"undo"===e.trailingButtonIcon?(0,n.R1)(a.a):(0,n.R1)(a.b)},null,8,["path"]))]),key:"1"}:void 0]),1040,["modelValue"]))}})},96593:function(e,i,t){"use strict";t.d(i,{A:()=>n._});var n=t(68532)}}]);
//# sourceMappingURL=chunk-core__677.js.map