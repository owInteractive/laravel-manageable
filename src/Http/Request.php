<?php

namespace Ow\Manageable\Http;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    public static $reflection_model = null;

    protected $model = null;

    protected $allowed = [];

    protected $override_allowed = false;

    public function __construct()
    {
        if ($this->model == null) {
            $this->model = static::$reflection_model;
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        if ($this->model !== null) {
            $model = $this->model();

            if (method_exists($model, 'getRules')) {
                if ($this->isUpdate()) {
                    return ($this->model)::getRules($this->getId());
                }

                return ($this->model)::getRules();
            }

            return ($this->model)::$rules;
        }

        return [];
    }

    public function fillable()
    {
        if ($this->model !== null) {
            $allowed = $this->getAllowed();

            return array_filter(
                $this->all(),
                function ($key) use ($allowed) {
                    return in_array($key, $allowed);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $this->all();
    }

    protected function getAllowed()
    {
        if ($this->override_allowed === true) {
            return $this->allowed ?: [];
        }

        $allowed = [];
        if ($this->model !== null) {
            $allowed = $this->model()->getFillable();

            if (is_array($this->allowed)) {
                return array_merge($allowed, $this->allowed);
            }
        }

        return $allowed;
    }

    protected function model()
    {
        if ($this->model == null) {
            return null;
        }

        return new $this->model;
    }


    protected function isUpdate()
    {
        return in_array($this->method(), ['PATCH', 'PUT']);
    }

    protected function getId()
    {
        return $this->input('id') ?: ($this->route('id') ?: null);
    }
}
