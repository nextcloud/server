<?php

/** @generate-function-entries */

class Imagick
{
#if MagickLibVersion > 0x628
    public function optimizeImageLayers(): bool  {}

    // METRIC_*
    public function compareImageLayers(int $metric): Imagick  {}

    public function pingImageBlob(string $image): bool  {}

    public function pingImageFile(resource $filehandle, ?string $filename = null): bool  {}

    public function transposeImage(): bool  {}

    public function transverseImage(): bool  {}

    public function trimImage(float $fuzz): bool  {}

    public function waveImage(float $amplitude, float $length): bool  {}

    public function vignetteImage(float $black_point, float $white_point, int $x, int $y): bool  {}

    public function uniqueImageColors(): bool  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
//    PHP_ME(imagick, getimagematte, imagick_zero_args, ZEND_ACC_PUBLIC | ZEND_ACC_DEPRECATED)
    /** @deprecated */
    public function getImageMatte(): bool  {}
#endif
#endif

    // TODO - enabled?
    public function setImageMatte(bool $matte): bool  {}

    public function adaptiveResizeImage(
        int $columns,
        int $rows,
        bool $bestfit = false,
        bool $legacy = false): bool  {}

    public function sketchImage(float $radius, float $sigma, float $angle): bool  {}

    public function shadeImage(bool $gray, float $azimuth, float $elevation): bool  {}

    public function getSizeOffset(): int  {}

    public function setSizeOffset(int $columns, int $rows, int $offset): bool  {}


