$(function () {

    var delete_role = $(".delete_role");

    delete_role.on('click', function () {
        var deleted_role_id = $(this).prev('.deleted_role_id').val();
        var that = $(this);

        var del_conf = confirm(delete_confirmation);
        if(del_conf){
            $.ajax({
                url: "/agentic-cms-laravel-admin/roles/" + deleted_role_id + "/delete/",
                type: 'DELETE',
                data: {
                    "id": deleted_role_id
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