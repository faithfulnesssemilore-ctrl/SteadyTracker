<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->categories()
            ->withCount('activities')
            ->get();
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $request->user()->categories()->create($request->validated());

        return response()->json($category, 201);
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $affectedCount = $category->activities()->count();

        $category->delete();

        return response()->json([
            'deleted' => true,
            'activities_uncategorized' => $affectedCount,
        ]);
    }
}
