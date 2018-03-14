<?php

namespace Ow\Manageable\Http;

use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;
use Ow\Manageable\Contracts\Manageable;

use Ow\Manageable\Entities\RepositoryFactory;
use Ow\Manageable\Entities\EntityFactory;
use Ow\Manageable\Http\Criteria;
use Ow\Manageable\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class MediaController extends Controller
{
    public function store(Request $request)
    {
        $entity = resolve(config('manageable.mediahub_model'));

        $media = $this->processMedia($entity, $request);

        return $this->respond($this->parseMedia($media));
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

        $this->checkPolicies($entity, 'media-attach');

        $media = $this->processMedia($entity, $request);

        return $this->respond($this->parseMedia($media));
    }

    public function destroy($entity_name, $entity_id, $media_id, Request $request)
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

        $this->checkPolicies($entity, 'media-destroy');

        $media = $entity->media()->find($media_id);

        if ($media === null) {
            return $this->respondNotFound();
        }

        $media->delete();

        return $this->respondAccepted();
    }

    protected function parseMedia($media)
    {
        return $parsed_media = array_merge([
            '_links' => [
                'thumb' => $media->hasEdited() ? $media->getFirstMediaUrl('edited') : $media->getUrl(),
                'original' => $media->getUrl(),
            ]
        ], $media->toArray());
    }

    /**
     * @todo Create a specifc request for the media?
     */
    protected function processMedia(HasMedia $entity, Request $request)
    {
        $collection = $request->input('collection', 'default');

        Validator::make($request->all(), $entity::getMediaRules($collection))->validate();

        $file = $request->file('file');

        $extension = $file->getClientOriginalExtension();
        $sha1 = sha1($file->getClientOriginalName());
        $filename = date('Y-m-d-h-i-s') . "_" . $sha1 . "." . $extension;
        $file = $file->move(config('manageable.temp_dir'), $filename);

        $media = $entity->addMedia($file)->toMediaCollection($collection);

        return $media;
    }
}