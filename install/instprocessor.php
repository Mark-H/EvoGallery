<?php
global $moduleName;
global $moduleVersion;
global $moduleSQLBaseFile;
global $moduleSQLDataFile;

global $moduleChunks;
global $moduleTemplates;
global $moduleSnippets;
global $modulePlugins;
global $moduleModules;
global $moduleTVs;

global $errors;

$create = false;

// set timout limit
@ set_time_limit(120); // used @ to prevent warning when using safe mode?

echo "<p>{$_lang['setup_database']}</p>\n";


//if ($installMode == 1) {
//	include "../manager/includes/config.inc.php";
//} else {
	// get db info from post
	$database_server = $_POST['databasehost'];
	$database_user = $_POST['databaseloginname'];
	$database_password = $_POST['databaseloginpassword'];
	$database_collation = $_POST['database_collation'];
	$database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
	$database_connection_charset = $_POST['database_connection_charset'];
	$database_connection_method = $_POST['database_connection_method'];
	$dbase = "`" . $_POST['database_name'] . "`";
	$table_prefix = $_POST['tableprefix'];
	$adminname = $_POST['cmsadmin'];
	$adminemail = $_POST['cmsadminemail'];
	$adminpass = $_POST['cmspassword'];
	$managerlanguage = $_POST['managerlanguage'];
//}

// get base path and url
$a = explode("install", str_replace("\\", "/", dirname($_SERVER["PHP_SELF"])));
if (count($a) > 1)
	array_pop($a);
$url = implode("install", $a);
reset($a);
$a = explode("install", str_replace("\\", "/", realpath(dirname(__FILE__))));
if (count($a) > 1)
	array_pop($a);
$pth = implode("install", $a);
unset ($a);
$base_url = $url . (substr($url, -1) != "/" ? "/" : "");
$base_path = $pth . (substr($pth, -1) != "/" ? "/" : "");

// connect to the database
echo "<p>". $_lang['setup_database_create_connection'];
if (!@ $conn = mysql_connect($database_server, $database_user, $database_password)) {
	echo "<span class=\"notok\">".$_lang["setup_database_create_connection_failed"]."</span></p><p>".$_lang['setup_database_create_connection_failed_note']."</p>";
	return;
} else {
	echo "<span class=\"ok\">".$_lang['ok']."</span></p>";
}

// select database
echo "<p>".$_lang['setup_database_selection']. str_replace("`", "", $dbase) . "`: ";
if (!@ mysql_select_db(str_replace("`", "", $dbase), $conn)) {
	echo "<span class=\"notok\" style='color:#707070'>".$_lang['setup_database_selection_failed']."</span>".$_lang['setup_database_selection_failed_note']."</p>";
	$create = true;
} else {
    @ mysql_query("{$database_connection_method} {$database_connection_charset}");
	echo "<span class=\"ok\">".$_lang['ok']."</span></p>";
}

// open db connection
$setupPath = realpath(dirname(__FILE__));
include "{$setupPath}/setup.info.php";
include "{$setupPath}/sqlParser.class.php";
$sqlParser = new SqlParser($database_server, $database_user, $database_password, str_replace("`", "", $dbase), $table_prefix, $adminname, $adminemail, $adminpass, $database_connection_charset, $managerlanguage, $database_connection_method);
$sqlParser->mode = ($installMode < 1) ? "new" : "upd";
//$sqlParser->imageUrl = 'http://' . $_SERVER['SERVER_NAME'] . $base_url . "assets/";
$sqlParser->imageUrl = "assets/";
$sqlParser->imagePath = $base_path . "assets/";
$sqlParser->fileManagerPath = $base_path;
$sqlParser->ignoreDuplicateErrors = true;
$sqlParser->connect();

