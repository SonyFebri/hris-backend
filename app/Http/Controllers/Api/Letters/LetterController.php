<?php

namespace App\Http\Controllers\Api\Letters;

use App\Models\LetterModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LetterController extends Controller
{
    // Menampilkan semua data surat
    public function index(): JsonResponse
    {
        $letters = LetterModel::with('employees')->get(); // optional: eager load employee
        return response()->json([
            'data' => $letters,
        ]);
    }

    // Mengubah status surat (approve atau reject)
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:letters,id',
            'newStatus' => 'required|in:Approved,Rejected',
        ]);

        $letter = LetterModel::find($request->id);
        $letter->status = $request->status;
        $letter->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
        ]);
    }

    public function sendLetter(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'user_id' => 'required|exists:employees,id',
            'is_admin' => 'required|boolean',
        ]);

        $status = $request->is_admin ? 'Approved' : 'Pending';

        $letter = LetterModel::create([
            'letter_name' => $request->title,
            'path_content' => $request->content,
            'user_id' => $request->user_id,
            'status' => $status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Letter sent successfully.',
            'data' => $letter,
        ]);
    }

}