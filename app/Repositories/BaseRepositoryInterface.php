<?php

/**
 * AgenticCms-Laravel
 * File: BaseRepositoryInterface.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 28.07.2019
 */

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * Contract for the application's Eloquent-backed repositories.
 *
 * Implementations encapsulate all persistence for a single model (or its
 * translatable counterpart). Write methods (create/update) accept either a
 * FormRequest, a plain Request, or an explicit associative array. FormRequests
 * are always reduced to their validated() payload, so only whitelisted data is
 * ever mass assigned — callers must never hand raw, unvalidated input to a
 * repository. Read methods return Eloquent models/collections (or trigger the
 * shared not-found / abort helpers) so controllers stay thin.
 */
interface BaseRepositoryInterface
{
    /**
     * Persist a new record from validated/whitelisted data.
     *
     * @param  FormRequest|Request|array  $data
     */
    public function create($data);

    /**
     * Get all records
     */
    public function all();

    /**
     * Get only limited amount of records
     */
    public function only($count, $page = 1);

    /**
     * Get first value from database
     */
    public function first();

    /**
     * Get one record by $param
     */
    public function get($param);

    /**
     * Get one record by custom parameter
     *
     * @param  array  $fields
     */
    public function getBy($parameter, $value, $field = []);

    /**
     * Get record by ID
     */
    public function update(int $id, $newData);

    /**
     * Update one record by custom parameter
     */
    public function updateWhere($newData, $parameter);

    /**
     * Delete record by ID
     */
    public function delete($id);

    /**
     * Delete record by custom parameter
     */
    public function deleteWhere($parameter);
}
