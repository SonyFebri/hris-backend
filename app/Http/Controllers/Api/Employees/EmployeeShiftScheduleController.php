<?php

namespace App\Http\Controllers;

use App\Models\EmployeeShiftScheduleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeShiftScheduleController extends Controller
{
    public function index()
    {
        $schedules = EmployeeShiftScheduleModel::all();
        return response()->json($schedules);
    }

    public function show($id)
    {
        $schedule = EmployeeShiftScheduleModel::findOrFail($id);
        return response()->json($schedule);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:employees,id',
            'work_date' => 'required|date',
            'shift_number' => 'required|integer|in:1,2,3',
        ]);

        // Cek unik (user_id + work_date)
        $exists = EmployeeShiftScheduleModel::where('user_id', $validated['user_id'])
            ->where('work_date', $validated['work_date'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Schedule for this user on this date already exists'], 422);
        }

        $schedule = EmployeeShiftScheduleModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $validated['user_id'],
            'work_date' => $validated['work_date'],
            'shift_number' => $validated['shift_number'],
        ]);

        return response()->json($schedule, 201);
    }

    public function update(Request $request, $id)
    {
        $schedule = EmployeeShiftScheduleModel::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'sometimes|required|uuid|exists:employees,id',
            'work_date' => 'sometimes|required|date',
            'shift_number' => 'sometimes|required|integer|in:1,2,3',
        ]);

        // Jika user_id atau work_date diubah, cek unik kembali
        if (
            (isset($validated['user_id']) || isset($validated['work_date'])) &&
            EmployeeShiftScheduleModel::where('user_id', $validated['user_id'] ?? $schedule->user_id)
                ->where('work_date', $validated['work_date'] ?? $schedule->work_date)
                ->where('id', '!=', $schedule->id)
                ->exists()
        ) {
            return response()->json(['message' => 'Schedule for this user on this date already exists'], 422);
        }

        $schedule->update($validated);

        return response()->json($schedule);
    }

    public function destroy($id)
    {
        $schedule = EmployeeShiftScheduleModel::findOrFail($id);
        $schedule->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}