// Install Templates
if (isset ($_POST['template'])) {
	echo "<p style=\"color:#707070\">" . $_lang['templates'] . ":</p> ";
	$selTemplates = $_POST['template'];
	foreach ($selTemplates as $si) {
		$si = (int) trim($si);
		$name = mysql_real_escape_string($moduleTemplates[$si][0]);
		$desc = mysql_real_escape_string($moduleTemplates[$si][1]);
		$category = mysql_real_escape_string($moduleTemplates[$si][4]);
		$locked = mysql_real_escape_string($moduleTemplates[$si][5]);
		$filecontent = $moduleTemplates[$si][3];
		if (!file_exists($filecontent)) {
			echo "<p>&nbsp;&nbsp;$name: <span class=\"notok\">" . $_lang['unable_install_template'] . " '$filecontent' " . $_lang['not_found'] . ".</span></p>";
		} else {
			// Create the category if it does not already exist
			if($category) {
				$rs = mysql_query("REPLACE INTO $dbase.`".$table_prefix."categories` (`id`,`category`) ( SELECT MIN(`id`), '$category' FROM ( SELECT `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category' UNION SELECT (CASE COUNT(*) WHEN 0 THEN 1 ELSE MAX(`id`)+1 END ) `id` FROM $dbase.`" . $table_prefix . "categories` ) AS _tmp )", $sqlParser->conn);
				
				$rs = mysql_query("SELECT id FROM $dbase.`".$table_prefix."categories` WHERE category = '".$category."'");
				if(mysql_num_rows($rs) && ($row = mysql_fetch_assoc($rs))) {
					$category = $row['id'];
				} else {
					$category = 0;
				}
			} else {
				$category = 0;
			}
			
			// Strip the first comment up top
			$template = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);
			$template = mysql_real_escape_string($template);
			
			// See if the template already exists
			$rs = mysql_query("SELECT * FROM $dbase.`" . $table_prefix . "site_templates` WHERE templatename='$name'", $sqlParser->conn);
			
			if (mysql_num_rows($rs)) {
				if (!@ mysql_query("UPDATE $dbase.`" . $table_prefix . "site_templates` SET content='$template', description='$desc', category='$category', locked='$locked'  WHERE templatename='$name';", $sqlParser->conn)) {
					$errors += 1;
					echo "<p>" . mysql_error() . "</p>";
					return;
				}
				echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['upgraded'] . "</span></p>";
			} else {
				if (!@ mysql_query("INSERT INTO $dbase.`" . $table_prefix . "site_templates` (templatename,description,content,category,locked) VALUES('$name','$desc','$template','$category','$locked');", $sqlParser->conn)) {
					$errors += 1;
					echo "<p>" . mysql_error() . "</p>";
					return;
				}
				echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['installed'] . "</span></p>";
			}
		}
	}
}

