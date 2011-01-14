<?php
$installMode = intval($_POST['installmode']);
echo "<h2>" . $_lang['preinstall_validation'] . "</h2>";
echo "<h3>" . $_lang['summary_setup_check'] . "</h3>";
$errors = 0;
// check PHP version
echo "<p>" . $_lang['checking_php_version'];
$php_ver_comp = version_compare(phpversion(), "4.2.0");
$php_ver_comp2 = version_compare(phpversion(), "4.3.8");
// -1 if left is less, 0 if equal, +1 if left is higher
if ($php_ver_comp < 0) {
    echo "<span class=\"notok\">" . $_lang['failed'] . "</span>".$_lang['you_running_php'] . phpversion() . $_lang["modx_requires_php"]."</p>";
    $errors += 1;
} else {
    echo "<span class=\"ok\">" . $_lang['ok'] . "</span></p>";
    if ($php_ver_comp2 < 0) {
        echo "<fieldset>" . $_lang['php_security_notice'] . "</fieldset>";
    }
}
// connect to the database
include "../manager/includes/config.inc.php";
echo "<p>".$_lang['creating_database_connection'];
if (!@ $conn = mysql_connect($database_server, $database_user, $database_password)) {
    $errors += 1;
    echo "<span class=\"notok\">".$_lang['database_connection_failed']."</span><p />".$_lang['database_connection_failed_note']."</p>";
} else {
    echo "<span class=\"ok\">".$_lang['ok']."</span></p>";
}
// make sure we can use the database
if (!@ mysql_query("USE {$dbase}")) {
    $errors += 1;
    echo "<span class=\"notok\">".$_lang['database_use_failed']."</span><p />".$_lang["database_use_failed_note"]."</p>";
}

// check the database collation if not specified in the configuration
if (!isset ($database_connection_charset) || empty ($database_connection_charset)) {
    if (!$rs = @ mysql_query("show session variables like 'collation_database'")) {
        $rs = @ mysql_query("show session variables like 'collation_server'");
    }
    if ($rs && $collation = mysql_fetch_row($rs)) {
        $database_collation = $collation[1];
    }
    if (empty ($database_collation)) {
        $database_collation = 'utf8_unicode_ci';
    }
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_') - 1);
    $database_connection_charset = $database_charset;
}

// determine the database connection method if not specified in the configuration
if (!isset($database_connection_method) || empty($database_connection_method)) {
    $database_connection_method = 'SET CHARACTER SET';
}

// check mysql version
if ($conn) {
    echo "<p>" . $_lang['checking_mysql_version'];
    if ( version_compare(mysql_get_server_info(), '5.0.51', '=') ) {
        echo "<span class=\"notok\">"  . $_lang['warning'] . "</span></b>&nbsp;&nbsp;<strong>". $_lang['mysql_5051'] . "</strong></p>";
        echo "<p><span class=\"notok\">" . $_lang['mysql_5051_warning'] . "</span></p>";
    } else {
        echo "<span class=\"ok\">" . $_lang['ok'] . "</span>&nbsp;&nbsp;<strong>" . $_lang['mysql_version_is'] . mysql_get_server_info() . "</strong></p>";
    }
}


// andrazk 20070416 - add install flag and disable manager login
// assets/cache writable?
if (is_writable("../assets/cache")) {
    if (file_exists('../assets/cache/installProc.inc.php')) {
        @chmod('../assets/cache/installProc.inc.php', 0755);
        unlink('../assets/cache/installProc.inc.php');
    }

    // make an attempt to create the file
    @ $hnd = fopen("../assets/cache/installProc.inc.php", 'w');
    @ fwrite($hnd, '<?php $installStartTime = '.time().'; ?>');
    @ fclose($hnd);
}


if ($errors > 0) {
?>
      <p>
      <?php
      echo $_lang['setup_cannot_continue'];
      echo $errors > 1 ? $errors." " : "";
      if ($errors > 1) echo $_lang['errors'];
      else echo $_lang['error'];
      if ($errors > 1) echo $_lang['please_correct_errors'];
      else echo $_lang['please_correct_error'];
      if ($errors > 1) echo $_lang['and_try_again_plural'];
      else echo $_lang['and_try_again'];
      echo $_lang['visit_forum'];
      ?>
      </p>
      <?php
}

