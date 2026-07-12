$(function () {

    var AllPages            = $("#selectAll"),
        pages_checkbox      = $(".pages-checkbox-input"),
        delete_page         = $(".delete_page"),
        destroy_page        = $(".destroy_page");


    AllPages.on("click", function () {
        AllPages.toggleClass('all-checked');
        pages_checkbox.each(function(index,el){

            if(AllPages.hasClass('all-checked')){
                $(el).prop('checked', 'checked');
            }
            else {
                $(el).prop('checked', false);
            }
        });
    });

    pages_checkbox.on('click', function () {
       if(!$(this).prop('checked')){
           AllPages.removeClass('all-checked');
           AllPages.prop('checked', false);
       }
    });

    delete_page.on('click', function () {
        var deleted_page_id = $(this).prev('.deleted_page_id').val();
        var that = $(this);

        var delete_conf = confirm(delete_confirmation);
        if(delete_conf){
            $.ajax({
                url: "/agentic-cms-laravel-admin/pages/" + deleted_page_id + "/delete/",
                type: 'DELETE',
                data: {
                    "id": deleted_page_id
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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

    destroy_page.on('click', function () {
        var destroyed_page_id = $(this).prev('.deleted_page_id').val();
        var that = $(this);

        var destroy_conf = confirm(destroy_confirmation);
        if(destroy_conf){
            $.ajax({
                url: "/agentic-cms-laravel-admin/pages/" + destroyed_page_id + "/destroy/",
                type: 'DELETE',
                data: {
                    "id": destroyed_page_id
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data)
                {
                    if(data === "OK")
                    {
                        var message = destroy_success;
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

});