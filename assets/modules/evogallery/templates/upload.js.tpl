<script type="text/javascript">
<!--
$(document).ready(function(){
	$("#uploadify").uploadify({
		'uploader': '[+base_path+]js/uploadify/uploadify.swf',
		'script': '[+base_path+]upload.php',
		'checkScript': '[+base_path+]check.php',
		'scriptData': {[+params+]},
		'folder': '[+base_url+]assets/galleries/[+content_id+]',
		'multi': true,
		'fileDesc': 'Image Files',
		'fileExt': '*.jpg;*.png;*.gif',
		'simUploadLimit': 2,
		'sizeLimit': 2097152,
		'buttonText': 'Select Files',
		'cancelImg': '[+base_path+]js/uploadify/cancel.png',
		'onComplete': function(event, queueID, fileObj, response, data) {
            var uploadList = $('#uploadList');
            uploadList.append("<li><div class=\"thbButtons\"><a href=\"" + unescape('[+self+]') + "&action=edit&content_id=[+content_id+]&edit=" + escape(fileObj.name) + "\" class=\"edit\">Edit</a><a href=\"" + unescape('[+self+]') + "&delete=" + escape(fileObj.name) + "\" class=\"delete\">Delete</a></div><img src=\"" + unescape('[+thumbs+]') + "&filename=" + escape(fileObj.name) + "\" alt=\"" + fileObj.name + "\" class=\"thb\" /><input type=\"hidden\" name=\"sort[]\" value=\"" + escape(fileObj.name) + "\" /></li>");
        },
        'onAllComplete': function(){
            $(".thbButtons").hide();
        }
	});
    $('#uploadFiles').click(function(){
        $('#uploadify').uploadifyUpload();
        return false;
    });
    $('#clearQueue').click(function(){
        $('#uploadify').uploadifyClearQueue();
        return false;
    });
	if($('#uploadList').length > 0){
        $(".thbButtons").hide();
        $("#uploadList li").live("mouseover", function(){
                $(this).find(".thbButtons").show();
        });
        $("#uploadList li").live("mouseout", function(){
                $(this).find(".thbButtons").hide();
        });
        $(".thbButtons .delete").live("click", function(event){
            if(confirm('Are you sure you want to delete this image?')){
                $.get($(this).attr('href'));
                $(this).parent().parent('li').remove();            
            }
            return false;
        });
        $(".edit").live("click", function(event){
            var link = $(this).attr("href");
            var overlay = $(this).overlay({
                api: 'true',
                target: '#overlay',
                oneInstance: true,
                onBeforeLoad: function(){
                    $("#overlay .contentWrap").load(link, function(){
                        var keyword_tags = new TagCompleter("keywords", "keyword_tagList", ",");
                    });
                },
                onClose: function(){
                    if($('.newimage').length > 0){
                        window.location.reload();
                    }
                },
                onLoad: function(){
                    $("#cmdsave").click(function(){
                        overlay.close();
                    });
                    $.urlParam = function(name){
                    	var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(link);
                    	return results[1] || 0;
                    }
                	$("#newimage").uploadify({
                		'uploader': '[+base_path+]js/uploadify/uploadify.swf',
                		'script': '[+base_path+]replace.php',
                		'checkScript': '[+base_path+]check.php',
                		'scriptData': {[+params+], 'edit': $.urlParam('edit')},
                		'folder': '[+base_url+]assets/galleries/[+content_id+]',
                		'multi': false,
                		'fileDesc': 'Image Files',
                		'fileExt': '*.jpg;*.png;*.gif',
                		'simUploadLimit': 2,
                		'sizeLimit': 2097152,
                   		'cancelImg': '[+base_path+]js/uploadify/cancel.png',
                		'onComplete': function(event, queueID, fileObj, response, data) {
                            $('.thumbPreview').empty().append('<img class="newimage" src="' + unescape('[+thumbs+]') + '&filename=' + escape(fileObj.name) + '" alt="' + fileObj.name + '" />');

                        }
               	    });
                    $('#newimageupload').click(function(){
                        $('#newimage').uploadifyUpload();
                        return false;
                    });
                }
            });
            overlay.load();
            return false;
        });

        $("#uploadList").sortable();
	}
});
-->
</script>
