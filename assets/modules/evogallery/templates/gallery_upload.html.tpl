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

<div class="submit">
	<input type="submit" name="cmdprev" value="[+lang.back+]" title="[+lang.back_description+]" /> &nbsp; <input type="submit" id="cmdsort" name="cmdsort" value="[+lang.save_order+]" title="[+lang.save_order_description+]" />
    <p>[+lang.sort_text+]</p>
</div>

<div class="doccontrols">
	[+lang.in_this_doc+]: <a id="cmdCntDel" href="#">[+lang.delete_all+]</a> <a id="cmdCntRegenerate" href="#">[+lang.regenerate_all+]</a>
</div>
</form>
