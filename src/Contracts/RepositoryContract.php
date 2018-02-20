<?php

namespace Ow\Manageable\Contracts;

interface RepositoryContract
{
    public function get();
    // public function all($columns = ['*']);
    // public function first($columns = ['*']);
    public function paginate($limit = null, $columns = ['*'], $method = "paginate");
    // public function find($id, $columns = ['*']);
    public function findWithoutFail($id, $field = null);
    // public function findWhere(array $where, $columns = ['*']);
    // public function whereHas(string $relation, closure $closure);
    // public function orderBy($column, $direction = 'asc');
    // public function with(array $relations);
    // public function has(string $relation);
    // public function addScopeQuery(Closure $scope);
    // public function getSearchableFields();
    // public function setPresenter($presenter);
    // public function skipPresenter($status = true);
    public function pushCriteria(CriteriaContract $criteria);
}
