<?php

namespace Safe;

use Safe\Exceptions\PdfException;

/**
 * Activates a previously created structure element or other content item.
 * Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param int $id
 * @throws PdfException
 *
 */
function PDF_activate_item($pdfdoc, int $id): void
{
    error_clear_last();
    $result = \PDF_activate_item($pdfdoc, $id);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Add a link annotation to a target within the current PDF file.
 * Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * PDF_create_action with type=GoTo
 * and PDF_create_annotation with
 * type=Link instead.
 *
 * @param resource $pdfdoc
 * @param float $lowerleftx
 * @param float $lowerlefty
 * @param float $upperrightx
 * @param float $upperrighty
 * @param int $page
 * @param string $dest
 * @throws PdfException
 *
 */
function PDF_add_locallink($pdfdoc, float $lowerleftx, float $lowerlefty, float $upperrightx, float $upperrighty, int $page, string $dest): void
{
    error_clear_last();
    $result = \PDF_add_locallink($pdfdoc, $lowerleftx, $lowerlefty, $upperrightx, $upperrighty, $page, $dest);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Creates a named destination on an arbitrary page in the current document.
 * Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param string $name
 * @param string $optlist
 * @throws PdfException
 *
 */
function PDF_add_nameddest($pdfdoc, string $name, string $optlist): void
{
    error_clear_last();
    $result = \PDF_add_nameddest($pdfdoc, $name, $optlist);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets an annotation for the current page. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * PDF_create_annotation with
 * type=Text instead.
 *
 * @param resource $pdfdoc
 * @param float $llx
 * @param float $lly
 * @param float $urx
 * @param float $ury
 * @param string $contents
 * @param string $title
 * @param string $icon
 * @param int $open
 * @throws PdfException
 *
 */
function PDF_add_note($pdfdoc, float $llx, float $lly, float $urx, float $ury, string $contents, string $title, string $icon, int $open): void
{
    error_clear_last();
    $result = \PDF_add_note($pdfdoc, $llx, $lly, $urx, $ury, $contents, $title, $icon, $open);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Add a file link annotation to a PDF target.
 * Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * PDF_create_action with
 * type=GoToR and
 * PDF_create_annotation with
 * type=Link instead.
 *
 * @param resource $pdfdoc
 * @param float $bottom_left_x
 * @param float $bottom_left_y
 * @param float $up_right_x
 * @param float $up_right_y
 * @param string $filename
 * @param int $page
 * @param string $dest
 * @throws PdfException
 *
 */
function PDF_add_pdflink($pdfdoc, float $bottom_left_x, float $bottom_left_y, float $up_right_x, float $up_right_y, string $filename, int $page, string $dest): void
{
    error_clear_last();
    $result = \PDF_add_pdflink($pdfdoc, $bottom_left_x, $bottom_left_y, $up_right_x, $up_right_y, $filename, $page, $dest);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Adds an existing image as thumbnail for the current page.
 * Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param int $image
 * @throws PdfException
 *
 */
function PDF_add_thumbnail($pdfdoc, int $image): void
{
    error_clear_last();
    $result = \PDF_add_thumbnail($pdfdoc, $image);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Adds a weblink annotation to a target url on the Web.
 * Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * PDF_create_action with type=URI
 * and PDF_create_annotation with
 * type=Link instead.
 *
 * @param resource $pdfdoc
 * @param float $lowerleftx
 * @param float $lowerlefty
 * @param float $upperrightx
 * @param float $upperrighty
 * @param string $url
 * @throws PdfException
 *
 */
function PDF_add_weblink($pdfdoc, float $lowerleftx, float $lowerlefty, float $upperrightx, float $upperrighty, string $url): void
{
    error_clear_last();
    $result = \PDF_add_weblink($pdfdoc, $lowerleftx, $lowerlefty, $upperrightx, $upperrighty, $url);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Adds a file attachment annotation. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * PDF_create_annotation with
 * type=FileAttachment instead.
 *
 * @param resource $pdfdoc
 * @param float $llx
 * @param float $lly
 * @param float $urx
 * @param float $ury
 * @param string $filename
 * @param string $description
 * @param string $author
 * @param string $mimetype
 * @param string $icon
 * @throws PdfException
 *
 */
function PDF_attach_file($pdfdoc, float $llx, float $lly, float $urx, float $ury, string $filename, string $description, string $author, string $mimetype, string $icon): void
{
    error_clear_last();
    $result = \PDF_attach_file($pdfdoc, $llx, $lly, $urx, $ury, $filename, $description, $author, $mimetype, $icon);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Starts a layer for subsequent output on the page. Returns TRUE on success.
 *
 * This function requires PDF 1.5.
 *
 * @param resource $pdfdoc
 * @param int $layer
 * @throws PdfException
 *
 */
function PDF_begin_layer($pdfdoc, int $layer): void
{
    error_clear_last();
    $result = \PDF_begin_layer($pdfdoc, $layer);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Adds a new page to the document, and specifies various options.
 * The parameters width and height
 * are the dimensions of the new page in points. Returns TRUE on success.
 *
 *
 * Common Page Sizes in Points
 *
 *
 *
 * name
 * size
 *
 *
 *
 *
 * A0
 * 2380 x 3368
 *
 *
 * A1
 * 1684 x 2380
 *
 *
 * A2
 * 1190 x 1684
 *
 *
 * A3
 * 842 x 1190
 *
 *
 * A4
 * 595 x 842
 *
 *
 * A5
 * 421 x 595
 *
 *
 * A6
 * 297 x 421
 *
 *
 * B5
 * 501 x 709
 *
 *
 * letter (8.5" x 11")
 * 612 x 792
 *
 *
 * legal (8.5" x 14")
 * 612 x 1008
 *
 *
 * ledger (17" x 11")
 * 1224 x 792
 *
 *
 * 11" x 17"
 * 792 x 1224
 *
 *
 *
 *
 *
 * @param resource $pdfdoc
 * @param float $width
 * @param float $height
 * @param string $optlist
 * @throws PdfException
 *
 */
function PDF_begin_page_ext($pdfdoc, float $width, float $height, string $optlist): void
{
    error_clear_last();
    $result = \PDF_begin_page_ext($pdfdoc, $width, $height, $optlist);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Adds a new page to the document. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * PDF_begin_page_ext instead.
 *
 * @param resource $pdfdoc
 * @param float $width
 * @param float $height
 * @throws PdfException
 *
 */
function PDF_begin_page($pdfdoc, float $width, float $height): void
{
    error_clear_last();
    $result = \PDF_begin_page($pdfdoc, $width, $height);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Adds a circle. Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param float $x
 * @param float $y
 * @param float $r
 * @throws PdfException
 *
 */
function PDF_circle($pdfdoc, float $x, float $y, float $r): void
{
    error_clear_last();
    $result = \PDF_circle($pdfdoc, $x, $y, $r);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Uses the current path as clipping path, and terminate the path. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_clip($p): void
{
    error_clear_last();
    $result = \PDF_clip($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Closes the page handle, and frees all page-related resources. Returns TRUE on success.
 *
 * @param resource $p
 * @param int $page
 * @throws PdfException
 *
 */
function PDF_close_pdi_page($p, int $page): void
{
    error_clear_last();
    $result = \PDF_close_pdi_page($p, $page);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Closes all open page handles, and closes the input PDF document. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 7,
 * use PDF_close_pdi_document instead.
 *
 * @param resource $p
 * @param int $doc
 * @throws PdfException
 *
 */
function PDF_close_pdi($p, int $doc): void
{
    error_clear_last();
    $result = \PDF_close_pdi($p, $doc);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Closes the generated PDF file, and frees all document-related resources.
 * Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * PDF_end_document instead.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_close($p): void
{
    error_clear_last();
    $result = \PDF_close($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Closes the path, fills, and strokes it. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_closepath_fill_stroke($p): void
{
    error_clear_last();
    $result = \PDF_closepath_fill_stroke($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Closes the path, and strokes it. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_closepath_stroke($p): void
{
    error_clear_last();
    $result = \PDF_closepath_stroke($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Closes the current path. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_closepath($p): void
{
    error_clear_last();
    $result = \PDF_closepath($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Concatenates a matrix to the current transformation matrix (CTM). Returns TRUE on success.
 *
 * @param resource $p
 * @param float $a
 * @param float $b
 * @param float $c
 * @param float $d
 * @param float $e
 * @param float $f
 * @throws PdfException
 *
 */
function PDF_concat($p, float $a, float $b, float $c, float $d, float $e, float $f): void
{
    error_clear_last();
    $result = \PDF_concat($p, $a, $b, $c, $d, $e, $f);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Prints text at the next line. Returns TRUE on success.
 *
 * @param resource $p
 * @param string $text
 * @throws PdfException
 *
 */
function PDF_continue_text($p, string $text): void
{
    error_clear_last();
    $result = \PDF_continue_text($p, $text);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Draws a Bezier curve from the current point, using 3 more control points.
 * Returns TRUE on success.
 *
 * @param resource $p
 * @param float $x1
 * @param float $y1
 * @param float $x2
 * @param float $y2
 * @param float $x3
 * @param float $y3
 * @throws PdfException
 *
 */
function PDF_curveto($p, float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
{
    error_clear_last();
    $result = \PDF_curveto($p, $x1, $y1, $x2, $y2, $x3, $y3);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Deletes a PDFlib object, and frees all internal resources. Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @throws PdfException
 *
 */
function PDF_delete($pdfdoc): void
{
    error_clear_last();
    $result = \PDF_delete($pdfdoc);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Deactivates all active layers. Returns TRUE on success.
 *
 * This function requires PDF 1.5.
 *
 * @param resource $pdfdoc
 * @throws PdfException
 *
 */
function PDF_end_layer($pdfdoc): void
{
    error_clear_last();
    $result = \PDF_end_layer($pdfdoc);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Finishes a page, and applies various options. Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param string $optlist
 * @throws PdfException
 *
 */
function PDF_end_page_ext($pdfdoc, string $optlist): void
{
    error_clear_last();
    $result = \PDF_end_page_ext($pdfdoc, $optlist);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Finishes the page. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_end_page($p): void
{
    error_clear_last();
    $result = \PDF_end_page($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Finishes the pattern definition. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_end_pattern($p): void
{
    error_clear_last();
    $result = \PDF_end_pattern($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Finishes a template definition. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_end_template($p): void
{
    error_clear_last();
    $result = \PDF_end_template($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Fills and strokes the current path with the current fill and stroke color.
 * Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_fill_stroke($p): void
{
    error_clear_last();
    $result = \PDF_fill_stroke($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Fills the interior of the current path with the current fill color.
 * Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_fill($p): void
{
    error_clear_last();
    $result = \PDF_fill($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Places an image or template on the page, subject to various options.
 * Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param int $image
 * @param float $x
 * @param float $y
 * @param string $optlist
 * @throws PdfException
 *
 */
function PDF_fit_image($pdfdoc, int $image, float $x, float $y, string $optlist): void
{
    error_clear_last();
    $result = \PDF_fit_image($pdfdoc, $image, $x, $y, $optlist);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Places an imported PDF page on the page, subject to various options.
 * Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param int $page
 * @param float $x
 * @param float $y
 * @param string $optlist
 * @throws PdfException
 *
 */
function PDF_fit_pdi_page($pdfdoc, int $page, float $x, float $y, string $optlist): void
{
    error_clear_last();
    $result = \PDF_fit_pdi_page($pdfdoc, $page, $x, $y, $optlist);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Places a single line of text on the page, subject to various options. Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param string $text
 * @param float $x
 * @param float $y
 * @param string $optlist
 * @throws PdfException
 *
 */
function PDF_fit_textline($pdfdoc, string $text, float $x, float $y, string $optlist): void
{
    error_clear_last();
    $result = \PDF_fit_textline($pdfdoc, $text, $x, $y, $optlist);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Reset all color and graphics state parameters to their defaults.
 * Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_initgraphics($p): void
{
    error_clear_last();
    $result = \PDF_initgraphics($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Draws a line from the current point to another point. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $x
 * @param float $y
 * @throws PdfException
 *
 */
function PDF_lineto($p, float $x, float $y): void
{
    error_clear_last();
    $result = \PDF_lineto($p, $x, $y);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Finds a built-in spot color name, or makes a named spot color from the
 * current fill color. Returns TRUE on success.
 *
 * @param resource $p
 * @param string $spotname
 * @return int
 * @throws PdfException
 *
 */
function PDF_makespotcolor($p, string $spotname): int
{
    error_clear_last();
    $result = \PDF_makespotcolor($p, $spotname);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets the current point for graphics output. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $x
 * @param float $y
 * @throws PdfException
 *
 */
function PDF_moveto($p, float $x, float $y): void
{
    error_clear_last();
    $result = \PDF_moveto($p, $x, $y);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Creates a new PDF file using the supplied file name.
 * Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * PDF_begin_document instead.
 *
 * @param resource $p
 * @param string $filename
 * @throws PdfException
 *
 */
function PDF_open_file($p, string $filename): void
{
    error_clear_last();
    $result = \PDF_open_file($p, $filename);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Places an image and scales it. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 5, use
 * PDF_fit_image instead.
 *
 * @param resource $pdfdoc
 * @param int $image
 * @param float $x
 * @param float $y
 * @param float $scale
 * @throws PdfException
 *
 */
function PDF_place_image($pdfdoc, int $image, float $x, float $y, float $scale): void
{
    error_clear_last();
    $result = \PDF_place_image($pdfdoc, $image, $x, $y, $scale);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Places a PDF page and scales it. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 5, use
 * PDF_fit_pdi_page instead.
 *
 * @param resource $pdfdoc
 * @param int $page
 * @param float $x
 * @param float $y
 * @param float $sx
 * @param float $sy
 * @throws PdfException
 *
 */
function PDF_place_pdi_page($pdfdoc, int $page, float $x, float $y, float $sx, float $sy): void
{
    error_clear_last();
    $result = \PDF_place_pdi_page($pdfdoc, $page, $x, $y, $sx, $sy);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Draws a rectangle. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $x
 * @param float $y
 * @param float $width
 * @param float $height
 * @throws PdfException
 *
 */
function PDF_rect($p, float $x, float $y, float $width, float $height): void
{
    error_clear_last();
    $result = \PDF_rect($p, $x, $y, $width, $height);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Restores the most recently saved graphics state. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_restore($p): void
{
    error_clear_last();
    $result = \PDF_restore($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Rotates the coordinate system. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $phi
 * @throws PdfException
 *
 */
function PDF_rotate($p, float $phi): void
{
    error_clear_last();
    $result = \PDF_rotate($p, $phi);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Saves the current graphics state. Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_save($p): void
{
    error_clear_last();
    $result = \PDF_save($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Scales the coordinate system. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $sx
 * @param float $sy
 * @throws PdfException
 *
 */
function PDF_scale($p, float $sx, float $sy): void
{
    error_clear_last();
    $result = \PDF_scale($p, $sx, $sy);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the border color for all kinds of annotations. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * the option annotcolor in
 * PDF_create_annotation instead.
 *
 * @param resource $p
 * @param float $red
 * @param float $green
 * @param float $blue
 * @throws PdfException
 *
 */
function PDF_set_border_color($p, float $red, float $green, float $blue): void
{
    error_clear_last();
    $result = \PDF_set_border_color($p, $red, $green, $blue);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the border dash style for all kinds of annotations. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * the option dasharray in
 * PDF_create_annotation instead.
 *
 * @param resource $pdfdoc
 * @param float $black
 * @param float $white
 * @throws PdfException
 *
 */
function PDF_set_border_dash($pdfdoc, float $black, float $white): void
{
    error_clear_last();
    $result = \PDF_set_border_dash($pdfdoc, $black, $white);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the border style for all kinds of annotations. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 6, use
 * the options borderstyle and
 * linewidth in
 * PDF_create_annotation instead.
 *
 * @param resource $pdfdoc
 * @param string $style
 * @param float $width
 * @throws PdfException
 *
 */
function PDF_set_border_style($pdfdoc, string $style, float $width): void
{
    error_clear_last();
    $result = \PDF_set_border_style($pdfdoc, $style, $width);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Fill document information field key with
 * value. Returns TRUE on success.
 *
 * @param resource $p
 * @param string $key
 * @param string $value
 * @throws PdfException
 *
 */
function PDF_set_info($p, string $key, string $value): void
{
    error_clear_last();
    $result = \PDF_set_info($p, $key, $value);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Defines hierarchical and group relationships among layers. Returns TRUE on success.
 *
 * This function requires PDF 1.5.
 *
 * @param resource $pdfdoc
 * @param string $type
 * @param string $optlist
 * @throws PdfException
 *
 */
function PDF_set_layer_dependency($pdfdoc, string $type, string $optlist): void
{
    error_clear_last();
    $result = \PDF_set_layer_dependency($pdfdoc, $type, $optlist);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets some PDFlib parameter with string type. Returns TRUE on success.
 *
 * @param resource $p
 * @param string $key
 * @param string $value
 * @throws PdfException
 *
 */
function PDF_set_parameter($p, string $key, string $value): void
{
    error_clear_last();
    $result = \PDF_set_parameter($p, $key, $value);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the position for text output on the page. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $x
 * @param float $y
 * @throws PdfException
 *
 */
function PDF_set_text_pos($p, float $x, float $y): void
{
    error_clear_last();
    $result = \PDF_set_text_pos($p, $x, $y);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the value of some PDFlib parameter with numerical type. Returns TRUE on success.
 *
 * @param resource $p
 * @param string $key
 * @param float $value
 * @throws PdfException
 *
 */
function PDF_set_value($p, string $key, float $value): void
{
    error_clear_last();
    $result = \PDF_set_value($p, $key, $value);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current color space and color. Returns TRUE on success.
 *
 * @param resource $p
 * @param string $fstype
 * @param string $colorspace
 * @param float $c1
 * @param float $c2
 * @param float $c3
 * @param float $c4
 * @throws PdfException
 *
 */
function PDF_setcolor($p, string $fstype, string $colorspace, float $c1, float $c2, float $c3, float $c4): void
{
    error_clear_last();
    $result = \PDF_setcolor($p, $fstype, $colorspace, $c1, $c2, $c3, $c4);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current dash pattern to b black
 * and w white units. Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param float $b
 * @param float $w
 * @throws PdfException
 *
 */
function PDF_setdash($pdfdoc, float $b, float $w): void
{
    error_clear_last();
    $result = \PDF_setdash($pdfdoc, $b, $w);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets a dash pattern defined by an option list. Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param string $optlist
 * @throws PdfException
 *
 */
function PDF_setdashpattern($pdfdoc, string $optlist): void
{
    error_clear_last();
    $result = \PDF_setdashpattern($pdfdoc, $optlist);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the flatness parameter. Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param float $flatness
 * @throws PdfException
 *
 */
function PDF_setflat($pdfdoc, float $flatness): void
{
    error_clear_last();
    $result = \PDF_setflat($pdfdoc, $flatness);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current font in the specified fontsize, using a
 * font handle returned by PDF_load_font.
 * Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param int $font
 * @param float $fontsize
 * @throws PdfException
 *
 */
function PDF_setfont($pdfdoc, int $font, float $fontsize): void
{
    error_clear_last();
    $result = \PDF_setfont($pdfdoc, $font, $fontsize);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current fill color to a gray value between 0 and 1 inclusive.
 * Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 4, use
 * PDF_setcolor instead.
 *
 * @param resource $p
 * @param float $g
 * @throws PdfException
 *
 */
function PDF_setgray_fill($p, float $g): void
{
    error_clear_last();
    $result = \PDF_setgray_fill($p, $g);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current stroke color to a gray value between 0 and 1 inclusive.
 * Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 4, use
 * PDF_setcolor instead.
 *
 * @param resource $p
 * @param float $g
 * @throws PdfException
 *
 */
function PDF_setgray_stroke($p, float $g): void
{
    error_clear_last();
    $result = \PDF_setgray_stroke($p, $g);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current fill and stroke color to a gray value between 0 and 1 inclusive. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 4, use
 * PDF_setcolor instead.
 *
 * @param resource $p
 * @param float $g
 * @throws PdfException
 *
 */
function PDF_setgray($p, float $g): void
{
    error_clear_last();
    $result = \PDF_setgray($p, $g);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the linejoin parameter to specify the shape
 * at the corners of paths that are stroked. Returns TRUE on success.
 *
 * @param resource $p
 * @param int $value
 * @throws PdfException
 *
 */
function PDF_setlinejoin($p, int $value): void
{
    error_clear_last();
    $result = \PDF_setlinejoin($p, $value);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current line width. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $width
 * @throws PdfException
 *
 */
function PDF_setlinewidth($p, float $width): void
{
    error_clear_last();
    $result = \PDF_setlinewidth($p, $width);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Explicitly sets the current transformation matrix. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $a
 * @param float $b
 * @param float $c
 * @param float $d
 * @param float $e
 * @param float $f
 * @throws PdfException
 *
 */
function PDF_setmatrix($p, float $a, float $b, float $c, float $d, float $e, float $f): void
{
    error_clear_last();
    $result = \PDF_setmatrix($p, $a, $b, $c, $d, $e, $f);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the miter limit.Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param float $miter
 * @throws PdfException
 *
 */
function PDF_setmiterlimit($pdfdoc, float $miter): void
{
    error_clear_last();
    $result = \PDF_setmiterlimit($pdfdoc, $miter);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current fill color to the supplied RGB values. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 4, use
 * PDF_setcolor instead.
 *
 * @param resource $p
 * @param float $red
 * @param float $green
 * @param float $blue
 * @throws PdfException
 *
 */
function PDF_setrgbcolor_fill($p, float $red, float $green, float $blue): void
{
    error_clear_last();
    $result = \PDF_setrgbcolor_fill($p, $red, $green, $blue);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current stroke color to the supplied RGB values. Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 4, use
 * PDF_setcolor instead.
 *
 * @param resource $p
 * @param float $red
 * @param float $green
 * @param float $blue
 * @throws PdfException
 *
 */
function PDF_setrgbcolor_stroke($p, float $red, float $green, float $blue): void
{
    error_clear_last();
    $result = \PDF_setrgbcolor_stroke($p, $red, $green, $blue);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Sets the current fill and stroke color to the supplied RGB values.
 * Returns TRUE on success.
 *
 * This function is deprecated since PDFlib version 4, use
 * PDF_setcolor instead.
 *
 * @param resource $p
 * @param float $red
 * @param float $green
 * @param float $blue
 * @throws PdfException
 *
 */
function PDF_setrgbcolor($p, float $red, float $green, float $blue): void
{
    error_clear_last();
    $result = \PDF_setrgbcolor($p, $red, $green, $blue);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Prints text in the current font. Returns TRUE on success.
 *
 * @param resource $p
 * @param string $text
 * @param float $x
 * @param float $y
 * @throws PdfException
 *
 */
function PDF_show_xy($p, string $text, float $x, float $y): void
{
    error_clear_last();
    $result = \PDF_show_xy($p, $text, $x, $y);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Prints text in the current font and size at
 * the current position. Returns TRUE on success.
 *
 * @param resource $pdfdoc
 * @param string $text
 * @throws PdfException
 *
 */
function PDF_show($pdfdoc, string $text): void
{
    error_clear_last();
    $result = \PDF_show($pdfdoc, $text);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Skews the coordinate system in x and y direction by alpha
 * and beta degrees, respectively. Returns TRUE on success.
 *
 * @param resource $p
 * @param float $alpha
 * @param float $beta
 * @throws PdfException
 *
 */
function PDF_skew($p, float $alpha, float $beta): void
{
    error_clear_last();
    $result = \PDF_skew($p, $alpha, $beta);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}


/**
 * Strokes the path with the current color and line width, and clear it.
 * Returns TRUE on success.
 *
 * @param resource $p
 * @throws PdfException
 *
 */
function PDF_stroke($p): void
{
    error_clear_last();
    $result = \PDF_stroke($p);
    if ($result === false) {
        throw PdfException::createFromPhpError();
    }
}
