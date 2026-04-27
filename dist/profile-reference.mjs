const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=[window.OC.filePath('', '', 'dist/ProfilePickerReferenceWidget-D97y66SP.chunk.mjs'),window.OC.filePath('', '', 'dist/NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs'),window.OC.filePath('', '', 'dist/index-rAufP352.chunk.mjs'),window.OC.filePath('', '', 'dist/index-D5H5XMHa.chunk.mjs'),window.OC.filePath('', '', 'dist/preload-helper-xAe3EUYB.chunk.mjs'),window.OC.filePath('', '', 'dist/util-BSOXDoOW.chunk.mjs'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-Da4dXMUU.chunk.mjs'),window.OC.filePath('', '', 'dist/ArrowRight-BC77f5L9.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-BOM4en5n.chunk.mjs'),window.OC.filePath('', '', 'dist/translation-DoG5ZELJ-2UfAUX2V.chunk.mjs'),window.OC.filePath('', '', 'dist/index-o76qk6sn.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-N3OwSN9O.chunk.css'),window.OC.filePath('', '', 'dist/ArrowRight-Ch8zyY_U.chunk.css'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-B2iEdqc2.chunk.css'),window.OC.filePath('', '', 'dist/colors-BHGKZFDI-C0-WujoK.chunk.mjs'),window.OC.filePath('', '', 'dist/NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs'),window.OC.filePath('', '', 'dist/NcUserStatusIcon-XiwrgeCm-B3aHoBAd.chunk.css'),window.OC.filePath('', '', 'dist/PencilOutline-BMYBdzdS.chunk.mjs'),window.OC.filePath('', '', 'dist/PencilOutline-Bb0ihLdt.chunk.css'),window.OC.filePath('', '', 'dist/NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs'),window.OC.filePath('', '', 'dist/NcDateTime-DRcCH7xq.chunk.css'),window.OC.filePath('', '', 'dist/TrashCanOutline-DgEtyFGH.chunk.mjs'),window.OC.filePath('', '', 'dist/TrashCanOutline-CWUlo4XY.chunk.css'),window.OC.filePath('', '', 'dist/NcAvatar-C9d7Wrc8-CeBxkemU.chunk.css'),window.OC.filePath('', '', 'dist/logger-BQwTrq8j.chunk.mjs'),window.OC.filePath('', '', 'dist/profile-ProfilePickerReferenceWidget-D25y9NeU.chunk.css'),window.OC.filePath('', '', 'dist/ProfilesCustomPicker-DJnPF11L.chunk.mjs'),window.OC.filePath('', '', 'dist/NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs'),window.OC.filePath('', '', 'dist/NcEmptyContent-B8-90BSI-CLjlZ-UT.chunk.css'),window.OC.filePath('', '', 'dist/NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs'),window.OC.filePath('', '', 'dist/NcSelect-DLheQ2yp-DEY3FLux.chunk.css'),window.OC.filePath('', '', 'dist/profile-ProfilesCustomPicker-iB16tC1U.chunk.css')])))=>i.map(i=>d[i]);
const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { _ as __vitePreload } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { r as registerWidget, a as registerCustomPickerElement, N as NcCustomPickerRenderResult } from "./index-D5BR15En.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
import "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
registerWidget("profile_widget", async (el, { richObjectType, richObject, accessible }) => {
  const { createApp } = await __vitePreload(async () => {
    const { createApp: createApp2 } = await import("./preload-helper-xAe3EUYB.chunk.mjs").then((n2) => n2.at);
    return { createApp: createApp2 };
  }, true ? [] : void 0, import.meta.url);
  const { default: ProfilePickerReferenceWidget } = await __vitePreload(async () => {
    const { default: ProfilePickerReferenceWidget2 } = await import("./ProfilePickerReferenceWidget-D97y66SP.chunk.mjs");
    return { default: ProfilePickerReferenceWidget2 };
  }, true ? __vite__mapDeps([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25]) : void 0, import.meta.url);
  const app = createApp(
    ProfilePickerReferenceWidget,
    {
      richObjectType,
      richObject,
      accessible
    }
  );
  app.mixin({ methods: { t, n } });
  app.mount(el);
}, () => {
}, { hasInteractiveView: false });
registerCustomPickerElement("profile_picker", async (el, { providerId, accessible }) => {
  const { createApp } = await __vitePreload(async () => {
    const { createApp: createApp2 } = await import("./preload-helper-xAe3EUYB.chunk.mjs").then((n2) => n2.at);
    return { createApp: createApp2 };
  }, true ? [] : void 0, import.meta.url);
  const { default: ProfilesCustomPicker } = await __vitePreload(async () => {
    const { default: ProfilesCustomPicker2 } = await import("./ProfilesCustomPicker-DJnPF11L.chunk.mjs");
    return { default: ProfilesCustomPicker2 };
  }, true ? __vite__mapDeps([26,3,2,4,5,7,8,9,10,11,12,27,28,29,6,13,21,22,30,24,31]) : void 0, import.meta.url);
  const app = createApp(
    ProfilesCustomPicker,
    {
      providerId,
      accessible
    }
  );
  app.mixin({ methods: { t, n } });
  app.mount(el);
  return new NcCustomPickerRenderResult(el, app);
}, (el, renderResult) => {
  renderResult.object.unmount();
}, "normal");
//# sourceMappingURL=profile-reference.mjs.map
