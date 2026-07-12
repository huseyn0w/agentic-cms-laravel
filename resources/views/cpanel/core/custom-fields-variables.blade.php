<?php
/**
 * AgenticCms-Laravel
 * File: custom-fields-variables.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 19.11.2019
 */

$categories = get_post_categories_list(['category_id','title']);

?>

<script>
    var link_label          = '@lang('cpanel/custom-fields.link_label')',
        link_url            = '@lang('cpanel/custom-fields.link_url')',
        link_target         = '@lang('cpanel/custom-fields.link_target')',
        repeater_headline   = '@lang('cpanel/custom-fields.repeater_label')',
        repeater_cover_key  = '@lang('cpanel/custom-fields.repeater_key')',
        field_type          = '@lang('cpanel/custom-fields.field_type')',
        field_type_preview  = '@lang('cpanel/custom-fields.field_type_preview')',
        field_text          = '@lang('cpanel/custom-fields.type_text')',
        field_textarea      = '@lang('cpanel/custom-fields.type_textarea')',
        field_link          = '@lang('cpanel/custom-fields.type_link')',
        field_category      = '@lang('cpanel/custom-fields.type_category')',
        categories_list     = '@php echo $categories @endphp',
        field_image         = '@lang('cpanel/custom-fields.type_image')',
        text_label          = '@lang('cpanel/custom-fields.text_label')',
        text_name           = '@lang('cpanel/custom-fields.text_name')',
        group_delete_conf   = '@lang('cpanel/custom-fields.group_delete_conf')',
        delete_group_label  = '@lang('cpanel/custom-fields.delete_group')',
        toggle_group_label  = '@lang('cpanel/custom-fields.toggle_group')',
        delete_row          = '@lang('cpanel/custom-fields.delete_row')',
        add_row             = '@lang('cpanel/custom-fields.add_row')',
        image_label         = '@lang('cpanel/custom-fields.image_label')',
        image_preview_label = '@lang('cpanel/custom-fields.image_preview_label')';
</script>
