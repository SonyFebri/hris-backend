<?php

namespace App\Http\Controllers\Api\CheckClock;

use App\Http\Controllers\Controller;
use App\Models\CheckClockModel;
use App\Models\EmployeeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckClockController extends Controller
{
    public function index()
    {
        $checkClocks = CheckClockModel::with('employee')->latest()->get();
        return response()->json($checkClocks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:employees,id',
            'check_clock_type' => 'required|in:clock_in,clock_out,absent,sick_leave,annual_leave',
            'check_clock_time' => 'required|date',
            'status' => 'nullable|in:on_time,late',
            'image' => 'nullable|image|max:2048',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address' => 'nullable|string|max:255',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('check_clocks', 'public');
        }

        $checkClock = CheckClockModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user_id,
            'check_clock_type' => $request->check_clock_type,
            'check_clock_time' => $request->check_clock_time,
            'status' => $request->status,
            'image' => $imagePath,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
        ]);

        return response()->json($checkClock, 201);
    }

    public function show($id)
    {
        $checkClock = CheckClockModel::with('employee')->findOrFail($id);
        return response()->json($checkClock);
    }

    public function destroy($id)
    {
        $checkClock = CheckClockModel::findOrFail($id);
        $checkClock->delete();

        return response()->json(['message' => 'Check clock record deleted.']);
    }
}