// Install Template Variables
if (isset ($_POST['tv'])) {
    echo "<h3>" . $_lang['tvs'] . ":</h3> ";
    $selTVs = $_POST['tv'];
    foreach ($selTVs as $si) {
        $si = (int) trim($si);
        $name = mysql_real_escape_string($moduleTVs[$si][0]);
        $caption = mysql_real_escape_string($moduleTVs[$si][1]);
        $desc = mysql_real_escape_string($moduleTVs[$si][2]);
        $input_type = mysql_real_escape_string($moduleTVs[$si][3]);
        $input_options = mysql_real_escape_string($moduleTVs[$si][4]);
        $input_default = mysql_real_escape_string($moduleTVs[$si][5]);
        $output_widget = mysql_real_escape_string($moduleTVs[$si][6]);
        $output_widget_params = mysql_real_escape_string($moduleTVs[$si][7]);
        $filecontent = $moduleTVs[$si][8];
        $assignments = $moduleTVs[$si][9];
        $category = mysql_real_escape_string($moduleTVs[$si][10]);
        $locked = mysql_real_escape_string($moduleTVs[$si][11]);
        

        // Create the category if it does not already exist
        if( $category ){                
            $rs = mysql_query("REPLACE INTO $dbase.`" . $table_prefix . "categories` (`id`,`category`) ( SELECT MIN(`id`), '$category' FROM ( SELECT `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category' UNION SELECT (CASE COUNT(*) WHEN 0 THEN 1 ELSE MAX(`id`)+1 END ) `id` FROM $dbase.`" . $table_prefix . "categories` ) AS _tmp )", $sqlParser->conn);
        }
        $rs = mysql_query("SELECT * FROM $dbase.`" . $table_prefix . "site_tmplvars` WHERE name='$name'", $sqlParser->conn);
        if (mysql_num_rows($rs)) {
            $insert = true;
            while($row = mysql_fetch_assoc($rs)) {
                if (!@ mysql_query("UPDATE $dbase.`" . $table_prefix . "site_tmplvars` SET type='$input_type', caption='$caption', description='$desc', locked=$locked, elements='$input_options', display='$output_widget', display_params='$output_widget_params', default_text='$input_default' WHERE id={$row['id']};", $sqlParser->conn)) {
                    echo "<p>" . mysql_error() . "</p>";
                    return;
                }
                $insert = false;
            }
            echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['upgraded'] . "</span></p>";
        } else {
            if (!@ mysql_query("INSERT INTO $dbase.`" . $table_prefix . "site_tmplvars` (type,name,caption,description,category,locked,elements,display,display_params,default_text) VALUES('$input_type','$name','$caption','$desc',(SELECT (CASE COUNT(*) WHEN 0 THEN 0 ELSE `id` END) `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category'),$locked,'$input_options','$output_widget','$output_widget_params','$input_default');", $sqlParser->conn)) {
                echo "<p>" . mysql_error() . "</p>";
                return;
            }
            echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['installed'] . "</span></p>";
        }
      
        // add template assignements
        $assignments = explode(',', $assignments);
        
        if (count($assignments) > 0) {
            foreach ($assignments as $assignment) {
                $template = mysql_real_escape_string($assignment);
                $ts = mysql_query("SELECT id FROM $dbase.`".$table_prefix."site_templates` WHERE templatename='$template';",$sqlParser->conn);
                $ds=mysql_query("SELECT id FROM $dbase.`".$table_prefix."site_tmplvars` WHERE name='$name' AND description='$desc';",$sqlParser->conn);
                if ($ds && $ts) {
                    $tRow = mysql_fetch_assoc($ts);
                    $row = mysql_fetch_assoc($ds);
                    $templateId = $tRow['id'];
                    $id = $row["id"];
                    // remove existing tv -> template assignements
                    mysql_query('DELETE FROM ' . $dbase . '.`' . $table_prefix . 'site_tmplvar_templates` WHERE tmplvarid = \'' . $id . '\'');
                    // add existing tv -> template assignements
                    mysql_query("INSERT INTO $dbase.`" . $table_prefix . "site_tmplvar_templates` (tmplvarid, templateid) VALUES($id, $templateId)");
               }
            }
        }
        
    }
}

// Install Chunks
if (isset ($_POST['chunk'])) {
	echo "<h3>" . $_lang['chunks'] . ":</h3> ";
	$selChunks = $_POST['chunk'];
	foreach ($selChunks as $si) {
		$si = (int) trim($si);
		$name = mysql_real_escape_string($moduleChunks[$si][0]);
		$desc = mysql_real_escape_string($moduleChunks[$si][1]);
		$category = mysql_real_escape_string($moduleChunks[$si][3]);
		
		$filecontent = $moduleChunks[$si][2];
		if (!file_exists($filecontent))
			echo "<p>&nbsp;&nbsp;$name: <span class=\"notok\">" . $_lang['unable_install_chunk'] . " '$filecontent' " . $_lang['not_found'] . ".</span></p>";
		else {
			
			// Create the category if it does not already exist
			if( $category ){				
				$rs = mysql_query("REPLACE INTO $dbase.`" . $table_prefix . "categories` (`id`,`category`) ( SELECT MIN(`id`), '$category' FROM ( SELECT `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category' UNION SELECT (CASE COUNT(*) WHEN 0 THEN 1 ELSE MAX(`id`)+1 END ) `id` FROM $dbase.`" . $table_prefix . "categories` ) AS _tmp )", $sqlParser->conn);
			}
			
			$chunk = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);
			$chunk = mysql_real_escape_string($chunk);
			$rs = mysql_query("SELECT * FROM $dbase.`" . $table_prefix . "site_htmlsnippets` WHERE name='$name'", $sqlParser->conn);
			if (mysql_num_rows($rs)) {
				if (!@ mysql_query("UPDATE $dbase.`" . $table_prefix . "site_htmlsnippets` SET snippet='$chunk', description='$desc' WHERE name='$name';", $sqlParser->conn)) {
					$errors += 1;
					echo "<p>" . mysql_error() . "</p>";
					return;
				}
				echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['upgraded'] . "</span></p>";
			} else {
				if (!@ mysql_query("INSERT INTO $dbase.`" . $table_prefix . "site_htmlsnippets` (name,description,snippet,category) VALUES('$name','$desc','$chunk',(SELECT (CASE COUNT(*) WHEN 0 THEN 0 ELSE `id` END) `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category'));", $sqlParser->conn)) {
					$errors += 1;
					echo "<p>" . mysql_error() . "</p>";
					return;
				}
				echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['installed'] . "</span></p>";
			}
		}
	}
}

