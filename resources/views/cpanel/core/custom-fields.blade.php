<?php
/**
 * AgenticCms-Laravel
 * File: custom-fields.blade.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 24.09.2019
 *
 * NOTE: the input names (custom_fields[...]) and the JS hook classes
 * (.inputRow, .remove_field, .repeater_cover, .form-control, .form-type, etc.)
 * are load-bearing — public/admin/js/custom-fields/*.js builds and parses this
 * exact structure. Only presentational wrappers/classes were modernised.
 */

$route_name = \Route::currentRouteName();

if(isset ($entity->custom_fields)  && !empty($entity->custom_fields)){
    $custom_fields = json_decode($entity->custom_fields);
}
?>

<div class="mt-6 border-t border-[var(--border)] pt-5">
    <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold text-[var(--text)]">
        <svg class="h-4 w-4 text-[var(--text-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h10M4 17h7" stroke-linecap="round"/></svg>
        @lang('cpanel/custom-fields.headline')
    </h4>
    <div class="flex flex-wrap gap-2">
        <button type="button" data-toggle="modal" data-target="#custom_text_modal" class="custom-field-btn custom-text">@lang('cpanel/custom-fields.type_text')</button>
        <button type="button" data-toggle="modal" data-target="#custom_textarea_modal" class="custom-field-btn custom-textarea">@lang('cpanel/custom-fields.type_textarea')</button>
        <button type="button" data-toggle="modal" data-target="#custom_image_modal" class="custom-field-btn custom-image">@lang('cpanel/custom-fields.type_image')</button>
        <button type="button" data-toggle="modal" data-target="#custom_link_modal" class="custom-field-btn custom-link">@lang('cpanel/custom-fields.type_link')</button>
        <button type="button" data-toggle="modal" data-target="#custom_category_modal" class="custom-field-btn custom-link">@lang('cpanel/custom-fields.type_category')</button>
        <button type="button" id="custom_repeater" class="custom-field-btn custom-repeater">@lang('cpanel/custom-fields.type_repeater')</button>
    </div>
</div>

