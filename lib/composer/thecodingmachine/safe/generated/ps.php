<?php

namespace Safe;

use Safe\Exceptions\PsException;

/**
 * Places a hyperlink at the given position pointing to a file program
 * which is being started when clicked on. The hyperlink's source position
 * is a rectangle
 * with its lower left corner at (llx, lly) and its upper right corner at
 * (urx, ury). The rectangle has by default a thin blue border.
 *
 * The note will not be visible if the document
 * is printed or viewed but it will show up if the document is converted to
 * pdf by either Acrobat Distiller™ or Ghostview.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $llx The x-coordinate of the lower left corner.
 * @param float $lly The y-coordinate of the lower left corner.
 * @param float $urx The x-coordinate of the upper right corner.
 * @param float $ury The y-coordinate of the upper right corner.
 * @param string $filename The path of the program to be started, when the link is clicked on.
 * @throws PsException
 *
 */
function ps_add_launchlink($psdoc, float $llx, float $lly, float $urx, float $ury, string $filename): void
{
    error_clear_last();
    $result = \ps_add_launchlink($psdoc, $llx, $lly, $urx, $ury, $filename);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Places a hyperlink at the given position pointing to a page in the same
 * document. Clicking on the link will jump to the given page. The first page
 * in a document has number 1.
 *
 * The hyperlink's source position is a rectangle with its lower left corner at
 * (llx, lly) and its upper
 * right corner at (urx, ury).
 * The rectangle has by default a thin blue border.
 *
 * The note will not be visible if the document
 * is printed or viewed but it will show up if the document is converted to
 * pdf by either Acrobat Distiller™ or Ghostview.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $llx The x-coordinate of the lower left corner.
 * @param float $lly The y-coordinate of the lower left corner.
 * @param float $urx The x-coordinate of the upper right corner.
 * @param float $ury The y-coordinate of the upper right corner.
 * @param int $page The number of the page displayed when clicking on the link.
 * @param string $dest The parameter dest determines how the document
 * is being viewed. It can be fitpage,
 * fitwidth, fitheight, or
 * fitbbox.
 * @throws PsException
 *
 */
function ps_add_locallink($psdoc, float $llx, float $lly, float $urx, float $ury, int $page, string $dest): void
{
    error_clear_last();
    $result = \ps_add_locallink($psdoc, $llx, $lly, $urx, $ury, $page, $dest);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Adds a note at a certain position on the page. Notes are like little
 * rectangular sheets with text on it, which can be placed anywhere on
 * a page. They
 * are shown either folded or unfolded. If folded, the specified icon
 * is used as a placeholder.
 *
 * The note will not be visible if the document
 * is printed or viewed but it will show up if the document is converted to
 * pdf by either Acrobat Distiller™ or Ghostview.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $llx The x-coordinate of the lower left corner.
 * @param float $lly The y-coordinate of the lower left corner.
 * @param float $urx The x-coordinate of the upper right corner.
 * @param float $ury The y-coordinate of the upper right corner.
 * @param string $contents The text of the note.
 * @param string $title The title of the note as displayed in the header of the note.
 * @param string $icon The icon shown if the note is folded. This parameter can be set
 * to comment, insert,
 * note, paragraph,
 * newparagraph, key, or
 * help.
 * @param int $open If open is unequal to zero the note will
 * be shown unfolded after opening the document with a pdf viewer.
 * @throws PsException
 *
 */
function ps_add_note($psdoc, float $llx, float $lly, float $urx, float $ury, string $contents, string $title, string $icon, int $open): void
{
    error_clear_last();
    $result = \ps_add_note($psdoc, $llx, $lly, $urx, $ury, $contents, $title, $icon, $open);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Places a hyperlink at the given position pointing to a second pdf document.
 * Clicking on the link will branch to the document at the given page. The
 * first page in a document has number 1.
 *
 * The hyperlink's source position is a rectangle with its lower left corner at
 * (llx, lly) and its upper
 * right corner at (urx, ury).
 * The rectangle has by default a thin blue border.
 *
 * The note will not be visible if the document
 * is printed or viewed but it will show up if the document is converted to
 * pdf by either Acrobat Distiller™ or Ghostview.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $llx The x-coordinate of the lower left corner.
 * @param float $lly The y-coordinate of the lower left corner.
 * @param float $urx The x-coordinate of the upper right corner.
 * @param float $ury The y-coordinate of the upper right corner.
 * @param string $filename The name of the pdf document to be opened when clicking on
 * this link.
 * @param int $page The page number of the destination pdf document
 * @param string $dest The parameter dest determines how the document
 * is being viewed. It can be fitpage,
 * fitwidth, fitheight, or
 * fitbbox.
 * @throws PsException
 *
 */
function ps_add_pdflink($psdoc, float $llx, float $lly, float $urx, float $ury, string $filename, int $page, string $dest): void
{
    error_clear_last();
    $result = \ps_add_pdflink($psdoc, $llx, $lly, $urx, $ury, $filename, $page, $dest);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Places a hyperlink at the given position pointing to a web page. The
 * hyperlink's source position is a rectangle with its lower left corner at
 * (llx, lly) and
 * its upper right corner at (urx,
 * ury). The rectangle has by default a thin
 * blue border.
 *
 * The note will not be visible if the document
 * is printed or viewed but it will show up if the document is converted to
 * pdf by either Acrobat Distiller™ or Ghostview.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $llx The x-coordinate of the lower left corner.
 * @param float $lly The y-coordinate of the lower left corner.
 * @param float $urx The x-coordinate of the upper right corner.
 * @param float $ury The y-coordinate of the upper right corner.
 * @param string $url The url of the hyperlink to be opened when clicking on
 * this link, e.g. http://www.php.net.
 * @throws PsException
 *
 */
function ps_add_weblink($psdoc, float $llx, float $lly, float $urx, float $ury, string $url): void
{
    error_clear_last();
    $result = \ps_add_weblink($psdoc, $llx, $lly, $urx, $ury, $url);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Draws a portion of a circle with at middle point at
 * (x, y). The arc starts at an
 * angle of alpha and ends at an angle of
 * beta. It is drawn counterclockwise (use
 * ps_arcn to draw clockwise). The subpath added
 * to the current path starts on the arc at angle alpha
 * and ends on the arc at angle beta.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x The x-coordinate of the circle's middle point.
 * @param float $y The y-coordinate of the circle's middle point.
 * @param float $radius The radius of the circle
 * @param float $alpha The start angle given in degrees.
 * @param float $beta The end angle given in degrees.
 * @throws PsException
 *
 */
function ps_arc($psdoc, float $x, float $y, float $radius, float $alpha, float $beta): void
{
    error_clear_last();
    $result = \ps_arc($psdoc, $x, $y, $radius, $alpha, $beta);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Draws a portion of a circle with at middle point at
 * (x, y). The arc starts at an
 * angle of alpha and ends at an angle of
 * beta. It is drawn clockwise (use
 * ps_arc to draw counterclockwise). The subpath added to
 * the current path starts on the arc at angle beta and
 * ends on the arc at angle alpha.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x The x-coordinate of the circle's middle point.
 * @param float $y The y-coordinate of the circle's middle point.
 * @param float $radius The radius of the circle
 * @param float $alpha The starting angle given in degrees.
 * @param float $beta The end angle given in degrees.
 * @throws PsException
 *
 */
function ps_arcn($psdoc, float $x, float $y, float $radius, float $alpha, float $beta): void
{
    error_clear_last();
    $result = \ps_arcn($psdoc, $x, $y, $radius, $alpha, $beta);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Starts a new page. Although the parameters width
 * and height imply a different page size for each
 * page, this is not possible in PostScript. The first call of
 * ps_begin_page will set the page size for the whole
 * document. Consecutive calls will have no effect, except for creating a new
 * page. The situation is different if you intent to convert the PostScript
 * document into PDF. This function places pdfmarks into the document which
 * can set the size for each page indiviually. The resulting PDF document will
 * have different page sizes.
 *
 * Though PostScript does not know different page sizes, pslib places
 * a bounding box for each page into the document. This size is evaluated
 * by some PostScript viewers and will have precedence over the BoundingBox
 * in the Header of the document. This can lead to unexpected results when
 * you set a BoundingBox whose lower left corner is not (0, 0), because the
 * bounding box of the page will always have a lower left corner (0, 0)
 * and overwrites the global setting.
 *
 * Each page is encapsulated into save/restore. This means, that most of the
 * settings made on one page will not be retained on the next page.
 *
 * If there is up to the first call of ps_begin_page no
 * call of ps_findfont, then the header of the PostScript
 * document will be output and the bounding box will be set to the size of
 * the first page. The lower left corner of the bounding box is set to (0, 0).
 * If ps_findfont was called before, then the
 * header has been output already, and the document will not have a valid
 * bounding box. In order to prevent this, one should call
 * ps_set_info to set the info field
 * BoundingBox and possibly Orientation
 * before any ps_findfont or
 * ps_begin_page calls.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $width The width of the page in pixel, e.g. 596 for A4 format.
 * @param float $height The height of the page in pixel, e.g. 842 for A4 format.
 * @throws PsException
 *
 */
function ps_begin_page($psdoc, float $width, float $height): void
{
    error_clear_last();
    $result = \ps_begin_page($psdoc, $width, $height);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Starts a new pattern. A pattern is like a page containing e.g. a drawing
 * which can be used for filling areas. It is used like a color by calling
 * ps_setcolor and setting the color space to
 * pattern.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $width The width of the pattern in pixel.
 * @param float $height The height of the pattern in pixel.
 * @param float $xstep The distance in pixel of placements of the pattern in
 * horizontal direction.
 * @param float $ystep The distance in pixel of placements of the pattern in
 * vertical direction.
 * @param int $painttype Must be 1 or 2.
 * @return int The identifier of the pattern.
 * @throws PsException
 *
 */
function ps_begin_pattern($psdoc, float $width, float $height, float $xstep, float $ystep, int $painttype): int
{
    error_clear_last();
    $result = \ps_begin_pattern($psdoc, $width, $height, $xstep, $ystep, $painttype);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
    return $result;
}


/**
 * Starts a new template. A template is called a form in the postscript
 * language. It is created similar to a pattern but used like an image.
 * Templates are often used for drawings which are placed several times
 * through out the document, e.g. like a company logo. All drawing functions
 * may be used within a template. The template will not be drawn until
 * it is placed by ps_place_image.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $width The width of the template in pixel.
 * @param float $height The height of the template in pixel.
 * @return int Returns TRUE on success.
 * @throws PsException
 *
 */
function ps_begin_template($psdoc, float $width, float $height): int
{
    error_clear_last();
    $result = \ps_begin_template($psdoc, $width, $height);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
    return $result;
}


/**
 * Draws a circle with its middle point at (x,
 * y). The circle starts and ends at position
 * (x+radius,
 * y). If this function is called outside a path it
 * will start a new path. If it is called within a path it will add the circle
 * as a subpath. If the last drawing operation does not end in point
 * (x+radius,
 * y) then there will be a gap in the path.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x The x-coordinate of the circle's middle point.
 * @param float $y The y-coordinate of the circle's middle point.
 * @param float $radius The radius of the circle
 * @throws PsException
 *
 */
function ps_circle($psdoc, float $x, float $y, float $radius): void
{
    error_clear_last();
    $result = \ps_circle($psdoc, $x, $y, $radius);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Takes the current path and uses it to define the border of a clipping area.
 * Everything drawn outside of that area will not be visible.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_clip($psdoc): void
{
    error_clear_last();
    $result = \ps_clip($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Closes an image and frees its resources. Once an image is closed
 * it cannot be used anymore.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $imageid Resource identifier of the image as returned by
 * ps_open_image or
 * ps_open_image_file.
 * @throws PsException
 *
 */
function ps_close_image($psdoc, int $imageid): void
{
    error_clear_last();
    $result = \ps_close_image($psdoc, $imageid);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Closes the PostScript document.
 *
 * This function writes the trailer of the PostScript document.
 * It also writes the bookmark tree. ps_close does
 * not free any resources, which is done by ps_delete.
 *
 * This function is also called by ps_delete if it
 * has not been called before.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_close($psdoc): void
{
    error_clear_last();
    $result = \ps_close($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Connects the last point with first point of a path and draws the resulting
 * closed line.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_closepath_stroke($psdoc): void
{
    error_clear_last();
    $result = \ps_closepath_stroke($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Connects the last point with the first point of a path. The resulting
 * path can be used for stroking, filling, clipping, etc..
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_closepath($psdoc): void
{
    error_clear_last();
    $result = \ps_closepath($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Output a text one line below the last line. The line spacing is
 * taken from the value "leading" which must be set with
 * ps_set_value. The actual position of the
 * text is determined by the values "textx" and "texty" which can be requested
 * with ps_get_value
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $text The text to output.
 * @throws PsException
 *
 */
function ps_continue_text($psdoc, string $text): void
{
    error_clear_last();
    $result = \ps_continue_text($psdoc, $text);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Add a section of a cubic Bézier curve described by the three given control
 * points to the current path.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x1 x-coordinate of first control point.
 * @param float $y1 y-coordinate of first control point.
 * @param float $x2 x-coordinate of second control point.
 * @param float $y2 y-coordinate of second control point.
 * @param float $x3 x-coordinate of third control point.
 * @param float $y3 y-coordinate of third control point.
 * @throws PsException
 *
 */
function ps_curveto($psdoc, float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
{
    error_clear_last();
    $result = \ps_curveto($psdoc, $x1, $y1, $x2, $y2, $x3, $y3);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Mainly frees memory used by the document. Also closes a file, if it was not
 * closed before with ps_close. You should in any case
 * close the file with ps_close before, because
 * ps_close not just closes the file but also outputs a
 * trailor containing PostScript comments like the number of pages in the
 * document and adding the bookmark hierarchy.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_delete($psdoc): void
{
    error_clear_last();
    $result = \ps_delete($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Ends a page which was started with ps_begin_page.
 * Ending a page will leave the current drawing context, which e.g. requires
 * to reload fonts if they were loading within the page, and to set many
 * other drawing parameters like the line width, or color..
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_end_page($psdoc): void
{
    error_clear_last();
    $result = \ps_end_page($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Ends a pattern which was started with ps_begin_pattern.
 * Once a pattern has been ended, it can be used like a color to fill
 * areas.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_end_pattern($psdoc): void
{
    error_clear_last();
    $result = \ps_end_pattern($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Ends a template which was started with ps_begin_template.
 * Once a template has been ended, it can be used like an image.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_end_template($psdoc): void
{
    error_clear_last();
    $result = \ps_end_template($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Fills and draws the path constructed with previously called drawing
 * functions like ps_lineto.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_fill_stroke($psdoc): void
{
    error_clear_last();
    $result = \ps_fill_stroke($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Fills the path constructed with previously called drawing functions like
 * ps_lineto.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_fill($psdoc): void
{
    error_clear_last();
    $result = \ps_fill($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Gets several parameters which were directly set by
 * ps_set_parameter or indirectly by one of the other
 * functions. Parameters are by definition string
 * values. This function cannot be used to retrieve resources which were also
 * set by ps_set_parameter.
 *
 * The parameter name can have the following values.
 *
 *
 *
 * fontname
 *
 *
 * The name of the currently active font or the font whose
 * identifier is passed in parameter modifier.
 *
 *
 *
 *
 * fontencoding
 *
 *
 * The encoding of the currently active font.
 *
 *
 *
 *
 * dottedversion
 *
 *
 * The version of the underlying pslib library in the format
 * &lt;major&gt;.&lt;minor&gt;.&lt;subminor&gt;
 *
 *
 *
 *
 * scope
 *
 *
 * The current drawing scope. Can be object, document, null, page,
 * pattern, path, template, prolog, font, glyph.
 *
 *
 *
 *
 * ligaturedisolvechar
 *
 *
 * The character which dissolves a ligature. If your are using a font
 * which contains the ligature `ff' and `|' is the char to dissolve the
 * ligature, then `f|f' will result in two `f' instead of the ligature `ff'.
 *
 *
 *
 *
 * imageencoding
 *
 *
 * The encoding used for encoding images. Can be either
 * hex or 85. hex encoding
 * uses two bytes in the postscript file each byte in the image.
 * 85 stand for Ascii85 encoding.
 *
 *
 *
 *
 * linenumbermode
 *
 *
 * Set to paragraph if lines are numbered
 * within a paragraph or box if they are
 * numbered within the surrounding box.
 *
 *
 *
 *
 * linebreak
 *
 *
 * Only used if text is output with ps_show_boxed.
 * If set to TRUE a carriage return will add a line
 * break.
 *
 *
 *
 *
 * parbreak
 *
 *
 * Only used if text is output with ps_show_boxed.
 * If set to TRUE a carriage return will start
 * a new paragraph.
 *
 *
 *
 *
 * hyphenation
 *
 *
 * Only used if text is output with ps_show_boxed.
 * If set to TRUE the paragraph will be hyphenated
 * if a hypen dictionary is set and exists.
 *
 *
 *
 *
 * hyphendict
 *
 *
 * Filename of the dictionary used for hyphenation pattern.
 *
 *
 *
 *
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $name Name of the parameter.
 * @param float $modifier An identifier needed if a parameter of a resource is requested,
 * e.g. the size of an image. In such a case the resource id is
 * passed.
 * @return string Returns the value of the parameter.
 * @throws PsException
 *
 */
function ps_get_parameter($psdoc, string $name, float $modifier = null): string
{
    error_clear_last();
    if ($modifier !== null) {
        $result = \ps_get_parameter($psdoc, $name, $modifier);
    } else {
        $result = \ps_get_parameter($psdoc, $name);
    }
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
    return $result;
}


/**
 * Hyphenates the passed word. ps_hyphenate evaluates the
 * value hyphenminchars (set by ps_set_value) and
 * the parameter hyphendict (set by ps_set_parameter).
 * hyphendict must be set before calling this function.
 *
 * This function requires the locale category LC_CTYPE to be set properly.
 * This is done when the extension is initialized by using the environment
 * variables. On Unix systems read the man page of locale for more information.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $text text should not contain any non alpha
 * characters. Possible positions for breaks are returned in an array of
 * interger numbers. Each number is the position of the char in
 * text after which a hyphenation can take place.
 * @return array An array of integers indicating the position of possible breaks in
 * the text.
 * @throws PsException
 *
 */
function ps_hyphenate($psdoc, string $text): array
{
    error_clear_last();
    $result = \ps_hyphenate($psdoc, $text);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
    return $result;
}


/**
 * This function is
 * currently not documented; only its argument list is available.
 *
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $file
 * @throws PsException
 *
 */
function ps_include_file($psdoc, string $file): void
{
    error_clear_last();
    $result = \ps_include_file($psdoc, $file);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Adds a straight line from the current point to the given coordinates to the
 * current path. Use ps_moveto to set the starting point
 * of the line.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x x-coordinate of the end point of the line.
 * @param float $y y-coordinate of the end point of the line.
 * @throws PsException
 *
 */
function ps_lineto($psdoc, float $x, float $y): void
{
    error_clear_last();
    $result = \ps_lineto($psdoc, $x, $y);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets the current point to new coordinates. If this is the first call of
 * ps_moveto after a previous path has been ended then it
 * will start a new path. If this function is called in the middle of a path
 * it will just set the current point and start a subpath.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x x-coordinate of the point to move to.
 * @param float $y y-coordinate of the point to move to.
 * @throws PsException
 *
 */
function ps_moveto($psdoc, float $x, float $y): void
{
    error_clear_last();
    $result = \ps_moveto($psdoc, $x, $y);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Creates a new document instance. It does not create the file on disk or in
 * memory, it just sets up everything. ps_new is usually
 * followed by a call of ps_open_file to actually create
 * the postscript document.
 *
 * @return resource Resource of PostScript document. The return value
 * is passed to all other functions as the first argument.
 * @throws PsException
 *
 */
function ps_new()
{
    error_clear_last();
    $result = \ps_new();
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
    return $result;
}


/**
 * Creates a new file on disk and writes the PostScript document into it. The
 * file will be closed when ps_close is called.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $filename The name of the postscript file.
 * If filename is not passed the document will be
 * created in memory and all output will go straight to the browser.
 * @throws PsException
 *
 */
function ps_open_file($psdoc, string $filename = null): void
{
    error_clear_last();
    if ($filename !== null) {
        $result = \ps_open_file($psdoc, $filename);
    } else {
        $result = \ps_open_file($psdoc);
    }
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Places a formerly loaded image on the page. The image can be scaled.
 * If the image shall be rotated as well, you will have to rotate the
 * coordinate system before with ps_rotate.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $imageid The resource identifier of the image as returned by
 * ps_open_image or
 * ps_open_image_file.
 * @param float $x x-coordinate of the lower left corner of the image.
 * @param float $y y-coordinate of the lower left corner of the image.
 * @param float $scale The scaling factor for the image. A scale of 1.0 will result
 * in a resolution of 72 dpi, because each pixel is equivalent to
 * 1 point.
 * @throws PsException
 *
 */
function ps_place_image($psdoc, int $imageid, float $x, float $y, float $scale): void
{
    error_clear_last();
    $result = \ps_place_image($psdoc, $imageid, $x, $y, $scale);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Draws a rectangle with its lower left corner at (x,
 * y). The rectangle starts and ends in its lower left
 * corner. If this function is called outside a path it will start a new path.
 * If it is called within a path it will add the rectangle as a subpath. If
 * the last drawing operation does not end in the lower left corner then there
 * will be a gap in the path.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x x-coordinate of the lower left corner of the rectangle.
 * @param float $y y-coordinate of the lower left corner of the rectangle.
 * @param float $width The width of the image.
 * @param float $height The height of the image.
 * @throws PsException
 *
 */
function ps_rect($psdoc, float $x, float $y, float $width, float $height): void
{
    error_clear_last();
    $result = \ps_rect($psdoc, $x, $y, $width, $height);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Restores a previously saved graphics context. Any call of
 * ps_save must be accompanied by a call to
 * ps_restore. All coordinate transformations, line
 * style settings, color settings, etc. are being restored to the state
 * before the call of ps_save.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_restore($psdoc): void
{
    error_clear_last();
    $result = \ps_restore($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets the rotation of the coordinate system.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $rot Angle of rotation in degree.
 * @throws PsException
 *
 */
function ps_rotate($psdoc, float $rot): void
{
    error_clear_last();
    $result = \ps_rotate($psdoc, $rot);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Saves the current graphics context, containing colors, translation and
 * rotation settings and some more. A saved context can be restored with
 * ps_restore.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_save($psdoc): void
{
    error_clear_last();
    $result = \ps_save($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets horizontal and vertical scaling of the coordinate system.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x Scaling factor in horizontal direction.
 * @param float $y Scaling factor in vertical direction.
 * @throws PsException
 *
 */
function ps_scale($psdoc, float $x, float $y): void
{
    error_clear_last();
    $result = \ps_scale($psdoc, $x, $y);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Links added with one of the functions ps_add_weblink,
 * ps_add_pdflink, etc. will be displayed with a
 * surounded rectangle when the postscript document is converted to
 * pdf and viewed in a pdf viewer. This rectangle is not visible in
 * the postscript document.
 * This function sets the color of the rectangle's border line.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $red The red component of the border color.
 * @param float $green The green component of the border color.
 * @param float $blue The blue component of the border color.
 * @throws PsException
 *
 */
function ps_set_border_color($psdoc, float $red, float $green, float $blue): void
{
    error_clear_last();
    $result = \ps_set_border_color($psdoc, $red, $green, $blue);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Links added with one of the functions ps_add_weblink,
 * ps_add_pdflink, etc. will be displayed with a
 * surounded rectangle when the postscript document is converted to
 * pdf and viewed in a pdf viewer. This rectangle is not visible in
 * the postscript document.
 * This function sets the length of the black and white portion of a
 * dashed border line.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $black The length of the dash.
 * @param float $white The length of the gap between dashes.
 * @throws PsException
 *
 */
function ps_set_border_dash($psdoc, float $black, float $white): void
{
    error_clear_last();
    $result = \ps_set_border_dash($psdoc, $black, $white);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Links added with one of the functions ps_add_weblink,
 * ps_add_pdflink, etc. will be displayed with a
 * surounded rectangle when the postscript document is converted to
 * pdf and viewed in a pdf viewer. This rectangle is not visible in
 * the postscript document.
 * This function sets the appearance and width of the border line.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $style style can be solid or
 * dashed.
 * @param float $width The line width of the border.
 * @throws PsException
 *
 */
function ps_set_border_style($psdoc, string $style, float $width): void
{
    error_clear_last();
    $result = \ps_set_border_style($psdoc, $style, $width);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets certain information fields of the document. This fields will be shown
 * as a comment in the header of the PostScript file. If the document is
 * converted to pdf this fields will also be used for the document
 * information.
 *
 * The BoundingBox is usually set to the value given to the
 * first page. This only works if ps_findfont has not
 * been called before. In such cases the BoundingBox would be left unset
 * unless you set it explicitly with this function.
 *
 * This function will have no effect anymore when the header of the postscript
 * file has been already written. It must be called before the first page
 * or the first call of ps_findfont.
 *
 * @param resource $p Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $key The name of the information field to set. The values which can be
 * set are Keywords, Subject,
 * Title, Creator,
 * Author, BoundingBox, and
 * Orientation. Be aware that some of them has a
 * meaning to PostScript viewers.
 * @param string $val The value of the information field. The field
 * Orientation can be set to either
 * Portrait or Landscape. The
 * BoundingBox is a string consisting of four numbers.
 * The first two numbers are the coordinates of the lower left corner of
 * the page. The last two numbers are the coordinates of the upper
 * right corner.
 *
 * Up to version 0.2.6 of pslib, the BoundingBox and Orientation
 * will be overwritten by ps_begin_page,
 * unless ps_findfont has been called before.
 * @throws PsException
 *
 */
function ps_set_info($p, string $key, string $val): void
{
    error_clear_last();
    $result = \ps_set_info($p, $key, $val);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets several parameters which are used by many functions. Parameters are by
 * definition string values.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $name For a list of possible names see ps_get_parameter.
 * @param string $value The value of the parameter.
 * @throws PsException
 *
 */
function ps_set_parameter($psdoc, string $name, string $value): void
{
    error_clear_last();
    $result = \ps_set_parameter($psdoc, $name, $value);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Set the position for the next text output. You may alternatively set the x
 * and y value separately by calling ps_set_value and
 * choosing textx respectively texty as
 * the value name.
 *
 * If you want to output text at a certain position it is more convenient
 * to use ps_show_xy instead of setting the text position
 * and calling ps_show.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x x-coordinate of the new text position.
 * @param float $y y-coordinate of the new text position.
 * @throws PsException
 *
 */
function ps_set_text_pos($psdoc, float $x, float $y): void
{
    error_clear_last();
    $result = \ps_set_text_pos($psdoc, $x, $y);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets several values which are used by many functions. Parameters are by
 * definition float values.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $name The name can be one of the following:
 *
 *
 * textrendering
 *
 *
 * The way how text is shown.
 *
 *
 *
 *
 * textx
 *
 *
 * The x coordinate for text output.
 *
 *
 *
 *
 * texty
 *
 *
 * The y coordinate for text output.
 *
 *
 *
 *
 * wordspacing
 *
 *
 * The distance between words relative to the width of a space.
 *
 *
 *
 *
 * leading
 *
 *
 * The distance between lines in pixels.
 *
 *
 *
 *
 *
 * The way how text is shown.
 *
 * The x coordinate for text output.
 *
 * The y coordinate for text output.
 *
 * The distance between words relative to the width of a space.
 *
 * The distance between lines in pixels.
 * @param float $value The way how text is shown.
 * @throws PsException
 *
 */
function ps_set_value($psdoc, string $name, float $value): void
{
    error_clear_last();
    $result = \ps_set_value($psdoc, $name, $value);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets the color for drawing, filling, or both.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $type The parameter type can be
 * both, fill, or
 * fillstroke.
 * @param string $colorspace The colorspace should be one of gray,
 * rgb, cmyk,
 * spot, pattern. Depending on the
 * colorspace either only the first, the first three or all parameters
 * will be used.
 * @param float $c1 Depending on the colorspace this is either the red component (rgb),
 * the cyan component (cmyk), the gray value (gray), the identifier of
 * the spot color or the identifier of the pattern.
 * @param float $c2 Depending on the colorspace this is either the green component (rgb),
 * the magenta component (cmyk).
 * @param float $c3 Depending on the colorspace this is either the blue component (rgb),
 * the yellow component (cmyk).
 * @param float $c4 This must only be set in cmyk colorspace and specifies the black
 * component.
 * @throws PsException
 *
 */
function ps_setcolor($psdoc, string $type, string $colorspace, float $c1, float $c2, float $c3, float $c4): void
{
    error_clear_last();
    $result = \ps_setcolor($psdoc, $type, $colorspace, $c1, $c2, $c3, $c4);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets the length of the black and white portions of a dashed line.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $on The length of the dash.
 * @param float $off The length of the gap between dashes.
 * @throws PsException
 *
 */
function ps_setdash($psdoc, float $on, float $off): void
{
    error_clear_last();
    $result = \ps_setdash($psdoc, $on, $off);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * This function is
 * currently not documented; only its argument list is available.
 *
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $value The value must be between 0.2 and 1.
 * @throws PsException
 *
 */
function ps_setflat($psdoc, float $value): void
{
    error_clear_last();
    $result = \ps_setflat($psdoc, $value);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets a font, which has to be loaded before with
 * ps_findfont. Outputting text without setting a font
 * results in an error.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $fontid The font identifier as returned by ps_findfont.
 * @param float $size The size of the font.
 * @throws PsException
 *
 */
function ps_setfont($psdoc, int $fontid, float $size): void
{
    error_clear_last();
    $result = \ps_setfont($psdoc, $fontid, $size);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets the gray value for all following drawing operations.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $gray The value must be between 0 (white) and 1 (black).
 * @throws PsException
 *
 */
function ps_setgray($psdoc, float $gray): void
{
    error_clear_last();
    $result = \ps_setgray($psdoc, $gray);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets how line ends look like.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $type The type of line ends. Possible values are
 * PS_LINECAP_BUTT,
 * PS_LINECAP_ROUND, or
 * PS_LINECAP_SQUARED.
 * @throws PsException
 *
 */
function ps_setlinecap($psdoc, int $type): void
{
    error_clear_last();
    $result = \ps_setlinecap($psdoc, $type);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets how lines are joined.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $type The way lines are joined. Possible values are
 * PS_LINEJOIN_MITER,
 * PS_LINEJOIN_ROUND, or
 * PS_LINEJOIN_BEVEL.
 * @throws PsException
 *
 */
function ps_setlinejoin($psdoc, int $type): void
{
    error_clear_last();
    $result = \ps_setlinejoin($psdoc, $type);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets the line width for all following drawing operations.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $width The width of lines in points.
 * @throws PsException
 *
 */
function ps_setlinewidth($psdoc, float $width): void
{
    error_clear_last();
    $result = \ps_setlinewidth($psdoc, $width);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * If two lines join in a small angle and the line join is set to
 * PS_LINEJOIN_MITER, then
 * the resulting spike will be very long. The miter limit is the maximum
 * ratio of the miter length (the length of the spike) and the line width.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $value The maximum ratio between the miter length and the line width. Larger
 * values (&gt; 10) will result in very long spikes when two lines meet
 * in a small angle. Keep the default unless you know what you are doing.
 * @throws PsException
 *
 */
function ps_setmiterlimit($psdoc, float $value): void
{
    error_clear_last();
    $result = \ps_setmiterlimit($psdoc, $value);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * This function is
 * currently not documented; only its argument list is available.
 *
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $mode
 * @throws PsException
 *
 */
function ps_setoverprintmode($psdoc, int $mode): void
{
    error_clear_last();
    $result = \ps_setoverprintmode($psdoc, $mode);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets the length of the black and white portions of a dashed line.
 * ps_setpolydash is used to set more complicated dash
 * patterns.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $arr arr is a list of length elements alternately for
 * the black and white portion.
 * @throws PsException
 *
 */
function ps_setpolydash($psdoc, float $arr): void
{
    error_clear_last();
    $result = \ps_setpolydash($psdoc, $arr);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Creates a pattern based on a shading, which has to be created before with
 * ps_shading. Shading patterns can be used like regular
 * patterns.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $shadingid The identifier of a shading previously created with
 * ps_shading.
 * @param string $optlist This argument is not currently used.
 * @return int The identifier of the pattern.
 * @throws PsException
 *
 */
function ps_shading_pattern($psdoc, int $shadingid, string $optlist): int
{
    error_clear_last();
    $result = \ps_shading_pattern($psdoc, $shadingid, $optlist);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
    return $result;
}


/**
 * Creates a shading, which can be used by ps_shfill or
 * ps_shading_pattern.
 *
 * The color of the shading can be in any color space except for
 * pattern.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $type The type of shading can be either radial or
 * axial. Each shading starts with the current fill
 * color and ends with the given color values passed in the parameters
 * c1 to c4
 * (see ps_setcolor for their meaning).
 * @param float $x0 The coordinates x0, y0,
 * x1, y1 are the start and
 * end point of the shading. If the type of shading is
 * radial the two points are the middle points of
 * a starting and ending circle.
 * @param float $y0 See ps_setcolor for their meaning.
 * @param float $x1 If the shading is of type radial the
 * optlist must also contain the parameters
 * r0 and r1 with the radius of the
 * start and end circle.
 * @param float $y1
 * @param float $c1
 * @param float $c2
 * @param float $c3
 * @param float $c4
 * @param string $optlist
 * @return int Returns the identifier of the pattern.
 * @throws PsException
 *
 */
function ps_shading($psdoc, string $type, float $x0, float $y0, float $x1, float $y1, float $c1, float $c2, float $c3, float $c4, string $optlist): int
{
    error_clear_last();
    $result = \ps_shading($psdoc, $type, $x0, $y0, $x1, $y1, $c1, $c2, $c3, $c4, $optlist);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
    return $result;
}


/**
 * Fills an area with a shading, which has to be created before with
 * ps_shading. This is an alternative way to creating
 * a pattern from a shading ps_shading_pattern and using
 * the pattern as the filling color.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $shadingid The identifier of a shading previously created with
 * ps_shading.
 * @throws PsException
 *
 */
function ps_shfill($psdoc, int $shadingid): void
{
    error_clear_last();
    $result = \ps_shfill($psdoc, $shadingid);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Output a text at the given text position.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $text The text to be output.
 * @param float $x x-coordinate of the lower left corner of the box surrounding the text.
 * @param float $y y-coordinate of the lower left corner of the box surrounding the text.
 * @throws PsException
 *
 */
function ps_show_xy($psdoc, string $text, float $x, float $y): void
{
    error_clear_last();
    $result = \ps_show_xy($psdoc, $text, $x, $y);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * This function is
 * currently not documented; only its argument list is available.
 *
 *
 * @param resource $psdoc
 * @param string $text
 * @param int $len
 * @param float $xcoor
 * @param float $ycoor
 * @throws PsException
 *
 */
function ps_show_xy2($psdoc, string $text, int $len, float $xcoor, float $ycoor): void
{
    error_clear_last();
    $result = \ps_show_xy2($psdoc, $text, $len, $xcoor, $ycoor);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Output a text at the current text position. The text position can be set
 * by storing the x and y coordinates into the values textx
 * and texty with the function
 * ps_set_value. The function will issue an
 * error if a font was not set before with ps_setfont.
 *
 * ps_show evaluates the following parameters and values
 * as set by ps_set_parameter and
 * ps_set_value.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $text The text to be output.
 * @throws PsException
 *
 */
function ps_show($psdoc, string $text): void
{
    error_clear_last();
    $result = \ps_show($psdoc, $text);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Output text at the current position. Do not print more than len characters.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param string $text The text to be output.
 * @param int $len The maximum number of characters to print.
 * @throws PsException
 *
 */
function ps_show2($psdoc, string $text, int $len): void
{
    error_clear_last();
    $result = \ps_show2($psdoc, $text, $len);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Draws the path constructed with previously called drawing functions like
 * ps_lineto.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @throws PsException
 *
 */
function ps_stroke($psdoc): void
{
    error_clear_last();
    $result = \ps_stroke($psdoc);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Output the glyph at position ord in the font
 * encoding vector of the current font. The font encoding for a font can be
 * set when loading the font with ps_findfont.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param int $ord The position of the glyph in the font encoding vector.
 * @throws PsException
 *
 */
function ps_symbol($psdoc, int $ord): void
{
    error_clear_last();
    $result = \ps_symbol($psdoc, $ord);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}


/**
 * Sets a new initial point of the coordinate system.
 *
 * @param resource $psdoc Resource identifier of the postscript file
 * as returned by ps_new.
 * @param float $x x-coordinate of the origin of the translated coordinate system.
 * @param float $y y-coordinate of the origin of the translated coordinate system.
 * @throws PsException
 *
 */
function ps_translate($psdoc, float $x, float $y): void
{
    error_clear_last();
    $result = \ps_translate($psdoc, $x, $y);
    if ($result === false) {
        throw PsException::createFromPhpError();
    }
}
