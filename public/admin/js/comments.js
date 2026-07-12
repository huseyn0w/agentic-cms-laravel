$(function(){

    var AllComments            = $("#selectAll"),
        comments_checkbox      = $(".comments-checkbox-input"),
        delete_comment         = $(".delete_comment"),
        approve_comment        = $(".approve_comment"),
        unapprove_comment      = $(".unapprove_comment");



    AllComments.on("click", function () {
        AllComments.toggleClass('all-checked');
        comments_checkbox.each(function(index,el){

            if(AllComments.hasClass('all-checked')){
                $(el).prop('checked', 'checked');
            }
            else {
                $(el).prop('checked', false);
            }
        });
    });

    comments_checkbox.on('click', function () {
        console.log('salam');
        if(!$(this).prop('checked')){
            AllComments.removeClass('all-checked');
            AllComments.prop('checked', false);
        }
    });


    delete_comment.on('click', function () {

        var deleted_comment_id = $(this).parent().find(".action_comment_id").val();

        console.log(deleted_comment_id);
        var that = $(this);

        var delete_confirmed = confirm(delete_confirmation);
        if(delete_confirmed){

            $.ajax({
                url: "/agentic-cms-laravel-admin/comments/" + deleted_comment_id + "/delete/",
                type: 'DELETE',
                data: {
                    "id": deleted_comment_id
                },
                headers: {
                    'X-CSRF-TOKEN':_token
                },
                success: function (data)
                {
                    if(data === "OK")
                    {
                        var message = delete_success;
                        that.closest('tr').fadeOut(1000, function () {
                            that.closest('tr').remove();
                            showNotification('top','right', message, 'success', 2);
                        });
                    }
                    else{
                        var message = error_message;
                        showNotification('top','right', message, 'error');
                    }
                },
                error:function(data)
                {
                    var message = error_message;
                    showNotification('top','right', message, 'error');
                }
            });
        }
    });


    approve_comment.on('click', function () {

        var approved_comment_id = $(this).parent().find(".action_comment_id").val();

        var that = $(this);

        var approve_confirmation = confirm(approve_confirmation);
        if(approve_confirmation){

            $.ajax({
                url: "/agentic-cms-laravel-admin/comments/" + approved_comment_id + "/approve/",
                type: 'PUT',
                data: {
                    "id": approved_comment_id
                },
                headers: {
                    'X-CSRF-TOKEN': _token
                },
                success: function (data)
                {
                    if(data === "OK")
                    {
                        var message = approve_success;
                        showNotification('top','right', message, 'success', 2);
                        that.hide().remove();
                    }
                    else{
                        var message = error_message;
                        showNotification('top','right', message, 'error');
                    }
                },
                error:function(data)
                {
                    var message = error_message;
                    showNotification('top','right', message, 'error');
                }
            });
        }
    });

    unapprove_comment.on('click', function () {

        var unapproved_comment_id = $(this).parent().find(".action_comment_id").val();

        var that = $(this);

        var approve_confirmation = confirm(unapprove_confirmation);
        if(approve_confirmation){

            $.ajax({
                url: "/agentic-cms-laravel-admin/comments/" + unapproved_comment_id + "/unapprove/",
                type: 'PUT',
                data: {
                    "id": unapproved_comment_id
                },
                headers: {
                    'X-CSRF-TOKEN': _token
                },
                success: function (data)
                {
                    if(data === "OK")
                    {
                        var message = unapprove_success;
                        showNotification('top','right', message, 'success', 2);
                        that.hide().remove();
                    }
                    else{
                        var message = error_message;
                        showNotification('top','right', message, 'error');
                    }
                },
                error:function(data)
                {
                    var message = error_message;
                    showNotification('top','right', message, 'error');
                }
            });
        }
    });
})