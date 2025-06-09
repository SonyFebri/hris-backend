<?php

namespace App\Http\Controllers;

use App\Models\EmployeeModel;
use App\Models\CompanyModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    
    // Menampilkan daftar semua employee
    public function index()
    {
        $employees = EmployeeModel::with('user')->get();
        return response()->json($employees);
    }
    
    /**
     * Create new employee dengan format sesuai model UsersModel
     */
    public function createEmployee(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'mobile_number' => 'nullable|string|max:20',
            'NIK' => 'nullable|string|max:20',
            'last_education' => 'nullable|string|max:50',
            'place_birth' => 'nullable|string|max:100',
            'date_birth' => 'nullable|date',
            'role' => 'nullable|string|max:100',
            'branch' => 'nullable|string|max:100',
            'contract_type' => 'required|string|in:permanen,percobaan,magang,kontrak',
            'bank' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
            'SP' => 'nullable|string|max:10',
            'address' => 'nullable|text',
            'shift_count' => 'nullable|integer|in:1,2,3',
            'email' => 'nullable|email|unique:users,email',
        ]);

        $company = CompanyModel::findOrFail($validated['company_id']);

        // Check employee limit
        if (!$company->canAddEmployees()) {
            return response()->json([
                'message' => 'Jumlah employee sudah mencapai batas maksimum perusahaan.',
                'current_count' => $company->employee_count,
                'max_count' => $company->max_employee_count
            ], 400);
        }

        DB::beginTransaction();
        try {
            $user = UsersModel::createEmployeeAccountWithValidation(
                $validated['company_id'],
                $validated['first_name'],
                $validated['mobile_number'] ?? null,
                $validated['email'] ?? null
            );

            // Create employee profile
            $employee = EmployeeModel::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'NIK' => $validated['NIK'] ?? null,
                'last_education' => $validated['last_education'] ?? null,
                'place_birth' => $validated['place_birth'] ?? null,
                'date_birth' => $validated['date_birth'] ?? null,
                'role' => $validated['role'] ?? null,
                'branch' => $validated['branch'] ?? null,
                'contract_type' => $validated['contract_type'],
                'bank' => $validated['bank'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_name' => $validated['bank_account_name'] ?? null,
                'position' => $validated['position'] ?? null,
                'gender' => $validated['gender'],
                'SP' => $validated['SP'] ?? null,
                'address' => $validated['address'] ?? null,
                'shift_count' => $validated['shift_count'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Employee created successfully',
                'employee' => $employee->load('user'),
                'company_username' => $user->company_username,
                'default_password' => $user->company_username // For admin reference
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create employee',
                'error' => $e->getMessage()
            ], 500);
        }
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
            'gender' => ['sometimes', 'required', Rule::in(['Laki-laki', 'Perempuan'])], // PERBAIKAN: Sesuai dengan enum di model
            'address' => 'nullable|string',
            'shift_count' => ['nullable', Rule::in([1, 2, 3])], // PERBAIKAN: Integer bukan string
        ]);

        $employee->update($validated);

        return response()->json($employee);
    }

    // Menghapus data employee (soft delete)
    public function destroy($id)
    {
        $employee = EmployeeModel::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $employee->resign(); // Ini akan handle soft delete dan update company count
            
            DB::commit();
            return response()->json(['message' => 'Employee deleted successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to delete employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee dashboard data with statistics
     * Sesuai dengan UI yang ditampilkan
     */
    public function getDashboardData(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2030',
        ]);

        $companyId = $request->company_id;
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;
        $date = Carbon::create($year, $month, 1);

        $company = CompanyModel::findOrFail($companyId);

        $statistics = $company->getEmployeeStatisticsForPeriod($date);

        // Get employee list dengan pagination
        $employees = $this->getEmployeeList($request, $companyId);

        return response()->json([
            'statistics' => $statistics,
            'employees' => $employees,
            'company' => $company
        ]);
    }

    /**
     * Get employee list dengan search, filter, dan pagination
     */
    public function getEmployeeList(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->company_id;
        $search = $request->search;
        $filter = $request->filter; // bisa berupa status, branch, dll
        $perPage = $request->per_page ?? 10;

        $query = EmployeeModel::with(['user', 'company'])
            ->byCompany($companyId);

        // Search functionality
        if ($search) {
            $query->search($search);
        }

        // Filter by status (active/inactive)
        if ($filter === 'active') {
            $query->active();
        } elseif ($filter === 'inactive') {
            $query->onlyTrashed();
        }

        // Filter by branch if provided
        if ($request->branch) {
            $query->byBranch($request->branch);
        }

        // Filter by contract type if provided
        if ($request->contract_type) {
            $query->byContractType($request->contract_type);
        }

        $employees = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $employees->getCollection()->transform(function ($employee) {
            return $employee->table_data; // Menggunakan accessor getTableDataAttribute
        });

        return $employees;
    }

    /**
     * Update employee data
     */
    public function updateEmployee(Request $request, $id)
    {
        $employee = EmployeeModel::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'mobile_number' => 'nullable|string|max:20',
            'NIK' => 'nullable|string|max:20',
            'last_education' => 'nullable|string|max:50',
            'place_birth' => 'nullable|string|max:100',
            'date_birth' => 'nullable|date',
            'role' => 'nullable|string|max:100',
            'branch' => 'nullable|string|max:100',
            'contract_type' => 'sometimes|required|string|in:permanen,percobaan,magang,kontrak',
            'bank' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'gender' => 'sometimes|required|string|in:Laki-laki,Perempuan',
            'SP' => 'nullable|string|max:10',
            'address' => 'nullable|text',
            'shift_count' => 'nullable|integer|in:1,2,3',
            'email' => 'nullable|email|unique:users,email,' . $employee->user_id,
        ]);

        DB::beginTransaction();
        try {
            // Update employee data
            $employee->update($validated);

            // Update user data if mobile_number or email changed
            if (isset($validated['mobile_number']) || isset($validated['email'])) {
                $userUpdate = [];
                if (isset($validated['mobile_number'])) {
                    $userUpdate['mobile_number'] = $validated['mobile_number'];
                }
                if (isset($validated['email'])) {
                    $userUpdate['email'] = $validated['email'];
                }
                $employee->user->update($userUpdate);
            }

            DB::commit();

            return response()->json([
                'message' => 'Employee updated successfully',
                'employee' => $employee->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete employee (resign)
     */
    public function resignEmployee($id)
    {
        $employee = EmployeeModel::findOrFail($id);

        DB::beginTransaction();
        try {
            // Use resign method from model
            $employee->resign();

            DB::commit();

            return response()->json([
                'message' => 'Employee resigned successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to resign employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset employee password to default
     */
    public function resetPassword($id)
    {
        $employee = EmployeeModel::findOrFail($id);

        if ($employee->user->resetToDefaultPassword()) {
            return response()->json([
                'message' => 'Password reset successfully',
                'new_password' => $employee->user->company_username
            ]);
        }

        return response()->json([
            'message' => 'Failed to reset password'
        ], 500);
    }

    /**
     * Get available branches for dropdown
     * PERBAIKAN: Menambahkan method untuk mendapatkan list branch yang pernah diinput
     */
    public function getBranches($companyId)
    {
        $branches = EmployeeModel::byCompany($companyId)
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->pluck('branch')
            ->filter()
            ->values();

        return response()->json($branches);
    }

    /**
     * Get available roles for dropdown
     */
    public function getRoles($companyId)
    {
        $roles = EmployeeModel::byCompany($companyId)
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->distinct()
            ->pluck('role')
            ->filter()
            ->values();

        return response()->json($roles);
    }

    /**
     * Get employee statistics for export
     */
    public function getEmployeeStatistics(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $companyId = $request->company_id;
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $statistics = [
            'period' => $startDate->format('M Y') . ' - ' . $endDate->format('M Y'),
            'total_employees' => EmployeeModel::withTrashed()
                ->byCompany($companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'active_employees' => EmployeeModel::byCompany($companyId)->active()->count(),
            'inactive_employees' => EmployeeModel::onlyTrashed()->byCompany($companyId)->count(),
            'by_contract_type' => EmployeeModel::byCompany($companyId)
                ->selectRaw('contract_type, COUNT(*) as count')
                ->groupBy('contract_type')
                ->pluck('count', 'contract_type'),
            'by_gender' => EmployeeModel::byCompany($companyId)
                ->selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->pluck('count', 'gender'),
        ];

        return response()->json($statistics);
    }
}