    public function adaptiveBlurImage(
        float $radius,
        float $sigma,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool  {}

    public function contrastStretchImage(
        float $black_point,
        float $white_point,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool  {}

    public function adaptiveSharpenImage(
        float $radius,
        float $sigma,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool  {}


    public function randomThresholdImage(
        float $low,
        float $high,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool  {}

#if MagickLibVersion < 0x700
    /** @deprecated */
    public function roundCornersImage(
        float $x_rounding,
        float $y_rounding,
        float $stroke_width = 10,
        float $displace = 5,
        float $size_correction = -6): bool {}

    /* This alias is due to BWC */
    /**
     * @deprecated
     * @alias Imagick::roundCornersImage
     */
    public function roundCorners(
        float $x_rounding,
        float $y_rounding,
        float $stroke_width = 10,
        float $displace = 5,
        float $size_correction = -6): bool {}

#endif

    public function setIteratorIndex(int $index): bool  {}

    public function getIteratorIndex(): int  {}

#if MagickLibVersion < 0x700
    /** @deprecated */
    public function transformImage(string $crop, string $geometry): Imagick  {}
#endif
#endif

#if MagickLibVersion > 0x630
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function setImageOpacity(float $opacity): bool  {}
#endif

#if MagickLibVersion >= 0x700
    public function setImageAlpha(float $alpha): bool {}
#endif

#if MagickLibVersion < 0x700

    /** @deprecated */
    public function orderedPosterizeImage(
        string $threshold_map,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool  {}
#endif
#endif

#if MagickLibVersion > 0x631
    // TODO - ImagickDraw ....
    public function polaroidImage(ImagickDraw $settings, float $angle): bool  {}

    public function getImageProperty(string $name): string  {}

    public function setImageProperty(string $name, string $value): bool  {}

    public function deleteImageProperty(string $name): bool  {}

    // Replaces any embedded formatting characters with the appropriate
    // image property and returns the interpreted text.
    // See http://www.imagemagick.org/script/escape.php for escape sequences.
    // -format "%m:%f %wx%h"
    public function identifyFormat(string $format): string  {}


#if IM_HAVE_IMAGICK_SETIMAGEINTERPOLATEMETHOD
    // INTERPOLATE_*
    public function setImageInterpolateMethod(int $method): bool  {}
#endif

    // why does this not need to be inside the 'if' for IM_HAVE_IMAGICK_SETIMAGEINTERPOLATEMETHOD ..?
    public function getImageInterpolateMethod(): int  {}

    public function linearStretchImage(float $black_point, float $white_point): bool  {}

    public function getImageLength(): int  {}

    public function extentImage(int $width, int $height, int $x, int $y): bool  {}
#endif
#if MagickLibVersion > 0x633
    public function getImageOrientation(): int  {}

    public function setImageOrientation(int $orientation): bool  {}
#endif

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion > 0x634
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function paintFloodfillImage(
        ImagickPixel|string $fill_color,
        float $fuzz,
        ImagickPixel|string $border_color,
        int $x,
        int $y,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool {}
#endif
#endif
#endif

#if MagickLibVersion > 0x635

    // TODO - Imagick
    public function clutImage(Imagick $lookup_table, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function getImageProperties(string $pattern = "*", bool $include_values = true): array  {}

    public function getImageProfiles(string $pattern = "*", bool $include_values = true): array  {}

    // DISTORTION_*
    public function distortImage(int $distortion, array $arguments, bool $bestfit): bool  {}

    public function writeImageFile(resource $filehandle, ?string $format = null): bool  {}

    public function writeImagesFile(resource $filehandle, ?string $format = null): bool  {}

    public function resetImagePage(string $page): bool  {}

#if MagickLibVersion < 0x700
    /** @deprecated */
    public function setImageClipMask(imagick $clip_mask): bool  {}

    /** @deprecated */
    public function getImageClipMask(): Imagick  {}
#endif

    // TODO - x server?
    public function animateImages(string $x_server): bool  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function recolorImage(array $matrix): bool  {}
#endif
#endif
#endif

#if MagickLibVersion > 0x636
    public function setFont(string $font): bool  {}

    public function getFont(): string  {}

    public function setPointSize(float $point_size): bool  {}

    public function getPointSize(): float  {}

    // LAYERMETHOD_*
    public function mergeImageLayers(int $layermethod): Imagick  {}
#endif

#if MagickLibVersion > 0x637
    // ALPHACHANNEL_*
    public function setImageAlphaChannel(int $alphachannel): bool  {}

    // TODO - ImagickPixel ugh
//  TODO - ugh MagickBooleanType MagickFloodfillPaintImage(MagickWand *wand,
//    const PixelWand *fill,const double fuzz,const PixelWand *bordercolor,
//    const ssize_t x,const ssize_t y,const MagickBooleanType invert)

    public function floodfillPaintImage(
        ImagickPixel|string $fill_color,
        float $fuzz,
        ImagickPixel|string $border_color,
        int $x,
        int $y,
        bool $invert,
        ?int $channel = Imagick::CHANNEL_DEFAULT
    ): bool{}



    public function opaquePaintImage(
        ImagickPixel|string $target_color,
        ImagickPixel|string $fill_color,
        float $fuzz,
        bool $invert,
        int $channel = Imagick::CHANNEL_DEFAULT): bool {}

    public function transparentPaintImage(
        ImagickPixel|string $target_color,
        float $alpha,
        float $fuzz,
        bool $invert
    ): bool  {}
#endif
#if MagickLibVersion > 0x638
    public function liquidRescaleImage(int $width, int $height, float $delta_x, float $rigidity): bool  {}

    public function encipherImage(string $passphrase): bool  {}

//    PHP_ME(imagick, decipherimage, imagick_decipherimage_args, ZEND_ACC_PUBLIC)
    public function decipherImage(string $passphrase): bool  {}
#endif

#if MagickLibVersion > 0x639

    // GRAVITY_*
    public function setGravity(int $gravity): bool  {}

    public function getGravity(): int  {}

    // CHANNEL_
    public function getImageChannelRange(int $channel): array  {}

    public function getImageAlphaChannel(): int  {}
#endif

#if MagickLibVersion > 0x642
    public function getImageChannelDistortions(
        Imagick $reference_image,
        int $metric,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): float {}
#endif

#if MagickLibVersion > 0x643
    // GRAVITY_
    public function setImageGravity(int $gravity): bool  {}

    public function getImageGravity(): int  {}
#endif

#if MagickLibVersion > 0x645
    /**
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @param string $map
     * @param int $pixelstorage // PIXELSTORAGE
     * @param array $pixels
     * @return bool
     */
    public function importImagePixels(
        int $x,
        int $y,
        int $width,
        int $height,
        string $map,
        int $pixelstorage,
        array $pixels): bool {}

    public function deskewImage(float $threshold): bool  {}

    /**
     * @param int $colorspace // COLORSPACE
     * @param float $cluster_threshold
     * @param float $smooth_threshold
     * @param bool $verbose
     * @return bool
     */
    public function segmentImage(
        int $colorspace,
        float $cluster_threshold,
        float $smooth_threshold,
        bool $verbose = false
    ): bool  {}

    // SPARSECOLORMETHOD_*
    public function sparseColorImage(
        int $sparsecolormethod,
        array $arguments,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool {}

    public function remapImage(Imagick $replacement, int $dither_method): bool  {}
#endif


#if PHP_IMAGICK_HAVE_HOUGHLINE
    public function houghLineImage(int $width, int $height, float $threshold): bool {}
#endif

#if MagickLibVersion > 0x646
    /**
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @param string $map e.g. "RGB"
     * @param int $pixelstorage // PIXELSTORAGE
     * @return array
     */
    public function exportImagePixels(
        int $x,
        int $y,
        int $width,
        int $height,
        string $map,
        int $pixelstorage
    ): array {}
#endif

#if MagickLibVersion > 0x648
    public function getImageChannelKurtosis(int $channel = Imagick::CHANNEL_DEFAULT): array  {}

    public function functionImage(
        int $function,
        array $parameters,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool {}
#endif

#if MagickLibVersion > 0x651
    // COLORSPACE_*
    public function transformImageColorspace(int $colorspace): bool  {}
#endif

#if MagickLibVersion > 0x652
    public function haldClutImage(Imagick $clut, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}
#endif

#if MagickLibVersion > 0x655
    public function autoLevelImage(int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function blueShiftImage(float $factor = 1.5): bool  {}
#endif

#if MagickLibVersion > 0x656
    /**
     * @param string $artifact example 'compose:args'
     * @return string
     */
    public function getImageArtifact(string $artifact): string  {}

    /**
     * @param string $artifact example 'compose:args'
     * @param string $value example  "1,0,-0.5,0.5"
     * @return bool
     */
    public function setImageArtifact(string $artifact, string $value): bool  {}

    public function deleteImageArtifact(string $artifact): bool  {}

    // Will return CHANNEL_*
    public function getColorspace(): int  {}

//    PHP_ME(imagick, setcolorspace, imagick_setcolorspace_args, ZEND_ACC_PUBLIC)
    public function setColorspace(int $colorspace): bool  {}

    // CHANNEL_*
    public function clampImage(int $channel = Imagick::CHANNEL_DEFAULT): bool  {}
#endif

#if MagickLibVersion > 0x667
    // stack By default, images are stacked left-to-right. Set stack to MagickTrue to stack them top-to-bottom.
    //offset minimum distance in pixels between images.
    public function smushImages(bool $stack, int $offset): Imagick  {}
#endif

//    PHP_ME(imagick, __construct, imagick_construct_args, ZEND_ACC_PUBLIC|ZEND_ACC_CTOR)
    // TODO int|float? :spocks_eyebrow.gif:
    public function __construct(string|array|int|float|null $files = null) {}

    public function __toString(): string  {}

#if PHP_VERSION_ID >= 50600
    // This calls MagickGetNumberImages underneath
    // mode is unused. Remove at next major release
    // https://github.com/Imagick/imagick/commit/13302500c0ab0ce58e6502e68871187180f7987c
    public function count(int $mode = 0): int  {}
#else
    public function count(): int  {}
#endif

    public function getPixelIterator(): ImagickPixelIterator  {}

    public function getPixelRegionIterator(int $x, int $y, int $columns, int $rows): ImagickPixelIterator  {}

    public function readImage(string $filename): bool  {}

    public function readImages(array $filenames): bool  {}

    public function readImageBlob(string $image, ?string $filename = null): bool  {}

    public function setImageFormat(string $format): bool  {}

    public function scaleImage(int $columns, int $rows, bool $bestfit = false, bool $legacy = false): bool  {}

    public function writeImage(?string $filename = null): bool  {}

    public function writeImages(string $filename, bool $adjoin): bool  {}

    // CHANNEL_
    public function blurImage(float $radius, float $sigma, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function thumbnailImage(
        ?int $columns,
        ?int $rows,
        bool $bestfit = false,
        bool $fill = false,
        bool $legacy = false): bool {}

    public function cropThumbnailImage(int $width, int $height, bool $legacy = false): bool  {}

    public function getImageFilename(): string  {}

    public function setImageFilename(string $filename): bool  {}

    public function getImageFormat(): string  {}

    public function getImageMimeType(): string  {}

    public function removeImage(): bool  {}

    /** @alias Imagick::clear */
    public function destroy(): bool  {}

    public function clear(): bool  {}

    public function clone(): Imagick  {}
        
#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function getImageSize(): int  {}
#endif
#endif

    public function getImageBlob(): string  {}

    public function getImagesBlob(): string  {}

    public function setFirstIterator(): bool  {}

    public function setLastIterator(): bool  {}

    public function resetIterator(): void {}

    public function previousImage(): bool  {}

    public function nextImage(): bool  {}

    public function hasPreviousImage(): bool  {}

    public function hasNextImage(): bool  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function setImageIndex(int $index): bool  {}

    /** @deprecated */
    public function getImageIndex(): int  {}
#endif
#endif

    public function commentImage(string $comment): bool  {}

    public function cropImage(int $width, int $height, int $x, int $y): bool  {}

    public function labelImage(string $label): bool  {}

    public function getImageGeometry(): array  {}

    public function drawImage(ImagickDraw $drawing): bool  {}

    public function setImageCompressionQuality(int $quality): bool  {}

    public function getImageCompressionQuality(): int  {}

    public function setImageCompression(int $compression): bool  {}

    public function getImageCompression(): int  {}

    public function annotateImage(
        ImagickDraw $settings,
        float $x,
        float $y,
        float $angle,
        string $text
    ): bool  {}

    public function compositeImage(
        Imagick $composite_image,
        int $composite,
        int $x,
        int $y,
        int $channel = Imagick::CHANNEL_DEFAULT): bool{}

    public function modulateImage(float $brightness, float $saturation, float $hue): bool  {}

    public function getImageColors(): int  {}



    /**
     * @param ImagickDraw $settings
     * @param string $tile_geometry  e.g. "3x2+0+0"
     * @param string $thumbnail_geometry e.g. "200x160+3+3>"
     * @param int $monatgemode // MONTAGEMODE_
     * @param string $frame // "10x10+2+2"
     * @return Imagick
     */
    public function montageImage(
        ImagickDraw $settings,
        string $tile_geometry,
        string $thumbnail_geometry,
        int $monatgemode,
        string $frame
    ): Imagick {}

    public function identifyImage(bool $append_raw_output = false): array  {}

    public function thresholdImage(float $threshold, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function adaptiveThresholdImage(int $width, int $height, int $offset): bool  {}

    public function blackThresholdImage(ImagickPixel|string $threshold_color): bool  {}

    public function whiteThresholdImage(ImagickPixel|string $threshold_color): bool  {}

    public function appendImages(bool $stack): Imagick  {}

    public function charcoalImage(float $radius, float $sigma): bool  {}

    public function normalizeImage(int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function oilPaintImage(float $radius): bool  {}

    public function posterizeImage(int $levels, bool $dither): bool  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function radialBlurImage(float $angle, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}
#endif
#endif

    public function raiseImage(int $width, int $height, int $x, int $y, bool $raise): bool  {}

    public function resampleImage(float $x_resolution, float $y_resolution, int $filter, float $blur): bool  {}

    public function resizeImage(
        int $columns,
        int $rows,
        int $filter,
        float $blur,
        bool $bestfit = false,
        bool $legacy = false): bool {}

    public function rollImage(int $x, int $y): bool  {}

    public function rotateImage(ImagickPixel|string $background_color, float $degrees): bool  {}

    public function sampleImage(int $columns, int $rows): bool  {}

    public function solarizeImage(int $threshold): bool  {}

    public function shadowImage(float $opacity, float $sigma, int $x, int $y): bool  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function setImageAttribute(string $key, string $value): bool  {}
#endif
#endif

    public function setImageBackgroundColor(ImagickPixel|string $background_color): bool  {}

#if MagickLibVersion >= 0x700
    public function setImageChannelMask(int $channel): int {}
#endif

    public function setImageCompose(int $compose): bool  {}

    public function setImageDelay(int $delay): bool  {}

    public function setImageDepth(int $depth): bool  {}

    public function setImageGamma(float $gamma): bool  {}

    public function setImageIterations(int $iterations): bool  {}

#if MagickLibVersion < 0x700
    /** @deprecated */
    public function setImageMatteColor(ImagickPixel|string $matte_color): bool  {}
#endif

    public function setImagePage(int $width, int $height, int $x, int $y): bool  {}

    // TODO test this.
    public function setImageProgressMonitor(string $filename): bool {}

#if MagickLibVersion > 0x653
    public function setProgressMonitor(callable $callback): bool  {}
#endif

    public function setImageResolution(float $x_resolution, float $y_resolution): bool  {}

    // I have no idea what scene does.
    public function setImageScene(int $scene): bool  {}

    public function setImageTicksPerSecond(int $ticks_per_second): bool  {}

    // IMGTYPE_*
    public function setImageType(int $image_type): bool  {}

    public function setImageUnits(int $units): bool  {}

    public function sharpenImage(float $radius, float $sigma, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function shaveImage(int $columns, int $rows): bool  {}

    public function shearImage(ImagickPixel|string $background_color, float $x_shear, float $y_shear): bool  {}

    public function spliceImage(int $width, int $height, int $x, int $y): bool  {}

    public function pingImage(string $filename): bool  {}

    public function readImageFile(resource $filehandle, ?string $filename = null): bool  {}

    public function displayImage(string $servername): bool  {}

    public function displayImages(string $servername): bool  {}

    public function spreadImage(float $radius): bool  {}

    public function swirlImage(float $degrees): bool  {}

    public function stripImage(): bool  {}

    public static function queryFormats(string $pattern = "*"): array  {}

    public static function queryFonts(string $pattern = "*"): array  {}

    /* TODO  $multiline == null,  means we should autodetect */
    public function queryFontMetrics(ImagickDraw $settings, string $text, ?bool $multiline = null): array  {}

    public function steganoImage(Imagick $watermark, int $offset): Imagick  {}

    // NOISE_*
    public function addNoiseImage(int $noise, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function motionBlurImage(
        float $radius,
        float $sigma,
        float $angle,
        int $channel = Imagick::CHANNEL_DEFAULT
    ):bool {}

#if MagickLibVersion < 0x700
#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
    /** @deprecated */
    public function mosaicImages(): Imagick  {}
#endif
#endif

    public function morphImages(int $number_frames): Imagick  {}

    public function minifyImage(): bool  {}

    public function affineTransformImage(ImagickDraw $settings): bool  {}

#if MagickLibVersion < 0x700
#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
    /** @deprecated */
    public function averageImages(): Imagick  {}
#endif
#endif

    public function borderImage(ImagickPixel|string $border_color, int $width, int $height): bool  {}

    public static function calculateCrop(
        int $original_width,
        int $original_height,
        int $desired_width,
        int $desired_height,
        bool $legacy = false): array {}

    public function chopImage(int $width, int $height, int $x, int $y): bool  {}

    public function clipImage(): bool  {}

    public function clipPathImage(string $pathname, bool $inside): bool  {}

    /* clippathimage has been deprecated. Create alias here and use the newer API function if present */
    /** @alias Imagick::clipPathImage */
    public function clipImagePath(string $pathname, bool $inside): void  {}

    public function coalesceImages(): Imagick  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function colorFloodfillImage(
        ImagickPixel|string $fill_color,
        float $fuzz,
        ImagickPixel|string $border_color,
        int $x,
        int $y
    ): bool  {}
#endif
#endif

    // TODO - opacity is actually float if legacy is true...
    public function colorizeImage(
        ImagickPixel|string $colorize_color,
        ImagickPixel|string|false $opacity_color,
        ?bool $legacy = false ): bool  {}

    public function compareImageChannels(Imagick $reference, int $channel, int $metric): array  {}

    public function compareImages(Imagick $reference, int $metric): array  {}

    public function contrastImage(bool $sharpen): bool  {}

    public function combineImages(int $colorspace): Imagick  {}

    // kernel is a 2d array of float values
    public function convolveImage(array $kernel, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function cycleColormapImage(int $displace): bool  {}

    public function deconstructImages(): Imagick  {}

    public function despeckleImage(): bool  {}

    public function edgeImage(float $radius): bool  {}

    public function embossImage(float $radius, float $sigma): bool  {}

    public function enhanceImage(): bool  {}

    public function equalizeImage(): bool  {}

    // EVALUATE_*
    public function evaluateImage(int $evaluate, float $constant, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

#if MagickLibVersion >= 0x687
//	Merge multiple images of the same size together with the selected operator.
//http://www.imagemagick.org/Usage/layers/#evaluate-sequence

    // EVALUATE_*
    public function evaluateImages(int $evaluate): bool {}

#endif

#if MagickLibVersion < 0x700
#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
    /** @deprecated */
    public function flattenImages(): Imagick  {}
#endif
#endif
    public function flipImage(): bool  {}

    public function flopImage(): bool  {}

#if MagickLibVersion >= 0x655
    public function forwardFourierTransformImage(bool $magnitude): bool  {}
#endif

    public function frameImage(
        ImagickPixel|string $matte_color,
        int $width,
        int $height,
        int $inner_bevel,
        int $outer_bevel
    ): bool  {}


    public function fxImage(string $expression, int $channel = Imagick::CHANNEL_DEFAULT): Imagick  {}

    public function gammaImage(float $gamma, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

    public function gaussianBlurImage(float $radius, float $sigma, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

#if MagickLibVersion < 0x700
#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
    /** @deprecated */
    public function getImageAttribute(string $key): string  {}
#endif
#endif

    public function getImageBackgroundColor(): ImagickPixel  {}

    public function getImageBluePrimary(): array  {}

    public function getImageBorderColor(): ImagickPixel  {}

    public function getImageChannelDepth(int $channel): int  {}

    public function getImageChannelDistortion(Imagick $reference, int $channel, int $metric): float  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function getImageChannelExtrema(int $channel): array  {}
#endif
#endif

    public function getImageChannelMean(int $channel): array  {}

    public function getImageChannelStatistics(): array  {}

    // index - the offset into the image colormap. I have no idea.
    public function getImageColormapColor(int $index): ImagickPixel  {}

    public function getImageColorspace(): int  {}

    public function getImageCompose(): int  {}

    public function getImageDelay(): int  {}

    public function getImageDepth(): int  {}

    // METRIC_
    public function getImageDistortion(Imagick $reference, int $metric): float  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function getImageExtrema(): array  {}
#endif
#endif

    public function getImageDispose(): int  {}

    public function getImageGamma(): float  {}

    public function getImageGreenPrimary(): array  {}

    public function getImageHeight(): int  {}

    public function getImageHistogram(): array  {}

    public function getImageInterlaceScheme(): int  {}

    public function getImageIterations(): int  {}

#if MagickLibVersion < 0x700
    /** @deprecated */
    public function getImageMatteColor(): ImagickPixel  {}
#endif

    public function getImagePage(): array  {}

    public function getImagePixelColor(int $x, int $y): ImagickPixel  {}


#if IM_HAVE_IMAGICK_SETIMAGEPIXELCOLOR
    // TODO - needs a test.
    public function setImagePixelColor(int $x, int $y, ImagickPixel|string $color): ImagickPixel  {}
#endif

    public function getImageProfile(string $name): string  {}

    public function getImageRedPrimary(): array  {}

    public function getImageRenderingIntent(): int  {}

    public function getImageResolution(): array  {}

    public function getImageScene(): int  {}

    public function getImageSignature(): string  {}

    public function getImageTicksPerSecond(): int  {}

    public function getImageType(): int  {}

    public function getImageUnits(): int  {}

    public function getImageVirtualPixelMethod(): int  {}

    public function getImageWhitePoint(): array  {}

    public function getImageWidth(): int  {}

    public function getNumberImages(): int  {}

    public function getImageTotalInkDensity(): float  {}

    public function getImageRegion(int $width, int $height, int $x, int $y): Imagick  {}

    public function implodeImage(float $radius): bool  {}

#if MagickLibVersion >= 0x658
    // TODO MagickWand *magnitude_wand,MagickWand *phase_wand,
    public function inverseFourierTransformImage(Imagick $complement, bool $magnitude): bool  {}
#endif

    public function levelImage(
        float $black_point,
        float $gamma,
        float $white_point,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool    {}

    public function magnifyImage(): bool  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function mapImage(imagick $map, bool $dither): bool  {}

    /** @deprecated */
    public function matteFloodfillImage(
        float $alpha,
        float $fuzz,
        ImagickPixel|string $border_color,
        int $x,
        int $y
    ): bool  {}
#endif
#endif

#if MagickLibVersion < 0x700
#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
    /** @deprecated */
    public function medianFilterImage(float $radius): bool  {}
#endif
#endif

    public function negateImage(bool $gray, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function paintOpaqueImage(
        ImagickPixel|string $target_color,
        ImagickPixel|string $fill_color,
        float $fuzz,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool  {}

    /** @deprecated */
    public function paintTransparentImage(ImagickPixel|string $target_color, float $alpha, float $fuzz): bool  {}
#endif
#endif

    // PREVIEW_*
    public function previewImages(int $preview): bool  {}

    public function profileImage(string $name, string $profile): bool  {}

    public function  quantizeImage(
        int $number_colors,
        int $colorspace,
        int $tree_depth,
        bool $dither,
        bool $measure_error
    ):  bool {}


    public function quantizeImages(
        int $number_colors,
        int $colorspace,
        int $tree_depth,
        bool $dither,
        bool $measure_error): bool {}

#if !defined(MAGICKCORE_EXCLUDE_DEPRECATED)
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function reduceNoiseImage(float $radius): bool  {}
#endif
#endif

    public function removeImageProfile(string $name): string  {}

    public function separateImageChannel(int $channel): bool  {}

    public function sepiaToneImage(float $threshold): bool  {}

#if MagickLibVersion < 0x700
    /** @deprecated */
    public function setImageBias(float $bias): bool  {}

    /** @deprecated */
    public function setImageBiasQuantum(string $bias): void  {}
#endif

    public function setImageBluePrimary(float $x, float $y): bool  {}
    /* {{{ proto bool Imagick::setImageBluePrimary(float x,float y)
For IM7 the prototype is
proto bool Imagick::setImageBluePrimary(float x, float y, float z) */

    public function setImageBorderColor(ImagickPixel|string $border_color): bool  {}

    public function setImageChannelDepth(int $channel, int $depth): bool  {}

    public function setImageColormapColor(int $index, ImagickPixel|string $color): bool  {}

    public function setImageColorspace(int $colorspace): bool  {}

    public function setImageDispose(int $dispose): bool  {}

    public function setImageExtent(int $columns, int $rows): bool  {}

    public function setImageGreenPrimary(float $x, float $y): bool  {}

    // INTERLACE_*
    public function setImageInterlaceScheme(int $interlace): bool  {}

    public function setImageProfile(string $name, string $profile): bool  {}

    public function setImageRedPrimary(float $x, float $y): bool  {}

    // RENDERINGINTENT
    public function setImageRenderingIntent(int $rendering_intent): bool  {}

    public function setImageVirtualPixelMethod(int $method): bool  {}

    public function setImageWhitePoint(float $x, float $y): bool  {}

    public function  sigmoidalContrastImage(
        bool $sharpen,
        float $alpha,
        float $beta,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool{}

    // TODO - MagickStereoImage() composites two images and produces a single
    // image that is the composite of a left and right image of a stereo pair
    public function stereoImage(Imagick $offset_image): bool  {}

    public function textureImage(Imagick $texture): Imagick  {}

    public function tintImage(
        ImagickPixel|string $tint_color,
        ImagickPixel|string $opacity_color,
        bool $legacy = false
    ): bool  {}

    public function unsharpMaskImage(
        float $radius,
        float $sigma,
        float $amount,
        float $threshold,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool {}

    public function getImage(): Imagick  {}

    public function addImage(Imagick $image): bool  {}

    public function setImage(Imagick $image): bool  {}


    public function newImage(
        int $columns,
        int $rows,
        ImagickPixel|string $background_color,
        string $format = null
    ): bool  {}

    // TODO - canvas? description
    public function newPseudoImage(int $columns, int $rows, string $pseudo_format): bool  {}

    public function getCompression(): int  {}

    public function getCompressionQuality(): int  {}

    public static function getCopyright(): string  {}

    public static function getConfigureOptions(string $pattern = "*"): string {}


#if MagickLibVersion > 0x660
    public static function getFeatures(): string {}
#endif

    public function getFilename(): string  {}

    public function getFormat(): string  {}

    public static function getHomeURL(): string  {}

    public function getInterlaceScheme(): int {}

    public function getOption(string $key): string  {}

    public static function getPackageName(): string  {}

    public function getPage(): array  {}

    public static function getQuantum(): int  {}

    public static function getHdriEnabled(): bool {}

    public static function getQuantumDepth(): array  {}

    public static function getQuantumRange(): array  {}

    public static function getReleaseDate(): string  {}

    public static function getResource(int $type): int  {}

    public static function getResourceLimit(int $type): int  {}

    public function getSamplingFactors(): array  {}

    public function getSize(): array  {}

    public static function getVersion(): array  {}

    public function setBackgroundColor(ImagickPixel|string $background_color): bool  {}

    public function setCompression(int $compression): bool  {}

    public function setCompressionQuality(int $quality): bool  {}

    public function setFilename(string $filename): bool  {}

    public function setFormat(string $format): bool  {}

    // INTERLACE_*
    public function setInterlaceScheme(int $interlace): bool  {}

    public function setOption(string $key, string $value): bool  {}

    public function setPage(int $width, int $height, int $x, int $y): bool  {}

    public static function setResourceLimit(int $type, int $limit): bool  {}

    public function setResolution(float $x_resolution, float $y_resolution): bool  {}

    public function setSamplingFactors(array $factors): bool  {}

    public function setSize(int $columns, int $rows): bool  {}

    // IMGTYPE_*
    public function setType(int $imgtype): bool  {}

#if MagickLibVersion > 0x628
    /** @alias Imagick::getIteratorIndex */
    public function key(): int  {}

//#else
//# if defined(MAGICKCORE_EXCLUDE_DEPRECATED)
//#  error "MAGICKCORE_EXCLUDE_DEPRECATED should not be defined with ImageMagick version below 6.2.8"
//# else
////    PHP_MALIAS(imagick, key, getimageindex, imagick_zero_args, ZEND_ACC_PUBLIC)
//        /** @alias Imagick::getImageIndex */
//    public function key(): int  {}
//
//# endif
//#endif

    /** @alias Imagick::nextImage
     *  @tentative-return-type
     */
    public function next(): void  {}

    /** @alias Imagick::setFirstIterator
     *  @tentative-return-type
     */
    public function rewind(): void  {}

    public function valid(): bool  {}

    public function current(): Imagick  {}

#if MagickLibVersion >= 0x659
    public function brightnessContrastImage(
        float $brightness,
        float $contrast,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool {}
#endif

#if MagickLibVersion > 0x661
    public function colorMatrixImage(array $color_matrix): bool  {}
#endif

    public function selectiveBlurImage(
        float $radius,
        float $sigma,
        float $threshold,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool {}

#if MagickLibVersion >= 0x689
    public function rotationalBlurImage(float $angle, int $channel = Imagick::CHANNEL_DEFAULT): bool  {}
#endif

#if MagickLibVersion >= 0x683
    public function statisticImage(
        int $type,
        int $width,
        int $height,
        int $channel = Imagick::CHANNEL_DEFAULT
        ): bool {}
#endif

#if MagickLibVersion >= 0x652
    public function subimageMatch(Imagick $image, ?array &$offset = null, ?float &$similarity = null, float $threshold = 0.0, int $metric = 0): Imagick  {}

    /** @alias Imagick::subimageMatch */
    public function similarityimage(Imagick $image, ?array &$offset = null, ?float &$similarity = null, float $threshold = 0.0, int $metric = 0): Imagick  {}
#endif

    public static function setRegistry(string $key, string $value): bool  {}

    public static function getRegistry(string $key): string  {}

    public static function listRegistry(): array {}

#if MagickLibVersion >= 0x680

    /**
     * @param int $morphology MORPHOLOGY_*
     * @param int $iterations
     * @param ImagickKernel $kernel
     * @param int $channel
     * @return bool
     */
    public function morphology(
        int $morphology,
        int $iterations,
        ImagickKernel $kernel,
        int $channel = Imagick::CHANNEL_DEFAULT
    ): bool {}
#endif

#ifdef IMAGICK_WITH_KERNEL
#if MagickLibVersion < 0x700
    /** @deprecated */
    public function filter(ImagickKernel $kernel, int $channel = Imagick::CHANNEL_UNDEFINED): bool  {}
#endif
#endif

    public function setAntialias(bool $antialias): void {}

    public function getAntialias(): bool {}

#if MagickLibVersion > 0x676
    /**
     * @param string $color_correction_collection
     * <ColorCorrectionCollection xmlns="urn:ASC:CDL:v1.2">
     * <ColorCorrection id="cc03345">
     * <SOPNode>
     * <Slope> 0.9 1.2 0.5 </Slope>
     * <Offset> 0.4 -0.5 0.6 </Offset>
     * <Power> 1.0 0.8 1.5 </Power>
     * </SOPNode>
     * <SATNode>
     * <Saturation> 0.85 </Saturation>
     * </SATNode>
     * </ColorCorrection>
     * </ColorCorrectionCollection>
     *
     * @return bool
     */
    public function colorDecisionListImage(string $color_correction_collection): bool {}
#endif

#if MagickLibVersion >= 0x687
    public function optimizeImageTransparency(): void {}
#endif

#if MagickLibVersion >= 0x660
    public function autoGammaImage(?int $channel = Imagick::CHANNEL_ALL): void {}
#endif

#if MagickLibVersion >= 0x692
    public function autoOrient(): void {}

    /** @alias Imagick::autoOrient */
    public function autoOrientate(): void {}

    // COMPOSITE_*
    public function compositeImageGravity(Imagick $image, int $composite_constant, int $gravity): bool {}

#endif

#if MagickLibVersion >= 0x693
    public function localContrastImage(float $radius, float $strength): void {}
#endif

#if MagickLibVersion >= 0x700
    // Identifies the potential image type, returns one of the Imagick::IMGTYPE_* constants
    public function identifyImageType(): int {}
#endif


#if IM_HAVE_IMAGICK_GETSETIMAGEMASK
    // PIXELMASK_*
    public function getImageMask(int $pixelmask): ?Imagick {}

    // PIXELMASK_*
    public function setImageMask(Imagick $clip_mask, int $pixelmask): void {}
#endif


    // TODO - needs deleting from docs.
//    public function getImageMagickLicense(): string  {}

    // TODO - needs deleting from docs.
//    public function render(): bool  {}

//    public function floodfillPaintImage(
//        ImagickPixel|string $fill,
//        float $fuzz,
//        ImagickPixel|string $bordercolor,
//        int $x,
//        int $y,
//        bool $invert,
//        int $channel = Imagick::CHANNEL_DEFAULT): null {}
}
