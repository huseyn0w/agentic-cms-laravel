<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;

class ValidatePageData extends AgenticCmsLaravelRequest
{
    protected $table = 'page_translations';

    protected $ignore_column = 'page_id';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Phase 7: normalise the per-entity noindex checkbox to a real boolean so
     * an unchecked box persists as false rather than being dropped.
     */
    protected function prepareForValidation()
    {
        if ($this->has('meta_noindex')) {
            $this->merge(['meta_noindex' => $this->boolean('meta_noindex')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'author_id' => 'required|string|exists:users,id',
            'created_at' => 'sometimes|required|string',
            'updated_at' => 'sometimes|required|string',
            'content' => 'nullable|string',
            'meta_keywords' => 'string|nullable',
            'meta_description' => 'string|nullable',
            'canonical_url' => 'nullable|url|max:255',
            'meta_noindex' => 'sometimes|boolean',
            'custom_fields' => 'array',
            'template' => 'required|string',
            'status' => 'required|numeric',
        ];

        $title = $this->newRecordRule('title');
        $slug = $this->newRecordRule('slug');

        if ($this->route_name === 'cpanel_update_page') {
            $title = $this->updateRecordRule('title');
            $slug = $this->updateRecordRule('slug');
        }

        $rules['title'][] = $title;
        $rules['slug'][] = $slug;

        return $rules;
    }
}
