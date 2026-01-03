<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();

        $query->when($request->search, function ($q, $search) {
            return $q->where('name', 'like', "%{$search}%");
        });

        $categories = $query->withCount('books')->paginate(10);

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = Category::create($data);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category)
        ], 201);
    }

    public function show($id)
    {
        $category = Category::withCount('books')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }

        return new CategoryResource($category);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) return response()->json(['message' => 'Không tìm thấy'], 404);

        if ($category->books()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa danh mục đang chứa sách!'
            ], 400);
        }

        $category->delete();
        return response()->json(['success' => true, 'message' => 'Xóa danh mục thành công']);
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $category = Category::find($id);
        if (!$category) return response()->json(['message' => 'Không tìm thấy'], 404);

        $data = $request->validated();
        if (isset($data['name'])) {
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        }

        $category->update($data);

        return response()->json(['success' => true, 'data' => new CategoryResource($category)]);
    }
}