// Install Modules

if (isset ($_POST['module'])) {
	echo "<h3>" . $_lang['modules'] . ":</h3> ";
	$selPlugs = $_POST['module'];
	foreach ($selPlugs as $si) {
		$si = (int) trim($si);
		$name = mysql_real_escape_string($moduleModules[$si][0]);
		$desc = mysql_real_escape_string($moduleModules[$si][1]);
		$filecontent = $moduleModules[$si][2];
		$properties = mysql_real_escape_string($moduleModules[$si][3]);
		$guid = mysql_real_escape_string($moduleModules[$si][4]);
		$shared = mysql_real_escape_string($moduleModules[$si][5]);
		$category = mysql_real_escape_string($moduleModules[$si][6]);

		if (!file_exists($filecontent))
			echo "<p>&nbsp;&nbsp;$name: <span class=\"notok\">" . $_lang['unable_install_module'] . " '$filecontent' " . $_lang['not_found'] . ".</span></p>";
		else {
			
			// Create the category if it does not already exist
			if( $category ){				
				$rs = mysql_query("REPLACE INTO $dbase.`" . $table_prefix . "categories` (`id`,`category`) ( SELECT MIN(`id`), '$category' FROM ( SELECT `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category' UNION SELECT (CASE COUNT(*) WHEN 0 THEN 1 ELSE MAX(`id`)+1 END ) `id` FROM $dbase.`" . $table_prefix . "categories` ) AS _tmp )", $sqlParser->conn);
			}
			
			$module = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2));
			// remove installer docblock
			$module = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', $module, 1);
			$module = mysql_real_escape_string($module);
			$rs = mysql_query("SELECT * FROM $dbase.`" . $table_prefix . "site_modules` WHERE name='$name'", $sqlParser->conn);
			if (mysql_num_rows($rs)) {
			    $row = mysql_fetch_assoc($rs);
			    $props = propUpdate($properties,$row['properties']);
			    if (!@ mysql_query("UPDATE $dbase.`" . $table_prefix . "site_modules` SET modulecode='$module', description='$desc', properties='$props', enable_sharedparams='$shared' WHERE name='$name';", $sqlParser->conn)) {
					echo "<p>" . mysql_error() . "</p>";
					return;
				}
				echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['upgraded'] . "</span></p>";
			} else {
				if (!@ mysql_query("INSERT INTO $dbase.`" . $table_prefix . "site_modules` (name,description,modulecode,properties,guid,enable_sharedparams,category) VALUES('$name','$desc','$module','$properties','$guid','$shared',(SELECT (CASE COUNT(*) WHEN 0 THEN 0 ELSE `id` END) `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category'));", $sqlParser->conn)) {
					echo "<p>" . mysql_error() . "</p>";
					return;
				}
				echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['installed'] . "</span></p>";
			}
		}
	}
}

