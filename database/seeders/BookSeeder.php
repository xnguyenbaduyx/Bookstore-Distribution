<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\Category;
use App\Models\Supplier; // Đảm bảo import Supplier

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Category::truncate(); // Không cần truncate ở đây nếu đã tắt foreign key checks ở DatabaseSeeder
        // Supplier::truncate(); // Không cần truncate ở đây nếu đã tắt foreign key checks ở DatabaseSeeder
        // Book::truncate(); // Không cần truncate ở đây nếu đã tắt foreign key checks ở DatabaseSeeder

        $categories = Category::all();
        $suppliers = Supplier::all();

        // Đảm bảo Category và Supplier đã có dữ liệu trước khi tạo Book
        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::all(); // Lấy lại categories sau khi seed
        }
        if ($suppliers->isEmpty()) {
            $this->call(SupplierSeeder::class);
            $suppliers = Supplier::all(); // Lấy lại suppliers sau khi seed
        }

        if ($categories->isEmpty()) {
            $this->command->error('Không thể tạo sách vì CategorySeeder không tạo ra dữ liệu.');
            return;
        }
        // Kiểm tra này chỉ cần thiết nếu bạn có cột supplier_id trong bảng books
        // if ($suppliers->isEmpty()) {
        //     $this->command->error('Không thể tạo sách vì SupplierSeeder không tạo ra dữ liệu.');
        //     return;
        // }


        // Tạo một số sách mẫu cụ thể
        Book::create([
            'title' => 'Dế Mèn Phiêu Lưu Ký',
            'author' => 'Tô Hoài',
            'publisher' => 'NXB Kim Đồng',
            'isbn' => '978-604-2-19253-8',
            'category_id' => $categories->random()->id,
            'price' => 75000,
            'description' => 'Cuốn sách kinh điển về cuộc phiêu lưu của Dế Mèn.',
            'published_date' => '2020-01-15',
            'is_active' => true,
            // 'supplier_id' => $suppliers->random()->id, // CHỈ THÊM DÒNG NÀY NẾU CÓ CỘT supplier_id TRONG BẢNG BOOKS
        ]);

        Book::create([
            'title' => 'Nhà Giả Kim',
            'author' => 'Paulo Coelho',
            'publisher' => 'NXB Văn Học',
            'isbn' => '978-604-9-90600-0',
            'category_id' => $categories->random()->id,
            'price' => 89000,
            'description' => 'Câu chuyện đầy cảm hứng về hành trình khám phá bản thân.',
            'published_date' => '2018-05-20',
            'is_active' => true,
            // 'supplier_id' => $suppliers->random()->id, // CHỈ THÊM DÒNG NÀY NẾU CÓ CỘT supplier_id TRONG BẢNG BOOKS
        ]);

        // Tạo thêm sách bằng factory
        Book::factory()->count(10)->create([
            'category_id' => $categories->random()->id,
            // 'supplier_id' => $suppliers->random()->id, // CHỈ THÊM DÒNG NÀY NẾU CÓ CỘT supplier_id TRONG BẢNG BOOKS
        ]);
    }
}