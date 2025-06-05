<?php

namespace App\Http\Controllers;

use App\Models\CompanyModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    // Menampilkan semua perusahaan
    public function index()
    {
        $companies = CompanyModel::all();
        return response()->json($companies);
    }

    // Menyimpan data perusahaan baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'subscription_days' => 'nullable|integer|min:0',
            'employee_count' => 'nullable|integer|min:0',
            'max_employee_count' => 'nullable|integer|min:0',
        ]);

        $company = CompanyModel::create([
            'id' => Str::uuid(),
            'company_name' => $validated['company_name'],
            'address' => $validated['address'] ?? null,
            'subscription_days' => $validated['subscription_days'] ?? 30,
            'employee_count' => $validated['employee_count'] ?? 0,
            'max_employee_count' => $validated['max_employee_count'] ?? 0,
        ]);

        return response()->json($company, 201);
    }

    // Menampilkan satu perusahaan
    public function show($id)
    {
        $company = CompanyModel::findOrFail($id);
        return response()->json($company);
    }

    // Memperbarui data perusahaan
    public function update(Request $request, $id)
    {
        $company = CompanyModel::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'subscription_days' => 'nullable|integer|min:0',
            'employee_count' => 'nullable|integer|min:0',
            'max_employee_count' => 'nullable|integer|min:0',
        ]);

        $company->update($validated);

        return response()->json($company);
    }

    // Menghapus perusahaan (soft delete)
    public function destroy($id)
    {
        $company = CompanyModel::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }
}