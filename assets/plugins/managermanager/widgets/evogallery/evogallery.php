<?php

//---------------------------------------------------------------------------------
// mm_widget_evogallery
//--------------------------------------------------------------------------------- 
function mm_widget_evogallery($moduleid, $title='', $roles='', $templates='') {
	
	global $modx, $content, $mm_fields;
	$e = &$modx->Event;
	
	if (useThisRule($roles, $templates)) {
		
		$title = empty($title) ? "Photos" : $title;
	
		//TODO: Add iframe autoheight
		if (isset($content['id']))
			$iframecontent = '<iframe id="mm_evogallery" src="'.$modx->config['site_url'].'/manager/index.php?a=112&id='.$moduleid.'&action=view&content_id='.$content['id'].'" style="width:100%;height:600px;" scrolling="auto" frameborder="0"></iframe>';
		else
			$iframecontent = '<p class="warning">You must save this page before you can manage the photos associated with it.</p>';
		
		mm_createTab($title, 'evogallery', '', '', $iframecontent);
		
	} // end if
	
	$e->output($output . "\n");	// Send the output to the browser
	
}

?>
