$(function () {
    var link_label          = $("#link_label"),
        link_url            = $("#link_url"),
        add_menu_item       = $(".add_menu_item"),
        delete_menu         = $(".delete_menu"),
        menu_list           = $(".menu-list"),
        sortable            = $(".sortable"),
        pages_list          = $("#pages_list"),
        posts_list          = $("#posts_list"),
        categories_list     = $("#categories_list"),
        add_menu_form       = $("#add_menu_form"),
        menuContent         = $("#menuContent"),
        create_menu         = $(".create_menu");

    delete_menu.on('click', function () {
        var deleted_menu_id = $(this).prev('.deleted_menu_id').val();
        var that = $(this);

        var del_conf = confirm(delete_confirmation);
        if(del_conf){
            $.ajax({
                url: "/agentic-cms-laravel-admin/menus/" + deleted_menu_id + "/delete/",
                type: 'DELETE',
                data: {
                    "id": deleted_menu_id
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



    // Legacy jQuery UI nestedSortable drag (loaded from a googleapis CDN) was
    // removed for DESIGN_SYSTEM §5 (keyboard-first reordering) + §7 (no CDN).
    // Reordering is now handled by the self-hosted, accessible menu-reorder.js
    // (keyboard buttons/arrows + native HTML5 drag). This guard keeps the call
    // harmless if the plugin ever loads again.
    if (sortable.length > 0 && typeof sortable.nestedSortable === 'function') {
        sortable.nestedSortable({
            forcePlaceholderSize: true,
            items: 'li',
            handle: 'a',
            placeholder: 'menu-highlight',
            listType: 'ul',
            maxLevels: 5,
            opacity: .6,
        });
    }



    add_menu_item.on("click", function () {
        //var test = [];
        //test.push(pages_list.find('option:selected').text());
        getPagesFromSelects(pages_list);
        getPagesFromSelects(posts_list);
        getPagesFromSelects(categories_list);

        if(link_label.val() !== "" && link_url.val() !== ""){
            var li = '<li data-type="custom_link" data-title="'+ link_label.val() + '" data-link="'+ link_url.val() +'"><a href="javascript:void(0)"><span>'+ link_label.val() + '</span><button class="remove_menu_item" type="button">X</button></a></li>';
            menu_list.append(li);
            link_label.val("");
            link_url.val("");
        }
    });

    $(document).on("click", ".remove_menu_item", function(){
        $(this).closest("li").fadeOut(500, function(){
            $(this).remove();
        })
    });

    function FetchChild(){
        var data =[];
        $('.sortable > li').each(function(){
            data.push(buildJSON($(this)));
        });

        return data;
    }

    function buildJSON($li) {
        var subObj = { "title": $li.data('title'), 'slug':$li.data('link'), 'type':$li.data('type') };
        $li.children('ul').children().each(function() {
            if (!subObj.children) {
                subObj.children = [];
            }
            subObj.children.push(buildJSON($(this)));
        });
        return subObj;
    }


    function getPagesFromSelects(select)
    {
        var dataType = select.data('type');
        select.find('option:selected').each(function (ind,el) {
            var link = $(el).val();
            if(link == "") link = "/";
            var label = $(el).text();
            var li = '<li data-type="'+dataType+'" data-title="'+ label + '" data-link="'+ link +'"><a href="javascript:void(0)"><span>'+ label + '</span><button class="remove_menu_item" type="button">X</button></a></li>';

            menu_list.append(li);
        })
        select.val([]);
    }

    create_menu.on("click", function (e) {
        e.preventDefault();
        var obj = FetchChild();
        var json = JSON.stringify(obj, null);
        menuContent.val(json);
        add_menu_form.submit();
    })


});