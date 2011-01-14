<?php
/*---------------------------------------------------------------------------
* Gallery - Contains functions for generating a listing of gallery thumbanils
*                   while controlling various display aspects.
*--------------------------------------------------------------------------*/
class Gallery
{
	var $config;  // Array containing snippet configuration values

	/**
	* Class constructor, set configuration parameters
	*/
	function Gallery($params)
	{
		global $modx;

		$this->config = $params;

		$this->galleriesTable = 'portfolio_galleries';
	}

	/**
	* Determine what action was requested and process request
	*/
	function execute()
	{
		$output = '';

		$this->config['type'] = isset($this->config['type']) ? $this->config['type'] : 'simple-list';

		if ($this->config['includeAssets'])
			$this->getConfig($this->config['type']);

		if ($this->config['display'] == 'galleries')
			$output = $this->renderGalleries();
		elseif ($this->config['display'] == 'single')
			$output = $this->renderSingle();
        else
			$output = $this->renderImages();

		return $output;
	}

	/**
	* Generate a listing of document galleries
	*/
	function renderGalleries()
	{
		global $modx;

		// Retrieve chunks/default templates from disk
		$tpl = ($this->config['tpl'] == '') ? file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.default.txt') : $modx->getChunk($this->config['tpl']);
		$item_tpl = ($this->config['itemTpl'] == '') ? file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.default.txt') : $modx->getChunk($this->config['itemTpl']);
		$item_tpl_first = ($this->config['itemTplFirst'] == '') ? @file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.first.txt') : $modx->getChunk($this->config['itemTplFirst']);
		$item_tpl_alt = ($this->config['itemTplAlt'] == '') ? @file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.alt.txt') : $modx->getChunk($this->config['itemTplAlt']);
		$item_tpl_last = ($this->config['itemTplLast'] == '') ? @file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.last.txt') : $modx->getChunk($this->config['itemTplLast']);

		// Hide/show docs based on configuration
		$docSelect = '';
		if ($this->config['docId'] != '*' && !empty($this->config['docId']))
		{
			if (strpos($this->config['docId'], ',') !== false)
			{
				$docSelect = '(';
				foreach (explode(',', $this->config['docId']) as $docId)
					$docSelect .= "parent = '" . $docId . "' OR";
				$docSelect = rtrim($docSelect, ' OR ') . ') AND ';
			}
			else
				$docSelect = "parent = '" . $this->config['docId'] . "' AND ";
		}
		if ($this->config['excludeDocs'] > 0)
		{
			if (strpos($this->config['excludeDocs'], ',') !== false)
			{
				foreach (explode(',', $this->config['excludeDocs']) as $docId)
					$docSelect .= "parent != '" . $docId . "' AND ";
			}
			else
				$docSelect .= "parent != '" . $this->config['excludeDocs'] . "' AND ";
		}

		$phx = new PHxParser();  // Instantiate PHx

		$items = '';

		// Retrieve list of documents under the requested id
		$filter = "published = '1' AND type = 'document' AND isfolder = '0' AND " . $docSelect . " hidemenu <= '" . $this->config['ignoreHidden'] . "'";
		$result = $modx->db->select("id, pagetitle, longtitle, description, alias, pub_date, introtext, editedby, editedon, publishedon, publishedby, menutitle", $modx->getFullTableName('site_content'), $filter, $this->config['gallerySortBy'] . ' ' . $this->config['gallerySortDir']);
        $recordCount = $modx->db->getRecordCount($result);
		if ($recordCount > 0)
		{
		    $count = 1;
			while ($row = $modx->fetchRow($result))
			{
				$item_phx = new PHxParser();

				// Get total number of images for total placeholder
				$total_result = $modx->db->select("filename", $modx->getFullTableName($this->galleriesTable), "content_id = '" . $row['id'] . "'");
                $total = $modx->db->getRecordCount($total_result);
                
				// Fetch first image for each gallery, using the image sort order/direction
				$image_result = $modx->db->select("filename", $modx->getFullTableName($this->galleriesTable), "content_id = '" . $row['id'] . "'", $this->config['sortBy'] . ' ' . $this->config['sortDir'], '1');
				if ($modx->db->getRecordCount($image_result) > 0)
				{
					$image = $modx->fetchRow($image_result);
					foreach ($image as $name => $value)
						$item_phx->setPHxVariable($name, trim($value));
					$item_phx->setPHxVariable('images_dir', $this->config['galleriesUrl'] . $row['id'] . '/');
					$item_phx->setPHxVariable('thumbs_dir', $this->config['galleriesUrl'] . $row['id'] . '/thumbs/');

					foreach ($row as $name => $value)
						$item_phx->setPHxVariable($name, trim($value));
                    
                    // Get template variable output for row and set variables as needed
                    $row_tvs = $modx->getTemplateVarOutput('*',$row['id']);
					foreach ($row_tvs as $name => $value)
						$item_phx->setPHxVariable($name, trim($value));

					$item_phx->setPHxVariable('total', $total);

    				if(!empty($item_tpl_first) && $count == 1){
        				$items .= $item_phx->Parse($item_tpl_first);
    				} else if(!empty($item_tpl_last) && $count == $recordCount){
        				$items .= $item_phx->Parse($item_tpl_last);
    				} else if(!empty($item_tpl_alt) && $count % $this->config['itemAltNum'] == 0){
        				$items .= $item_phx->Parse($item_tpl_alt);
    				} else {
        				$items .= $item_phx->Parse($item_tpl);
    				}

				}
				$count++;
			}
		}

		$phx->setPHxVariable('items', $items);

		return $phx->Parse($tpl);  // Pass through PHx;
	}

