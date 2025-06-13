<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::withCount('books');

        // Tìm kiếm
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $categories = $query->latest()->paginate(10)->appends($request->all());

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
        ]);

        Category::create($request->all());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Danh mục đã được tạo thành công.');
    }

    public function show(Category $category)
    {
        $category->load('books.inventory');
        
        $stats = [
            'total_books' => $category->books()->where('is_active', true)->count(),
            'inactive_books' => $category->books()->where('is_active', false)->count(),
            'total_inventory' => $category->books()->with('inventory')->get()->sum(function($book) {
                return $book->inventory ? $book->inventory->quantity : 0;
            }),
            'total_value' => $category->books()->with('inventory')->get()->sum(function($book) {
                $inventory = $book->inventory ? $book->inventory->quantity : 0;
                return $inventory * $book->price;
            }),
        ];

        $books = $category->books()->with('inventory')->latest()->take(10)->get();

        return view('admin.categories.show', compact('category', 'stats', 'books'));
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
        ]);

        $category->update($request->all());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Danh mục đã được cập nhật thành công.');
    }

    public function destroy(Category $category)
    {
        // Kiểm tra danh mục có sách đang hoạt động không
        if ($category->books()->where('is_active', true)->count() > 0) {
            return back()->with('error', 'Không thể xóa danh mục có sách đang hoạt động.');
        }

        $category->update(['is_active' => false]);
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Danh mục đã được vô hiệu hóa.');
    }

    public function restore($id)
    {
        $category = Category::find($id);
        if ($category) {
            $category->update(['is_active' => true]);
            return back()->with('success', 'Danh mục đã được khôi phục.');
        }
        return back()->with('error', 'Không tìm thấy danh mục.');
    }
}