<?php

namespace App\Http\Controllers\Api\CheckClock;

use App\Http\Controllers\Controller;
use App\Models\CheckClockModel;
use App\Models\EmployeeShiftScheduleModel;
use App\Models\CheckClockSettingTimeModel;
use Illuminate\Http\Request;

use Carbon\Carbon;

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
            'image' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'required|string|max:255',
        ]);

        // Tentukan approval berdasarkan jenis check_clock
        $checkClockType = $request->check_clock_type;
        $approval = in_array($checkClockType, ['clock_in', 'clock_out']) ? 'Approve' : 'Waiting Approval';

        // Default status
        $status = 'on_time';

        // Jika clock_in, hitung on_time / late
        if ($checkClockType === 'clock_in') {
            $schedule = EmployeeShiftScheduleModel::where('employee_id', $request->user_id)->first();

            if (!$schedule) {
                return response()->json(['error' => 'Shift schedule not found'], 404);
            }

            $ckSettingsId = $schedule->ck_settings_id;

            $clockSetting = CheckClockSettingTimeModel::where('ck_settings_id', $ckSettingsId)->first();

            if (!$clockSetting) {
                return response()->json(['error' => 'Clock setting not found'], 404);
            }

            $checkClockTime = Carbon::parse($request->check_clock_time);
            $clockInTime = Carbon::parse($clockSetting->clock_in);

            $status = $checkClockTime->lte($clockInTime) ? 'on_time' : 'late';
        } elseif ($checkClockType === 'clock_out') {
            $status = 'on_time'; // atau bisa kamu atur sesuai kebutuhan
        }

        // Simpan data
        $checkClock = CheckClockModel::create([
            'user_id' => $request->user_id,
            'check_clock_type' => $checkClockType,
            'check_clock_time' => $request->check_clock_time,
            'status' => $status,
            'approval' => $approval,
            'image' => $request->image,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
        ]);

        return response()->json([
            'message' => 'Records have been saved',
            'status' => $status,
            'approval' => $approval
        ], 201);
    }

    public function respondApproval(Request $request, $id)
    {
        $request->validate([
            'approval' => 'required|in:Approve,Reject'
        ]);

        $checkClock = CheckClockModel::find($id);

        if (!$checkClock) {
            return response()->json(['error' => 'Check clock record not found'], 404);
        }

        // Update status approval
        $checkClock->approval = $request->approval;
        $checkClock->save();

        return response()->json([
            'message' => "Check clock has been {$request->approval}d",
            'data' => $checkClock
        ], 200);
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