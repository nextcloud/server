const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=[window.OC.filePath('', '', 'dist/FilesVersionsSidebarTab-B6rMR4ZT.chunk.mjs'),window.OC.filePath('', '', 'dist/preload-helper-xAe3EUYB.chunk.mjs'),window.OC.filePath('', '', 'dist/index-C1xmmKTZ-kBgT3zMc.chunk.mjs'),window.OC.filePath('', '', 'dist/NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-Da4dXMUU.chunk.mjs'),window.OC.filePath('', '', 'dist/ArrowRight-BC77f5L9.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-BOM4en5n.chunk.mjs'),window.OC.filePath('', '', 'dist/translation-DoG5ZELJ-2UfAUX2V.chunk.mjs'),window.OC.filePath('', '', 'dist/index-rAufP352.chunk.mjs'),window.OC.filePath('', '', 'dist/index-o76qk6sn.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-N3OwSN9O.chunk.css'),window.OC.filePath('', '', 'dist/ArrowRight-Ch8zyY_U.chunk.css'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-B2iEdqc2.chunk.css'),window.OC.filePath('', '', 'dist/TrashCanOutline-DgEtyFGH.chunk.mjs'),window.OC.filePath('', '', 'dist/TrashCanOutline-CWUlo4XY.chunk.css'),window.OC.filePath('', '', 'dist/NcDialog-BG9t4Psg-BSV74Bru.chunk.css'),window.OC.filePath('', '', 'dist/mdi-BGU2G5q5.chunk.mjs'),window.OC.filePath('', '', 'dist/mdi-DZSuYX4-.chunk.css'),window.OC.filePath('', '', 'dist/index-DCPyCjGS.chunk.mjs'),window.OC.filePath('', '', 'dist/public-CKeAb98h.chunk.mjs'),window.OC.filePath('', '', 'dist/util-BSOXDoOW.chunk.mjs'),window.OC.filePath('', '', 'dist/PencilOutline-BMYBdzdS.chunk.mjs'),window.OC.filePath('', '', 'dist/PencilOutline-Bb0ihLdt.chunk.css'),window.OC.filePath('', '', 'dist/NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs'),window.OC.filePath('', '', 'dist/NcDateTime-DRcCH7xq.chunk.css'),window.OC.filePath('', '', 'dist/NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs'),window.OC.filePath('', '', 'dist/index-D5H5XMHa.chunk.mjs'),window.OC.filePath('', '', 'dist/colors-BHGKZFDI-C0-WujoK.chunk.mjs'),window.OC.filePath('', '', 'dist/NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs'),window.OC.filePath('', '', 'dist/NcUserStatusIcon-XiwrgeCm-B3aHoBAd.chunk.css'),window.OC.filePath('', '', 'dist/NcAvatar-C9d7Wrc8-CeBxkemU.chunk.css'),window.OC.filePath('', '', 'dist/TrayArrowDown-DVjUGg6-.chunk.mjs'),window.OC.filePath('', '', 'dist/TrayArrowDown-DzNPKSuT.chunk.css'),window.OC.filePath('', '', 'dist/NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs'),window.OC.filePath('', '', 'dist/NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs'),window.OC.filePath('', '', 'dist/NcInputField-o5OFv3z6-B0lNBgr9.chunk.css'),window.OC.filePath('', '', 'dist/dav-DGipjjQH.chunk.mjs'),window.OC.filePath('', '', 'dist/index-595Vk4Ec.chunk.mjs'),window.OC.filePath('', '', 'dist/files_versions-FilesVersionsSidebarTab-Xj1KhLzV.chunk.css')])))=>i.map(i=>d[i]);
const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { d as defineCustomElement, a as defineAsyncComponent, _ as __vitePreload } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { r as registerSidebarTab } from "./index-DCPyCjGS.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { i as isPublicShare, F as FileType } from "./public-CKeAb98h.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
const BackupRestore = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-backup-restore" viewBox="0 0 24 24"><path d="M12,3A9,9 0 0,0 3,12H0L4,16L8,12H5A7,7 0 0,1 12,5A7,7 0 0,1 19,12A7,7 0 0,1 12,19C10.5,19 9.09,18.5 7.94,17.7L6.5,19.14C8.04,20.3 9.94,21 12,21A9,9 0 0,0 21,12A9,9 0 0,0 12,3M14,12A2,2 0 0,0 12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12Z" /></svg>';
const tagName = "files-versions_sidebar-tab";
registerSidebarTab({
  id: "files_versions",
  tagName,
  order: 90,
  displayName: translate("files_versions", "Versions"),
  iconSvgInline: BackupRestore,
  enabled({ node }) {
    if (isPublicShare()) {
      return false;
    }
    if (node.type !== FileType.File) {
      return false;
    }
    return true;
  },
  async onInit() {
    const FilesVersionsSidebarTab = defineAsyncComponent(() => __vitePreload(() => import("./FilesVersionsSidebarTab-B6rMR4ZT.chunk.mjs"), true ? __vite__mapDeps([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38]) : void 0, import.meta.url));
    window.customElements.define(tagName, defineCustomElement(FilesVersionsSidebarTab, {
      shadowRoot: false
    }));
  }
});
//# sourceMappingURL=files_versions-sidebar-tab.mjs.map
