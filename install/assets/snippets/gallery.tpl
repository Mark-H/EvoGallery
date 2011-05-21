//<?php
/**
 * EvoGallery
 *
 * Generates sortable listing of galleries, with full templating control
 *
 * @category	snippet
 * @version 	1.0 Beta 1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@properties
 * @internal	@modx_category Content
 */

/*---------------------------------------------------------------------------
* EvoGallery Snippet - Generates sortable listing of
* galleries, with full templating control
*--------------------------------------------------------------------------*/
//
// System settings
//

$params['display'] = isset($display) ? $display : 'images';
	// Have the snippet output either a list of galleries within the specified doc Id, a list of images within a gallery, or a single image based on a pic Id
	// Possible values: galleries, images, single

$params['type'] = isset($type) ? $type : 'simple-list';
	// Output type, if specified, the snippet will automatically load the required javascript
	// Current types: simple-list, single, jquery-cycle

$params['includeAssets'] = isset($includeAssets) ? intval($includeAssets) : 1;
	// Register external scripts and CSS files required by the specified gallery type
	// If set to 0, these will need to be included manually in the document <head>

$params['picId'] = isset($picId) ? $picId : $_REQUEST['picId'];
	// ID of specific pic to show when displaying by a single image

$params['docId'] = isset($docId) ? $docId : $modx->documentIdentifier;
	// Document ID for which to display gallery (default: document from which snippet was called)
        // Multiple document id's can be specified by commas (no spaces), or * for all documents

$params['gallerySortBy'] = isset($gallerySortBy) ? $gallerySortBy : 'menuindex';
	// Galleries sort order (possible fields: id, pagetitle, longtitle, description, alias, pub_date, introtext,
	// editedby, editedon, publishedon, publishedby, menutitle) or RAND()

$params['gallerySortDir'] = isset($gallerySortDir) ? $gallerySortDir : 'ASC';
	// Direction to sort the galleries ASC or DESC

$params['ignoreHidden'] = isset($ignoreHidden) ? $ignoreHidden : 0;
	// Display documents marked as hidden in the gallery listing

$params['excludeDocs'] = isset($excludeDocs) ? $excludeDocs : 0;
	// Prevent the specified documents from showing in the gallery listing
        // Multiple document id's can be specified by commas (no spaces)

$params['sortBy'] = isset($sortBy) ? $sortBy : 'sortorder';
	// Sort items by field (possible fields: id, content_id, filename, title, description, sortorder) or RAND()

$params['sortDir'] = isset($sortDir) ? $sortDir : 'ASC';
	// Direction to sort the items ASC or DESC

$params['limit'] = isset($limit) ? $limit : '';
	// Limit the number of items to display

$params['tags'] = isset($tags) ? $tags : '';
	// Comma delimited set of tags to filter results by

$params['tagMode'] = isset($tagMode) ? $tagMode : 'AND';
	// Search mode for tag: AND or OR

$params['tpl'] = isset($tpl) ? $tpl : '';
	// Chunk template for the outer gallery template (defaults to tpl.default.txt for selected type)
	// Placeholders: items

$params['itemTpl'] = isset($itemTpl) ? $itemTpl : '';
	// Chunk template for each thumbnail/image in the gallery (defaults to tpl.item.default.txt for selected type)

$params['itemTplFirst'] = isset($itemTplFirst) ? $itemTplFirst : '';
	// Chunk template for last thumbnail/image in the gallery (defaults to tpl.item.first.txt for selected type)

$params['itemTplLast'] = isset($itemTplLast) ? $itemTplLast : '';
	// Chunk template for last thumbnail/image in the gallery (defaults to tpl.item.last.txt for selected type)

$params['itemTplAlt'] = isset($itemTplAlt) ? $itemTplAlt : '';
	// Chunk template for alternate thumbnail/image in the gallery (defaults to tpl.item.alt.txt for selected type)

$params['itemAltNum'] = isset($itemAltNum) ? $itemAltNum : '2';
	// Modifier for the alternate thumbnail/image (defaults to every second item)

$params['galleriesUrl'] = isset($galleriesUrl) ? $galleriesUrl : $modx->config['base_url'] . 'assets/galleries/';
	// URL to the galleries directory (should contain folders with the Id of the document, with a thumbs/ folder within each document's gallery)

$params['galleriesPath'] = isset($galleriesPath) ? $galleriesPath : $modx->config['base_path'] . 'assets/galleries/';
	// Path to the galleries directory

$params['snippetUrl'] = isset($snippetUrl) ? $snippetUrl : $modx->config['base_url'] . 'assets/snippets/evogallery/';
	// URL to the snippet directory

$params['snippetPath'] = isset($snippetPath) ? $snippetPath : $modx->config['base_path'] . 'assets/snippets/evogallery/';
	// Path to the snippet directory

/*--------------------------------------------------------------------------*/

include_once($params['snippetPath'] . 'classes/gallery.class.inc.php');

if (!class_exists('PHxParser'))
	include_once($params['snippetPath'] . 'classes/phx.parser.class.inc.php');

if (class_exists('Gallery'))
	$gal = new Gallery($params);
else
	$modx->logEvent(1, 3, 'Error loading gallery snippet');

return $gal->execute();