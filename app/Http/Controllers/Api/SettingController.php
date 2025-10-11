<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Setting\SaveSettingRequest;
use App\Models\Setting;
use App\Service\ResponseJSON;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        return ResponseJSON::getInstance()
            ->setData(Setting::first())
            ->render();
    }

    public function update(SaveSettingRequest $request)
    {
        $setting = Setting::first();
        if (!$setting) {
            $setting = new Setting();
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
