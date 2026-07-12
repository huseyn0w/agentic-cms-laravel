$(function () {

    var AllServices         = $("#selectAll"),
        services_checkbox   = $(".services-checkbox-input"),
        delete_service      = $(".delete_service"),
        destroy_service     = $(".destroy_service");


    AllServices.on("click", function () {
        AllServices.toggleClass('all-checked');
        services_checkbox.each(function(index,el){

            if(AllServices.hasClass('all-checked')){
                $(el).prop('checked', 'checked');
            }
            else {
                $(el).prop('checked', false);
            }
        });
    });

    services_checkbox.on('click', function () {
       if(!$(this).prop('checked')){
           AllServices.removeClass('all-checked');
           AllServices.prop('checked', false);
       }
    });

    delete_service.on('click', function () {
        var deleted_service_id = $(this).prev('.deleted_service_id').val();
        var that = $(this);

        var delete_conf = confirm(delete_confirmation);
        if(delete_conf){
            $.ajax({
                url: "/agentic-cms-laravel-admin/services/" + deleted_service_id + "/delete",
                type: 'DELETE',
                data: {
                    "id": deleted_service_id
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

    destroy_service.on('click', function () {
        var destroyed_service_id = $(this).prev('.deleted_service_id').val();
        var that = $(this);

        var destroy_conf = confirm(destroy_confirmation);
        if(destroy_conf){
            $.ajax({
                url: "/agentic-cms-laravel-admin/services/" + destroyed_service_id + "/destroy",
                type: 'DELETE',
                data: {
                    "id": destroyed_service_id
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
