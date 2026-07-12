<?php
/**
 * AgenticCms-Laravel
 * File: modals.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 24.09.2019
 *
 * Reimplemented as Tailwind modals driven by resources/js/admin.js. The element
 * IDs, input names, button IDs and the data-toggle/data-dismiss="modal" hooks
 * are preserved so the custom-fields/*.js scripts (which call
 * $('#id').modal('hide')) keep working unchanged.
 */
?>

{{-- Text field --}}
<div class="modal" id="custom_text_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body space-y-4">
                <div class="field">
                    <label for="custom_text_label" class="field-label">@lang('cpanel/custom-fields.text_label')</label>
                    <input type="text" id="custom_text_label" required class="form-control" name="input_label">
                </div>
                <div class="field">
                    <label for="custom_input_name" class="field-label">@lang('cpanel/custom-fields.text_name')</label>
                    <input type="text" id="custom_input_name" required class="form-control" name="input_name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-dismiss="modal">@lang('cpanel/custom-fields.close_button_label')</button>
                <button type="button" class="btn btn-info" id="add_custom_input_text">@lang('cpanel/custom-fields.add_button_label')</button>
            </div>
        </div>
    </div>
</div>

{{-- Textarea field --}}
<div class="modal" id="custom_textarea_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body space-y-4">
                <div class="field">
                    <label for="custom_textarea_label" class="field-label">@lang('cpanel/custom-fields.textarea_label')</label>
                    <input type="text" id="custom_textarea_label" required class="form-control" name="input_label">
                </div>
                <div class="field">
                    <label for="custom_textarea_name" class="field-label">@lang('cpanel/custom-fields.text_name')</label>
                    <input type="text" id="custom_textarea_name" required class="form-control" name="input_name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-dismiss="modal">@lang('cpanel/custom-fields.close_button_label')</button>
                <button type="button" class="btn btn-info" id="add_custom_textarea_input">@lang('cpanel/custom-fields.add_button_label')</button>
            </div>
        </div>
    </div>
</div>

{{-- Link field --}}
<div class="modal" id="custom_link_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body space-y-4">
                <div class="field">
                    <label for="custom_link_key" class="field-label">@lang('cpanel/custom-fields.link_key')</label>
                    <input type="text" id="custom_link_key" required class="form-control" name="input_key">
                </div>
                <div class="field">
                    <label for="admin_text_label" class="field-label">@lang('cpanel/custom-fields.link_cpanel_label')</label>
                    <input type="text" id="admin_text_label" required class="form-control" name="input_admin_label">
                </div>
                <div class="field">
                    <label for="custom_link_label" class="field-label">@lang('cpanel/custom-fields.link_label')</label>
                    <input type="text" id="custom_link_label" required class="form-control" name="input_label">
                </div>
                <div class="field">
                    <label for="custom_link_url" class="field-label">@lang('cpanel/custom-fields.link_url')</label>
                    <input type="text" id="custom_link_url" required class="form-control" name="input_name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-dismiss="modal">@lang('cpanel/custom-fields.close_button_label')</button>
                <button type="button" class="btn btn-info" id="add_custom_link">@lang('cpanel/custom-fields.add_button_label')</button>
            </div>
        </div>
    </div>
</div>

{{-- Image field --}}
<div class="modal" id="custom_image_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body space-y-4">
                <div class="field">
                    <label for="custom_image_label" class="field-label">@lang('cpanel/custom-fields.image_label')</label>
                    <input type="text" id="custom_image_label" required class="form-control" name="input_label">
                </div>
                <div class="field">
                    <label for="custom_image_key" class="field-label">@lang('cpanel/custom-fields.image_key')</label>
                    <input type="text" id="custom_image_key" required class="form-control" name="input_key">
                </div>
                <div class="field">
                    <label for="custom_input_image" class="field-label">@lang('cpanel/custom-fields.image_key')</label>
                    <span class="input-group-btn">
                        <a id="lfm" data-input="thumbnail" data-preview="holder" class="choose-image">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 19"/></svg>
                            @lang('cpanel/custom-fields.image_preview_label')
                        </a>
                    </span>
                    <input id="thumbnail" class="form-control mt-2" type="text" name="filepath">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-dismiss="modal">@lang('cpanel/custom-fields.close_button_label')</button>
                <button type="button" class="btn btn-info" id="add_custom_image">@lang('cpanel/custom-fields.add_button_label')</button>
            </div>
        </div>
    </div>
</div>

{{-- Category field --}}
<div class="modal" id="custom_category_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body space-y-4">
                <div class="field">
                    <label for="custom_category_label" class="field-label">@lang('cpanel/custom-fields.text_label')</label>
                    <input type="text" id="custom_category_label" required class="form-control" name="category_label">
                </div>
                <div class="field">
                    <label for="custom_category_name" class="field-label">@lang('cpanel/custom-fields.text_name')</label>
                    <input type="text" id="custom_category_name" required class="form-control" name="category_name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-dismiss="modal">@lang('cpanel/custom-fields.close_button_label')</button>
                <button type="button" class="btn btn-info" id="add_custom_category_text">@lang('cpanel/custom-fields.add_button_label')</button>
            </div>
        </div>
    </div>
</div>

<script>
    var site_url = "<?php echo config('app.url'); ?>/";
</script>
