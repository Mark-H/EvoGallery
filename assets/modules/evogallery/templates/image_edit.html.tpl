<div id="content">

<form action="[+action+]" method="post" target="main">
<input type="hidden" name="edit" value="[+id+]" />

<p class="thumbPreview">
	<img src="[+image+]" alt="[+filename+]" title="[+filename+]" />
</p>

<label for="title">Title:</label> <div class="field"><input type="text" name="title" id="title" value="[+title+]" size="35" /></div>
<label for="description">Description:</label> <div class="field"><textarea name="description" id="description" rows="5" cols="35">[+description+]</textarea></div>
<label for="title">Keywords:</label> <div class="field"><input type="text" name="keywords" id="keywords" value="[+keywords+]" size="35" />[+keyword_tagList+]</div>
<div class="submit">
	<input type="submit" value="Update" id="cmdsave" class="awesome" name="cmdsave" />
</div>
<div class="imageupdater">
    <label for="newimage">Update Image:</label><br />
    <input id="newimage" name="newimage" type="file" /> 
    <a id="newimageupload" href="#">Upload File</a></div>
</div>
</form>
