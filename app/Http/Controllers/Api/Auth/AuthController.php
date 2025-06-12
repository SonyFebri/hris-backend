<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\UsersModel;
use App\Models\EmployeeModel;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyModel;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Notifications\CustomResetPassword;

class AuthController extends Controller
{
    public function registerAdmin(Request $request)
    {

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:100',
            'bank_number' => 'required|string|max:50',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',

        ]);

        $existingCompany = CompanyModel::where('company_name', $validated['company_name'])->first();
        if ($existingCompany) {
            return response()->json([
                'message' => 'Company name already exists.',
            ], 422); // Unprocessable Entity
        }

        $nextCompanyId = CompanyModel::max('id') + 1;

        $company = CompanyModel::create([
            'company_name' => $validated['company_name'],
            'company_code' => 'COM' . str_pad($nextCompanyId, 3, '0', STR_PAD_LEFT),
            'bank_name' => $validated['bank_name'],
            'bank_number' => $validated['bank_number'],
        ]);

        $user = UsersModel::create([
            'company_id' => $company->id,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => true,
        ]);

        Auth::login($user);            // tempel sesi
        $request->session()->regenerate(); // CSRF token baru

        // Kirim respons (cookie sudah terset otomatis oleh Laravel)
        return response()->json([
            'message' => 'Admin registered & logged-in successfully',
        ], 201);
    }

    public function loginAdmin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Coba login pakai Auth
        if (!Auth::attempt($validated)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Cek apakah user admin
        $user = Auth::user();
        if (!$user->is_admin) {
            Auth::logout(); // logout kalau bukan admin
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->session()->regenerate(); // amankan session
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
        ]);
    }


    public function loginEmployee(Request $request)
    {
        $validated = $request->validate([
            'company_username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Ambil user dengan email dan role admin
        $user = UsersModel::with('employee')
            ->where('company_username', $validated['company_username'])
            ->where('is_admin', false)
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials or not an admin'], 401);
        }

        // Buat token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }



    // Logout dan revoke token
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = UsersModel::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $token = Password::createToken($user);

        $user->notify(new CustomResetPassword($token, $user->email));

        return response()->json([
            'message' => 'Password reset link sent to your email',
            'email' => $user->email
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => bcrypt($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successful'])
            : response()->json(['message' => 'Reset failed'], 400);
    }

    // Get data user yang login
    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }
}