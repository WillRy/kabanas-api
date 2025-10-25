<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Guest;
use App\Service\ResponseJSON;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function autocomplete(Request $request)
    {
        $guests = (new Guest)->autocomplete(
            $request->query('search'),
        );

        return ResponseJSON::getInstance()
            ->setData(UserResource::collection($guests))
            ->render();
    }
}
