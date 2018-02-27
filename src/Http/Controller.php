<?php

namespace Ow\Manageable\Http;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Ow\Manageable\Http\Traits\Crudful;
use Ow\Manageable\Http\Traits\ProcessFileAndMedia;
use Ow\Manageable\Http\Traits\ResolveEntityRequest;

class Controller extends BaseController
{
    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests,
        Crudful, // index, show, store, update, destroy
        ProcessFileAndMedia, // upload, download, media
        ResolveEntityRequest; // resolveRequest

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
        $search_query = $request->has('_search') ? ('_search=' . $request->input('_search')) : '' ;
        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $collection = $data->getCollection()->toArray();

            if (config('app.api.pagination.body', false)) {
                $pagination = $data->toArray();
                unset($pagination['data']);

                $pagination['query_string'] = $search_query;
                $pagination['count'] = count($collection);

                $response = response()->make([
                    'data' => [
                        'collection' => $collection,
                        'pagination' => $pagination,
                    ],
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
}
