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

use App\Http\Traits\Controllers\ResolveEntityRequestTrait;

// @todo use on a configuration allow multiple presenters
use App\Http\Traits\Controllers\BindToFalseTrait;

trait Crudful
{
    use ResolveEntityRequestTrait, BindToFalseTrait;

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

        return $this->respondWithPagination($request, $this->bindToFalse($collection));
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

        return $this->respond($this->bindToFalse($instance));
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

            $this->postInterfaces($model, $entity_request);

            $this->setMessage("{$entity_name} criada com sucesso");

            $this->last_inserted_id = $model->id;

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
            // $this->visa_users = collect([]);

            $builder = new EntityBuilder($instance);
            $entity = $builder->update($entity_id, $input);

            $this->postInterfaces($entity, $entity_request);

            $this->setMessage("{$entity_name} atualizado com sucesso");

            $this->last_inserted_id = $entity->id;

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

        return $this->respond($this->bindToFalse($model));
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

    // protected function preInterfaces($model_instance, $request)
    // {
    //     // If the controller implements the VisaControll and also the model, lets get the users to update the visa
    //     if ($this instanceof VisaControl  && $model_instance instanceof ModelVisaControl) {
    //         // We will update for all visas associated with this model, so no need to specify the visa group
    //         $this->visa_users = $model_instance->upstreamList();
    //     }

    //     return;
    // }

    protected function postInterfaces($model_instance, $request)
    {
        if ($model_instance instanceof \App\Services\Permissions\Interfaces\VisaControl
            && method_exists($this, 'regenerateVisas')
        ) {
            $this->regenerateVisas($model_instance, $model_instance->visasFor());
        }
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