// Install Plugins
if (isset ($_POST['plugin'])) {
	echo "<h3>" . $_lang['plugins'] . ":</h3> ";
	$selPlugs = $_POST['plugin'];
	foreach ($selPlugs as $si) {
		$si = (int) trim($si);
		$name = mysql_real_escape_string($modulePlugins[$si][0]);
		$desc = mysql_real_escape_string($modulePlugins[$si][1]);
		$filecontent = $modulePlugins[$si][2];
		$properties = mysql_real_escape_string($modulePlugins[$si][3]);
		$events = explode(",", $modulePlugins[$si][4]);
		$guid = mysql_real_escape_string($modulePlugins[$si][5]);
		$category = mysql_real_escape_string($modulePlugins[$si][6]);
		$leg_names = '';
		if(array_key_exists(7, $modulePlugins[$si])) {
		    // parse comma-separated legacy names and prepare them for sql IN clause
    		$leg_names = "'" . implode("','", preg_split('/\s*,\s*/', mysql_real_escape_string($modulePlugins[$si][7]))) . "'";
		}
		if (!file_exists($filecontent))
			echo "<p>&nbsp;&nbsp;$name: <span class=\"notok\">" . $_lang['unable_install_plugin'] . " '$filecontent' " . $_lang['not_found'] . ".</span></p>";
		else {

		    // disable legacy versions based on legacy_names provided
		    if(!empty($leg_names)) {
		        $update_query = "UPDATE $dbase.`" . $table_prefix . "site_plugins` SET disabled='1' WHERE name IN ($leg_names);";
    		    $rs = mysql_query($update_query, $sqlParser->conn);
		    }

			// Create the category if it does not already exist
			if( $category ){				
				$rs = mysql_query("REPLACE INTO $dbase.`" . $table_prefix . "categories` (`id`,`category`) ( SELECT MIN(`id`), '$category' FROM ( SELECT `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category' UNION SELECT (CASE COUNT(*) WHEN 0 THEN 1 ELSE MAX(`id`)+1 END ) `id` FROM $dbase.`" . $table_prefix . "categories` ) AS _tmp )", $sqlParser->conn);
			}
			
			$plugin = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2));
			// remove installer docblock
			$plugin = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', $plugin, 1);
			$plugin = mysql_real_escape_string($plugin);
			$rs = mysql_query("SELECT * FROM $dbase.`" . $table_prefix . "site_plugins` WHERE name='$name'", $sqlParser->conn);
            if (mysql_num_rows($rs)) {
                $insert = true;
                while($row = mysql_fetch_assoc($rs)) {
                    $props = propUpdate($properties,$row['properties']);
                    if($row['description'] == $desc){
                        if (!@ mysql_query("UPDATE $dbase.`" . $table_prefix . "site_plugins` SET plugincode='$plugin', description='$desc', properties='$props' WHERE id={$row['id']};", $sqlParser->conn)) {
                            echo "<p>" . mysql_error() . "</p>";
                            return;
                        }
                        $insert = false;
                    } else {
                        if (!@ mysql_query("UPDATE $dbase.`" . $table_prefix . "site_plugins` SET disabled='1' WHERE id={$row['id']};", $sqlParser->conn)) {
                            echo "<p>".mysql_error()."</p>";
                            return;
                        }
                    }
                }
                if($insert === true) {
                    if(!@mysql_query("INSERT INTO $dbase.`".$table_prefix."site_plugins` (name,description,plugincode,properties,moduleguid,disabled,category) VALUES('$name','$desc','$plugin','$properties','$guid','0',(SELECT (CASE COUNT(*) WHEN 0 THEN 0 ELSE `id` END) `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category'));",$sqlParser->conn)) {
                        echo "<p>".mysql_error()."</p>";
                        return;
                    }
                }
                echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['upgraded'] . "</span></p>";
            } else {
                if (!@ mysql_query("INSERT INTO $dbase.`" . $table_prefix . "site_plugins` (name,description,plugincode,properties,moduleguid,category) VALUES('$name','$desc','$plugin','$properties','$guid',(SELECT (CASE COUNT(*) WHEN 0 THEN 0 ELSE `id` END) `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category'));", $sqlParser->conn)) {
                    echo "<p>" . mysql_error() . "</p>";
                    return;
                }
                echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['installed'] . "</span></p>";
            }
			// add system events
			if (count($events) > 0) {
				$ds=mysql_query("SELECT id FROM $dbase.`".$table_prefix."site_plugins` WHERE name='$name' AND description='$desc';",$sqlParser->conn);
				if ($ds) {
					$row = mysql_fetch_assoc($ds);
					$id = $row["id"];
					// remove existing events
					mysql_query('DELETE FROM ' . $dbase . '.`' . $table_prefix . 'site_plugin_events` WHERE pluginid = \'' . $id . '\'');
					// add new events
					mysql_query("INSERT INTO $dbase.`" . $table_prefix . "site_plugin_events` (pluginid, evtid) SELECT '$id' as 'pluginid',se.id as 'evtid' FROM $dbase.`" . $table_prefix . "system_eventnames` se WHERE name IN ('" . implode("','", $events) . "')");
				}
			}
		}
	}
}

// Install Snippets

