[+upload_script+]

<hr />
<h2 class="second">Photos for [+title+]</h2>

<form action="[+action+]" method="post" enctype="multipart/form-data">
	
<div id="uploadContainer">
		<p><strong>TIP:</strong> You can select multiple files with CTRL and SHIFT combinations.</p>
        <input id="uploadify" name="uploadify" type="file" /> 
        <a href="#" id="uploadFiles">Upload Files</a> | <a href="#" id="clearQueue">Clear Queue</a>
</div>
<p id="sortdesc">Drag the images to and click save below to update the order in which they are displayed.</p>
<div id="uploadFiles"><ul id="uploadList">[+thumbs+]</ul></div>

<div class="submit">
	<input type="submit" name="cmdprev" value="&lt; Back" title="Click to go back to the document listing" /> &nbsp; <input type="submit" id="cmdsort" name="cmdsort" value="Save Order &gt;" title="Click to save the sort order" />
    <p><strong>SORTING:</strong> To set the order of images, simply drag and drop the images in the order you want and click the 'Save Order' button.</p>
</div>

</form>
