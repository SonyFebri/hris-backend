<?php

namespace App\Http\Controllers;

use App\Models\EmployeeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    // Menampilkan daftar semua employee
    public function index()
    {
        $employees = EmployeeModel::with('user')->get();
        return response()->json($employees);
    }

    // Menyimpan data employee baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => ['required', Rule::in(['M', 'F'])],
            'address' => 'nullable|string',
            'shift_count' => ['nullable', Rule::in(['1', '2', '3'])],
        ]);

        $employee = EmployeeModel::create([
            'id' => Str::uuid(),
            'user_id' => $validated['user_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'],
            'address' => $validated['address'] ?? null,
            'shift_count' => $validated['shift_count'] ?? '1',
        ]);

        return response()->json($employee, 201);
    }

    // Menampilkan detail employee berdasarkan ID
    public function show($id)
    {
        $employee = EmployeeModel::with('user')->findOrFail($id);
        return response()->json($employee);
    }

    // Memperbarui data employee
    public function update(Request $request, $id)
    {
        $employee = EmployeeModel::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'gender' => ['sometimes', 'required', Rule::in(['M', 'F'])],
            'address' => 'nullable|string',
            'shift_count' => ['nullable', Rule::in(['1', '2', '3'])],
        ]);

        $employee->update($validated);

        return response()->json($employee);
    }

    // Menghapus data employee (soft delete)
    public function destroy($id)
    {
        $employee = EmployeeModel::findOrFail($id);
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully']);
    }
}