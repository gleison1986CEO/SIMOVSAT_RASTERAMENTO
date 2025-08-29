<?php namespace Tobuli\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable {

    public function scopeSearch(Builder $query, $value )
    {
        $value = trim($value);

        if (empty($value))
            return $query;

        if (empty($this->searchable))
            return $query;

        $values = explode(';', $value);

        if (count($values) > 1) {
            $query->where(function ($query) use ($values) {
                foreach ($values as $value) {
                    $query->orWhere(function($q) use ($value){
                        $q->search($value);
                    });
                }
            });

            return $query;
        }

        $query->where(function ($query) use ($value) {
            foreach ($this->searchable as $searchable) {
                $parts = explode('.', $searchable);
                $relation = null;
                $field = $parts[0];

                if (isset($parts[1])) {
                    $relation = $parts[0];
                    $field = $parts[1];
                }

                if ($relation) {
                    $query->orWhereHas($relation, function($query) use ($field, $value){
                        $query->where($field, 'like', '%' . $value . '%');
                    });
                } else {
                    $query->orWhere($this->table.'.'.$field, 'like', '%' . $value . '%');
                }

            }
        });

        return $query;
    }
}
