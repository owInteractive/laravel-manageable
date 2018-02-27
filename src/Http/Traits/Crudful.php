<?php

namespace Ow\Manageable\Http\Traits;

use Ow\Manageable\Contracts\Manageable;

use Ow\Manageable\Entities\RepositoryFactory;
use Ow\Manageable\Entities\EntityBuilder;
use Ow\Manageable\Entities\EntityFactory;
use Ow\Manageable\Http\Criteria;
use Illuminate\Http\Request;
use Sentinel;
use DB;

trait Crudful
{
    protected $last_inserted_id = null;

    public function index($entity_name, Request $request)
    {
        $entity = EntityFactory::build($entity_name);

        if ($entity === null || !$entity instanceof Manageable) {
            return $this->respondNotFound();
        }

        $this->checkPolicies($entity, 'index');

        $repository = RepositoryFactory::build($entity);

        $collection = $repository->unshiftCriteria(new Criteria($request));

        if ($request->has('_onepage') && $request->input('_onepage', false)) {
            $collection = $collection->paginate($entity->count());
        } else {
            $collection = $collection->paginate($request->input('perPage') ?: ($request->input('per_page') ?: null));
        }

        // Set presenter by policy transformer
        return $this->respondWithPagination($request, $collection);
    }

    public function show($entity_name, $entity_id, Request $request)
    {
        $entity = EntityFactory::build($entity_name);

        if ($entity === null || !$entity instanceof Manageable) {
            return $this->respondNotFound();
        }

        $repository = RepositoryFactory::build($entity);

        $instance = $repository->pushCriteria(new Criteria($request))
            ->findWithoutFail($entity_id);

        if ($instance === null) {
            return $this->respondNotFound();
        }

        // Set presenter by policy transformer

        return $this->respond($instance);
    }

    public function store($entity_name, Request $request)
    {
        $entity = EntityFactory::build($entity_name);

        if ($entity === null || !$entity instanceof Manageable) {
            return $this->respondNotFound();
        }

        $entity_request = $this->resolveEntityRequest(EntityFactory::name($entity_name));

        $this->checkPolicies($entity, 'store');

        $input = $entity_request->fillable();

        DB::beginTransaction();
        try {
            $this->visa_users = collect([]);

            $builder = new EntityBuilder($entity);
            $model = $builder->create($input);

            $model->postProcess($entity_request->all());

            $this->setMessage("{$entity_name} criada com sucesso");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            $this->logOrThrow($e);

            return $this->respondInternalError();
        }

        $response = $this->respondCreated();

        $response->header('X-Last-Inserted-Id', $model->id ?: null);

        return $response;
    }

    public function update($entity_name, $entity_id, Request $request)
    {
        $entity = EntityFactory::build($entity_name);

        if ($entity === null || !$entity instanceof Manageable) {
            return $this->respondNotFound();
        }

        $repository = RepositoryFactory::build($entity);
        $instance = $repository->withoutCriteria()->findWithoutFail($entity_id);

        if ($instance === null) {
            return $this->respondNotFound();
        }

        $entity_request = $this->resolveEntityRequest(EntityFactory::name($entity_name));

        $input = $entity_request->fillable();

        DB::beginTransaction();
        try {
            $entity->preProcess($entity_request->all());

            $builder = new EntityBuilder($instance);
            $entity = $builder->update($entity_id, $input);

            $entity->postProcess($entity_request->all());

            $this->setMessage("{$entity_name} atualizado com sucesso");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            $this->logOrThrow($e);

            return $this->respondInternalError();
        }

        // Refreshes the model
        $model = $repository->withCriteria()
            ->unshiftCriteria(new Criteria($request))
            ->findWithoutFail($entity_id);

        return $this->respond($model);
    }

    public function destroy($entity_name, $entity_id, Request $request)
    {
        $entity = EntityFactory::build($entity_name);

        if ($entity === null || !$entity instanceof Manageable) {
            return $this->respondNotFound();
        }

        $repository = RepositoryFactory::build($entity);
        $instance = $repository->findWithoutFail($entity_id);

        if ($instance === null) {
            return $this->respondNotFound();
        }

        $instance->delete();

        $entity_name = EntityFactory::name($entity_name);

        $this->setMessage("{$entity_name} removido com sucesso");

        return $this->respondAccepted();
    }

    protected function checkPolicies($entity, $action, $user = null)
    {
        $user = $user ?: Sentinel::getUser();
        $entity_class = get_class($entity);

        if (isset(config('manageable.custom_policies')[$entity_class])) {
            foreach (config('manageable.custom_policies')[$entity_class] as $policy_class) {
                $policy = new $policy_class($entity);

                if (is_callable([$policy, $action])) {
                    if (!$policy->{$action}(Sentinel::getUser(), resolve(Request::class)->all())) {
                        throw new \Illuminate\Auth\Access\AuthorizationException;
                    }
                }
            }
        }

        return;
    }
}
