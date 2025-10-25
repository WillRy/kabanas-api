<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Setting\SaveSettingRequest;
use App\Http\Resources\Api\Setting\SettingResource;
use App\Models\Setting;
use App\Service\ResponseJSON;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    public function index(Request $request)
    {

        $setting = (new Setting)->getSettings();

        return ResponseJSON::getInstance()
            ->setData(new SettingResource($setting))
            ->render();
    }

    public function update(SaveSettingRequest $request)
    {
        Gate::authorize('update', Setting::class);

        $setting = Setting::first();
        if (! $setting) {
            $setting = new Setting;
        }
        $setting->fill($request->validated());
        $setting->save();

        return ResponseJSON::getInstance()
            ->setMessage('Settings updated successfully')
            ->setData($setting)
            ->setStatusCode(200)
            ->render();
    }
}
