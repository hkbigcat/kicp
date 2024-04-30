jQuery(document).ready(function ($) {
    $("#submitBtn").click(function () {
        $.ajax({
            type: "POST",
            url: app_path+'blog_comment_add',
            data: $("#CommentForm").serialize(), // serializes the form's elements.
            success: function (data)
            {
                LoadEntryComment();
            }
        });
        
    });

    function LoadEntryComment() {
        var this_entry_id = $("#entry_id").val();
        $.ajax({
            type: "POST",
            url:  app_path+'/blog_comment_list/'+this_entry_id,
            success: function (data)
            {
                $("#blogCommentContainer").html(data);
            }
        });
    }

})