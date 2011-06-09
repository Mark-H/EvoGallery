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
		
		$this->loadLanguage();
	}

	/**
	* Determine what action was requested and process request
	*/
	function execute()
	{
		global $modx;

		$old_umask = umask(0);


		if (isset($_GET['edit']))
		{
			$tpl = $this->editImage();  // Display single image edit form
		}
		else
		{
			$output = $this->viewListing();  // View/uplaod galleries and gallery images
    		$tplparams = array(
				'base_url' => $modx->config['base_url'],
				'content' => $output
			);
			$tpl = $this->processTemplate($this->mainTemplate, $tplparams);
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
    			$lis .= '<li title="'.sprintf($this->lang['used_times'],$c).'">'.htmlentities($t, ENT_QUOTES, $modx->config['modx_charset'], false).($display_count?' ('.$c.')':'').'</li>';
		    }
		}

		$keyword_tagList = '<ul class="mmTagList" id="keyword_tagList">'.$lis.'</ul>';

		$tplparams = array(
			'action' => $this_page . '&action=view&content_id=' . $contentId,
			'id' => $info['id'],
			'filename' => $filename,
			'image' => $this->thumbHandler . "content_id=" . $contentId . "&filename=" . urlencode($filename),
			'title' => $info['title'],
			'description' => $info['description'],
			'keywords' => $info['keywords'],
			'keyword_tagList' => $keyword_tagList
		);
				
		$tpl = $this->processTemplate($this->editTemplate, $tplparams);

		return $tpl;
	}

	/**
	* Display a searchable/sortable listing of documents
	*/
	function viewListing()
	{
		global $modx;

		$this_page = $this->current . '?a=' . $this->a . '&id=' . $this->id;

		$tplparams = array();

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
			$header = $this->header($this->lang['search_results']);
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
					'pagetitle' => '<a href="' . $this_page . '&action=view&content_id=' . $row['id'] . '" title="'.$this->lang['click_view_photos'].'">' . $row['pagetitle'] . ' (' . $row['id'] . ')</a>',
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
				'pagetitle' => $table->prepareOrderByLink('c.pagetitle', $this->lang['title']),
				'longtitle' => $table->prepareOrderByLink('c.longtitle', $this->lang['long_title']),
				'photos' => $table->prepareOrderByLink('photos', $this->lang['N_photos']),
				'editedon' => $table->prepareOrderByLink('c.editedon', $this->lang['last_edited']),
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
			$table_html = '<p>'.$this->lang['no_docs_found'].'</p>';  // No records were found
		}
		else
		{
			$table_html = '<p class="first">'.$this->lang['no_children'].'</p>';
		}

		$tplparams['table'] =  $table_html;

		if (isset($_GET['query']))
			$tplparams['gallery'] = '';
		else
			$tplparams['gallery'] = $this->viewGallery();
		
		$tpl = $this->processTemplate($this->listingTemplate, $tplparams);
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
				$sortnum = 0; 
				foreach ($_POST['sort'] as $key => $filename)
				{
					$sortnum++; 
					$modx->db->update("sortorder='" . $sortnum . "'", $modx->getFullTableName($this->galleriesTable), "filename='" . urldecode($filename) . "' AND content_id='" . $content_id . "'");
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

			// Get contents of upload script and replace necessary action URL
			$tplparams = array(
				'self' => urlencode(html_entity_decode($this_page . '&action=upload&content_id=' . $content_id)),
				'action' => $this->current,
				'params' => '"id": "' . $this->id . '", "a": "' . $this->a . '", "' . session_name() . '": "' . session_id() . '", "action": "upload", "js": "1", "content_id": "' . $content_id . '"',
				'base_path' => $modx->config['base_url'] . 'assets/modules/evogallery/',
				'base_url' => $modx->config['base_url'],
				'content_id' => $content_id,
				'thumbs' => urlencode(html_entity_decode($this->thumbHandler . 'content_id=' . $content_id)),
				'upload_maxsize' => $modx->config['upload_maxsize']
			);

			$upload_script = $this->processTemplate('upload.js.tpl', $tplparams);

			$tplparams = array(
				'title' => stripslashes($info['pagetitle']),
				'upload_script' => $upload_script
			);


			// Read through project files directory and show thumbs
			$thumbs = '';
			$result = $modx->db->select('filename, title, description, keywords', $modx->getFullTableName($this->galleriesTable), 'content_id=' . $content_id, 'sortorder ASC');
			while ($row = $modx->fetchRow($result))
			{
//				$thumbs .= "<li><div class=\"thbButtons\"><a href=\"" . $this_page . "&action=edit&content_id=$content_id&edit=" . urlencode($row['filename']) . "\" title=\"" . stripslashes($row['filename']) . "\" class=\"edit\" rel=\"moodalbox 420 375\">Edit</a><a href=\"$this_page&action=view&content_id=$content_id&delete=" . urlencode($row['filename']) . "\" onclick=\"return Uploader.deleteConfirm()\" class=\"delete\">Delete</a></div><img src=\"" . $this->thumbHandler . "content_id=" . $content_id . "&filename=" . urlencode($row['filename']) . "\" alt=\"" . htmlentities(stripslashes($row['filename'])) . "\" class=\"thb\" /><input type=\"hidden\" name=\"sort[]\" value=\"" . urlencode($row['filename']) . "\" /></li>\n";
				$thumbs .= "<li><div class=\"thbButtons\"><a href=\"" . $this_page . "&action=edit&content_id=$content_id&edit=" . urlencode($row['filename']) . "\" class=\"edit\">".$this->lang['edit']."</a><a href=\"$this_page&action=view&content_id=$content_id&delete=" . urlencode($row['filename']) . "\" class=\"delete\">".$this->lang['delete']."</a></div><img src=\"" . $this->thumbHandler . "content_id=" . $content_id . "&filename=" . urlencode($row['filename']) . "\" alt=\"" . htmlentities(stripslashes($row['filename'])) . "\" class=\"thb\" /><input type=\"hidden\" name=\"sort[]\" value=\"" . urlencode($row['filename']) . "\" /></li>\n";
			}

			$tplparams['action'] = $this_page . '&action=view&content_id=' . $content_id;
			$tplparams['thumbs'] = $thumbs;

			$tpl = $this->processTemplate($this->uploadTemplate, $tplparams);

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

		if (isset($_GET['query']))
			$search = '<label for="query">'.$this->lang['search'].':</label> <input type="text" name="query" id="query" value="' . $_GET['query'] . '" />';
		else
			$search = '<label for="query">'.$this->lang['search'].':</label> <input type="text" name="query" id="query" />';

		// Generate breadcrumbs
		$result = $modx->db->select('id, pagetitle, parent', $modx->getFullTableName('site_content'), 'id=' . $parentId);
		$row = $modx->fetchRow($result);
		$breadcrumbs = '<a href="' . $this_page . '&action=view&content_id=' . $row['id'] . '" title="'.$this->lang['click_view_categories'].'">' . stripslashes($row['pagetitle']) . '</a>';
		while ($row['id'] > $this->config['docId'])
		{
			$row = $modx->fetchRow($modx->db->select('id, pagetitle, parent', $modx->getFullTableName('site_content'), 'id=' . $row['parent']));
			$breadcrumbs = '<a href="' . $this_page . '&action=view&content_id=' . $row['id'] . '" title="'.$this->lang['click_view_categories'].'">' . stripslashes($row['pagetitle']) . '</a> &raquo; ' . $breadcrumbs;
		}

		$tplparams = array(
			'breadcrumbs' => $breadcrumbs,
			'search' => $search,
			'action' => $this_page,
			'a' => $this->a,
			'id' => $this->id
		);

		if ($title == '')
			$tplparams['title'] = '';
		else
			$tplparams['title'] = '<h2>' . $title . '</h2>';

		$tpl = $this->processTemplate($this->headerTemplate, $tplparams);

		return $tpl;
	}

	/**
	* Resize a given image
	*/
	function resizeImage($filename, $target, $params)
	{
		if (!class_exists('phpthumb'))
		{
			include 'classes/phpthumb/phpthumb.class.php';
			include 'classes/phpthumb/phpThumb.config.php';
		}
		
		$phpthumb = new phpThumb();
			
		if (!empty($PHPTHUMB_CONFIG))
		{
			foreach ($PHPTHUMB_CONFIG as $key => $value)
			{
				$keyname = 'config_'.$key;
				$phpthumb->setParameter($keyname, $value);
			}
		}
		foreach($params as $key=>$value)
			$phpthumb->setParameter($key,$value);
		$phpthumb->setSourceFilename($filename);
		// generate & output thumbnail
		if ($phpthumb->GenerateThumbnail())
			$phpthumb->RenderToFile($target);
		unset($phpthumb);
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
		
	/**
	* Load language file
	*/
	function loadLanguage()
	{
		global $modx;
		$langpath = $this->config['modulePath'].'lang/';
		//First load english lang by defaule
		$fname = $langpath.'english.inc.php';
		if (file_exists($fname))
		{
			include($fname);
		}
		//And now load current lang file
		$fname = $langpath.$modx->config['manager_language'].'.inc.php';
		if (file_exists($fname))
		{
			include($fname);
		}
		$this->lang = $_lang;
		unset($_lang);
	}
    
	/**
	* Replace placeholders in template
	*/
	function processTemplate($tplfile, $params)
	{
		$tpl = file_get_contents($this->config['modulePath'] . 'templates/' . $tplfile);
		//Parse placeholders
		foreach($params as $key=>$value)
		{
			$tpl = str_replace('[+'.$key.'+]', $value, $tpl);
		}
		//Parse lang placeholders
		foreach ($this->lang as $key=>$value)
		{
			$tpl = str_replace('[+lang.'.$key.'+]', $value, $tpl);
		}
		return $tpl;
	}
	
	function executeAction()
	{
		switch($_REQUEST['action'])
		{
			case 'upload':
				return $this->uploadFile();
				break;
		}
	}
	
	function getPhpthumbConfig($params)
	{
		$params_arr = explode(',',$params);
		$result = array();
		$fltr = array();
		foreach($params_arr as $param)
		{
			list($key,$value) = explode('#',$param);
			if (strpos($key,'fltr')!==false)
			{
				$key = rtrim($key,'[]');
				$fltr[] = $value;
			} else
				$result[$key] = $value;
		}
		if (sizeof($fltr))
			$result['fltr'] = $fltr;
		return $result;	
	}
	
	function uploadFile()
	{
		global $modx;
		
		if (is_uploaded_file($_FILES['Filedata']['tmp_name'])){
			$content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : $params['docId'];  // Get document id3_get_frame_long_name(string frameId)
			$target_dir = $modx->config['base_path'].$this->config['savePath'] . '/' . $content_id . '/';
			$target_fname = $_FILES['Filedata']['name'];
			$keepOriginal = $this->config['keepOriginal']=='Yes';
			
			if($modx->config['clean_uploaded_filename']) {
				$nameparts = explode('.', $target_fname);
				$nameparts = array_map(array($modx, 'stripAlias'), $nameparts);
				$target_fname = implode('.', $nameparts);
			}
			
			$target_file = $target_dir . $target_fname;
			$target_thumb = $target_dir . 'thumbs/' . $target_fname;
			$target_original = $target_dir . 'original/' . $target_fname;
			
			// Check for existence of document/gallery directories
			if (!file_exists($target_dir))
			{
				$new_folder_permissions = octdec($modx->config['new_folder_permissions']);
				mkdir($target_dir, $new_folder_permissions);
				mkdir($target_dir . 'thumbs/', $new_folder_permissions);
				if ($keepOriginal)
					mkdir($target_dir . 'original/', $new_folder_permissions);
			}

			$movetofile = $keepOriginal?$target_original:$target_file;
			// Copy uploaded image to final destination
			if (move_uploaded_file($_FILES['Filedata']['tmp_name'], $movetofile))
			{
				
				$this->resizeImage($movetofile, $target_file, $this->getPhpthumbConfig($this->config['phpthumbImage']));  // Create and save main image
				$this->resizeImage($movetofile, $target_thumb, $this->getPhpthumbConfig($this->config['phpthumbThumb']));  // Create and save thumb
				
				$new_file_permissions = octdec($modx->config['new_file_permissions']);
				chmod($target_file, $new_file_permissions);
				chmod($target_thumb, $new_file_permissions);
				if ($keepOriginal)
					chmod($target_original, $new_file_permissions);
			}

			if (isset($_POST['edit']))
			{
				// Replace mode
				
				// Delete existing image
				$oldfilename = urldecode($_POST['edit']);
				if($oldfilename !== $target_fname){
					if (file_exists($target_dir . 'thumbs/' . $oldfilename))
						unlink($target_dir . 'thumbs/' . $oldfilename);
					if (file_exists($target_dir . 'original/' . $oldfilename))
						unlink($target_dir . 'original/' . $oldfilename);
					if (file_exists($target_dir . $oldfilename))
						unlink($target_dir . $oldfilename);
				}
				
				// Update record in the database
				$fields = array(
					'filename' => $modx->db->escape($target_fname)
				);
				$modx->db->update($fields, $modx->getFullTableName('portfolio_galleries'), "filename='".$oldfilename."' AND content_id='" . $content_id . "'");
				
			} else
			{
				// Find the last order position
				$rs = $modx->db->select('sortorder', $modx->getFullTableName('portfolio_galleries'), '', 'sortorder DESC', '1');
				if ($modx->db->getRecordCount($rs) > 0)
					$pos = $modx->db->getValue($rs) + 1;
				else
					$pos = 1; 

				// Create record in the database
				$fields = array(
					'content_id' => $content_id,
					'filename' => $modx->db->escape($target_fname),
					'sortorder' => $pos
				);
				$modx->db->insert($fields, $modx->getFullTableName('portfolio_galleries'));
			}
			
			//return new filename
			echo $target_fname;
		}
		
	}
}
?>
