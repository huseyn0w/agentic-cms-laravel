<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;

class MenuRequest extends AgenticCmsLaravelRequest
{
    protected $table = 'menu_translations';

    protected $ignore_column = 'menu_id';

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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [
            'content' => 'required|string|min:3',
            'slug' => 'required|string',
            'title' => ['required', 'string', 'max:20'],
        ];

        $title = $this->newRecordRule('title');

        if ($this->route_name === 'cpanel_update_menu') {
            $title = $this->updateRecordRule('title');
        }

        $rules['title'][] = $title;

        return $rules;
    }
}