if (isset ($_POST['snippet'])) {
	echo "<h3>" . $_lang['snippets'] . ":</h3> ";
	$selSnips = $_POST['snippet'];
	foreach ($selSnips as $si) {
		$si = (int) trim($si);
		$name = mysql_real_escape_string($moduleSnippets[$si][0]);
		$desc = mysql_real_escape_string($moduleSnippets[$si][1]);
		$filecontent = $moduleSnippets[$si][2];
		$properties = mysql_real_escape_string($moduleSnippets[$si][3]);
		$category = mysql_real_escape_string($moduleSnippets[$si][4]);
		if (!file_exists($filecontent))
			echo "<p>&nbsp;&nbsp;$name: <span class=\"notok\">" . $_lang['unable_install_snippet'] . " '$filecontent' " . $_lang['not_found'] . ".</span></p>";
		else {			
			
			// Create the category if it does not already exist
			if( $category ){				
				$rs = mysql_query("REPLACE INTO $dbase.`" . $table_prefix . "categories` (`id`,`category`) ( SELECT MIN(`id`), '$category' FROM ( SELECT `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category' UNION SELECT (CASE COUNT(*) WHEN 0 THEN 1 ELSE MAX(`id`)+1 END ) `id` FROM $dbase.`" . $table_prefix . "categories` ) AS _tmp )", $sqlParser->conn);
			}
			
			$snippet = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent)));
			// remove installer docblock
			$snippet = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', $snippet, 1);
			$snippet = mysql_real_escape_string($snippet);
			$rs = mysql_query("SELECT * FROM $dbase.`" . $table_prefix . "site_snippets` WHERE name='$name'", $sqlParser->conn);
			if (mysql_num_rows($rs)) {
			    $row = mysql_fetch_assoc($rs);
			    $props = propUpdate($properties,$row['properties']);
			    if (!@ mysql_query("UPDATE $dbase.`" . $table_prefix . "site_snippets` SET snippet='$snippet', description='$desc', properties='$props' WHERE name='$name';", $sqlParser->conn)) {
					echo "<p>" . mysql_error() . "</p>";
					return;
				}
				echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['upgraded'] . "</span></p>";
			} else {
				if (!@ mysql_query("INSERT INTO $dbase.`" . $table_prefix . "site_snippets` (name,description,snippet,properties,category) VALUES('$name','$desc','$snippet','$properties',(SELECT (CASE COUNT(*) WHEN 0 THEN 0 ELSE `id` END) `id` FROM $dbase.`" . $table_prefix . "categories` WHERE `category` = '$category'));", $sqlParser->conn)) {
					echo "<p>" . mysql_error() . "</p>";
					return;
				}
				echo "<p>&nbsp;&nbsp;$name: <span class=\"ok\">" . $_lang['installed'] . "</span></p>";
			}
		}
	}
}

// remove any locks on the manager functions so initial manager login is not blocked
mysql_query("TRUNCATE TABLE `".$table_prefix."active_users`");

// close db connection
$sqlParser->close();

// andrazk 20070416 - release manager access
  if (file_exists('../assets/cache/installProc.inc.php')) {
	  @chmod('../assets/cache/installProc.inc.php', 0755);
    unlink('../assets/cache/installProc.inc.php');
	}

// setup completed!
echo "<p><b>" . $_lang['installation_successful'] . "</b></p>";
echo "<p>" . $_lang['to_log_into_content_manager'] . "</p>";
if ($installMode == 0) {
	echo "<p><img src=\"img/ico_info.png\" width=\"40\" height=\"42\" align=\"left\" style=\"margin-right:10px;\" />" . $_lang['installation_note'] . "</p>";
} else {
	echo "<p><img src=\"img/ico_info.png\" width=\"40\" height=\"42\" align=\"left\" style=\"margin-right:10px;\" />" . $_lang['upgrade_note'] . "</p>";
}

// Property Update function
function propUpdate($new,$old){
    // Split properties up into arrays
    $returnArr = array();
    $newArr = explode("&",$new);
    $oldArr = explode("&",$old);

    foreach ($newArr as $k => $v) {
        if(!empty($v)){
	        $tempArr = explode("=",trim($v));
	        $returnArr[$tempArr[0]] = $tempArr[1];
        }
    }
    foreach ($oldArr as $k => $v) {
        if(!empty($v)){
            $tempArr = explode("=",trim($v));
            $returnArr[$tempArr[0]] = $tempArr[1];
        }
    }

    // Make unique array
    $returnArr = array_unique($returnArr);

    // Build new string for new properties value
    foreach ($returnArr as $k => $v) {
        $return .= "&$k=$v ";
    }

    return $return;
}
?>