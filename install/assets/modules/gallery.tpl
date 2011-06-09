// <?php
/**
 * EvoGallery
 * 
 * Gallery Management Module
 * 
 * @category	module
 * @version 	1.0 Beta 1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@properties	&docId=Root Document ID;integer;0 &imageSize=Image size;int;940 &imageQuality=Image quality;int;85 &thumbSize=Thumb size;int;175 &thumbQuality=Thumb quality;int;75 &savePath=Save path;str;assets/galleries
 * @internal	@guid 	
 * @internal	@shareparams 1
 * @internal	@dependencies requires files located at /assets/modules/evogallery/
 * @internal	@modx_category Manager and Admin
 */

/**
 * EvoGallery
 * Gallery Management Module
 * Written by Brian Stanback
 * jQuery rewrite and updates by Jeff Whitfield <jeff@collabpad.com>
 */

include_once($modx->config['base_path'] . "assets/modules/evogallery/config.inc.php");
include_once($params['modulePath'] . "classes/maketable.class.inc.php");
include_once($params['modulePath'] . "classes/management.class.inc.php");

if (class_exists('GalleryManagement'))
	$manager = new GalleryManagement($params);
else
	$modx->logEvent(1, 3, 'Error loading Portfolio Galleries management module');

$manager->checkGalleryTable();

echo $manager->execute();
