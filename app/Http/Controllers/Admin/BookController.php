<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['category']);

        // Tìm kiếm
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%")
                  ->orWhere('publisher', 'like', "%{$search}%");
            });
        }

        // Lọc theo danh mục
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category_id', $request->category);
        }

        // Lọc theo trạng thái
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        // Lọc theo giá
        if ($request->has('price_from') && !empty($request->price_from)) {
            $query->where('price', '>=', $request->price_from);
        }
        if ($request->has('price_to') && !empty($request->price_to)) {
            $query->where('price', '<=', $request->price_to);
        }

        $books = $query->latest()->paginate(15)->appends($request->all());
        $categories = Category::where('is_active', true)->get();

        return view('admin.books.index', compact('books', 'categories'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        return view('admin.books.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|max:50|unique:books',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'description' => 'nullable|string|max:2000',
            'publisher' => 'required|string|max:255',
            'published_date' => 'required|date|before_or_equal:today',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'title.required' => 'Tiêu đề sách là bắt buộc.',
            'author.required' => 'Tác giả là bắt buộc.',
            'isbn.required' => 'ISBN là bắt buộc.',
            'isbn.unique' => 'ISBN đã tồn tại.',
            'category_id.required' => 'Danh mục là bắt buộc.',
            'price.required' => 'Giá sách là bắt buộc.',
            'price.numeric' => 'Giá sách phải là số.',
            'price.min' => 'Giá sách không được nhỏ hơn 0.',
            'price.max' => 'Giá sách quá lớn.',
            'publisher.required' => 'Nhà xuất bản là bắt buộc.',
            'published_date.required' => 'Ngày xuất bản là bắt buộc.',
            'published_date.before_or_equal' => 'Ngày xuất bản không được trong tương lai.',
            'image.image' => 'File phải là hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
        ]);

        $data = $request->all();

        // Xử lý upload hình ảnh
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('books', 'public');
        }

        $book = Book::create($data);

        // Tạo bản ghi inventory cho sách mới
        Inventory::create([
            'book_id' => $book->id,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
        ]);

        return redirect()->route('admin.books.index')
            ->with('success', 'Sách đã được tạo thành công.');
    }

    public function show(Book $book)
    {
        $book->load(['category', 'inventory', 'orderRequestDetails.orderRequest']);
        
        $stats = [
            'total_orders' => $book->orderRequestDetails()->count(),
            'total_ordered_quantity' => $book->orderRequestDetails()->sum('quantity'),
            'current_inventory' => $book->inventory->quantity ?? 0,
            'available_inventory' => $book->inventory->available_quantity ?? 0,
            'reserved_inventory' => $book->inventory->reserved_quantity ?? 0,
            'total_inventory_value' => ($book->inventory->quantity ?? 0) * $book->price,
        ];

        // Lịch sử đặt hàng
        $order_history = $book->orderRequestDetails()
            ->with(['orderRequest.branch', 'orderRequest.creator'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.books.show', compact('book', 'stats', 'order_history'));
    }

    public function edit(Book $book)
    {
        $categories = Category::where('is_active', true)->get();
        return view('admin.books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, Book $book)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|max:50|unique:books,isbn,' . $book->id,
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'description' => 'nullable|string|max:2000',
            'publisher' => 'required|string|max:255',
            'published_date' => 'required|date|before_or_equal:today',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'title.required' => 'Tiêu đề sách là bắt buộc.',
            'author.required' => 'Tác giả là bắt buộc.',
            'isbn.required' => 'ISBN là bắt buộc.',
            'isbn.unique' => 'ISBN đã tồn tại.',
            'category_id.required' => 'Danh mục là bắt buộc.',
            'price.required' => 'Giá sách là bắt buộc.',
            'price.numeric' => 'Giá sách phải là số.',
            'price.min' => 'Giá sách không được nhỏ hơn 0.',
            'price.max' => 'Giá sách quá lớn.',
            'publisher.required' => 'Nhà xuất bản là bắt buộc.',
            'published_date.required' => 'Ngày xuất bản là bắt buộc.',
            'published_date.before_or_equal' => 'Ngày xuất bản không được trong tương lai.',
            'image.image' => 'File phải là hình ảnh.',
            'image.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'image.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
        ]);

        $data = $request->all();

        // Xử lý upload hình ảnh mới
        if ($request->hasFile('image')) {
            // Xóa hình ảnh cũ
            if ($book->image) {
                Storage::disk('public')->delete($book->image);
            }
            $data['image'] = $request->file('image')->store('books', 'public');
        }

        $book->update($data);

        return redirect()->route('admin.books.index')
            ->with('success', 'Sách đã được cập nhật thành công.');
    }

    public function destroy(Book $book)
    {
        // Kiểm tra sách có trong đơn hàng đang chờ duyệt không
        $pendingOrders = $book->orderRequestDetails()
            ->whereHas('orderRequest', function($q) {
                $q->whereIn('status', ['pending', 'approved']);
            })->count();

        if ($pendingOrders > 0) {
            return back()->with('error', 'Không thể xóa sách đang có trong đơn hàng chờ duyệt.');
        }

        $book->update(['is_active' => false]);
        
        return redirect()->route('admin.books.index')
            ->with('success', 'Sách đã được vô hiệu hóa.');
    }

    public function restore($id)
    {
        $book = Book::find($id);
        if ($book) {
            $book->update(['is_active' => true]);
            return back()->with('success', 'Sách đã được khôi phục.');
        }
        return back()->with('error', 'Không tìm thấy sách.');
    }
}