	/**
	* Generate a listing of thumbnails/images for gallery/slideshow display
	*/
	function renderImages()
	{
		global $modx;

		// Retrieve chunks/default templates from disk
		$tpl = ($this->config['tpl'] == '') ? file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.default.txt') : $modx->getChunk($this->config['tpl']);
		$item_tpl = ($this->config['itemTpl'] == '') ? file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.default.txt') : $modx->getChunk($this->config['itemTpl']);
		$item_tpl_first = ($this->config['itemTplFirst'] == '') ? @file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.first.txt') : $modx->getChunk($this->config['itemTplFirst']);
		$item_tpl_alt = ($this->config['itemTplAlt'] == '') ? @file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.alt.txt') : $modx->getChunk($this->config['itemTplAlt']);
		$item_tpl_last = ($this->config['itemTplLast'] == '') ? @file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.last.txt') : $modx->getChunk($this->config['itemTplLast']);

		$docSelect = '';
		if ($this->config['docId'] != '*' && !empty($this->config['docId']))
		{
			if (strpos($this->config['docId'], ',') !== false)
			{
				$docSelect = '(';
				foreach (explode(',', $this->config['docId']) as $docId)
					$docSelect .= "content_id = '" . $docId . "' OR";
				$docSelect = rtrim($docSelect, ' OR ') . ')';
			}
			else
				$docSelect = "content_id = '" . $this->config['docId'] . "'";
		}
		if ($this->config['excludeDocs'] > 0)
		{
			if (strpos($this->config['excludeDocs'], ',') !== false)
			{
				foreach (explode(',', $this->config['excludeDocs']) as $docId)
					$docSelect .= "content_id != '" . $docId . "' AND ";
				$docSelect = rtrim($docSelect, ' AND ');
			}
			else
				$docSelect .= "content_id != '" . $this->config['excludeDocs'] . "'";
		}
		if (!empty($this->config['tags']))
		{
            $mode = (!empty($this->config['tagMode']) ? $this->config['tagMode'] : 'AND');
            $tagSelect = '';
            foreach (explode(',', $this->config['tags']) as $tag) {
            	$tagSelect .= "keywords LIKE '%" . trim($tag) . "%' ".$mode." ";
            }
            $tagSelect = rtrim($tagSelect, ' '.$mode.' ');
            $docSelect .= " AND (".$tagSelect.")";
		}

		$phx = new PHxParser();  // Instantiate PHx

		$items = '';

		// Retrieve photos from the database table
	    $result = $modx->db->select("*", $modx->getFullTableName($this->galleriesTable), $docSelect, $this->config['sortBy'] . ' ' . $this->config['sortDir'],(!empty($this->config['limit']) ? $this->config['limit'] : ""));
        $recordCount = $modx->db->getRecordCount($result);
		if ($recordCount > 0)
		{
            $count = 1;		    
			while ($row = $modx->fetchRow($result))
			{
				$item_phx = new PHxParser();
				foreach ($row as $name => $value)
					$item_phx->setPHxVariable($name, $value);
				$item_phx->setPHxVariable('images_dir', $this->config['galleriesUrl'] . $row['content_id'] . '/');
				$item_phx->setPHxVariable('thumbs_dir', $this->config['galleriesUrl'] . $row['content_id'] . '/thumbs/');
				if(!empty($item_tpl_first) && $count == 1){
    				$items .= $item_phx->Parse($item_tpl_first);
				} else if(!empty($item_tpl_last) && $count == $recordCount){
    				$items .= $item_phx->Parse($item_tpl_last);
				} else if(!empty($item_tpl_alt) && $count % $this->config['itemAltNum'] == 0){
    				$items .= $item_phx->Parse($item_tpl_alt);
				} else {
    				$items .= $item_phx->Parse($item_tpl);
				}
				$count++;
			}
		}

		$phx->setPHxVariable('items', $items);

		return $phx->Parse($tpl);  // Pass through PHx;
	}

