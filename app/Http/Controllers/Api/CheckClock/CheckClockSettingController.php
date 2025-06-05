<?php

namespace App\Http\Controllers\Api\CheckClock;

use App\Http\Controllers\Controller;
use App\Models\CheckClockSettingModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckClockSettingController extends Controller
{
    public function index()
    {
        $settings = CheckClockSettingModel::all();
        return response()->json($settings);
    }

    public function show($id)
    {
        $setting = CheckClockSettingModel::findOrFail($id);
        return response()->json($setting);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'shift_count' => 'required|integer|in:1,2,3',
        ]);

        $setting = CheckClockSettingModel::create([
            'id' => (string) Str::uuid(),
            'name' => $validated['name'],
            'shift_count' => $validated['shift_count'],
        ]);

        return response()->json($setting, 201);
    }

    public function update(Request $request, $id)
    {
        $setting = CheckClockSettingModel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'shift_count' => 'sometimes|required|integer|in:1,2,3',
        ]);

        $setting->update($validated);

        return response()->json($setting);
    }

    public function destroy($id)
    {
        $setting = CheckClockSettingModel::findOrFail($id);
        $setting->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}