<?php

namespace App\Http\Controllers\Api\CheckClock;

use App\Http\Controllers\Controller;
use App\Models\CheckClockSettingTimeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckClockSettingTimeController extends Controller
{
    public function index()
    {
        $times = CheckClockSettingTimeModel::all();
        return response()->json($times);
    }

    public function show($id)
    {
        $time = CheckClockSettingTimeModel::findOrFail($id);
        return response()->json($time);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ck_settings_id' => 'required|uuid|exists:check_clock_settings,id',
            'clock_in' => 'required|date_format:H:i:s',
            'clock_out' => 'required|date_format:H:i:s|after:clock_in',
            'break_start' => 'nullable|date_format:H:i:s',
            'break_end' => 'nullable|date_format:H:i:s|after:break_start',
        ]);

        $time = CheckClockSettingTimeModel::create([
            'id' => (string) Str::uuid(),
            'ck_settings_id' => $validated['ck_settings_id'],
            'clock_in' => $validated['clock_in'],
            'clock_out' => $validated['clock_out'],
            'break_start' => $validated['break_start'] ?? null,
            'break_end' => $validated['break_end'] ?? null,
        ]);

        return response()->json($time, 201);
    }

    public function update(Request $request, $id)
    {
        $time = CheckClockSettingTimeModel::findOrFail($id);

        $validated = $request->validate([
            'ck_settings_id' => 'sometimes|required|uuid|exists:check_clock_settings,id',
            'clock_in' => 'sometimes|required|date_format:H:i:s',
            'clock_out' => 'sometimes|required|date_format:H:i:s',
            'break_start' => 'nullable|date_format:H:i:s',
            'break_end' => 'nullable|date_format:H:i:s',
        ]);

        $time->update($validated);

        return response()->json($time);
    }

    public function destroy($id)
    {
        $time = CheckClockSettingTimeModel::findOrFail($id);
        $time->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}