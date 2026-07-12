<?php

/**
 * AgenticCms-Laravel
 * File: AgenticCmsLaravelRequest.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 23.11.2019
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class AgenticCmsLaravelRequest extends FormRequest
{
    // NOTE: do NOT name this $locale — Symfony 7's HttpFoundation\Request (the
    // parent of FormRequest) declares a typed `protected ?string $locale`, and
    // an untyped redeclaration here is a fatal type-incompatibility on Laravel 11.
    protected ?string $currentLocale = null;

    protected $term_id;

    protected $table;

    protected $ignore_column;

    protected $route_name;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->currentLocale = app()->getLocale();

        $this->term_id = (int) $this->route('id');

        $this->route_name = Route::currentRouteName();
    }

    protected function newRecordRule(string $field)
    {
        return Rule::unique($this->table, $field)->where(function ($query) {
            return $query->where('locale', $this->currentLocale);
        });
    }

    protected function updateRecordRule(string $field)
    {
        $term_id = $this->route('id');

        return Rule::unique($this->table, $field)->where(function ($query) {
            return $query->where('locale', $this->currentLocale);
        })->ignore($term_id, $this->ignore_column);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
