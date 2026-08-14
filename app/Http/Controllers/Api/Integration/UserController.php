<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->orderBy('id');

        if ($request->filled('since')) {
            $query->where('updated_at', '>=', $request->query('since'));
        }

        return response()->json(
            $query->get(['id', 'name', 'name_kana', 'email', 'birthday', 'gender', 'zip_code', 'address', 'created_at', 'updated_at'])
        );
    }
}
