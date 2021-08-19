<?php

namespace Safe;

use Safe\Exceptions\ImageException;

/**
 * The getimagesize function will determine the
 * size of any supported given image file and return the dimensions along with
 * the file type and a height/width text string to be used inside a
 * normal HTML IMG tag and the
 * correspondent HTTP content type.
 *
 * getimagesize can also return some more information
 * in imageinfo parameter.
 *
 * @param string $filename This parameter specifies the file you wish to retrieve information
 * about. It can reference a local file or (configuration permitting) a
 * remote file using one of the supported streams.
 * @param array $imageinfo This optional parameter allows you to extract some extended
 * information from the image file. Currently, this will return the
 * different JPG APP markers as an associative array.
 * Some programs use these APP markers to embed text information in
 * images. A very common one is to embed
 * IPTC information in the APP13 marker.
 * You can use the iptcparse function to parse the
 * binary APP13 marker into something readable.
 *
 * The imageinfo only supports
 * JFIF files.
 * @return array Returns an array with up to 7 elements. Not all image types will include
 * the channels and bits elements.
 *
 * Index 0 and 1 contains respectively the width and the height of the image.
 *
 * Index 2 is one of the IMAGETYPE_XXX constants indicating
 * the type of the image.
 *
 * Index 3 is a text string with the correct
 * height="yyy" width="xxx" string that can be used
 * directly in an IMG tag.
 *
 * mime is the correspondant MIME type of the image.
 * This information can be used to deliver images with the correct HTTP
 * Content-type header:
 *
 * getimagesize and MIME types
 *
 *
 * ]]>
 *
 *
 *
 * channels will be 3 for RGB pictures and 4 for CMYK
 * pictures.
 *
 * bits is the number of bits for each color.
 *
 * For some image types, the presence of channels and
 * bits values can be a bit
 * confusing. As an example, GIF always uses 3 channels
 * per pixel, but the number of bits per pixel cannot be calculated for an
 * animated GIF with a global color table.
 *
 * On failure, FALSE is returned.
 * @throws ImageException
 *
 */