<div class="mt-4">
    <div id="custom_repeater_cover"></div>
    <div id="custom_repeater_buttons" class="mb-4 flex flex-wrap gap-2">
        <button type="button" class="btn btn-ghost add-repeater-field-btn" id="add_repeater_field">@lang('cpanel/custom-fields.add_field')</button>
        <button type="button" class="btn btn-success insert-repeater-fields-btn" id="insert_repeater_fields">@lang('cpanel/custom-fields.insert_field')</button>
    </div>

    <div id="custom_fields_cover">
        @if(isset ($custom_fields)  && !empty($custom_fields))
            @foreach($custom_fields as $key => $item)
                @php $repeater_count = 0; @endphp

                @if($item->type ===  "repeater")
                    @php $repeater_count++; @endphp
                    <div class="repeater_cover repeater_{{$repeater_count}}_cover">
                        <div class="group_headline">
                            <h4>{{$item->admin_label}}</h4>
                            <button type="button" class="btn btn-danger deleteGroup">@lang('cpanel/custom-fields.delete_group')</button>
                            <button type="button" class="btn btn-ghost toogleGroup">@lang('cpanel/custom-fields.toggle_group')</button>
                            <input type="hidden" name="custom_fields[{{$key}}][type]" value="{{$item->type}}">
                            <input type="hidden" name="custom_fields[{{$key}}][admin_label]" value="{{$item->admin_label}}">
                        </div>

                        @php
                            $checkbox_item_count = -1;
                            $row_count = -1;
                            $row_item_count = -1;
                            $checkbox_item_id_count = 0;
                        @endphp

                        @foreach($item->value as $row)
                            <div class="repeater_content_cover">
                                @php
                                    $checkbox_item_count++;
                                    $row_count++;
                                    $row_item_count++;
                                    $checkbox_item_id_count++;
                                @endphp

                                @foreach($row as $row_item_key => $row_item)
                                    @php $row_item_count++; @endphp
                                    <div class="repeater_item">
                                        @if($row_item->type === "link")
                                            <div class="row inputRow" data-type="{{$row_item->type}}" data-key="{{$key}}" data-row="{{$checkbox_item_count}}" data-name="{{$row_item_key}}">
                                                <div class="col-12">
                                                    <p class="mb-2 text-sm font-medium text-[var(--text-muted)]">{{$row_item->admin_label}}</p>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group custom-form-group">
                                                        <label>@lang('cpanel/custom-fields.link_label')</label>
                                                        <input type="text" class="form-control form-label" name="custom_fields[{{$key}}][value][row-{{$checkbox_item_count}}][{{$row_item_key}}][value][label]" value="{{$row_item->value->label}}">
                                                        <label>@lang('cpanel/custom-fields.link_url')</label>
                                                        <input type="text" class="form-control form-url" name="custom_fields[{{$key}}][value][row-{{$checkbox_item_count}}][{{$row_item_key}}][value][url]" value="{{$row_item->value->url}}">
                                                        <label class="mt-2 flex cursor-pointer items-center gap-2.5 text-sm text-[var(--text-muted)]" for="{{$key.'__'.$row_item_count}}">
                                                            <input id="{{$key.'__'.$row_item_count}}" class="form-check-input pages-checkbox-input form-tab exist-input-checkbox pages-checkbox-input" {{$row_item->value->target === "1" ? "checked" : null}} type="checkbox">
                                                            @lang('cpanel/custom-fields.link_target')
                                                        </label>
                                                        <input name="custom_fields[{{$key}}][value][row-{{$checkbox_item_count}}][{{$row_item_key}}][value][target]" type="hidden" class="checkbox-link-value" value="{{$row_item->value->target === "1" ? "1" : "0"}}">
                                                        <input type="hidden" class="form-type" name="custom_fields[{{$key}}][value][row-{{$checkbox_item_count}}][{{$row_item_key}}][type]" value="link">
                                                        <input type="hidden" class="form-admin-label" name="custom_fields[{{$key}}][value][row-{{$checkbox_item_count}}][{{$row_item_key}}][admin_label]" value="{{$row_item->admin_label}}">
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-danger remove_item">@lang('cpanel/custom-fields.delete_row')</button>
                                        @elseif($row_item->type === "text")
                                            <div class="row inputRow" data-type="{{$row_item->type}}" data-key="{{$key}}" data-row="{{$row_count}}" data-name="{{$row_item_key}}">
                                                <div class="col-md-12">
                                                    <div class="form-group custom-form-group">
                                                        <label>{{$row_item->admin_label}}</label>
                                                        <input type="text" name="custom_fields[{{$key}}][value][row-{{$row_count}}][{{$row_item_key}}][value]" class="form-control" value="{{$row_item->value}}">
                                                        <input type="hidden" name="custom_fields[{{$key}}][value][row-{{$row_count}}][{{$row_item_key}}][type]" class="form-type" value="text">
                                                        <input type="hidden" class="form-admin-label" name="custom_fields[{{$key}}][value][row-{{$checkbox_item_count}}][{{$row_item_key}}][admin_label]" value="{{$row_item->admin_label}}">
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($row_item->type === "textarea")
                                            <div class="row inputRow" data-type="{{$row_item->type}}" data-key="{{$key}}" data-row="{{$row_count}}" data-name="{{$row_item_key}}">
                                                <div class="col-md-12">
                                                    <div class="form-group custom-form-group">
                                                        <label>{{$row_item->admin_label}}</label>
                                                        <textarea name="custom_fields[{{$key}}][value][row-{{$row_count}}][{{$row_item_key}}][value]" class="form-control my-editor" value="{{$row_item->value}}" id="custom_fields[{{$key}}][value][row-{{$row_count}}][{{$row_item_key}}]">{{$row_item->value}}</textarea>
                                                        <input type="hidden" name="custom_fields[{{$key}}][value][row-{{$row_count}}][{{$row_item_key}}][type]" class="form-type" value="textarea">
                                                        <input type="hidden" class="form-admin-label" name="custom_fields[{{$key}}][value][row-{{$checkbox_item_count}}][{{$row_item_key}}][admin_label]" value="{{$row_item->admin_label}}">
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($row_item->type === "image")
                                            <div class="row inputRow" data-type="{{$row_item->type}}" data-key="{{$key}}" data-row="{{$row_count}}" data-name="{{$row_item_key}}">
                                                <div class="col-12">
                                                    <p class="mb-2 text-sm font-medium text-[var(--text-muted)]">{{$row_item->admin_label}}</p>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group custom-form-group">
                                                        <label for="custom_input_image">@lang('cpanel/custom-fields.image_label')</label>
                                                        <span class="input-group-btn">
                                                            <a id="lfm" data-input="thumbnail_{{$row_item_count}}" data-preview="holder" class="choose-image">
                                                                @lang('cpanel/custom-fields.image_preview_label')
                                                            </a>
                                                        </span>
                                                        <input id="thumbnail_{{$row_item_count}}" class="form-control form-image mt-2" type="text" name="custom_fields[{{$key}}][value][row-{{$row_count}}][{{$row_item_key}}][value]" value="{{$row_item->value}}">
                                                        <input type="hidden" name="custom_fields[{{$key}}][value][row-{{$row_count}}][{{$row_item_key}}][type]" class="form-type" value="image">
                                                        <input type="hidden" class="form-admin-label" name="custom_fields[{{$key}}][value][row-{{$checkbox_item_count}}][{{$row_item_key}}][admin_label]" value="{{$row_item->admin_label}}">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        <button type="button" class="btn btn-ghost duplicate_group">@lang('cpanel/custom-fields.add_row')</button>
                    </div>
                @elseif($item->type === "text")
                    <div class="row inputRow">
                        <div class="form-group custom-form-group">
                            <label>{{$item->admin_label}}</label>
                            <input type="text" required name="custom_fields[{{$key}}][value]" class="form-control" value="{{$item->value}}">
                            <input type="hidden" name="custom_fields[{{$key}}][type]" value="text">
                            <input type="hidden" name="custom_fields[{{$key}}][admin_label]" value="{{$item->admin_label}}">
                            <button type="button" class="remove_field">X</button>
                        </div>
                    </div>
                @elseif($item->type === "category")
                    <div class="row inputRow">
                        <div class="form-group custom-form-group">
                            <label>{{$item->admin_label}}</label>
                            <select name="custom_fields[{{$key}}][value]" required class="form-control">
                                @foreach($categories_list as $category)
                                    <option value="{{$category->category_id}}" {{$item->value == $category->category_id ? 'selected' : null}}>{{$category->title}}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="custom_fields[{{$key}}][type]" value="category">
                            <input type="hidden" name="custom_fields[{{$key}}][admin_label]" value="{{$item->admin_label}}">
                            <button type="button" class="remove_field">X</button>
                        </div>
                    </div>
                @elseif($item->type === "textarea")
                    <div class="row inputRow">
                        <div class="form-group custom-form-group">
                            <label>{{$item->admin_label}}</label>
                            <textarea name="custom_fields[{{$key}}][value]" required class="form-control my-editor">{{$item->value}}</textarea>
                            <input type="hidden" name="custom_fields[{{$key}}][type]" value="textarea">
                            <input type="hidden" name="custom_fields[{{$key}}][admin_label]" value="{{$item->admin_label}}">
                            <button type="button" class="remove_field">X</button>
                        </div>
                    </div>
                @elseif($item->type === "link")
                    <div class="row inputRow">
                        <div class="form-group custom-form-group">
                            <label>{{$item->admin_label}}</label>
                            <label>@lang('cpanel/custom-fields.link_label')</label>
                            <input type="text" class="form-control" name="custom_fields[{{$key}}][value][label]" value="{{$item->value->label}}">
                            <label>@lang('cpanel/custom-fields.link_url')</label>
                            <input type="text" class="form-control" name="custom_fields[{{$key}}][value][url]" value="{{$item->value->url}}">
                            <label class="mt-2 flex cursor-pointer items-center gap-2.5 text-sm text-[var(--text-muted)]" for="{{$key}}">
                                <input type="checkbox" id="{{$key}}" class="form-check-input exist-input-checkbox pages-checkbox-input form-tab" {{$item->value->target === "1" ? "checked" : null}}>
                                @lang('cpanel/custom-fields.open_in_new_tab')
                            </label>
                            <input name="custom_fields[{{$key}}][value][target]" type="hidden" class="checkbox-link-value" value="{{$item->value->target}}">
                            <input type="hidden" name="custom_fields[{{$key}}][type]" value="link">
                            <input type="hidden" name="custom_fields[{{$key}}][admin_label]" value="{{$item->admin_label}}">
                            <button type="button" class="remove_field">X</button>
                        </div>
                    </div>
                @elseif($item->type === "image")
                    <div class="row inputRow">
                        <div class="form-group custom-form-group">
                            <label>{{$item->admin_label}}</label>
                            <input type="text" required name="custom_fields[{{$key}}][value]" class="form-control" value="{{$item->value}}">
                            <input type="hidden" name="custom_fields[{{$key}}][type]"  value="image">
                            <input type="hidden" name="custom_fields[{{$key}}][admin_label]" value="{{$item->admin_label}}">
                            <button type="button" class="remove_field">X</button>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</div>
