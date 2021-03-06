<?php

namespace Ow\Manageable\Http;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests;

    protected $status_code = Response::HTTP_OK;

    protected $success_status = true;

    protected $message = '';

    protected $message_level = 'success';

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        if (is_string($message)) {
            $this->message = trim($message);
        }

        return $this;
    }

    public function hasMessage()
    {
        return is_string($this->message) && (trim($this->message) !== '');
    }

    public function getMessageLevel()
    {
        return $this->message_level;
    }

    public function setMessageLevel($message_level)
    {
        $this->message_level = trim($message_level);

        return $this;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function setStatusCode($status_code)
    {
        $this->status_code = $status_code;

        return $this;
    }

    public function getSuccessStatus()
    {
        return $this->success_status;
    }

    public function setSuccessStatus($value)
    {
        $this->success_status = $value;

        return $this;
    }

    public function respondUnauthorized($errors = [], $message = '')
    {
        $this->setMessage($message ?: trans('error.unauthorized'));

        return $this->setStatusCode(Response::HTTP_UNAUTHORIZED)->respondWithError($message);
    }

    public function respondForbidden($errors = [], $message = '')
    {
        $this->setMessage($message ?: trans('error.forbidden'));

        return $this->setStatusCode(Response::HTTP_FORBIDDEN)->respondWithError($errors);
    }

    public function respondNotFound($errors = [], $message = '')
    {
        $this->setMessage($message ?: trans('error.not_found'));

        return $this->setStatusCode(Response::HTTP_NOT_FOUND)->respondWithError($errors);
    }

    public function respondUnprocessableEntity($errors = [], $message = '')
    {
        $this->setMessage($message ?: trans('error.unprocessable_entity'));

        return $this->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->setMessage($message)
            ->respondWithError($errors);
    }

    public function respondInternalError($message = null)
    {
        if ($message == null) {
            $message =  trans('messages/errors.http.500');
        }

        return $this->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->setMessage($message)
            ->respondWithError($message);
    }

    public function respondWithError($errors = [])
    {
        $this->setSuccessStatus(false);

        if ($this->getStatusCode() < 400) {
            $this->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'message' => $this->getMessage() ?: trans('error.internal_server_error'),
            'errors' => $errors
        ]);
    }

    public function respondAccepted($data = [], $message = '')
    {
        return $this->setStatusCode(Response::HTTP_ACCEPTED)->respond($message);
    }


    public function respondCreated($data = [], $message = '')
    {
        return $this->setStatusCode(Response::HTTP_CREATED)->respond($message);
    }

    public function respond($data = [], $headers = [])
    {
        $response = response()->json($data, $this->getStatusCode(), $headers)
            ->header('Content-Type', 'application/json');

        return $this->addMessage($response);
    }

    protected function respondWithPagination(Request $request, $data)
    {
        $search_query = $request->has('_search') ? http_build_query($request->only('_search')) : '';

        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $collection = $data->getCollection()->toArray();

            if (config('manageable.pagination.body', false)) {
                $pagination = $data->toArray();
                unset($pagination['data']);

                $pagination['query_string'] = $search_query;

                // Adds the count
                $pagination['count'] = count($collection);

                $response = response()->make([
                    'collection' => $collection,
                    'pagination' => $pagination,
                    'message' => $this->getMessage() ?: ''
                ]);
            } else {
                $response = response()->make($collection);
            }

            $response->header('X-query-string', $search_query);
            $response->header('X-total', $data->total());
            $response->header('X-offset', $data->perPage());
            $response->header('X-page', $data->currentPage());
            $response->header('X-last-page', ceil($data->total() / $data->perPage()));
        } else {
            $response = response()->make($data->toJson());
        }

        $response->header('Content-Type', 'application/json');

        return $response;
    }

    protected function addMessage($response)
    {
        if ($this->hasMessage()) {
            $response->header('X-message-content', $this->getMessage());
            $response->header('X-message-level', $this->getMessageLevel());
        }

        return $response;
    }

    protected function logOrThrow($e)
    {
        if (config('app.debug') !== true) {
            Log::error($e->getMessage());

            return;
        }

        throw $e;
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
