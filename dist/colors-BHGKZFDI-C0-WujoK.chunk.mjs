const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { r as register, d as t3, b as t } from "./Web-BOM4en5n.chunk.mjs";
register(t3);
class Color {
  /**
   * @param r - The red value
   * @param g - The green value
   * @param b - The blue value
   * @param name - The name of the color
   */
  constructor(r, g, b, name) {
    this.r = r;
    this.g = g;
    this.b = b;
    this.name = name;
    this.r = Math.min(r, 255);
    this.g = Math.min(g, 255);
    this.b = Math.min(b, 255);
    this.name = name;
  }
  /**
   * The hexadecimal color string.
   */
  get color() {
    const toHex = (int) => `00${int.toString(16)}`.slice(-2);
    return `#${toHex(this.r)}${toHex(this.g)}${toHex(this.b)}`;
  }
}
function calculateStepIncrement(steps, color1, color2) {
  return {
    r: (color2.r - color1.r) / steps,
    g: (color2.g - color1.g) / steps,
    b: (color2.b - color1.b) / steps
  };
}
function mixPalette(steps, color1, color2) {
  const palette = [];
  palette.push(color1);
  const increment = calculateStepIncrement(steps, color1, color2);
  for (let i = 1; i < steps; i++) {
    const r = Math.floor(color1.r + increment.r * i);
    const g = Math.floor(color1.g + increment.g * i);
    const b = Math.floor(color1.b + increment.b * i);
    palette.push(new Color(r, g, b));
  }
  return palette;
}
const COLOR_RED = new Color(182, 70, 157, t("Purple"));
const COLOR_YELLOW = new Color(221, 203, 85, t("Gold"));
const COLOR_BLUE = new Color(0, 130, 201, t("Nextcloud blue"));
const COLOR_BLACK = new Color(0, 0, 0, t("Black"));
const COLOR_WHITE = new Color(255, 255, 255, t("White"));
const defaultPalette = [
  COLOR_RED,
  new Color(
    ...[191, 103, 139],
    t("Rosy brown")
    // TRANSLATORS: A color name for RGB(191, 103, 139)
  ),
  new Color(
    ...[201, 136, 121],
    t("Feldspar")
    // TRANSLATORS: A color name for RGB(201, 136, 121)
  ),
  new Color(
    ...[211, 169, 103],
    t("Whiskey")
    // TRANSLATORS: A color name for RGB(211, 169, 103)
  ),
  COLOR_YELLOW,
  new Color(
    ...[165, 184, 114],
    t("Olivine")
    // TRANSLATORS: A color name for RGB(165, 184, 114)
  ),
  new Color(
    ...[110, 166, 143],
    t("Acapulco")
    // TRANSLATORS: A color name for RGB(110, 166, 143)
  ),
  new Color(
    ...[55, 148, 172],
    t("Boston Blue")
    // TRANSLATORS: A color name for RGB(55, 148, 172)
  ),
  COLOR_BLUE,
  new Color(
    ...[45, 115, 190],
    t("Mariner")
    // TRANSLATORS: A color name for RGB(45, 115, 190)
  ),
  new Color(
    ...[91, 100, 179],
    t("Blue Violet")
    // TRANSLATORS: A color name for RGB(91, 100, 179)
  ),
  new Color(
    ...[136, 85, 168],
    t("Deluge")
    // TRANSLATORS: A color name for RGB(136, 85, 168)
  )
];
function generatePalette(steps) {
  const palette1 = mixPalette(steps, COLOR_RED, COLOR_YELLOW);
  const palette2 = mixPalette(steps, COLOR_YELLOW, COLOR_BLUE);
  const palette3 = mixPalette(steps, COLOR_BLUE, COLOR_RED);
  return palette1.concat(palette2).concat(palette3);
}
export {
  Color as C,
  COLOR_BLACK as a,
  COLOR_WHITE as b,
  defaultPalette as d,
  generatePalette as g
};
//# sourceMappingURL=colors-BHGKZFDI-C0-WujoK.chunk.mjs.map
