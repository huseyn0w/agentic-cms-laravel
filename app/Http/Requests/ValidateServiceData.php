<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;

class ValidateServiceData extends AgenticCmsLaravelRequest
{
    protected $table = 'service_translations';

    protected $ignore_column = 'service_id';

    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Normalise the per-entity noindex checkbox to a real boolean so an
     * unchecked box persists as false rather than being dropped.
     */
    protected function prepareForValidation()
    {
        if ($this->has('meta_noindex')) {
            $this->merge(['meta_noindex' => $this->boolean('meta_noindex')]);
        }
    }

    public function rules()
    {
        $rules = [
            'title' => ['string', 'required', 'max:120'],
            'slug' => ['required', 'string', 'max:160'],
            'icon' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'url'],
            'meta_keywords' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'meta_noindex' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['required', 'numeric'],
        ];

        // slug is unique per locale on service_translations.
        $slug = $this->route_name === 'cpanel_update_service'
            ? $this->updateRecordRule('slug')
            : $this->newRecordRule('slug');

        $rules['slug'][] = $slug;

        return $rules;
    }
}
