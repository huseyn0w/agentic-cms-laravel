<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;

class ValidatePostData extends AgenticCmsLaravelRequest
{
    protected $table = 'post_translations';

    protected $ignore_column = 'post_id';

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
            'title' => ['string', 'required', 'max:20'],
            'slug' => ['required', 'string', 'max:20'],
            'content' => 'nullable|string',
            'preview' => 'nullable|string',
            'author_id' => 'required|integer|exists:users,id',
            'meta_keywords' => 'required|string',
            'meta_description' => 'required|string',
            'canonical_url' => 'nullable|url|max:255',
            'meta_noindex' => 'sometimes|boolean',
            'created_at' => 'sometimes|required|string',
            'updated_at' => 'sometimes|required|string',
            'category' => 'required|array',
            'thumbnail' => 'nullable|url',
            'status' => 'required|numeric',
            // Optional future publish time; empty clears the schedule.
            'scheduled_at' => 'nullable|date',
        ];

        $title = $this->newRecordRule('title');
        $slug = $this->newRecordRule('slug');

        if ($this->route_name === 'cpanel_update_post') {
            $title = $this->updateRecordRule('title');
            $slug = $this->updateRecordRule('slug');
        }

        $rules['title'][] = $title;
        $rules['slug'][] = $slug;

        return $rules;
    }
}
