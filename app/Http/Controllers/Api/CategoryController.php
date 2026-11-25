<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->categories()->orderBy('name')->get(),
            'meta' => null,
            'message' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->where(fn ($q) => $q->where('user_id', $request->user()->id))],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $category = $request->user()->categories()->create($validated);
        return response()->json(['data' => $category, 'meta' => null, 'message' => 'Category created'], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)->where(fn ($q) => $q->where('user_id', $request->user()->id))],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $category->update($validated);
        return response()->json(['data' => $category->fresh(), 'meta' => null, 'message' => 'Category updated']);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        $category->tasks()->update(['category_id' => null]);
        $category->delete();
        return response()->json(['data' => null, 'meta' => null, 'message' => 'Category deleted']);
    }
}