echo "<p>&nbsp;</p>";

$nextAction= $errors > 0 ? 'summary' : 'install';
$nextButton= $errors > 0 ? $_lang['retry'] : $_lang['install'];
$nextVisibility= $errors > 0 || isset($_POST['chkagree']) ? 'visible' : 'hidden';
$agreeToggle= $errors > 0 ? '' : ' onclick="if(document.getElementById(\'chkagree\').checked){document.getElementById(\'nextbutton\').style.visibility=\'visible\';}else{document.getElementById(\'nextbutton\').style.visibility=\'hidden\';}"';
?>
<form name="install" id="install_form" action="index.php?action=<?php echo $nextAction ?>" method="post">
  <div>
    <input type="hidden" value="<?php echo $install_language?>" name="language" />
	<input type="hidden" value="<?php echo $manager_language?>" name="managerlanguage" />
    <input type="hidden" value="<?php echo $installMode ?>" name="installmode" />
    <input type="hidden" value="<?php echo trim($_POST['database_name'], '`'); ?>" name="database_name" />
    <input type="hidden" value="<?php echo $_POST['tableprefix'] ?>" name="tableprefix" />
    <input type="hidden" value="<?php echo $_POST['database_collation'] ?>" name="database_collation" />
    <input type="hidden" value="<?php echo $_POST['database_connection_charset'] ?>" name="database_connection_charset" />
    <input type="hidden" value="<?php echo $_POST['database_connection_method'] ?>" name="database_connection_method" />
    <input type="hidden" value="<?php echo $_POST['databasehost'] ?>" name="databasehost" />
    <input type="hidden" value="<?php echo $_POST['databaseloginname'] ?>" name="databaseloginname" />
    <input type="hidden" value="<?php echo $_POST['databaseloginpassword'] ?>" name="databaseloginpassword" />
    
    <input type="hidden" value="1" name="options_selected" />
    
<?php
$templates = isset ($_POST['template']) ? $_POST['template'] : array ();
foreach ($templates as $i => $template) echo "<input type=\"hidden\" name=\"template[]\" value=\"$template\" />\n";
$tvs = isset ($_POST['tv']) ? $_POST['tv'] : array ();
foreach ($tvs as $i => $tv) echo "<input type=\"hidden\" name=\"tv[]\" value=\"$tv\" />\n";
$chunks = isset ($_POST['chunk']) ? $_POST['chunk'] : array ();
foreach ($chunks as $i => $chunk) echo "<input type=\"hidden\" name=\"chunk[]\" value=\"$chunk\" />\n";
$snippets = isset ($_POST['snippet']) ? $_POST['snippet'] : array ();
foreach ($snippets as $i => $snippet) echo "<input type=\"hidden\" name=\"snippet[]\" value=\"$snippet\" />\n";
$plugins = isset ($_POST['plugin']) ? $_POST['plugin'] : array ();
foreach ($plugins as $i => $plugin) echo "<input type=\"hidden\" name=\"plugin[]\" value=\"$plugin\" />\n";
$modules = isset ($_POST['module']) ? $_POST['module'] : array ();
foreach ($modules as $i => $module) echo "<input type=\"hidden\" name=\"module[]\" value=\"$module\" />\n";
?>
  </div>

<h2><?php echo $_lang['agree_to_terms'];?></h2>
<p>
<input type="checkbox" value="1" id="chkagree" name="chkagree" style="line-height:18px" <?php echo isset($_POST['chkagree']) ? 'checked="checked" ':""; ?><?php echo $agreeToggle;?>/><label for="chkagree" style="display:inline;float:none;line-height:18px;"> <?php echo $_lang['iagree_box']?> </label>
</p>
    <p class="buttonlinks">
        <a href="javascript:document.getElementById('install_form').action='index.php?action=options&language=<?php $install_language?>';document.getElementById('install_form').submit();" class="prev" title="<?php echo $_lang['btnback_value']?>"><span><?php echo $_lang['btnback_value']?></span></a>
        <a id="nextbutton" href="javascript:document.getElementById('install_form').submit();" title="<?php echo $nextButton ?>" style="visibility:<?php echo $nextVisibility;?>"><span><?php echo $nextButton ?></span></a>
    </p>
</form>