function getimagesize(string $filename, array &$imageinfo = null): array
{
    error_clear_last();
    $result = \getimagesize($filename, $imageinfo);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * image2wbmp outputs or save a WBMP
 * version of the given image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param string|null $filename Path to the saved file. If not given, the raw image stream will be
 * output directly.
 * @param int $foreground You can set the foreground color with this parameter by setting an
 * identifier obtained from imagecolorallocate.
 * The default foreground color is black.
 * @throws ImageException
 *
 */
function image2wbmp($image, ?string $filename = null, int $foreground = null): void
{
    error_clear_last();
    if ($foreground !== null) {
        $result = \image2wbmp($image, $filename, $foreground);
    } elseif ($filename !== null) {
        $result = \image2wbmp($image, $filename);
    } else {
        $result = \image2wbmp($image);
    }
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 *
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param array $affine Array with keys 0 to 5.
 * @param array $clip Array with keys "x", "y", "width" and "height".
 * @return resource Return affined image resource on success.
 * @throws ImageException
 *
 */
function imageaffine($image, array $affine, array $clip = null)
{
    error_clear_last();
    if ($clip !== null) {
        $result = \imageaffine($image, $affine, $clip);
    } else {
        $result = \imageaffine($image, $affine);
    }
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the concatenation of two affine transformation matrices,
 * what is useful if multiple transformations should be applied to the same
 * image in one go.
 *
 * @param array $m1 An affine transformation matrix (an array with keys
 * 0 to 5 and float values).
 * @param array $m2 An affine transformation matrix (an array with keys
 * 0 to 5 and float values).
 * @return array{0:float,1:float,2:float,3:float,4:float,5:float} An affine transformation matrix (an array with keys
 * 0 to 5 and float values).
 * @throws ImageException
 *
 */
function imageaffinematrixconcat(array $m1, array $m2): array
{
    error_clear_last();
    $result = \imageaffinematrixconcat($m1, $m2);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns an affine transformation matrix.
 *
 * @param int $type One of the IMG_AFFINE_* constants.
 * @param array|float $options If type is IMG_AFFINE_TRANSLATE
 * or IMG_AFFINE_SCALE,
 * options has to be an array with keys x
 * and y, both having float values.
 *
 * If type is IMG_AFFINE_ROTATE,
 * IMG_AFFINE_SHEAR_HORIZONTAL or IMG_AFFINE_SHEAR_VERTICAL,
 * options has to be a float specifying the angle.
 * @return array{0:float,1:float,2:float,3:float,4:float,5:float} An affine transformation matrix (an array with keys
 * 0 to 5 and float values).
 * @throws ImageException
 *
 */
function imageaffinematrixget(int $type, $options = null): array
{
    error_clear_last();
    if ($options !== null) {
        $result = \imageaffinematrixget($type, $options);
    } else {
        $result = \imageaffinematrixget($type);
    }
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagealphablending allows for two different
 * modes of drawing on truecolor images. In blending mode, the
 * alpha channel component of the color supplied to all drawing function,
 * such as imagesetpixel determines how much of the
 * underlying color should be allowed to shine through.  As a result, gd
 * automatically blends the existing color at that point with the drawing color,
 * and stores the result in the image.  The resulting pixel is opaque.  In
 * non-blending mode, the drawing color is copied literally with its alpha channel
 * information, replacing the destination pixel.  Blending mode is not available
 * when drawing on palette images.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param bool $blendmode Whether to enable the blending mode or not. On true color images
 * the default value is TRUE otherwise the default value is FALSE
 * @throws ImageException
 *
 */
function imagealphablending($image, bool $blendmode): void
{
    error_clear_last();
    $result = \imagealphablending($image, $blendmode);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Activate the fast drawing antialiased methods for lines and wired polygons.
 * It does not support alpha components. It works using a direct blend
 * operation. It works only with truecolor images.
 *
 * Thickness and styled are not supported.
 *
 * Using antialiased primitives with transparent background color can end with
 * some unexpected results. The blend method uses the background color as any
 * other colors. The lack of alpha component support does not allow an alpha
 * based antialiasing method.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param bool $enabled Whether to enable antialiasing or not.
 * @throws ImageException
 *
 */
function imageantialias($image, bool $enabled): void
{
    error_clear_last();
    $result = \imageantialias($image, $enabled);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagearc draws an arc of circle centered at the given
 * coordinates.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $cx x-coordinate of the center.
 * @param int $cy y-coordinate of the center.
 * @param int $width The arc width.
 * @param int $height The arc height.
 * @param int $start The arc start angle, in degrees.
 * @param int $end The arc end angle, in degrees.
 * 0° is located at the three-o'clock position, and the arc is drawn
 * clockwise.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagearc($image, int $cx, int $cy, int $width, int $height, int $start, int $end, int $color): void
{
    error_clear_last();
    $result = \imagearc($image, $cx, $cy, $width, $height, $start, $end, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Outputs or saves a BMP version of the given image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param mixed $to The path or an open stream resource (which is automatically being closed after this function returns) to save the file to. If not set or NULL, the raw image stream will be outputted directly.
 *
 * NULL is invalid if the compressed arguments is
 * not used.
 * @param bool $compressed Whether the BMP should be compressed with run-length encoding (RLE), or not.
 * @throws ImageException
 *
 */
function imagebmp($image, $to = null, bool $compressed = true): void
{
    error_clear_last();
    $result = \imagebmp($image, $to, $compressed);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagechar draws the first character of
 * c in the image identified by
 * image with its upper-left at
 * x,y (top left is 0,
 * 0) with the color color.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $font Can be 1, 2, 3, 4, 5 for built-in
 * fonts in latin2 encoding (where higher numbers corresponding to larger fonts) or any of your
 * own font identifiers registered with imageloadfont.
 * @param int $x x-coordinate of the start.
 * @param int $y y-coordinate of the start.
 * @param string $c The character to draw.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagechar($image, int $font, int $x, int $y, string $c, int $color): void
{
    error_clear_last();
    $result = \imagechar($image, $font, $x, $y, $c, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Draws the character c vertically at the specified
 * coordinate on the given image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $font Can be 1, 2, 3, 4, 5 for built-in
 * fonts in latin2 encoding (where higher numbers corresponding to larger fonts) or any of your
 * own font identifiers registered with imageloadfont.
 * @param int $x x-coordinate of the start.
 * @param int $y y-coordinate of the start.
 * @param string $c The character to draw.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagecharup($image, int $font, int $x, int $y, string $c, int $color): void
{
    error_clear_last();
    $result = \imagecharup($image, $font, $x, $y, $c, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Returns the index of the color of the pixel at the
 * specified location in the image specified by image.
 *
 * If the image is a
 * truecolor image, this function returns the RGB value of that pixel as
 * integer. Use bitshifting and masking to access the distinct red, green and blue
 * component values:
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x x-coordinate of the point.
 * @param int $y y-coordinate of the point.
 * @return int Returns the index of the color.
 * @throws ImageException
 *
 */
function imagecolorat($image, int $x, int $y): int
{
    error_clear_last();
    $result = \imagecolorat($image, $x, $y);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * De-allocates a color previously allocated with
 * imagecolorallocate or
 * imagecolorallocatealpha.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $color The color identifier.
 * @throws ImageException
 *
 */
function imagecolordeallocate($image, int $color): void
{
    error_clear_last();
    $result = \imagecolordeallocate($image, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Makes the colors of the palette version of an image more closely match the true color version.
 *
 * @param resource $image1 A truecolor image resource.
 * @param resource $image2 A palette image resource pointing to an image that has the same
 * size as image1.
 * @throws ImageException
 *
 */
function imagecolormatch($image1, $image2): void
{
    error_clear_last();
    $result = \imagecolormatch($image1, $image2);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Applies a convolution matrix on the image, using the given coefficient and
 * offset.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param array $matrix A 3x3 matrix: an array of three arrays of three floats.
 * @param float $div The divisor of the result of the convolution, used for normalization.
 * @param float $offset Color offset.
 * @throws ImageException
 *
 */
function imageconvolution($image, array $matrix, float $div, float $offset): void
{
    error_clear_last();
    $result = \imageconvolution($image, $matrix, $div, $offset);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Copy a part of src_im onto
 * dst_im starting at the x,y coordinates
 * src_x, src_y  with
 * a width of src_w and a height of
 * src_h.  The portion defined will be copied
 * onto the x,y coordinates, dst_x and
 * dst_y.
 *
 * @param resource $dst_im Destination image resource.
 * @param resource $src_im Source image resource.
 * @param int $dst_x x-coordinate of destination point.
 * @param int $dst_y y-coordinate of destination point.
 * @param int $src_x x-coordinate of source point.
 * @param int $src_y y-coordinate of source point.
 * @param int $src_w Source width.
 * @param int $src_h Source height.
 * @throws ImageException
 *
 */
function imagecopy($dst_im, $src_im, int $dst_x, int $dst_y, int $src_x, int $src_y, int $src_w, int $src_h): void
{
    error_clear_last();
    $result = \imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Copy a part of src_im onto
 * dst_im starting at the x,y coordinates
 * src_x, src_y  with
 * a width of src_w and a height of
 * src_h.  The portion defined will be copied
 * onto the x,y coordinates, dst_x and
 * dst_y.
 *
 * @param resource $dst_im Destination image resource.
 * @param resource $src_im Source image resource.
 * @param int $dst_x x-coordinate of destination point.
 * @param int $dst_y y-coordinate of destination point.
 * @param int $src_x x-coordinate of source point.
 * @param int $src_y y-coordinate of source point.
 * @param int $src_w Source width.
 * @param int $src_h Source height.
 * @param int $pct The two images will be merged according to pct
 * which can range from 0 to 100.  When pct = 0,
 * no action is taken, when 100 this function behaves identically
 * to imagecopy for pallete images, except for
 * ignoring alpha components, while it implements alpha transparency
 * for true colour images.
 * @throws ImageException
 *
 */
function imagecopymerge($dst_im, $src_im, int $dst_x, int $dst_y, int $src_x, int $src_y, int $src_w, int $src_h, int $pct): void
{
    error_clear_last();
    $result = \imagecopymerge($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagecopymergegray copy a part of src_im onto
 * dst_im starting at the x,y coordinates
 * src_x, src_y  with
 * a width of src_w and a height of
 * src_h.  The portion defined will be copied
 * onto the x,y coordinates, dst_x and
 * dst_y.
 *
 * This function is identical to imagecopymerge except
 * that when merging it preserves the hue of the source by converting
 * the destination pixels to gray scale before the copy operation.
 *
 * @param resource $dst_im Destination image resource.
 * @param resource $src_im Source image resource.
 * @param int $dst_x x-coordinate of destination point.
 * @param int $dst_y y-coordinate of destination point.
 * @param int $src_x x-coordinate of source point.
 * @param int $src_y y-coordinate of source point.
 * @param int $src_w Source width.
 * @param int $src_h Source height.
 * @param int $pct The src_im will be changed to grayscale according
 * to pct where 0 is fully grayscale and 100 is
 * unchanged. When pct = 100 this function behaves
 * identically to imagecopy for pallete images, except for
 * ignoring alpha components, while
 * it implements alpha transparency for true colour images.
 * @throws ImageException
 *
 */
function imagecopymergegray($dst_im, $src_im, int $dst_x, int $dst_y, int $src_x, int $src_y, int $src_w, int $src_h, int $pct): void
{
    error_clear_last();
    $result = \imagecopymergegray($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagecopyresampled copies a rectangular
 * portion of one image to another image, smoothly interpolating pixel
 * values so that, in particular, reducing the size of an image still
 * retains a great deal of clarity.
 *
 * In other words, imagecopyresampled will take a
 * rectangular area from src_image of width
 * src_w and height src_h at
 * position (src_x,src_y)
 * and place it in a rectangular area of dst_image
 * of width dst_w and height dst_h
 * at position (dst_x,dst_y).
 *
 * If the source and destination coordinates and width and heights
 * differ, appropriate stretching or shrinking of the image fragment
 * will be performed. The coordinates refer to the upper left
 * corner.  This function can be used to copy regions within the
 * same image (if dst_image is the same as
 * src_image) but if the regions overlap the
 * results will be unpredictable.
 *
 * @param resource $dst_image Destination image resource.
 * @param resource $src_image Source image resource.
 * @param int $dst_x x-coordinate of destination point.
 * @param int $dst_y y-coordinate of destination point.
 * @param int $src_x x-coordinate of source point.
 * @param int $src_y y-coordinate of source point.
 * @param int $dst_w Destination width.
 * @param int $dst_h Destination height.
 * @param int $src_w Source width.
 * @param int $src_h Source height.
 * @throws ImageException
 *
 */
function imagecopyresampled($dst_image, $src_image, int $dst_x, int $dst_y, int $src_x, int $src_y, int $dst_w, int $dst_h, int $src_w, int $src_h): void
{
    error_clear_last();
    $result = \imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagecopyresized copies a rectangular
 * portion of one image to another image.
 * dst_image is the destination image,
 * src_image is the source image identifier.
 *
 * In other words, imagecopyresized will take a
 * rectangular area from src_image of width
 * src_w and height src_h at
 * position (src_x,src_y)
 * and place it in a rectangular area of dst_image
 * of width dst_w and height dst_h
 * at position (dst_x,dst_y).
 *
 * If the source and destination coordinates and width and heights
 * differ, appropriate stretching or shrinking of the image fragment
 * will be performed. The coordinates refer to the upper left
 * corner. This function can be used to copy regions within the
 * same image (if dst_image is the same as
 * src_image) but if the regions overlap the
 * results will be unpredictable.
 *
 * @param resource $dst_image Destination image resource.
 * @param resource $src_image Source image resource.
 * @param int $dst_x x-coordinate of destination point.
 * @param int $dst_y y-coordinate of destination point.
 * @param int $src_x x-coordinate of source point.
 * @param int $src_y y-coordinate of source point.
 * @param int $dst_w Destination width.
 * @param int $dst_h Destination height.
 * @param int $src_w Source width.
 * @param int $src_h Source height.
 * @throws ImageException
 *
 */
function imagecopyresized($dst_image, $src_image, int $dst_x, int $dst_y, int $src_x, int $src_y, int $dst_w, int $dst_h, int $src_w, int $src_h): void
{
    error_clear_last();
    $result = \imagecopyresized($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagecreate returns an image identifier
 * representing a blank image of specified size.
 *
 * In general, we recommend the use of
 * imagecreatetruecolor instead of
 * imagecreate so that image processing occurs on the
 * highest quality image possible. If you want to output a palette image, then
 * imagetruecolortopalette should be called immediately
 * before saving the image with imagepng or
 * imagegif.
 *
 * @param int $width The image width.
 * @param int $height The image height.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreate(int $width, int $height)
{
    error_clear_last();
    $result = \imagecreate($width, $height);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatefrombmp returns an image identifier
 * representing the image obtained from the given filename.
 *
 * @param string $filename Path to the BMP image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefrombmp(string $filename)
{
    error_clear_last();
    $result = \imagecreatefrombmp($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Create a new image from GD file or URL.
 *
 * @param string $filename Path to the GD file.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromgd(string $filename)
{
    error_clear_last();
    $result = \imagecreatefromgd($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Create a new image from GD2 file or URL.
 *
 * @param string $filename Path to the GD2 image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromgd2(string $filename)
{
    error_clear_last();
    $result = \imagecreatefromgd2($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Create a new image from a given part of GD2 file or URL.
 *
 * @param string $filename Path to the GD2 image.
 * @param int $srcX x-coordinate of source point.
 * @param int $srcY y-coordinate of source point.
 * @param int $width Source width.
 * @param int $height Source height.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromgd2part(string $filename, int $srcX, int $srcY, int $width, int $height)
{
    error_clear_last();
    $result = \imagecreatefromgd2part($filename, $srcX, $srcY, $width, $height);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatefromgif returns an image identifier
 * representing the image obtained from the given filename.
 *
 * @param string $filename Path to the GIF image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromgif(string $filename)
{
    error_clear_last();
    $result = \imagecreatefromgif($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatefromjpeg returns an image identifier
 * representing the image obtained from the given filename.
 *
 * @param string $filename Path to the JPEG image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromjpeg(string $filename)
{
    error_clear_last();
    $result = \imagecreatefromjpeg($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatefrompng returns an image identifier
 * representing the image obtained from the given filename.
 *
 * @param string $filename Path to the PNG image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefrompng(string $filename)
{
    error_clear_last();
    $result = \imagecreatefrompng($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatefromwbmp returns an image identifier
 * representing the image obtained from the given filename.
 *
 * @param string $filename Path to the WBMP image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromwbmp(string $filename)
{
    error_clear_last();
    $result = \imagecreatefromwbmp($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatefromwebp returns an image identifier
 * representing the image obtained from the given filename.
 *
 * @param string $filename Path to the WebP image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromwebp(string $filename)
{
    error_clear_last();
    $result = \imagecreatefromwebp($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatefromxbm returns an image identifier
 * representing the image obtained from the given filename.
 *
 * @param string $filename Path to the XBM image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromxbm(string $filename)
{
    error_clear_last();
    $result = \imagecreatefromxbm($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatefromxpm returns an image identifier
 * representing the image obtained from the given filename.
 *
 * @param string $filename Path to the XPM image.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatefromxpm(string $filename)
{
    error_clear_last();
    $result = \imagecreatefromxpm($filename);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagecreatetruecolor returns an image identifier
 * representing a black image of the specified size.
 *
 * @param int $width Image width.
 * @param int $height Image height.
 * @return resource Returns an image resource identifier on success, FALSE on errors.
 * @throws ImageException
 *
 */
function imagecreatetruecolor(int $width, int $height)
{
    error_clear_last();
    $result = \imagecreatetruecolor($width, $height);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Crops an image to the given rectangular area and returns the resulting image.
 * The given image is not modified.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param array $rect The cropping rectangle as array with keys
 * x, y, width and
 * height.
 * @return resource Return cropped image resource on success.
 * @throws ImageException
 *
 */
function imagecrop($image, array $rect)
{
    error_clear_last();
    $result = \imagecrop($image, $rect);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Automatically crops an image according to the given
 * mode.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $mode One of the following constants:
 * @param float $threshold
 * @param int $color
 * @return resource Returns a cropped image resource on success.
 * If the complete image was cropped, imagecrop returns FALSE.
 * @throws ImageException
 *
 */
function imagecropauto($image, int $mode = IMG_CROP_DEFAULT, float $threshold = .5, int $color = -1)
{
    error_clear_last();
    $result = \imagecropauto($image, $mode, $threshold, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * This function is deprecated. Use combination of
 * imagesetstyle and imageline
 * instead.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x1 Upper left x coordinate.
 * @param int $y1 Upper left y coordinate 0, 0 is the top left corner of the image.
 * @param int $x2 Bottom right x coordinate.
 * @param int $y2 Bottom right y coordinate.
 * @param int $color The fill color. A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagedashedline($image, int $x1, int $y1, int $x2, int $y2, int $color): void
{
    error_clear_last();
    $result = \imagedashedline($image, $x1, $y1, $x2, $y2, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagedestroy frees any memory associated
 * with image image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @throws ImageException
 *
 */
function imagedestroy($image): void
{
    error_clear_last();
    $result = \imagedestroy($image);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Draws an ellipse centered at the specified coordinates.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $cx x-coordinate of the center.
 * @param int $cy y-coordinate of the center.
 * @param int $width The ellipse width.
 * @param int $height The ellipse height.
 * @param int $color The color of the ellipse. A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imageellipse($image, int $cx, int $cy, int $width, int $height, int $color): void
{
    error_clear_last();
    $result = \imageellipse($image, $cx, $cy, $width, $height, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Performs a flood fill starting at the given coordinate (top left is 0, 0)
 * with the given color in the
 * image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x x-coordinate of start point.
 * @param int $y y-coordinate of start point.
 * @param int $color The fill color. A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagefill($image, int $x, int $y, int $color): void
{
    error_clear_last();
    $result = \imagefill($image, $x, $y, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Draws a partial arc centered at the specified coordinate in the
 * given image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $cx x-coordinate of the center.
 * @param int $cy y-coordinate of the center.
 * @param int $width The arc width.
 * @param int $height The arc height.
 * @param int $start The arc start angle, in degrees.
 * @param int $end The arc end angle, in degrees.
 * 0° is located at the three-o'clock position, and the arc is drawn
 * clockwise.
 * @param int $color A color identifier created with imagecolorallocate.
 * @param int $style A bitwise OR of the following possibilities:
 *
 * IMG_ARC_PIE
 * IMG_ARC_CHORD
 * IMG_ARC_NOFILL
 * IMG_ARC_EDGED
 *
 * IMG_ARC_PIE and IMG_ARC_CHORD are
 * mutually exclusive; IMG_ARC_CHORD just
 * connects the starting and ending angles with a straight line, while
 * IMG_ARC_PIE produces a rounded edge.
 * IMG_ARC_NOFILL indicates that the arc
 * or chord should be outlined, not filled.  IMG_ARC_EDGED,
 * used together with IMG_ARC_NOFILL, indicates that the
 * beginning and ending angles should be connected to the center - this is a
 * good way to outline (rather than fill) a 'pie slice'.
 * @throws ImageException
 *
 */
function imagefilledarc($image, int $cx, int $cy, int $width, int $height, int $start, int $end, int $color, int $style): void
{
    error_clear_last();
    $result = \imagefilledarc($image, $cx, $cy, $width, $height, $start, $end, $color, $style);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Draws an ellipse centered at the specified coordinate on the given
 * image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $cx x-coordinate of the center.
 * @param int $cy y-coordinate of the center.
 * @param int $width The ellipse width.
 * @param int $height The ellipse height.
 * @param int $color The fill color. A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagefilledellipse($image, int $cx, int $cy, int $width, int $height, int $color): void
{
    error_clear_last();
    $result = \imagefilledellipse($image, $cx, $cy, $width, $height, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagefilledpolygon creates a filled polygon
 * in the given image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param array $points An array containing the x and y
 * coordinates of the polygons vertices consecutively.
 * @param int $num_points Total number of points (vertices), which must be at least 3.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagefilledpolygon($image, array $points, int $num_points, int $color): void
{
    error_clear_last();
    $result = \imagefilledpolygon($image, $points, $num_points, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Creates a rectangle filled with color in the given
 * image starting at point 1 and ending at point 2.
 * 0, 0 is the top left corner of the image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x1 x-coordinate for point 1.
 * @param int $y1 y-coordinate for point 1.
 * @param int $x2 x-coordinate for point 2.
 * @param int $y2 y-coordinate for point 2.
 * @param int $color The fill color. A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagefilledrectangle($image, int $x1, int $y1, int $x2, int $y2, int $color): void
{
    error_clear_last();
    $result = \imagefilledrectangle($image, $x1, $y1, $x2, $y2, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagefilltoborder performs a flood fill
 * whose border color is defined by border.
 * The starting point for the fill is x,
 * y (top left is 0, 0) and the region is
 * filled with color color.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x x-coordinate of start.
 * @param int $y y-coordinate of start.
 * @param int $border The border color. A color identifier created with imagecolorallocate.
 * @param int $color The fill color. A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagefilltoborder($image, int $x, int $y, int $border, int $color): void
{
    error_clear_last();
    $result = \imagefilltoborder($image, $x, $y, $border, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagefilter applies the given filter
 * filtertype on the image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $filtertype filtertype can be one of the following:
 *
 *
 *
 * IMG_FILTER_NEGATE: Reverses all colors of
 * the image.
 *
 *
 *
 *
 * IMG_FILTER_GRAYSCALE: Converts the image into
 * grayscale by changing the red, green and blue components to their
 * weighted sum using the same coefficients as the REC.601 luma (Y')
 * calculation. The alpha components are retained. For palette images the
 * result may differ due to palette limitations.
 *
 *
 *
 *
 * IMG_FILTER_BRIGHTNESS: Changes the brightness
 * of the image. Use arg1 to set the level of
 * brightness. The range for the brightness is -255 to 255.
 *
 *
 *
 *
 * IMG_FILTER_CONTRAST: Changes the contrast of
 * the image. Use arg1 to set the level of
 * contrast.
 *
 *
 *
 *
 * IMG_FILTER_COLORIZE: Like
 * IMG_FILTER_GRAYSCALE, except you can specify the
 * color. Use arg1, arg2 and
 * arg3 in the form of
 * red, green,
 * blue and arg4 for the
 * alpha channel. The range for each color is 0 to 255.
 *
 *
 *
 *
 * IMG_FILTER_EDGEDETECT: Uses edge detection to
 * highlight the edges in the image.
 *
 *
 *
 *
 * IMG_FILTER_EMBOSS: Embosses the image.
 *
 *
 *
 *
 * IMG_FILTER_GAUSSIAN_BLUR: Blurs the image using
 * the Gaussian method.
 *
 *
 *
 *
 * IMG_FILTER_SELECTIVE_BLUR: Blurs the image.
 *
 *
 *
 *
 * IMG_FILTER_MEAN_REMOVAL: Uses mean removal to
 * achieve a "sketchy" effect.
 *
 *
 *
 *
 * IMG_FILTER_SMOOTH: Makes the image smoother.
 * Use arg1 to set the level of smoothness.
 *
 *
 *
 *
 * IMG_FILTER_PIXELATE: Applies pixelation effect
 * to the image, use arg1 to set the block size
 * and arg2 to set the pixelation effect mode.
 *
 *
 *
 *
 * IMG_FILTER_SCATTER: Applies scatter effect
 * to the image, use arg1 and
 * arg2 to define the effect strength and
 * additionally arg3 to only apply the
 * on select pixel colors.
 *
 *
 *
 * @param int $arg1
 *
 *
 * IMG_FILTER_BRIGHTNESS: Brightness level.
 *
 *
 *
 *
 * IMG_FILTER_CONTRAST: Contrast level.
 *
 *
 *
 *
 * IMG_FILTER_COLORIZE: Value of red component.
 *
 *
 *
 *
 * IMG_FILTER_SMOOTH: Smoothness level.
 *
 *
 *
 *
 * IMG_FILTER_PIXELATE: Block size in pixels.
 *
 *
 *
 *
 * IMG_FILTER_SCATTER: Effect substraction level.
 * This must not be higher or equal to the addition level set with
 * arg2.
 *
 *
 *
 * @param int $arg2
 *
 *
 * IMG_FILTER_COLORIZE: Value of green component.
 *
 *
 *
 *
 * IMG_FILTER_PIXELATE: Whether to use advanced pixelation
 * effect or not (defaults to FALSE).
 *
 *
 *
 *
 * IMG_FILTER_SCATTER: Effect addition level.
 *
 *
 *
 * @param int $arg3
 *
 *
 * IMG_FILTER_COLORIZE: Value of blue component.
 *
 *
 *
 *
 * IMG_FILTER_SCATTER: Optional array indexed color values
 * to apply effect at.
 *
 *
 *
 * @param int $arg4
 *
 *
 * IMG_FILTER_COLORIZE: Alpha channel, A value
 * between 0 and 127. 0 indicates completely opaque while 127 indicates
 * completely transparent.
 *
 *
 *
 * @throws ImageException
 *
 */
function imagefilter($image, int $filtertype, int $arg1 = null, int $arg2 = null, int $arg3 = null, int $arg4 = null): void
{
    error_clear_last();
    if ($arg4 !== null) {
        $result = \imagefilter($image, $filtertype, $arg1, $arg2, $arg3, $arg4);
    } elseif ($arg3 !== null) {
        $result = \imagefilter($image, $filtertype, $arg1, $arg2, $arg3);
    } elseif ($arg2 !== null) {
        $result = \imagefilter($image, $filtertype, $arg1, $arg2);
    } elseif ($arg1 !== null) {
        $result = \imagefilter($image, $filtertype, $arg1);
    } else {
        $result = \imagefilter($image, $filtertype);
    }
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Flips the image image using the given
 * mode.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $mode Flip mode, this can be one of the IMG_FLIP_* constants:
 *
 *
 *
 *
 *
 * Constant
 * Meaning
 *
 *
 *
 *
 * IMG_FLIP_HORIZONTAL
 *
 * Flips the image horizontally.
 *
 *
 *
 * IMG_FLIP_VERTICAL
 *
 * Flips the image vertically.
 *
 *
 *
 * IMG_FLIP_BOTH
 *
 * Flips the image both horizontally and vertically.
 *
 *
 *
 *
 *
 * @throws ImageException
 *
 */
function imageflip($image, int $mode): void
{
    error_clear_last();
    $result = \imageflip($image, $mode);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Applies gamma correction to the given gd image
 * given an input and an output gamma.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param float $inputgamma The input gamma.
 * @param float $outputgamma The output gamma.
 * @throws ImageException
 *
 */
function imagegammacorrect($image, float $inputgamma, float $outputgamma): void
{
    error_clear_last();
    $result = \imagegammacorrect($image, $inputgamma, $outputgamma);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Outputs a GD image to the given to.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param mixed $to The path or an open stream resource (which is automatically being closed after this function returns) to save the file to. If not set or NULL, the raw image stream will be outputted directly.
 * @throws ImageException
 *
 */
function imagegd($image, $to = null): void
{
    error_clear_last();
    $result = \imagegd($image, $to);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Outputs a GD2 image to the given to.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param mixed $to The path or an open stream resource (which is automatically being closed after this function returns) to save the file to. If not set or NULL, the raw image stream will be outputted directly.
 * @param int $chunk_size Chunk size.
 * @param int $type Either IMG_GD2_RAW or
 * IMG_GD2_COMPRESSED. Default is
 * IMG_GD2_RAW.
 * @throws ImageException
 *
 */
function imagegd2($image, $to = null, int $chunk_size = 128, int $type = IMG_GD2_RAW): void
{
    error_clear_last();
    $result = \imagegd2($image, $to, $chunk_size, $type);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagegif creates the GIF
 * file in to from the image image. The
 * image argument is the return from the
 * imagecreate or imagecreatefrom*
 * function.
 *
 * The image format will be GIF87a unless the
 * image has been made transparent with
 * imagecolortransparent, in which case the
 * image format will be GIF89a.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param mixed $to The path or an open stream resource (which is automatically being closed after this function returns) to save the file to. If not set or NULL, the raw image stream will be outputted directly.
 * @throws ImageException
 *
 */
function imagegif($image, $to = null): void
{
    error_clear_last();
    $result = \imagegif($image, $to);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Grabs a screenshot of the whole screen.
 *
 * @return resource Returns an image resource identifier on success, FALSE on failure.
 * @throws ImageException
 *
 */
function imagegrabscreen()
{
    error_clear_last();
    $result = \imagegrabscreen();
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Grabs a window or its client area using a windows handle (HWND property in COM instance)
 *
 * @param int $window_handle The HWND window ID.
 * @param int $client_area Include the client area of the application window.
 * @return resource Returns an image resource identifier on success, FALSE on failure.
 * @throws ImageException
 *
 */
function imagegrabwindow(int $window_handle, int $client_area = 0)
{
    error_clear_last();
    $result = \imagegrabwindow($window_handle, $client_area);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagejpeg creates a JPEG file from
 * the given image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param mixed $to The path or an open stream resource (which is automatically being closed after this function returns) to save the file to. If not set or NULL, the raw image stream will be outputted directly.
 * @param int $quality quality is optional, and ranges from 0 (worst
 * quality, smaller file) to 100 (best quality, biggest file). The
 * default (-1) uses the default IJG quality value (about 75).
 * @throws ImageException
 *
 */
function imagejpeg($image, $to = null, int $quality = -1): void
{
    error_clear_last();
    $result = \imagejpeg($image, $to, $quality);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Set the alpha blending flag to use layering effects.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $effect One of the following constants:
 *
 *
 * IMG_EFFECT_REPLACE
 *
 *
 * Use pixel replacement (equivalent of passing TRUE to
 * imagealphablending)
 *
 *
 *
 *
 * IMG_EFFECT_ALPHABLEND
 *
 *
 * Use normal pixel blending (equivalent of passing FALSE to
 * imagealphablending)
 *
 *
 *
 *
 * IMG_EFFECT_NORMAL
 *
 *
 * Same as IMG_EFFECT_ALPHABLEND.
 *
 *
 *
 *
 * IMG_EFFECT_OVERLAY
 *
 *
 * Overlay has the effect that black background pixels will remain
 * black, white background pixels will remain white, but grey
 * background pixels will take the colour of the foreground pixel.
 *
 *
 *
 *
 * IMG_EFFECT_MULTIPLY
 *
 *
 * Overlays with a multiply effect.
 *
 *
 *
 *
 * @throws ImageException
 *
 */
function imagelayereffect($image, int $effect): void
{
    error_clear_last();
    $result = \imagelayereffect($image, $effect);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Draws a line between the two given points.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x1 x-coordinate for first point.
 * @param int $y1 y-coordinate for first point.
 * @param int $x2 x-coordinate for second point.
 * @param int $y2 y-coordinate for second point.
 * @param int $color The line color. A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imageline($image, int $x1, int $y1, int $x2, int $y2, int $color): void
{
    error_clear_last();
    $result = \imageline($image, $x1, $y1, $x2, $y2, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imageloadfont loads a user-defined bitmap and returns
 * its identifier.
 *
 * @param string $file The font file format is currently binary and architecture
 * dependent.  This means you should generate the font files on the
 * same type of CPU as the machine you are running PHP on.
 *
 *
 * Font file format
 *
 *
 *
 * byte position
 * C data type
 * description
 *
 *
 *
 *
 * byte 0-3
 * int
 * number of characters in the font
 *
 *
 * byte 4-7
 * int
 *
 * value of first character in the font (often 32 for space)
 *
 *
 *
 * byte 8-11
 * int
 * pixel width of each character
 *
 *
 * byte 12-15
 * int
 * pixel height of each character
 *
 *
 * byte 16-
 * char
 *
 * array with character data, one byte per pixel in each
 * character, for a total of (nchars*width*height) bytes.
 *
 *
 *
 *
 *
 * @return int The font identifier which is always bigger than 5 to avoid conflicts with
 * built-in fontss.
 * @throws ImageException
 *
 */
function imageloadfont(string $file): int
{
    error_clear_last();
    $result = \imageloadfont($file);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imageopenpolygon draws an open polygon on the given
 * image. Contrary to imagepolygon,
 * no line is drawn between the last and the first point.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param array $points An array containing the polygon's vertices, e.g.:
 *
 *
 *
 *
 * points[0]
 * = x0
 *
 *
 * points[1]
 * = y0
 *
 *
 * points[2]
 * = x1
 *
 *
 * points[3]
 * = y1
 *
 *
 *
 *
 * @param int $num_points Total number of points (vertices), which must be at least 3.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imageopenpolygon($image, array $points, int $num_points, int $color): void
{
    error_clear_last();
    $result = \imageopenpolygon($image, $points, $num_points, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Outputs or saves a PNG image from the given
 * image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param mixed $to The path or an open stream resource (which is automatically being closed after this function returns) to save the file to. If not set or NULL, the raw image stream will be outputted directly.
 *
 * NULL is invalid if the quality and
 * filters arguments are not used.
 * @param int $quality Compression level: from 0 (no compression) to 9.
 * The default (-1) uses the zlib compression default.
 * For more information see the zlib manual.
 * @param int $filters Allows reducing the PNG file size. It is a bitmask field which may be
 * set to any combination of the PNG_FILTER_XXX
 * constants. PNG_NO_FILTER or
 * PNG_ALL_FILTERS may also be used to respectively
 * disable or activate all filters.
 * The default value (-1) disables filtering.
 * @throws ImageException
 *
 */
function imagepng($image, $to = null, int $quality = -1, int $filters = -1): void
{
    error_clear_last();
    $result = \imagepng($image, $to, $quality, $filters);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagepolygon creates a polygon in the given
 * image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param array $points An array containing the polygon's vertices, e.g.:
 *
 *
 *
 *
 * points[0]
 * = x0
 *
 *
 * points[1]
 * = y0
 *
 *
 * points[2]
 * = x1
 *
 *
 * points[3]
 * = y1
 *
 *
 *
 *
 * @param int $num_points Total number of points (vertices), which must be at least 3.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagepolygon($image, array $points, int $num_points, int $color): void
{
    error_clear_last();
    $result = \imagepolygon($image, $points, $num_points, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagerectangle creates a rectangle starting at
 * the specified coordinates.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x1 Upper left x coordinate.
 * @param int $y1 Upper left y coordinate
 * 0, 0 is the top left corner of the image.
 * @param int $x2 Bottom right x coordinate.
 * @param int $y2 Bottom right y coordinate.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagerectangle($image, int $x1, int $y1, int $x2, int $y2, int $color): void
{
    error_clear_last();
    $result = \imagerectangle($image, $x1, $y1, $x2, $y2, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Rotates the image image using the given
 * angle in degrees.
 *
 * The center of rotation is the center of the image, and the rotated
 * image may have different dimensions than the original image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param float $angle Rotation angle, in degrees. The rotation angle is interpreted as the
 * number of degrees to rotate the image anticlockwise.
 * @param int $bgd_color Specifies the color of the uncovered zone after the rotation
 * @param int $dummy This parameter is unused.
 * @return resource Returns an image resource for the rotated image.
 * @throws ImageException
 *
 */
function imagerotate($image, float $angle, int $bgd_color, int $dummy = 0)
{
    error_clear_last();
    $result = \imagerotate($image, $angle, $bgd_color, $dummy);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagesavealpha sets the flag which determines whether to retain
 * full alpha channel information (as opposed to single-color transparency)
 * when saving PNG images.
 *
 * Alphablending has to be disabled (imagealphablending($im, false))
 * to retain the alpha-channel in the first place.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param bool $saveflag Whether to save the alpha channel or not. Defaults to FALSE.
 * @throws ImageException
 *
 */
function imagesavealpha($image, bool $saveflag): void
{
    error_clear_last();
    $result = \imagesavealpha($image, $saveflag);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagescale scales an image using the given
 * interpolation algorithm.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $new_width The width to scale the image to.
 * @param int $new_height The height to scale the image to. If omitted or negative, the aspect
 * ratio will be preserved.
 * @param int $mode One of IMG_NEAREST_NEIGHBOUR,
 * IMG_BILINEAR_FIXED,
 * IMG_BICUBIC,
 * IMG_BICUBIC_FIXED or anything else (will use two
 * pass).
 *
 *
 * IMG_WEIGHTED4 is not yet supported.
 *
 *
 * @return resource Return the scaled image resource on success.
 * @throws ImageException
 *
 */
function imagescale($image, int $new_width, int $new_height = -1, int $mode = IMG_BILINEAR_FIXED)
{
    error_clear_last();
    $result = \imagescale($image, $new_width, $new_height, $mode);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagesetbrush sets the brush image to be
 * used by all line drawing functions (such as imageline
 * and imagepolygon) when drawing with the special
 * colors IMG_COLOR_BRUSHED or
 * IMG_COLOR_STYLEDBRUSHED.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param resource $brush An image resource.
 * @throws ImageException
 *
 */
function imagesetbrush($image, $brush): void
{
    error_clear_last();
    $result = \imagesetbrush($image, $brush);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagesetclip sets the current clipping rectangle, i.e.
 * the area beyond which no pixels will be drawn.
 *
 * @param resource $im An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x1 The x-coordinate of the upper left corner.
 * @param int $y1 The y-coordinate of the upper left corner.
 * @param int $x2 The x-coordinate of the lower right corner.
 * @param int $y2 The y-coordinate of the lower right corner.
 * @throws ImageException
 *
 */
function imagesetclip($im, int $x1, int $y1, int $x2, int $y2): void
{
    error_clear_last();
    $result = \imagesetclip($im, $x1, $y1, $x2, $y2);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Sets the interpolation method, setting an interpolation method affects the rendering
 * of various functions in GD, such as the imagerotate function.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $method The interpolation method, which can be one of the following:
 *
 *
 *
 * IMG_BELL: Bell filter.
 *
 *
 *
 *
 * IMG_BESSEL: Bessel filter.
 *
 *
 *
 *
 * IMG_BICUBIC: Bicubic interpolation.
 *
 *
 *
 *
 * IMG_BICUBIC_FIXED: Fixed point implementation of the bicubic interpolation.
 *
 *
 *
 *
 * IMG_BILINEAR_FIXED: Fixed point implementation of the  bilinear interpolation (default (also on image creation)).
 *
 *
 *
 *
 * IMG_BLACKMAN: Blackman window function.
 *
 *
 *
 *
 * IMG_BOX: Box blur filter.
 *
 *
 *
 *
 * IMG_BSPLINE: Spline interpolation.
 *
 *
 *
 *
 * IMG_CATMULLROM: Cubic Hermite spline interpolation.
 *
 *
 *
 *
 * IMG_GAUSSIAN: Gaussian function.
 *
 *
 *
 *
 * IMG_GENERALIZED_CUBIC: Generalized cubic spline fractal interpolation.
 *
 *
 *
 *
 * IMG_HERMITE: Hermite interpolation.
 *
 *
 *
 *
 * IMG_HAMMING: Hamming filter.
 *
 *
 *
 *
 * IMG_HANNING: Hanning filter.
 *
 *
 *
 *
 * IMG_MITCHELL: Mitchell filter.
 *
 *
 *
 *
 * IMG_POWER: Power interpolation.
 *
 *
 *
 *
 * IMG_QUADRATIC: Inverse quadratic interpolation.
 *
 *
 *
 *
 * IMG_SINC: Sinc function.
 *
 *
 *
 *
 * IMG_NEAREST_NEIGHBOUR: Nearest neighbour interpolation.
 *
 *
 *
 *
 * IMG_WEIGHTED4: Weighting filter.
 *
 *
 *
 *
 * IMG_TRIANGLE: Triangle interpolation.
 *
 *
 *
 * @throws ImageException
 *
 */
function imagesetinterpolation($image, int $method = IMG_BILINEAR_FIXED): void
{
    error_clear_last();
    $result = \imagesetinterpolation($image, $method);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagesetpixel draws a pixel at the specified
 * coordinate.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $x x-coordinate.
 * @param int $y y-coordinate.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagesetpixel($image, int $x, int $y, int $color): void
{
    error_clear_last();
    $result = \imagesetpixel($image, $x, $y, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagesetstyle sets the style to be used by all
 * line drawing functions (such as imageline
 * and imagepolygon) when drawing with the special
 * color IMG_COLOR_STYLED or lines of images with color
 * IMG_COLOR_STYLEDBRUSHED.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param array $style An array of pixel colors. You can use the
 * IMG_COLOR_TRANSPARENT constant to add a
 * transparent pixel.
 * Note that style must not be an empty array.
 * @throws ImageException
 *
 */
function imagesetstyle($image, array $style): void
{
    error_clear_last();
    $result = \imagesetstyle($image, $style);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagesetthickness sets the thickness of the lines
 * drawn when drawing rectangles, polygons, arcs etc. to
 * thickness pixels.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $thickness Thickness, in pixels.
 * @throws ImageException
 *
 */
function imagesetthickness($image, int $thickness): void
{
    error_clear_last();
    $result = \imagesetthickness($image, $thickness);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * imagesettile sets the tile image to be
 * used by all region filling functions (such as imagefill
 * and imagefilledpolygon) when filling with the special
 * color IMG_COLOR_TILED.
 *
 * A tile is an image used to fill an area with a repeated pattern. Any
 * GD image can be used as a tile, and by setting the transparent color index of the tile
 * image with imagecolortransparent, a tile allows certain parts
 * of the underlying area to shine through can be created.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param resource $tile The image resource to be used as a tile.
 * @throws ImageException
 *
 */
function imagesettile($image, $tile): void
{
    error_clear_last();
    $result = \imagesettile($image, $tile);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Draws a string at the given coordinates.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $font Can be 1, 2, 3, 4, 5 for built-in
 * fonts in latin2 encoding (where higher numbers corresponding to larger fonts) or any of your
 * own font identifiers registered with imageloadfont.
 * @param int $x x-coordinate of the upper left corner.
 * @param int $y y-coordinate of the upper left corner.
 * @param string $string The string to be written.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagestring($image, int $font, int $x, int $y, string $string, int $color): void
{
    error_clear_last();
    $result = \imagestring($image, $font, $x, $y, $string, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Draws a string vertically at the given
 * coordinates.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param int $font Can be 1, 2, 3, 4, 5 for built-in
 * fonts in latin2 encoding (where higher numbers corresponding to larger fonts) or any of your
 * own font identifiers registered with imageloadfont.
 * @param int $x x-coordinate of the bottom left corner.
 * @param int $y y-coordinate of the bottom left corner.
 * @param string $string The string to be written.
 * @param int $color A color identifier created with imagecolorallocate.
 * @throws ImageException
 *
 */
function imagestringup($image, int $font, int $x, int $y, string $string, int $color): void
{
    error_clear_last();
    $result = \imagestringup($image, $font, $x, $y, $string, $color);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Returns the width of the given image resource.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @return int Return the width of the images.
 * @throws ImageException
 *
 */
function imagesx($image): int
{
    error_clear_last();
    $result = \imagesx($image);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the height of the given image resource.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @return int Return the height of the images.
 * @throws ImageException
 *
 */
function imagesy($image): int
{
    error_clear_last();
    $result = \imagesy($image);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagetruecolortopalette converts a truecolor image
 * to a palette image. The code for this function was originally drawn from
 * the Independent JPEG Group library code, which is excellent. The code
 * has been modified to preserve as much alpha channel information as
 * possible in the resulting palette, in addition to preserving colors as
 * well as possible. This does not work as well as might be hoped. It is
 * usually best to simply produce a truecolor output image instead, which
 * guarantees the highest output quality.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param bool $dither Indicates if the image should be dithered - if it is TRUE then
 * dithering will be used which will result in a more speckled image but
 * with better color approximation.
 * @param int $ncolors Sets the maximum number of colors that should be retained in the palette.
 * @throws ImageException
 *
 */
function imagetruecolortopalette($image, bool $dither, int $ncolors): void
{
    error_clear_last();
    $result = \imagetruecolortopalette($image, $dither, $ncolors);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * This function calculates and returns the bounding box in pixels
 * for a TrueType text.
 *
 * @param float $size The font size in points.
 * @param float $angle Angle in degrees in which text will be measured.
 * @param string $fontfile The path to the TrueType font you wish to use.
 *
 * Depending on which version of the GD library PHP is using, when
 * fontfile does not begin with a leading
 * / then .ttf will be appended
 * to the filename and the library will attempt to search for that
 * filename along a library-defined font path.
 *
 * When using versions of the GD library lower than 2.0.18, a space character,
 * rather than a semicolon, was used as the 'path separator' for different font files.
 * Unintentional use of this feature will result in the warning message:
 * Warning: Could not find/open font. For these affected versions, the
 * only solution is moving the font to a path which does not contain spaces.
 *
 * In many cases where a font resides in the same directory as the script using it
 * the following trick will alleviate any include problems.
 *
 *
 * ]]>
 *
 *
 * Note that open_basedir does
 * not apply to fontfile.
 * @param string $text The string to be measured.
 * @return array imagettfbbox returns an array with 8
 * elements representing four points making the bounding box of the
 * text on success and FALSE on error.
 *
 *
 *
 *
 * key
 * contents
 *
 *
 *
 *
 * 0
 * lower left corner, X position
 *
 *
 * 1
 * lower left corner, Y position
 *
 *
 * 2
 * lower right corner, X position
 *
 *
 * 3
 * lower right corner, Y position
 *
 *
 * 4
 * upper right corner, X position
 *
 *
 * 5
 * upper right corner, Y position
 *
 *
 * 6
 * upper left corner, X position
 *
 *
 * 7
 * upper left corner, Y position
 *
 *
 *
 *
 *
 * The points are relative to the text regardless of the
 * angle, so "upper left" means in the top left-hand
 * corner seeing the text horizontally.
 * @throws ImageException
 *
 */
function imagettfbbox(float $size, float $angle, string $fontfile, string $text): array
{
    error_clear_last();
    $result = \imagettfbbox($size, $angle, $fontfile, $text);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Writes the given text into the image using TrueType
 * fonts.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param float $size The font size in points.
 * @param float $angle The angle in degrees, with 0 degrees being left-to-right reading text.
 * Higher values represent a counter-clockwise rotation. For example, a
 * value of 90 would result in bottom-to-top reading text.
 * @param int $x The coordinates given by x and
 * y will define the basepoint of the first
 * character (roughly the lower-left corner of the character). This
 * is different from the imagestring, where
 * x and y define the
 * upper-left corner of the first character. For example, "top left"
 * is 0, 0.
 * @param int $y The y-ordinate. This sets the position of the fonts baseline, not the
 * very bottom of the character.
 * @param int $color The color index. Using the negative of a color index has the effect of
 * turning off antialiasing. See imagecolorallocate.
 * @param string $fontfile The path to the TrueType font you wish to use.
 *
 * Depending on which version of the GD library PHP is using, when
 * fontfile does not begin with a leading
 * / then .ttf will be appended
 * to the filename and the library will attempt to search for that
 * filename along a library-defined font path.
 *
 * When using versions of the GD library lower than 2.0.18, a space character,
 * rather than a semicolon, was used as the 'path separator' for different font files.
 * Unintentional use of this feature will result in the warning message:
 * Warning: Could not find/open font. For these affected versions, the
 * only solution is moving the font to a path which does not contain spaces.
 *
 * In many cases where a font resides in the same directory as the script using it
 * the following trick will alleviate any include problems.
 *
 *
 * ]]>
 *
 *
 * Note that open_basedir does
 * not apply to fontfile.
 * @param string $text The text string in UTF-8 encoding.
 *
 * May include decimal numeric character references (of the form:
 * &amp;#8364;) to access characters in a font beyond position 127.
 * The hexadecimal format (like &amp;#xA9;) is supported.
 * Strings in UTF-8 encoding can be passed directly.
 *
 * Named entities, such as &amp;copy;, are not supported. Consider using
 * html_entity_decode
 * to decode these named entities into UTF-8 strings.
 *
 * If a character is used in the string which is not supported by the
 * font, a hollow rectangle will replace the character.
 * @return array Returns an array with 8 elements representing four points making the
 * bounding box of the text. The order of the points is lower left, lower
 * right, upper right, upper left. The points are relative to the text
 * regardless of the angle, so "upper left" means in the top left-hand
 * corner when you see the text horizontally.
 * @throws ImageException
 *
 */
function imagettftext($image, float $size, float $angle, int $x, int $y, int $color, string $fontfile, string $text): array
{
    error_clear_last();
    $result = \imagettftext($image, $size, $angle, $x, $y, $color, $fontfile, $text);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * imagewbmp outputs or save a WBMP
 * version of the given image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param mixed $to The path or an open stream resource (which is automatically being closed after this function returns) to save the file to. If not set or NULL, the raw image stream will be outputted directly.
 * @param int $foreground You can set the foreground color with this parameter by setting an
 * identifier obtained from imagecolorallocate.
 * The default foreground color is black.
 * @throws ImageException
 *
 */
function imagewbmp($image, $to = null, int $foreground = null): void
{
    error_clear_last();
    if ($foreground !== null) {
        $result = \imagewbmp($image, $to, $foreground);
    } else {
        $result = \imagewbmp($image, $to);
    }
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Outputs or saves a WebP version of the given image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param mixed $to The path or an open stream resource (which is automatically being closed after this function returns) to save the file to. If not set or NULL, the raw image stream will be outputted directly.
 * @param int $quality quality ranges from 0 (worst
 * quality, smaller file) to 100 (best quality, biggest file).
 * @throws ImageException
 *
 */
function imagewebp($image, $to = null, int $quality = 80): void
{
    error_clear_last();
    $result = \imagewebp($image, $to, $quality);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Outputs or save an XBM version of the given
 * image.
 *
 * @param resource $image An image resource, returned by one of the image creation functions,
 * such as imagecreatetruecolor.
 * @param string|null $filename The path to save the file to, given as string. If NULL, the raw image stream will be output directly.
 *
 * The filename (without the .xbm extension) is also
 * used for the C identifiers of the XBM, whereby non
 * alphanumeric characters of the current locale are substituted by
 * underscores. If filename is set to NULL,
 * image is used to build the C identifiers.
 * @param int $foreground You can set the foreground color with this parameter by setting an
 * identifier obtained from imagecolorallocate.
 * The default foreground color is black. All other colors are treated as
 * background.
 * @throws ImageException
 *
 */
function imagexbm($image, ?string $filename, int $foreground = null): void
{
    error_clear_last();
    if ($foreground !== null) {
        $result = \imagexbm($image, $filename, $foreground);
    } else {
        $result = \imagexbm($image, $filename);
    }
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Embeds binary IPTC data into a JPEG image.
 *
 * @param string $iptcdata The data to be written.
 * @param string $jpeg_file_name Path to the JPEG image.
 * @param int $spool Spool flag. If the spool flag is less than 2 then the JPEG will be
 * returned as a string. Otherwise the JPEG will be printed to STDOUT.
 * @return string|bool If spool is less than 2, the JPEG will be returned. Otherwise returns TRUE on success.
 * @throws ImageException
 *
 */
function iptcembed(string $iptcdata, string $jpeg_file_name, int $spool = 0)
{
    error_clear_last();
    $result = \iptcembed($iptcdata, $jpeg_file_name, $spool);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Parses an IPTC block into its single tags.
 *
 * @param string $iptcblock A binary IPTC block.
 * @return array Returns an array using the tagmarker as an index and the value as the
 * value. It returns FALSE on error or if no IPTC data was found.
 * @throws ImageException
 *
 */
function iptcparse(string $iptcblock): array
{
    error_clear_last();
    $result = \iptcparse($iptcblock);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
    return $result;
}


/**
 * Converts a JPEG file into a WBMP file.
 *
 * @param string $jpegname Path to JPEG file.
 * @param string $wbmpname Path to destination WBMP file.
 * @param int $dest_height Destination image height.
 * @param int $dest_width Destination image width.
 * @param int $threshold Threshold value, between 0 and 8 (inclusive).
 * @throws ImageException
 *
 */
function jpeg2wbmp(string $jpegname, string $wbmpname, int $dest_height, int $dest_width, int $threshold): void
{
    error_clear_last();
    $result = \jpeg2wbmp($jpegname, $wbmpname, $dest_height, $dest_width, $threshold);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}


/**
 * Converts a PNG file into a WBMP file.
 *
 * @param string $pngname Path to PNG file.
 * @param string $wbmpname Path to destination WBMP file.
 * @param int $dest_height Destination image height.
 * @param int $dest_width Destination image width.
 * @param int $threshold Threshold value, between 0 and 8 (inclusive).
 * @throws ImageException
 *
 */
function png2wbmp(string $pngname, string $wbmpname, int $dest_height, int $dest_width, int $threshold): void
{
    error_clear_last();
    $result = \png2wbmp($pngname, $wbmpname, $dest_height, $dest_width, $threshold);
    if ($result === false) {
        throw ImageException::createFromPhpError();
    }
}
