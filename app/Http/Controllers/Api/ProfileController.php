<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Profile\UpdateCurrentUserProfile;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Service\ResponseJSON;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class ProfileController extends Controller
{

    public function update(UpdateCurrentUserProfile $request)
    {
        (new User())->updateProfile($request->all());

        return ResponseJSON::getInstance()
            ->setMessage('Profile updated successfully')
            ->setStatusCode(200)
            ->setData(new UserResource($request->user()))
            ->render();
    }

}
