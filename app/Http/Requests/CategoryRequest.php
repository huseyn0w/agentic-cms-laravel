<?php

namespace App\Http\Requests;

use App\Repositories\CPanelCategoryRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryRequest extends AgenticCmsLaravelRequest
{
    protected $table = 'category_translations';

    protected $ignore_column = 'category_id';

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

        // Normalise a numeric parent id to a real int BEFORE validation so the
        // value the cycle guard (Rule::notIn) sees is the same int that gets
        // persisted — otherwise a fractional string ("5.5") passes notIn yet
        // truncates to 5 on the integer column, slipping a cycle through.
        if ($this->filled('parent_category_id') && is_numeric($this->input('parent_category_id'))) {
            $this->merge(['parent_category_id' => (int) $this->input('parent_category_id')]);
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
            'description' => 'string|nullable',
            'meta_description' => 'string|nullable',
            'meta_keywords' => 'string|nullable',
            'canonical_url' => 'nullable|url|max:255',
            'meta_noindex' => 'sometimes|boolean',
            'title' => ['required', 'string', 'max:30'],
            'slug' => ['required', 'string', 'max:30'],
            'parent_category_id' => ['nullable', 'integer'],
        ];

        $title = $this->newRecordRule('title');
        $slug = $this->newRecordRule('slug');

        if ($this->route_name === 'cpanel_update_category') {
            $title = $this->updateRecordRule('title');
            $slug = $this->updateRecordRule('slug');

            // A category may not be parented to itself or any of its descendants
            // (would create a cycle). The dropdown already hides these; this is
            // the server-side guard. Read the id from the route here (not the
            // constructor's $term_id, which is unset before route binding).
            $termId = (int) $this->route('id');
            $forbidden = array_merge(
                [$termId],
                app(CPanelCategoryRepository::class)->descendantIds($termId)
            );

            $rules['parent_category_id'][] = Rule::notIn($forbidden);
        }

        $rules['title'][] = $title;
        $rules['slug'][] = $slug;

        return $rules;
    }
}
