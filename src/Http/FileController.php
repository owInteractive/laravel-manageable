<?php

namespace Ow\Manageable\Http;

use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;
use Ow\Manageable\Contracts\Manageable;

use Ow\Manageable\Entities\RepositoryFactory;
use Ow\Manageable\Entities\EntityFactory;
use Ow\Manageable\Http\Criteria;
use Ow\Manageable\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class FileController extends Controller
{
    public function store(Request $request)
    {
        $entity = resolve(config('manageable.filehub_model'));

        $entry = $this->storeFile($entity, $request);

        return $this->respond($entry);
    }

    public function attach($entity_name, $entity_id, Request $request)
    {
        $entity = EntityFactory::build($entity_name);

        if ($entity === null || !$entity instanceof Manageable) {
            return $this->respondNotFound();
        }

        $repository = RepositoryFactory::build($entity);
        $entity = $repository->pushCriteria(new Criteria($request))
            ->findWithoutFail($entity_id);

        if ($entity === null) {
            return $this->respondNotFound();
        }

        $this->checkPolicies($entity, 'file-attach');

        $entry = $this->storeFile($entity, $request);

        return $this->respond($entry);
    }

    protected function storeFile($entity, Request $request)
    {
        $collection = $request->input('collection', 'default');
        Validator::make($request->all(), $entity::getFileRules($collection))->validate();

        $files = $request->file('file');

        if (is_array($files)) {
            throw new \Exception('Multiple upload not permited');
        }

        $file = $files;

        $entry = $entity->files()->getRelated();

        $entry->original_name = $file->getClientOriginalName();
        $sha1 = sha1($entry->original_name);
        $extension = $file->getClientOriginalExtension();
        $entry->file_name = date('Y-m-d-h-i-s') . "_" . $sha1 . "." . $extension;
        $entry->size = File::size($file);
        $entry->disk = $entry->getDisk();
        $entry->collection_name = $collection;

        $entry->custom_properties = array_intersect_key(
            $request->except(['file', 'collection']),
            $entity->files()->getRelated()->getCustomFieldsAttribute()
        );

        Storage::disk($entry->disk)->put($entry->file_name, File::get($file));
        $entity->files()->save($entry);

        return $entry;
    }
}