<?php

namespace App\Http\Controllers;

use App\Models\EmployeeModel;
use App\Models\CompanyModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
    public function addEmployee(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contract_type' => 'required|string|in:permanen,percobaan,magang,kontrak',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
            'sift_count' => 'required|integer|in:1,2,3',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Ambil data perusahaan
        $company = CompanyModel::findOrFail($validated['company_id']);

        // Hitung jumlah employee aktif (non-deleted)
        $employeeCount = UsersModel::where('company_id', $company->id)
            ->where('is_admin', false)
            ->whereNull('deleted_at') // hanya yang belum soft deleted
            ->count();

        $maxEmployeeCount = $company->max_employee_count;

        // Cek apakah masih bisa menambahkan employee
        if ($employeeCount >= $maxEmployeeCount) {
            return response()->json(['message' => 'Jumlah employee sudah mencapai batas maksimum perusahaan.'], 400);
        }

        // Buat company_username unik
        $userCount = UsersModel::where('company_id', $company->id)->count() + 1;
        $companyUsername = $company->company_code . '-' . ucfirst($validated['first_name']) . '-' . $userCount;

        // Simpan ke tabel users
        $user = UsersModel::create([
            'password' => Hash::make($validated['password']),
            'company_id' => $company->id,
            'company_username' => $companyUsername,
            'is_admin' => false,
        ]);

        // Simpan ke tabel employees
        EmployeeModel::create([
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'contract_type' => $validated['contract_type'],
            'gender' => $validated['gender'],
            'sift_count' => $validated['sift_count'],
        ]);

        return response()->json(['message' => 'Employee registered successfully'], 201);
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