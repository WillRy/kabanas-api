<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Property\StorePropertyRequest;
use App\Http\Resources\Api\Property\PropertyResource;
use App\Models\Property;
use App\Service\ResponseJSON;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PropertyController extends Controller
{
    public function store(StorePropertyRequest $request)
    {
        $property = (new Property())->newProperty($request->validated());

        return ResponseJSON::getInstance()
            ->setMessage('Property created successfully')
            ->setData(new PropertyResource($property))
            ->setStatusCode(201)
            ->render();
    }

    public function index(Request $request)
    {

        $properties = (new Property())->list(
            $request->query('sortBy', 'id'),
            $request->query('sortOrder', 'asc'),
            $request->query('discount'),
        );

        $data = ResponseJSON::fromPaginate($properties, PropertyResource::collection($properties));
        return ResponseJSON::getInstance()
            ->setData($data)
            ->render();
    }

    public function update(StorePropertyRequest $request, Property $property)
    {
        $property->updateProperty($request->validated());

        return ResponseJSON::getInstance()
            ->setMessage('Property updated successfully')
            ->setData(new PropertyResource($property))
            ->setStatusCode(200)
            ->render();
    }

    public function destroy(Property $property)
    {
        Gate::authorize('delete', $property);

        $property->delete();

        return response()->noContent();
    }
}
