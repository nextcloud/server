<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * Service definition for Customsearch (v1).
 *
 * <p>
 * Lets you search over a website or collection of websites</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/custom-search/v1/using_rest" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Customsearch extends Google_Service
{


  public $cse;
  

  /**
   * Constructs the internal representation of the Customsearch service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'customsearch/';
    $this->version = 'v1';
    $this->serviceName = 'customsearch';

    $this->cse = new Google_Service_Customsearch_Cse_Resource(
        $this,
        $this->serviceName,
        'cse',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'v1',
              'httpMethod' => 'GET',
              'parameters' => array(
                'q' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'sort' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'orTerms' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'highRange' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'num' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'cr' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'imgType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'gl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'relatedSite' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'searchType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'fileType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'start' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'imgDominantColor' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'lr' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'siteSearch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'cref' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'dateRestrict' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'safe' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'c2coff' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'googlehost' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'hq' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'exactTerms' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'lowRange' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'imgSize' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'imgColorType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'rights' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'excludeTerms' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'linkSite' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'cx' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'siteSearchFilter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
  }
}


/**
 * The "cse" collection of methods.
 * Typical usage is:
 *  <code>
 *   $customsearchService = new Google_Service_Customsearch(...);
 *   $cse = $customsearchService->cse;
 *  </code>
 */
class Google_Service_Customsearch_Cse_Resource extends Google_Service_Resource
{

  /**
   * Returns metadata about the search performed, metadata about the custom search
   * engine used for the search, and the search results. (cse.listCse)
   *
   * @param string $q Query
   * @param array $optParams Optional parameters.
   *
   * @opt_param string sort The sort expression to apply to the results
   * @opt_param string orTerms Provides additional search terms to check for in a
   * document, where each document in the search results must contain at least one
   * of the additional search terms
   * @opt_param string highRange Creates a range in form as_nlo value..as_nhi
   * value and attempts to append it to query
   * @opt_param string num Number of search results to return
   * @opt_param string cr Country restrict(s).
   * @opt_param string imgType Returns images of a type, which can be one of:
   * clipart, face, lineart, news, and photo.
   * @opt_param string gl Geolocation of end user.
   * @opt_param string relatedSite Specifies that all search results should be
   * pages that are related to the specified URL
   * @opt_param string searchType Specifies the search type: image.
   * @opt_param string fileType Returns images of a specified type. Some of the
   * allowed values are: bmp, gif, png, jpg, svg, pdf, ...
   * @opt_param string start The index of the first result to return
   * @opt_param string imgDominantColor Returns images of a specific dominant
   * color: yellow, green, teal, blue, purple, pink, white, gray, black and brown.
   * @opt_param string lr The language restriction for the search results
   * @opt_param string siteSearch Specifies all search results should be pages
   * from a given site
   * @opt_param string cref The URL of a linked custom search engine
   * @opt_param string dateRestrict Specifies all search results are from a time
   * period
   * @opt_param string safe Search safety level
   * @opt_param string c2coff Turns off the translation between zh-CN and zh-TW.
   * @opt_param string googlehost The local Google domain to use to perform the
   * search.
   * @opt_param string hq Appends the extra query terms to the query.
   * @opt_param string exactTerms Identifies a phrase that all documents in the
   * search results must contain
   * @opt_param string hl Sets the user interface language.
   * @opt_param string lowRange Creates a range in form as_nlo value..as_nhi value
   * and attempts to append it to query
   * @opt_param string imgSize Returns images of a specified size, where size can
   * be one of: icon, small, medium, large, xlarge, xxlarge, and huge.
   * @opt_param string imgColorType Returns black and white, grayscale, or color
   * images: mono, gray, and color.
   * @opt_param string rights Filters based on licensing. Supported values
   * include: cc_publicdomain, cc_attribute, cc_sharealike, cc_noncommercial,
   * cc_nonderived and combinations of these.
   * @opt_param string excludeTerms Identifies a word or phrase that should not
   * appear in any documents in the search results
   * @opt_param string filter Controls turning on or off the duplicate content
   * filter.
   * @opt_param string linkSite Specifies that all search results should contain a
   * link to a particular URL
   * @opt_param string cx The custom search engine ID to scope this search query
   * @opt_param string siteSearchFilter Controls whether to include or exclude
   * results from the site named in the as_sitesearch parameter
   * @return Google_Service_Customsearch_Search
   */
  public function listCse($q, $optParams = array())
  {
    $params = array('q' => $q);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Customsearch_Search");
  }
}




class Google_Service_Customsearch_Context extends Google_Collection
{
  protected $collection_key = 'facets';
  protected $internal_gapi_mappings = array(
  );
  protected $facetsType = 'Google_Service_Customsearch_ContextFacets';
  protected $facetsDataType = 'array';
  public $title;


  public function setFacets($facets)
  {
    $this->facets = $facets;
  }
  public function getFacets()
  {
    return $this->facets;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_Customsearch_ContextFacets extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "labelWithOp" => "label_with_op",
  );
  public $anchor;
  public $label;
  public $labelWithOp;


  public function setAnchor($anchor)
  {
    $this->anchor = $anchor;
  }
  public function getAnchor()
  {
    return $this->anchor;
  }
  public function setLabel($label)
  {
    $this->label = $label;
  }
  public function getLabel()
  {
    return $this->label;
  }
  public function setLabelWithOp($labelWithOp)
  {
    $this->labelWithOp = $labelWithOp;
  }
  public function getLabelWithOp()
  {
    return $this->labelWithOp;
  }
}

class Google_Service_Customsearch_Promotion extends Google_Collection
{
  protected $collection_key = 'bodyLines';
  protected $internal_gapi_mappings = array(
  );
  protected $bodyLinesType = 'Google_Service_Customsearch_PromotionBodyLines';
  protected $bodyLinesDataType = 'array';
  public $displayLink;
  public $htmlTitle;
  protected $imageType = 'Google_Service_Customsearch_PromotionImage';
  protected $imageDataType = '';
  public $link;
  public $title;


  public function setBodyLines($bodyLines)
  {
    $this->bodyLines = $bodyLines;
  }
  public function getBodyLines()
  {
    return $this->bodyLines;
  }
  public function setDisplayLink($displayLink)
  {
    $this->displayLink = $displayLink;
  }
  public function getDisplayLink()
  {
    return $this->displayLink;
  }
  public function setHtmlTitle($htmlTitle)
  {
    $this->htmlTitle = $htmlTitle;
  }
  public function getHtmlTitle()
  {
    return $this->htmlTitle;
  }
  public function setImage(Google_Service_Customsearch_PromotionImage $image)
  {
    $this->image = $image;
  }
  public function getImage()
  {
    return $this->image;
  }
  public function setLink($link)
  {
    $this->link = $link;
  }
  public function getLink()
  {
    return $this->link;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_Customsearch_PromotionBodyLines extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $htmlTitle;
  public $link;
  public $title;
  public $url;


  public function setHtmlTitle($htmlTitle)
  {
    $this->htmlTitle = $htmlTitle;
  }
  public function getHtmlTitle()
  {
    return $this->htmlTitle;
  }
  public function setLink($link)
  {
    $this->link = $link;
  }
  public function getLink()
  {
    return $this->link;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
}

class Google_Service_Customsearch_PromotionImage extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $source;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setSource($source)
  {
    $this->source = $source;
  }
  public function getSource()
  {
    return $this->source;
  }
  public function setWidth($width)
  {
    $this->width = $width;
  }
  public function getWidth()
  {
    return $this->width;
  }
}

class Google_Service_Customsearch_Query extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $count;
  public $cr;
  public $cref;
  public $cx;
  public $dateRestrict;
  public $disableCnTwTranslation;
  public $exactTerms;
  public $excludeTerms;
  public $fileType;
  public $filter;
  public $gl;
  public $googleHost;
  public $highRange;
  public $hl;
  public $hq;
  public $imgColorType;
  public $imgDominantColor;
  public $imgSize;
  public $imgType;
  public $inputEncoding;
  public $language;
  public $linkSite;
  public $lowRange;
  public $orTerms;
  public $outputEncoding;
  public $relatedSite;
  public $rights;
  public $safe;
  public $searchTerms;
  public $searchType;
  public $siteSearch;
  public $siteSearchFilter;
  public $sort;
  public $startIndex;
  public $startPage;
  public $title;
  public $totalResults;


  public function setCount($count)
  {
    $this->count = $count;
  }
  public function getCount()
  {
    return $this->count;
  }
  public function setCr($cr)
  {
    $this->cr = $cr;
  }
  public function getCr()
  {
    return $this->cr;
  }
  public function setCref($cref)
  {
    $this->cref = $cref;
  }
  public function getCref()
  {
    return $this->cref;
  }
  public function setCx($cx)
  {
    $this->cx = $cx;
  }
  public function getCx()
  {
    return $this->cx;
  }
  public function setDateRestrict($dateRestrict)
  {
    $this->dateRestrict = $dateRestrict;
  }
  public function getDateRestrict()
  {
    return $this->dateRestrict;
  }
  public function setDisableCnTwTranslation($disableCnTwTranslation)
  {
    $this->disableCnTwTranslation = $disableCnTwTranslation;
  }
  public function getDisableCnTwTranslation()
  {
    return $this->disableCnTwTranslation;
  }
  public function setExactTerms($exactTerms)
  {
    $this->exactTerms = $exactTerms;
  }
  public function getExactTerms()
  {
    return $this->exactTerms;
  }
  public function setExcludeTerms($excludeTerms)
  {
    $this->excludeTerms = $excludeTerms;
  }
  public function getExcludeTerms()
  {
    return $this->excludeTerms;
  }
  public function setFileType($fileType)
  {
    $this->fileType = $fileType;
  }
  public function getFileType()
  {
    return $this->fileType;
  }
  public function setFilter($filter)
  {
    $this->filter = $filter;
  }
  public function getFilter()
  {
    return $this->filter;
  }
  public function setGl($gl)
  {
    $this->gl = $gl;
  }
  public function getGl()
  {
    return $this->gl;
  }
  public function setGoogleHost($googleHost)
  {
    $this->googleHost = $googleHost;
  }
  public function getGoogleHost()
  {
    return $this->googleHost;
  }
  public function setHighRange($highRange)
  {
    $this->highRange = $highRange;
  }
  public function getHighRange()
  {
    return $this->highRange;
  }
  public function setHl($hl)
  {
    $this->hl = $hl;
  }
  public function getHl()
  {
    return $this->hl;
  }
  public function setHq($hq)
  {
    $this->hq = $hq;
  }
  public function getHq()
  {
    return $this->hq;
  }
  public function setImgColorType($imgColorType)
  {
    $this->imgColorType = $imgColorType;
  }
  public function getImgColorType()
  {
    return $this->imgColorType;
  }
  public function setImgDominantColor($imgDominantColor)
  {
    $this->imgDominantColor = $imgDominantColor;
  }
  public function getImgDominantColor()
  {
    return $this->imgDominantColor;
  }
  public function setImgSize($imgSize)
  {
    $this->imgSize = $imgSize;
  }
  public function getImgSize()
  {
    return $this->imgSize;
  }
  public function setImgType($imgType)
  {
    $this->imgType = $imgType;
  }
  public function getImgType()
  {
    return $this->imgType;
  }
  public function setInputEncoding($inputEncoding)
  {
    $this->inputEncoding = $inputEncoding;
  }
  public function getInputEncoding()
  {
    return $this->inputEncoding;
  }
  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
  public function setLinkSite($linkSite)
  {
    $this->linkSite = $linkSite;
  }
  public function getLinkSite()
  {
    return $this->linkSite;
  }
  public function setLowRange($lowRange)
  {
    $this->lowRange = $lowRange;
  }
  public function getLowRange()
  {
    return $this->lowRange;
  }
  public function setOrTerms($orTerms)
  {
    $this->orTerms = $orTerms;
  }
  public function getOrTerms()
  {
    return $this->orTerms;
  }
  public function setOutputEncoding($outputEncoding)
  {
    $this->outputEncoding = $outputEncoding;
  }
  public function getOutputEncoding()
  {
    return $this->outputEncoding;
  }
  public function setRelatedSite($relatedSite)
  {
    $this->relatedSite = $relatedSite;
  }
  public function getRelatedSite()
  {
    return $this->relatedSite;
  }
  public function setRights($rights)
  {
    $this->rights = $rights;
  }
  public function getRights()
  {
    return $this->rights;
  }
  public function setSafe($safe)
  {
    $this->safe = $safe;
  }
  public function getSafe()
  {
    return $this->safe;
  }
  public function setSearchTerms($searchTerms)
  {
    $this->searchTerms = $searchTerms;
  }
  public function getSearchTerms()
  {
    return $this->searchTerms;
  }
  public function setSearchType($searchType)
  {
    $this->searchType = $searchType;
  }
  public function getSearchType()
  {
    return $this->searchType;
  }
  public function setSiteSearch($siteSearch)
  {
    $this->siteSearch = $siteSearch;
  }
  public function getSiteSearch()
  {
    return $this->siteSearch;
  }
  public function setSiteSearchFilter($siteSearchFilter)
  {
    $this->siteSearchFilter = $siteSearchFilter;
  }
  public function getSiteSearchFilter()
  {
    return $this->siteSearchFilter;
  }
  public function setSort($sort)
  {
    $this->sort = $sort;
  }
  public function getSort()
  {
    return $this->sort;
  }
  public function setStartIndex($startIndex)
  {
    $this->startIndex = $startIndex;
  }
  public function getStartIndex()
  {
    return $this->startIndex;
  }
  public function setStartPage($startPage)
  {
    $this->startPage = $startPage;
  }
  public function getStartPage()
  {
    return $this->startPage;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setTotalResults($totalResults)
  {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults()
  {
    return $this->totalResults;
  }
}

class Google_Service_Customsearch_Result extends Google_Collection
{
  protected $collection_key = 'labels';
  protected $internal_gapi_mappings = array(
  );
  public $cacheId;
  public $displayLink;
  public $fileFormat;
  public $formattedUrl;
  public $htmlFormattedUrl;
  public $htmlSnippet;
  public $htmlTitle;
  protected $imageType = 'Google_Service_Customsearch_ResultImage';
  protected $imageDataType = '';
  public $kind;
  protected $labelsType = 'Google_Service_Customsearch_ResultLabels';
  protected $labelsDataType = 'array';
  public $link;
  public $mime;
  public $pagemap;
  public $snippet;
  public $title;


  public function setCacheId($cacheId)
  {
    $this->cacheId = $cacheId;
  }
  public function getCacheId()
  {
    return $this->cacheId;
  }
  public function setDisplayLink($displayLink)
  {
    $this->displayLink = $displayLink;
  }
  public function getDisplayLink()
  {
    return $this->displayLink;
  }
  public function setFileFormat($fileFormat)
  {
    $this->fileFormat = $fileFormat;
  }
  public function getFileFormat()
  {
    return $this->fileFormat;
  }
  public function setFormattedUrl($formattedUrl)
  {
    $this->formattedUrl = $formattedUrl;
  }
  public function getFormattedUrl()
  {
    return $this->formattedUrl;
  }
  public function setHtmlFormattedUrl($htmlFormattedUrl)
  {
    $this->htmlFormattedUrl = $htmlFormattedUrl;
  }
  public function getHtmlFormattedUrl()
  {
    return $this->htmlFormattedUrl;
  }
  public function setHtmlSnippet($htmlSnippet)
  {
    $this->htmlSnippet = $htmlSnippet;
  }
  public function getHtmlSnippet()
  {
    return $this->htmlSnippet;
  }
  public function setHtmlTitle($htmlTitle)
  {
    $this->htmlTitle = $htmlTitle;
  }
  public function getHtmlTitle()
  {
    return $this->htmlTitle;
  }
  public function setImage(Google_Service_Customsearch_ResultImage $image)
  {
    $this->image = $image;
  }
  public function getImage()
  {
    return $this->image;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  public function getLabels()
  {
    return $this->labels;
  }
  public function setLink($link)
  {
    $this->link = $link;
  }
  public function getLink()
  {
    return $this->link;
  }
  public function setMime($mime)
  {
    $this->mime = $mime;
  }
  public function getMime()
  {
    return $this->mime;
  }
  public function setPagemap($pagemap)
  {
    $this->pagemap = $pagemap;
  }
  public function getPagemap()
  {
    return $this->pagemap;
  }
  public function setSnippet($snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_Customsearch_ResultImage extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $byteSize;
  public $contextLink;
  public $height;
  public $thumbnailHeight;
  public $thumbnailLink;
  public $thumbnailWidth;
  public $width;


  public function setByteSize($byteSize)
  {
    $this->byteSize = $byteSize;
  }
  public function getByteSize()
  {
    return $this->byteSize;
  }
  public function setContextLink($contextLink)
  {
    $this->contextLink = $contextLink;
  }
  public function getContextLink()
  {
    return $this->contextLink;
  }
  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setThumbnailHeight($thumbnailHeight)
  {
    $this->thumbnailHeight = $thumbnailHeight;
  }
  public function getThumbnailHeight()
  {
    return $this->thumbnailHeight;
  }
  public function setThumbnailLink($thumbnailLink)
  {
    $this->thumbnailLink = $thumbnailLink;
  }
  public function getThumbnailLink()
  {
    return $this->thumbnailLink;
  }
  public function setThumbnailWidth($thumbnailWidth)
  {
    $this->thumbnailWidth = $thumbnailWidth;
  }
  public function getThumbnailWidth()
  {
    return $this->thumbnailWidth;
  }
  public function setWidth($width)
  {
    $this->width = $width;
  }
  public function getWidth()
  {
    return $this->width;
  }
}

class Google_Service_Customsearch_ResultLabels extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "labelWithOp" => "label_with_op",
  );
  public $displayName;
  public $labelWithOp;
  public $name;


  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  public function setLabelWithOp($labelWithOp)
  {
    $this->labelWithOp = $labelWithOp;
  }
  public function getLabelWithOp()
  {
    return $this->labelWithOp;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Customsearch_ResultPagemap extends Google_Model
{
}

class Google_Service_Customsearch_ResultPagemapItemElement extends Google_Model
{
}

class Google_Service_Customsearch_Search extends Google_Collection
{
  protected $collection_key = 'promotions';
  protected $internal_gapi_mappings = array(
  );
  protected $contextType = 'Google_Service_Customsearch_Context';
  protected $contextDataType = '';
  protected $itemsType = 'Google_Service_Customsearch_Result';
  protected $itemsDataType = 'array';
  public $kind;
  protected $promotionsType = 'Google_Service_Customsearch_Promotion';
  protected $promotionsDataType = 'array';
  protected $queriesType = 'Google_Service_Customsearch_Query';
  protected $queriesDataType = 'map';
  protected $searchInformationType = 'Google_Service_Customsearch_SearchSearchInformation';
  protected $searchInformationDataType = '';
  protected $spellingType = 'Google_Service_Customsearch_SearchSpelling';
  protected $spellingDataType = '';
  protected $urlType = 'Google_Service_Customsearch_SearchUrl';
  protected $urlDataType = '';


  public function setContext(Google_Service_Customsearch_Context $context)
  {
    $this->context = $context;
  }
  public function getContext()
  {
    return $this->context;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPromotions($promotions)
  {
    $this->promotions = $promotions;
  }
  public function getPromotions()
  {
    return $this->promotions;
  }
  public function setQueries($queries)
  {
    $this->queries = $queries;
  }
  public function getQueries()
  {
    return $this->queries;
  }
  public function setSearchInformation(Google_Service_Customsearch_SearchSearchInformation $searchInformation)
  {
    $this->searchInformation = $searchInformation;
  }
  public function getSearchInformation()
  {
    return $this->searchInformation;
  }
  public function setSpelling(Google_Service_Customsearch_SearchSpelling $spelling)
  {
    $this->spelling = $spelling;
  }
  public function getSpelling()
  {
    return $this->spelling;
  }
  public function setUrl(Google_Service_Customsearch_SearchUrl $url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
}

class Google_Service_Customsearch_SearchQueries extends Google_Model
{
}

class Google_Service_Customsearch_SearchSearchInformation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $formattedSearchTime;
  public $formattedTotalResults;
  public $searchTime;
  public $totalResults;


  public function setFormattedSearchTime($formattedSearchTime)
  {
    $this->formattedSearchTime = $formattedSearchTime;
  }
  public function getFormattedSearchTime()
  {
    return $this->formattedSearchTime;
  }
  public function setFormattedTotalResults($formattedTotalResults)
  {
    $this->formattedTotalResults = $formattedTotalResults;
  }
  public function getFormattedTotalResults()
  {
    return $this->formattedTotalResults;
  }
  public function setSearchTime($searchTime)
  {
    $this->searchTime = $searchTime;
  }
  public function getSearchTime()
  {
    return $this->searchTime;
  }
  public function setTotalResults($totalResults)
  {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults()
  {
    return $this->totalResults;
  }
}

class Google_Service_Customsearch_SearchSpelling extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $correctedQuery;
  public $htmlCorrectedQuery;


  public function setCorrectedQuery($correctedQuery)
  {
    $this->correctedQuery = $correctedQuery;
  }
  public function getCorrectedQuery()
  {
    return $this->correctedQuery;
  }
  public function setHtmlCorrectedQuery($htmlCorrectedQuery)
  {
    $this->htmlCorrectedQuery = $htmlCorrectedQuery;
  }
  public function getHtmlCorrectedQuery()
  {
    return $this->htmlCorrectedQuery;
  }
}

class Google_Service_Customsearch_SearchUrl extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $template;
  public $type;


  public function setTemplate($template)
  {
    $this->template = $template;
  }
  public function getTemplate()
  {
    return $this->template;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}
