[+upload_script+]

<hr />
<h2 class="second">[+lang.photos_for+] [+title+]</h2>

<form action="[+action+]" method="post" enctype="multipart/form-data">
	
<div id="uploadContainer">
		<p>[+lang.tip_multiple_files+]</p>
        <input id="uploadify" name="uploadify" type="file" /> 
        <a href="#" id="uploadFiles">[+lang.upload_files+]</a> | <a href="#" id="clearQueue">[+lang.clear_queue+]</a>
</div>
<p id="sortdesc">[+lang.sort_description+]</p>
<div id="uploadFiles"><ul id="uploadList">[+thumbs+]</ul></div>
<div id="selectallcontrols">
<div class="selectall"><a id="selectall" href="#">[+lang.selectall+]</a> | <a id="unselectall" href="#">[+lang.unselectall+]</a></div>
</div>

<div class="submit">
	<input type="submit" name="cmdprev" value="[+lang.back+]" title="[+lang.back_description+]" /> &nbsp; <input type="submit" id="cmdsort" name="cmdsort" value="[+lang.save_order+]" title="[+lang.save_order_description+]" />
    <p>[+lang.sort_text+]</p>
</div>
</form>

<div class="doccontrols">
	[+lang.in_this_doc+]: <a id="cmdCntDel" href="#">[+lang.delete_images+]</a> | <a id="cmdCntRegenerate" href="#">[+lang.regenerate_images+]</a> | <a id="cmdCntMoveTo" href="#">[+lang.move_to+]</a>
</div>

<div class="popupclose" id="moveto-popup"> 
	<p id="movetarget_doc">[+lang.select_document+]</p>
	<input id="movetarget_id" type="hidden" value="0"/>
	<input id="moveto" type="button" value="[+lang.start+]" class="awesome" name="cmdmoveto" />
</div>
