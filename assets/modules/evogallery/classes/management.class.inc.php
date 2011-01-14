<?php
/*---------------------------------------------------------------------------
* GalleryManagement Class - Contains functions for: viewing, uploading, and
*                           editing product galleries.
*
* Add the following after session_name($site_sessionname); in config.inc.php

	if (isset($_REQUEST[$site_sessionname])) {
		session_id($_REQUEST[$site_sessionname]);
	}

* Some server configurations will require the following inside the .htaccess
* file within the manager directory/

	<IfModule mod_security.c>
	SecFilterEngine Off
	SecFilterScanPOST Off
	</IfModule>

*--------------------------------------------------------------------------*/
class GalleryManagement
{
	var $config;  // Array containing module configuration values

	/**
	* Class constructor, set configuration parameters
	*/
	function GalleryManagement($params)
	{
		global $modx;

		$this->config = $params;

		$this->mainTemplate = 'template.html.tpl';
		$this->headerTemplate = 'header.html.tpl';
		$this->listingTemplate = 'gallery_listing.html.tpl';
		$this->uploadTemplate = 'gallery_upload.html.tpl';
		$this->editTemplate = 'image_edit.html.tpl';

		$this->galleriesTable = 'portfolio_galleries';

		$this->current = (($_SERVER['HTTPS'] == 'on') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $modx->config['base_url'] . 'manager/index.php';
		$this->a = $_GET['a'];
		$this->id = $_GET['id'];
		$this->thumbHandler = '../assets/modules/evogallery/thumb_handler.php?';
	}

	/**
	* Determine what action was requested and process request
	*/
	function execute()
	{
		global $modx;

		$old_umask = umask(0);

		$tpl = file_get_contents($this->config['modulePath'] . 'templates/' . $this->mainTemplate);

		if (isset($_GET['edit']))
		{
			$tpl = $this->editImage();  // Display single image edit form
		}
		else
		{
			$output = $this->viewListing();  // View/uplaod galleries and gallery images
    		$tpl = str_replace('[+base_url+]', $modx->config['base_url'], $tpl);
    		$tpl = str_replace('[+content+]', $output, $tpl);
		}


		umask($old_umask);

		return $tpl;
	}

	/**
	* Edit an image's details
	*/
	function editImage()
	{
		global $modx;

		$this_page = $this->current . '?a=' . $this->a . '&amp;id=' . $this->id;

		$contentId = isset($_GET['content_id']) ? intval($_GET['content_id']) : $this->config['docId'];
		$filename = isset($_GET['edit']) ? $modx->db->escape(urldecode($_GET['edit'])) : '';

		$tpl = file_get_contents($this->config['modulePath'] . 'templates/' . $this->editTemplate);

		$result = $modx->db->select('id, title, description, keywords', $modx->getFullTableName($this->galleriesTable), "content_id = '" . $contentId . "' AND filename = '" . $filename . "'");
		$info = $modx->fetchRow($result);

        /* Get keyword tags */
		$sql = "SELECT `keywords` FROM ".$modx->getFullTableName($this->galleriesTable);

		$keywords = $modx->dbQuery($sql);
		$all_docs = $modx->db->makeArray( $keywords );

		$foundTags = array();
		foreach ($all_docs as $theDoc) {
			$theTags = explode(",", $theDoc['keywords']);
			foreach ($theTags as $t) {
				$foundTags[trim($t)]++;
			}
		}

		// Sort the TV values (case insensitively)
		uksort($foundTags, 'strcasecmp');

		$lis = '';
		foreach($foundTags as $t=>$c) {
		    if($t != ''){
    			$lis .= '<li title="Used '.$c.' times">'.htmlentities($t, ENT_QUOTES, $modx->config['modx_charset'], false).($display_count?' ('.$c.')':'').'</li>';
		    }
		}

		$keyword_tagList = '<ul class="mmTagList" id="keyword_tagList">'.$lis.'</ul>';

		$tpl = str_replace('[+action+]', $this_page . '&action=view&content_id=' . $contentId, $tpl);
		$tpl = str_replace('[+id+]', $info['id'], $tpl);
		$tpl = str_replace('[+filename+]', $filename, $tpl);
		$tpl = str_replace('[+image+]', $this->thumbHandler . "content_id=" . $contentId . "&filename=" . urlencode($filename), $tpl);
		$tpl = str_replace('[+title+]', $info['title'], $tpl);
		$tpl = str_replace('[+description+]', $info['description'], $tpl);
		$tpl = str_replace('[+keywords+]', $info['keywords'], $tpl);
		$tpl = str_replace('[+keyword_tagList+]', $keyword_tagList, $tpl);

		return $tpl;
	}

	/**
	* Display a searchable/sortable listing of documents
	*/
	function viewListing()
	{
		global $modx;

		$this_page = $this->current . '?a=' . $this->a . '&id=' . $this->id;

		$tpl = file_get_contents($this->config['modulePath'] . 'templates/' . $this->listingTemplate);

		$parentId = isset($_GET['content_id']) ? intval($_GET['content_id']) : $this->config['docId'];

		// Get search filter values
		$filter = '';
		if (isset($_GET['query']))
		{
			$search = $modx->db->escape($modx->stripTags($_GET['query']));
			$filter .= "WHERE (";
			$filter .= "c.pagetitle LIKE '%" . $search . "%' OR ";
			$filter .= "c.longtitle LIKE '%" . $search . "%' OR ";
			$filter .= "c.description LIKE '%" . $search . "%' OR ";
			$filter .= "c.introtext LIKE '%" . $search . "%' OR ";
			$filter .= "c.content LIKE '%" . $search . "%' OR ";
			$filter .= "c.alias LIKE '%" . $search . "%'";
			$filter .= ")";
			$header = $this->header('Search Results');
		}
		else
		{
			$filter = "WHERE c.parent = '" . $parentId . "'";
			$header = $this->header();
		}

		$_GET['orderby'] = isset($_GET['orderby']) ? $_GET['orderby'] : 'c.menuindex';
		$_GET['orderdir'] = isset($_GET['orderdir']) ? $_GET['orderdir'] : 'ASC';

		// Check for number of records per page preferences and define global setting
		if (is_numeric($_GET['pageSize']))
		{
			setcookie("pageSize", $_GET['pageSize'], time() + 3600000);
			$maxPageSize = $_GET['pageSize'];
		}
		else
		{
			if (is_numeric($_COOKIE['pageSize']))
				$maxPageSize = $_COOKIE['pageSize'];
			else
				$maxPageSize = 100;
		}
		define('MAX_DISPLAY_RECORDS_NUM', $maxPageSize);

		$table = new MakeTable();  // Instantiate a new instance of the MakeTable class

		// Get document count
		$query = "SELECT COUNT(c.id) FROM " . $modx->getFullTableName('site_content') . " AS c " . $filter;
		$numRecords = $modx->db->getValue($query);

		// Execute the main table query with MakeTable sorting and paging features
		$query = "SELECT c.id, c.pagetitle, c.longtitle, c.editedon, c.isfolder, COUNT(g.id) as photos FROM " . $modx->getFullTableName('site_content') . " AS c " .
		         "LEFT JOIN " . $modx->getFullTableName($this->galleriesTable) . " AS g ON g.content_id = c.id " .
		         $filter . " GROUP BY c.id" . $table->handleSorting() . $table->handlePaging();

		if ($ds = $modx->db->query($query))
		{
			// If the query was successful, build our table array from the rows
			while ($row = $modx->db->getRow($ds))
			{
				$documents[] = array(
					'pagetitle' => '<a href="' . $this_page . '&action=view&content_id=' . $row['id'] . '" title="Click to view/upload photos">' . $row['pagetitle'] . ' (' . $row['id'] . ')</a>',
					'longtitle' => ($row['longtitle'] != '') ? stripslashes($row['longtitle']) : '-',
					'photos' => $row['photos'],
					'editedon' => ($row['editedon'] > 0) ? strftime('%m-%d-%Y', $row['editedon']) : '-',
				);
			}
		}

		if (is_array($documents))  // Ensure data was returned
		{
			// Create the table header definition with each header providing a link to sort by that field
			$documentTableHeader = array(
				'pagetitle' => $table->prepareOrderByLink('c.pagetitle', 'Title'),
				'longtitle' => $table->prepareOrderByLink('c.longtitle', 'Long Title'),
				'photos' => $table->prepareOrderByLink('photos', '# Photos'),
				'editedon' => $table->prepareOrderByLink('c.editedon', 'Last Edited'),
			);

			$table->setActionFieldName('id');  // Field passed in link urls

			// Table styling options
			$table->setTableClass('documentsTable');
			$table->setRowHeaderClass('headerRow');
			$table->setRowRegularClass('stdRow');
			$table->setRowAlternateClass('altRow');

			// Generate the paging navigation controls
			if ($numRecords > MAX_DISPLAY_RECORDS_NUM)
				$table->createPagingNavigation($numRecords);

			$table_html = $table->create($documents, $documentTableHeader);  // Generate documents table
			$table_html = str_replace('[~~]?', $this_page . '&action=view&', $table_html);  // Create page target
		}
		elseif (isset($_GET['query']))
		{
			$table_html = '<p>There are no documents matching your criteria.</p>';  // No records were found
		}
		else
		{
			$table_html = '<p class="first">This document contains no children.</p>';
		}

		$tpl = str_replace('[+table+]', $table_html, $tpl);

		if (isset($_GET['query']))
			$tpl = str_replace('[+gallery+]', '', $tpl);
		else
			$tpl = str_replace('[+gallery+]', $this->viewGallery(), $tpl);

		return $header . $tpl;
	}

	/**
	* View/manage photos for a particular document
	*/
	function viewGallery()
	{
		global $modx;

		$this_page = $this->current . '?a=' . $this->a . '&id=' . $this->id;

		$content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : $this->config['docId'];  // Get document id

		// Verify session and retrieve document information
		$result = $modx->db->select('pagetitle, longtitle, parent', $modx->getFullTableName('site_content'), "id = '" . $content_id . "'");
		if ($modx->db->getRecordCount($result) > 0)
		{
			$info = $modx->fetchRow($result);

			$target_dir = $this->config['savePath'] . '/' . $content_id . '/';

			if (isset($_POST['cmdprev']))  // Go to previous page
			{
				if ($info['parent'] > 0)
					$modx->sendRedirect(html_entity_decode($this_page . '&action=view&content_id=' . $info['parent']));
				else
					$modx->sendRedirect(html_entity_decode($this_page . '&action=view'));
				exit(0);
			}
			elseif (isset($_POST['cmdsort']))  // Update image sort order
			{
				foreach ($_POST['sort'] as $key => $filename)
				{
					$modx->db->update("sortorder='" . $key . "'", $modx->getFullTableName($this->galleriesTable), "filename='" . urldecode($filename) . "' AND content_id='" . $content_id . "'");
				}
			}
			elseif (isset($_GET['delete']))  // Delete requested image
			{
				$rs = $modx->db->select('filename', $modx->getFullTableName($this->galleriesTable), "filename='" . urldecode($_GET['delete']) . "' AND content_id='" . $content_id . "'");
                		if ($modx->db->getRecordCount($result) > 0)
				{
					$filename = $modx->db->getValue($rs);

					if (file_exists($target_dir . 'thumbs/' . $filename))
						unlink($target_dir . 'thumbs/' . $filename);
					if (file_exists($target_dir . $filename))
						unlink($target_dir . $filename);

					// Remove record from database
					$modx->db->delete($modx->getFullTableName($this->galleriesTable), "filename='" . urldecode($_GET['delete']) . "' AND content_id='" . $content_id . "'");
				}
			}
			elseif (isset($_POST['edit']))  // Update image information
			{
				$fields['title'] = isset($_POST['title']) ? addslashes($_POST['title']) : '';
				$fields['description'] = isset($_POST['description']) ? addslashes($_POST['description']) : '';
				$fields['keywords'] = isset($_POST['keywords']) ? addslashes($_POST['keywords']) : '';
				$modx->db->update($fields, $modx->getFullTableName($this->galleriesTable), "id='" . intval($_POST['edit']) . "'");
			}

			$tpl = file_get_contents($this->config['modulePath'] . 'templates/' . $this->uploadTemplate);
			$tpl = str_replace('[+title+]', stripslashes($info['pagetitle']), $tpl);

			// Get contents of upload script and replace necessary action URL
			$upload_script = file_get_contents($this->config['modulePath'] . 'templates/upload.js.tpl');
			$upload_script = str_replace('[+self+]', urlencode(html_entity_decode($this_page . '&action=upload&content_id=' . $content_id)), $upload_script);
			$upload_script = str_replace('[+action+]', $this->current, $upload_script);
			$upload_script = str_replace('[+params+]', '"id": "' . $this->id . '", "a": "' . $this->a . '", "' . session_name() . '": "' . session_id() . '", "action": "upload", "js": "1", "content_id": "' . $content_id . '"', $upload_script);
			$upload_script = str_replace('[+base_path+]', $modx->config['base_url'] . 'assets/modules/evogallery/', $upload_script);
			$upload_script = str_replace('[+base_url+]', $modx->config['base_url'], $upload_script);
			$upload_script = str_replace('[+content_id+]', $content_id, $upload_script);
			$upload_script = str_replace('[+thumbs+]', urlencode(html_entity_decode($this->thumbHandler . 'content_id=' . $content_id)), $upload_script);
			$tpl = str_replace('[+upload_script+]', $upload_script, $tpl);

			// Read through project files directory and show thumbs
			$thumbs = '';
			$result = $modx->db->select('filename, title, description, keywords', $modx->getFullTableName($this->galleriesTable), 'content_id=' . $content_id, 'sortorder ASC');
			while ($row = $modx->fetchRow($result))
			{
//				$thumbs .= "<li><div class=\"thbButtons\"><a href=\"" . $this_page . "&action=edit&content_id=$content_id&edit=" . urlencode($row['filename']) . "\" title=\"" . stripslashes($row['filename']) . "\" class=\"edit\" rel=\"moodalbox 420 375\">Edit</a><a href=\"$this_page&action=view&content_id=$content_id&delete=" . urlencode($row['filename']) . "\" onclick=\"return Uploader.deleteConfirm()\" class=\"delete\">Delete</a></div><img src=\"" . $this->thumbHandler . "content_id=" . $content_id . "&filename=" . urlencode($row['filename']) . "\" alt=\"" . htmlentities(stripslashes($row['filename'])) . "\" class=\"thb\" /><input type=\"hidden\" name=\"sort[]\" value=\"" . urlencode($row['filename']) . "\" /></li>\n";
				$thumbs .= "<li><div class=\"thbButtons\"><a href=\"" . $this_page . "&action=edit&content_id=$content_id&edit=" . urlencode($row['filename']) . "\" class=\"edit\">Edit</a><a href=\"$this_page&action=view&content_id=$content_id&delete=" . urlencode($row['filename']) . "\" class=\"delete\">Delete</a></div><img src=\"" . $this->thumbHandler . "content_id=" . $content_id . "&filename=" . urlencode($row['filename']) . "\" alt=\"" . htmlentities(stripslashes($row['filename'])) . "\" class=\"thb\" /><input type=\"hidden\" name=\"sort[]\" value=\"" . urlencode($row['filename']) . "\" /></li>\n";
			}

			$tpl = str_replace('[+action+]', $this_page . '&action=view&content_id=' . $content_id, $tpl);
			$tpl = str_replace('[+thumbs+]', $thumbs, $tpl);

			return $tpl;
		}
	}

	/**
	* Display management header
	*/
	function header($title = '')
	{
		global $modx;

		$this_page = $this->current . '?a=' . $this->a . '&id=' . $this->id;

		$parentId = isset($_GET['content_id']) ? intval($_GET['content_id']) : $this->config['docId'];

		$tpl = file_get_contents($this->config['modulePath'] . 'templates/' . $this->headerTemplate);

		if (isset($_GET['query']))
			$search = '<label for="query">Search:</label> <input type="text" name="query" id="query" value="' . $_GET['query'] . '" />';
		else
			$search = '<label for="query">Search:</label> <input type="text" name="query" id="query" />';

		// Generate breadcrumbs
		$result = $modx->db->select('id, pagetitle, parent', $modx->getFullTableName('site_content'), 'id=' . $parentId);
		$row = $modx->fetchRow($result);
		$breadcrumbs = '<a href="' . $this_page . '&action=view&content_id=' . $row['id'] . '" title="Click to view products/categories">' . stripslashes($row['pagetitle']) . '</a>';
		while ($row['id'] > $this->config['docId'])
		{
			$row = $modx->fetchRow($modx->db->select('id, pagetitle, parent', $modx->getFullTableName('site_content'), 'id=' . $row['parent']));
			$breadcrumbs = '<a href="' . $this_page . '&action=view&content_id=' . $row['id'] . '" title="Click to view products/categories">' . stripslashes($row['pagetitle']) . '</a> &raquo; ' . $breadcrumbs;
		}

		$tpl = str_replace('[+breadcrumbs+]', $breadcrumbs, $tpl);
		$tpl = str_replace('[+search+]', $search, $tpl);
		$tpl = str_replace('[+action+]', $this_page, $tpl);
		$tpl = str_replace('[+a+]', $this->a, $tpl);
		$tpl = str_replace('[+id+]', $this->id, $tpl);

		if ($title == '')
			$tpl = str_replace('[+title+]', '', $tpl);
		else
			$tpl = str_replace('[+title+]', '<h2>' . $title . '</h2>', $tpl);

		return $tpl;
	}

	/**
	* Resize a given image
	*/
	function resizeImage($filename, $target, $target_size = 110, $target_quality = 76)
	{
		$info = @getimagesize($filename);  // Determine whether file is an image using getimagesize()
		if ($info)
		{
			if ($info[2] > 3)  // Use Imagemagick to convert other filetypes
			{
			/*
				// SWF, PSD, BMP, TIFF (intel + motorola)
				if ($info[2] == 4 || $info[2] == 5 || $info[2] == 6 || $info[2] == 7 || $info[2] == 8)
				{
					$cmd = $this->convert . " \"" . addslashes($filename) . "\" -quality $target_quality -quiet -resize $target_size" . "x" . "$target_size \"jpeg:" . addslashes($target) . "\"";
					shell_exec($cmd);
				}
			*/
			}
			else  // Use the GD library to convert jpeg, gif, and png images
			{
				switch ($info[2])  // Check image type
				{
					case 1:
						$img = @imagecreatefromgif($filename);
						break;
					case 2:
						$img = @imagecreatefromjpeg($filename);
						break;
					case 3:
						$img = @imagecreatefrompng($filename);
						break;
				}

				if (!$img) return false;  // Incompatible type

				$width = imageSX($img);
				$height = imageSY($img);
				if (!$width || !$height) return;  // Invalid width or height

				$ratio = ($width / $height);

				$new_height = $height;
				$new_width = $width;
				if ($new_height > $target_size)
				{
					$new_height = $target_size;
					$new_width = ceil($new_height * $ratio);
				}
				if ($new_width > $target_size)
				{
					$new_width = $target_size;
					$new_height = ceil($new_width / $ratio);
				}

				$new_img = imagecreatetruecolor($new_width, $new_height);
				if (!@imagefilledrectangle($new_img, 0, 0, $new_width, $new_height, 0)) return false;  // Could not fill image
				if (!@imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height)) return false;  // Could not resize image

				imagejpeg($new_img, $target, $target_quality);  // Save resulting thumbnail
				imagedestroy($new_img);
			}
		}
	}

	/**
	* Determine the number of days in a given month/year
	*/
	function checkGalleryTable()
	{
                global $modx;
                $sql = "CREATE TABLE IF NOT EXISTS " . $modx->getFullTableName($this->galleriesTable) . " (" .
			"`id` int(11) NOT NULL auto_increment PRIMARY KEY, " .
			"`content_id` int(11) NOT NULL, " .
			"`filename` varchar(255) NOT NULL, " .
			"`title` varchar(255) NOT NULL, " .
			"`description` TEXT NOT NULL, " .
			"`keywords` TEXT NOT NULL, " .
			"`sortorder` tinyint(4) NOT NULL default '0'" .
                ")";
                $modx->db->query($sql);
        }

        
}
?>
