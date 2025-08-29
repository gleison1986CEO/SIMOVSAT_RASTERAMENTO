<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\ServiceProvider;

class QueryBuilderMacrosProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        QueryBuilder::macro('clearOrdersBy', function () {
            $this->{$this->unions ? 'unionOrders' : 'orders'} = null;

            return $this;
        });

        EloquentBuilder::macro("clearOrdersBy", function () {
            $query = $this->getQuery();

            $query->{$query->unions ? 'unionOrders' : 'orders'} = null;

            return $this;
        });

        EloquentBuilder::macro('isJoined', function ($table) {
            $query = $this->getQuery();

            if ($query->joins == null) {
                return false;
            }

            foreach ($query->joins as $join) {
                if ($join->table == $table) {
                    return true;
                }
            }

            return false;
        });

        EloquentBuilder::macro('toPaginator', function (int $limit, string $sortCol, string $sortDir) {
            $query = $this->orderBy($sortCol, $sortDir);

            $items = $query->paginate($limit);

            if ($items->currentPage() > $items->lastPage()) {
                $items = $query->paginate($limit, ['*'], 'page', 1);
            }

            $items->sorting = ['sort_by' => $sortCol, 'sort' => $sortDir];

            return $items;
        });
    }
}
