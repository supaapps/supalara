<?php

namespace Supaapps\Supalara\Controllers\CrudTraits;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait CrudIndexTrait
{
    public function index(Request $request)
    {
        $query = $this->model::query();

        if (!is_null($this->searchField) && $request->has('search')) {
            $query->where($this->searchField, 'LIKE', '%' . $request->get('search') . '%');
        }

        // SEARCH BY MULTIPLE FIELDS -----------
        if (!empty($this->searchFields) && $request->has('search')) {
            $query->where(function ($q) use ($request) {
                foreach ($this->searchFields as $field) {
                    $q->orWhere($field, 'LIKE', '%' . $request->get('search') . '%');
                }
            });
        }

        // FILTER BY COLUMNS -------------------
        foreach ($this->filters as $key) {
            if ($request->has($key)) {
                $query->whereIn(Str::singular($key), $request->get($key));
            }
        }

        // FILTER BY DATES ---------------------
        foreach ($this->dateFilters as $key) {
            if (is_string($request->get("{$key}_min"))) {
                $query->whereDate($key, '>=', $request->get("{$key}_min"));
            }

            if (is_string($request->get("{$key}_max"))) {
                $query->whereDate($key, '<=', $request->get("{$key}_max"));
            }
        }

        // SORT COLUMNS ------------------------
        if (!empty($this->searchFields) && $request->has('sort')) {
            foreach ($request->get('sort') as $value) {
                $value = explode(",", $value);
                $column = $value[0];
                $dir = $value[1] ?? 'asc';

                if (!in_array($column, $this->searchFields)) {
                    continue;
                }

                $query->orderBy($column, $dir);
            }
        }

        if ($this->shouldPaginate) {
            return $query->paginate($request->get('per_page', 50));
        } else {
            return $query->get();
        }
    }
}