	/**
	* Generate a listing of a single thumbnail/image for gallery/slideshow display
	*/
	function renderSingle()
	{
		global $modx;

		// Retrieve chunks/default templates from disk
		$tpl = ($this->config['tpl'] == '') ? file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.default.txt') : $modx->getChunk($this->config['tpl']);
		$item_tpl = ($this->config['itemTpl'] == '') ? file_get_contents($this->config['snippetPath'] . $this->config['type'] . '/tpl.item.default.txt') : $modx->getChunk($this->config['itemTpl']);

		$picSelect = '';
		if ($this->config['picId'] != '*' && !empty($this->config['picId']))
		{
				$picSelect = "id = '" . $this->config['picId'] . "'";
		}

		$phx = new PHxParser();  // Instantiate PHx

		$items = '';

		// Retrieve photos from the database table
	    $result = $modx->db->select("*", $modx->getFullTableName($this->galleriesTable), $picSelect);
		if ($modx->db->getRecordCount($result) > 0)
		{
			while ($row = $modx->fetchRow($result))
			{
				$item_phx = new PHxParser();
				foreach ($row as $name => $value)
					$item_phx->setPHxVariable($name, $value);
				$item_phx->setPHxVariable('images_dir', $this->config['galleriesUrl'] . $row['content_id'] . '/');
				$item_phx->setPHxVariable('thumbs_dir', $this->config['galleriesUrl'] . $row['content_id'] . '/thumbs/');
				$items .= $item_phx->Parse($item_tpl);
			}
		}

		$phx->setPHxVariable('items', $items);

		return $phx->Parse($tpl);  // Pass through PHx;
	}

	/**
	* Get configuration settings for the selected gallery/slideshow type
	*/
	function getConfig($type)
	{
		global $modx;

		if (file_exists($this->config['snippetPath'] . $type . '/tpl.config.txt'))
		{
			$register = 0;

			$config = file($this->config['snippetPath'] . $type . '/tpl.config.txt');
			foreach ($config as $line)
			{
				$line = trim($line);

				if ($line == '')
					$register = 0;
				elseif (strpos($line, '@SCRIPT') === 0)
					$register = 1;
				elseif (strpos($line, '@CSS') === 0)
					$register = 2;
				elseif (strpos($line, '@EXTSCRIPT') === 0)
					$register = 3;
				else
				{
					switch ($register)
					{
						case 1:
							$modx->regClientStartupScript($this->config['snippetUrl'] . $type . '/' . $line);
							break;
						case 2:
							$modx->regClientCSS($this->config['snippetUrl'] . $type . '/' . $line);
							break;
						case 3:
							$modx->regClientStartupScript($line);
							break;
					}
				}
			}
		}
	}
}
?>
