$(function () {

    var AllUsersCategories       = $("#selectAll"),
        categories_checkbox      = $(".categories-checkbox-input"),
        delete_category          = $(".delete_category");


    AllUsersCategories.on("click", function () {
        AllUsersCategories.toggleClass('all-checked');
        categories_checkbox.each(function(index,el){

            if(AllUsersCategories.hasClass('all-checked')){
                $(el).prop('checked', 'checked');
            }
            else {
                $(el).prop('checked', false);
            }
        });
    });

    categories_checkbox.on('click', function () {
        if(!$(this).prop('checked')){
            AllUsersCategories.removeClass('all-checked');
            AllUsersCategories.prop('checked', false);
        }
    });

    delete_category.on('click', function () {
        var deleted_category_id = $(this).prev('.deleted_category_id').val();
        var that = $(this);

        var delete_conf = confirm(delete_confirmation);
        if(delete_conf){
            $.ajax({
                url: "/agentic-cms-laravel-admin/categories/" + deleted_category_id + "/delete/",
                type: 'DELETE',
                data: {
                    "id": deleted_category_id
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

});