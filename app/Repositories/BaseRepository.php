<?php

/**
 * AgenticCms-Laravel
 * File: BaseRepository.php
 * Created by Elman (https://linkedin.com/in/huseyn0w)
 * Date: 25.07.2019
 */

namespace App\Repositories;

use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    protected $locale;

    protected $select;

    protected $select_fields;

    protected $main_table;

    protected $translated_table;

    protected $translated_model;

    // Front-family repositories (PostRepository, PageRepository, CategoryRepository,
    // ServiceRepository) assign their translation model here. Declared so the
    // assignment is a real, typed property rather than a dynamic one.
    protected $translated_table_model;

    protected $translated_table_join_column;

    protected $select_fields_ready_array;

    /**
     * Validated request keys that are NOT persisted attributes of the target
     * model and must be stripped before mass assignment. These are consumed
     * elsewhere (e.g. the PostObserver reads `category` straight off the
     * request to sync the category_post pivot), so letting them reach
     * Model::create()/update() would raise a MassAssignmentException now that
     * the models carry a minimal $fillable (Phase 3 hardening).
     *
     * @var array<int, string>
     */
    protected $non_persisted_fields = [];

    /**
     * Relations eager-loaded by the translatable read paths (getTranslatedBy /
     * translatedOnly). Defaults to the author relation that content models
     * (Post/Page/Category) carry; taxonomies without an author override this.
     *
     * @var array<int, string>
     */
    protected $eager_relations = ['author'];

    public function __construct() {}

    /**
     * Resolve a request (FormRequest, plain Request or array) into a
     * whitelisted associative array of attributes that are safe to mass
     * assign. FormRequests are reduced to their validated() payload so raw,
     * unvalidated user input can never reach create()/update().
     *
     * @param  FormRequest|Request|array  $request
     * @return array<string, mixed>
     */
    protected function extractData($request): array
    {
        if (is_array($request)) {
            return $request;
        }

        if ($request instanceof FormRequest) {
            return $request->validated();
        }

        if ($request instanceof Request) {
            return $request->all();
        }

        return (array) $request;
    }

    /**
     * When the current route targets a translatable entity, switch the active
     * model to its translation model and inject the (server-derived) join
     * column and locale into the data array. These values come from the route
     * and the session locale, never from arbitrary user input.
     *
     * @param  FormRequest|Request|array  $request
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function checkForTranslation($request, array $data): array
    {
        $routeId = (! is_array($request) && method_exists($request, 'route'))
            ? $request->route('id')
            : null;

        if (! empty($routeId) && ! is_null($this->translated_model)) {
            $this->model = $this->translated_model;
            $data[$this->translated_table_join_column] = $routeId;
            $data['locale'] = get_current_lang();
        }

        return $data;
    }

    /**
     * Drop validated keys that do not map to a persisted column on the target
     * model (see $non_persisted_fields).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function stripNonPersistedFields(array $data): array
    {
        foreach ($this->non_persisted_fields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    public function create($request)
    {
        $data = $this->extractData($request);
        $data = $this->stripNonPersistedFields($data);
        $data = $this->checkForTranslation($request, $data);

        try {
            $result = $this->model::create($data);
        } catch (QueryException|PDOException|\Error $e) {
            Log::error('Repository create failed', [
                'model' => is_object($this->model) ? get_class($this->model) : $this->model,
                'exception' => $e->getMessage(),
            ]);

            return throwAbort();
        }

        return $result;
    }

    public function all()
    {
        try {
            $data = $this->model::all();
        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $data;
    }

    public function get($param)
    {
        try {
            $data = $this->model::find($param);
        } catch (QueryException|PDOException|\Error $e) {
            Log::error('Repository get failed', [
                'param' => $param,
                'exception' => $e->getMessage(),
            ]);

            return throwAbort();
        }

        if (is_null($data)) {
            return throwNotFound();
        }

        return $data;
    }

    public function selectBy($fields)
    {
        try {
            $data = $this->model::select($fields)->get();
        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $data;
    }

    public function first()
    {
        try {
            $data = $this->model::first();
        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $data;
    }

    public function only($count, $page = 1)
    {

        if (! empty($this->translated_table && ! empty($this->translated_table_join_column))) {
            $data = $this->translatedOnly($count, $this->main_table, $this->translated_table, $this->translated_table_join_column, $page);
        } else {
            $data = $this->nonTranslatedOnly($count);
        }

        return $data;
    }

    protected function nonTranslatedOnly($count)
    {
        $fields = $this->select_fields;

        try {
            empty($fields) ? $data = $this->model::paginate($count) : $data = $this->model::select($fields)->paginate($count);
        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $data;
    }

    protected function translatedOnly($count, $main_table_name, $translated_table_name, $parent_table_join_column, $page = 1)
    {

        $this->select_fields_ready_array = $this->generateSelectFieldsArray($this->select_fields);
        $this->locale = get_current_lang();

        try {
            $data = $this->model::join($translated_table_name, $main_table_name.'.id', '=', $translated_table_name.'.'.$parent_table_join_column)
                ->select($this->select_fields_ready_array)
                ->where($translated_table_name.'.locale', $this->locale)
                ->with($this->eager_relations)->paginate($count, ['*'], 'page', $page);

        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $data;
    }

    public function getBy($paramName, $paramValue, $fields = [])
    {
        if (! empty($this->translated_table && ! empty($this->translated_table_join_column))) {
            $data = $this->getTranslatedBy($paramName, $paramValue);
        } else {
            $data = $this->getNonTranslatedBy($paramName, $paramValue, $fields);
        }

        return $data;
    }

    protected function getNonTranslatedBy($paramName, $paramValue, $fields = [])
    {

        if (! empty($fields)) {
            $data = $this->model::select($fields)->where($paramName, $paramValue)->first();
        } else {
            $data = $this->model::where($paramName, $paramValue)->first();
        }

        if (! $data) {
            return throwNotFound();
        }

        return $data;
    }

    //    protected function pre_getTranslatedBy($param, $value)
    //    {
    //        return $this->translated_table_model::where($param, $value)->firstOrFail();
    //    }

    public function getTranslatedBy($param, $value)
    {

        $this->select_fields_ready_array = $this->generateSelectFieldsArray($this->select_fields);

        $this->locale = get_current_lang();

        $searchColumn = $this->getSearchedTable($param);

        if (is_null($searchColumn)) {
            throwAbort();
        }

        try {
            $query = $this->model::join($this->translated_table, $this->main_table.'.id', '=', $this->translated_table.'.'.$this->translated_table_join_column)
                ->select($this->select_fields_ready_array)
                ->where($this->translated_table.'.locale', $this->locale)
                ->where($searchColumn.'.'.$param, $value)
                ->with($this->eager_relations);

            $data = $this->applyFrontReadScope($query)->first();

        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $data;
    }

    /**
     * Hook for public (front) read paths to add visibility constraints to a
     * single-record translated query. No-op by default; the front PostRepository
     * overrides it to hide future-scheduled posts. Admin repositories keep the
     * no-op so the panel still sees everything.
     *
     * @param  mixed  $query
     * @return mixed
     */
    protected function applyFrontReadScope($query)
    {
        return $query;
    }

    public function update(int $id, $request)
    {
        $result = false;

        $data = $this->extractData($request);
        $data = $this->stripNonPersistedFields($data);

        try {

            $instance = $this->model::find($id);

            if (is_null($instance)) {
                return throwNotFound();
            }

            // Atomic with any write-side effects fired by the model's observers
            // (e.g. the pre-update revision snapshot): commit or roll back as one.
            if (DB::transaction(fn () => $instance->update($data))) {
                $result = true;
            }

        } catch (QueryException|PDOException|\Error $e) {
            Log::error('Repository update failed', [
                'model' => is_object($this->model) ? get_class($this->model) : $this->model,
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return throwAbort();
        }

        return $result;
    }

    public function updateWhere($data, $parameter = 'id') {}

    /**
     * Resolve the translation row for a translatable entity in a given locale
     * (e.g. the post_translations row for post #5 in 'en'). Returns null for
     * non-translatable repositories or when no such translation exists. Used by
     * the revision UI, where revisions are keyed on the translation row id.
     */
    public function translationFor(int $parentId, string $locale): ?Model
    {
        if (is_null($this->translated_model)) {
            return null;
        }

        return $this->translated_model->newQuery()
            ->where($this->translated_table_join_column, $parentId)
            ->where('locale', $locale)
            ->first();
    }

    public function delete($id)
    {
        $result = false;

        try {
            if ($this->model::destroy($id)) {
                $result = true;
            }
        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $result;
    }

    public function deleteWhere($parameter = 'id') {}

    public function restore($id)
    {
        $result = false;

        try {
            if ($this->model::withTrashed()->where('id', $id)->restore()) {
                $result = true;
            }
        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $result;
    }

    public function destroy($id)
    {
        $result = false;
        try {
            if ($this->model::where('id', $id)->forceDelete()) {
                $result = true;
            }
        } catch (QueryException $e) {
            throwAbort();
        } catch (PDOException $e) {
            throwAbort();
        } catch (\Error $e) {
            throwAbort();
        }

        return $result;
    }

    protected function generateSelectFieldsArray($fields, $main_table_name = null, $translated_table_name = null)
    {
        if (empty($fields) || ! is_array($fields)) {
            return false;
        }

        if (is_null($main_table_name)) {
            $main_table_name = $this->main_table;
        }
        if (is_null($translated_table_name)) {
            $translated_table_name = $this->translated_table;
        }

        $fields_array = [];

        $main_table_columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($main_table_name);

        if (! empty($translated_table_name)) {
            $translated_table_columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($translated_table_name);
        }

        foreach ($fields as $field) {
            if (in_array($field, $main_table_columns)) {
                $fields_array[] = $main_table_name.'.'.$field;
            } elseif (! empty($translated_table_name) && in_array($field, $translated_table_columns)) {
                $fields_array[] = $translated_table_name.'.'.$field;
            }
        }

        return $fields_array;
    }

    protected function getSearchedTable($column)
    {
        $table_name = null;

        if (in_array($this->main_table.'.'.$column, $this->select_fields_ready_array)) {
            $table_name = $this->main_table;
        } elseif (isset($this->translated_table) && in_array($this->translated_table.'.'.$column, $this->select_fields_ready_array)) {
            $table_name = $this->translated_table;
        }

        return $table_name;
    }
}
