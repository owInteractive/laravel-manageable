<?php

namespace Ow\Manageable\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Ow\Manageable\Http\Traits\Crudful;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, CRUDful;

    protected $status_code = Response::HTTP_OK;

    protected $success_status = true;

    protected $message = null;

    protected $message_level = 'success';

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = trim($message);

        return $this;
    }

    /**
     * @param mixed $message
     */
    public function hasMessage()
    {
        return is_string($this->message) && (trim($this->message) !== '');
    }

    /**
     * @return mixed
     */
    public function getMessageLevel()
    {
        return $this->message_level;
    }

    /**
     * @param mixed $message_level
     */
    public function setMessageLevel($message_level)
    {
        $this->message_level = trim($message_level);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * @param mixed $status_code
     */
    public function setStatusCode($status_code)
    {
        $this->status_code = $status_code;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSuccessStatus()
    {
        return $this->success_status;
    }

    /**
     * @param $value
     * @return $this
     * @internal param mixed $status_code
     */
    public function setSuccessStatus($value)
    {
        $this->success_status = $value;

        return $this;
    }

    public function respondUnauthorized($message = 'Unauthorized Access. Please login')
    {
        return $this->setStatusCode(Response::HTTP_UNAUTHORIZED)
            ->setMessage($this->message ?: $message)
            ->respondWithError($message);
    }

    public function respondForbidden($message = 'Forbidden.')
    {
        return $this->setStatusCode(Response::HTTP_FORBIDDEN)
            ->setMessage($this->message ?: $message)
            ->respondWithError($message);
    }

    public function respondNotFound($message = 'Not Found.')
    {
        return $this->setStatusCode(Response::HTTP_NOT_FOUND)
            ->setMessage($this->message ?: $message)
            ->respondWithError($message);
    }

    public function respondUnprocessableEntity($message = 'Parameters failed validation')
    {
        return $this->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->setMessage($this->message ?: $message)
            ->respondWithError(['validation' => $message]);
    }

    public function respondInternalError($message = 'Internal Error.')
    {
        return $this->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->setMessage($this->message ?: $message)
            ->respondWithError($message);
    }

    public function respondWithError($errors)
    {
        $this->setSuccessStatus(false);

        return $this->respond([
            'errors' => $errors
        ]);
    }

    public function respondAccepted($message = '')
    {
        return $this->setStatusCode(Response::HTTP_ACCEPTED)
            ->setMessage($this->message ?: $message)
            ->respond($message);
    }


    public function respondCreated($message = '')
    {
        return $this->setStatusCode(Response::HTTP_CREATED)
            ->setMessage($this->message ?: $message)
            ->respond($message);
    }

    public function respond($data = '', $headers = [])
    {
        $response = response()->json($data, $this->getStatusCode(), $headers)
            ->header('Content-Type', 'application/json');

        return $this->addMessage($response);
    }

    /**
     * @param Request $request
     * @param $data
     * @return \Illuminate\Http\Response
     */
    protected function respondWithPagination($data, Request $request)
    {
        // if ($request->paginate()) {
            $json = $data->getCollection()->toJson();

            $response = response()->make($json);
            $response->header('X-total', $data->total());
            $response->header('X-offset', $data->perPage());
            $response->header('X-page', $data->currentPage());
            $response->header('X-last-page', ceil($data->total() / $data->perPage()));
        // } else {
        //     $json = $data->toJson();
        //     $response = response()->make($json);
        // }

        $response->header('Content-Type', 'application/json');

        return $this->addMessage($response);
    }

    protected function addMessage($response)
    {
        if ($this->hasMessage()) {
            $response->header('X-message-content', $this->getMessage());
            $response->header('X-message-level', $this->getMessageLevel());
        }

        return $response;
    }

    // /**
    //  * @param Request $request
    //  * @return mixed
    //  */
    // protected function findAllPaginated(Request $request, &$model)
    // {
    //     $limit = $limit = $request->input('limit', 5);
    //     $limit < 50 ?: $limit = 50;

    //     return $model::paginate($limit);
    // }

    // protected function firstOrFail($params)
    // {
    //     $this->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    //     $this->respondWithError("Implement your firstOrFail method on the child class if needed!");
    // }
}
