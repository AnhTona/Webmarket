<?php
declare(strict_types=1);

/**
 * Product_Controller.php
 * Product management controller with PHP 8.4 features
 *
 * @package Admin\Controller
 * @author AnhTona
 * @version 2.0.0
 * @since PHP 8.4
 */

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/BaseController.php';

final class Product_Controller extends BaseController
{
    // Upload configuration
    private const UPLOAD_DIR = __DIR__ . '/../../image';
    private const UPLOAD_URL = '/Webmarket/image';
    private const MAX_FILE_SIZE = 5_242_880; // 5MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Handle product requests
     *
     * @return array<string, mixed>
     */
    public static function handle(): array
    {
        self::requireAuth();

        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? $_POST['action'] ?? 'index';

        // AJAX requests
        if (self::isAjax()) {
            return match($action) {
                'save', 'create', 'update' => self::save(),
                'delete' => self::delete(),
                'get' => self::getOne(),
                default => self::error('Invalid action')
            };
        }

        // Regular request - list view
        try {
            return self::index();
        } catch (Throwable $e) {
            error_log("Products List Error: " . $e->getMessage());
            return [
                'product_list_paginated' => [],
                'category_options' => [],
                'page' => 1,
                'total_pages' => 1,
                'error' => 'Không thể tải danh sách sản phẩm',
            ];
        }
    }

    /**
     * List products with pagination and filters
     *
     * @return array<string, mixed>
     */
    private static function index(): array
    {
        $perPage = max(1, (int)($_GET['per_page'] ?? 20));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        // Filters
        $search = self::sanitize($_GET['search'] ?? '');
        $category = $_GET['category'] ?? 'All';
        $promo = $_GET['promo'] ?? '';

        [$where, $params] = self::buildWhereClause($search, $category, $promo);

        // Count total
        $total = (int)self::fetchOne(
            "SELECT COUNT(DISTINCT s.MaSanPham)
             FROM sanpham s
             LEFT JOIN sanpham_danhmuc sdm ON s.MaSanPham = sdm.MaSanPham
             LEFT JOIN danhmucsanpham dm ON sdm.MaDanhMuc = dm.MaDanhMuc
             LEFT JOIN sanpham_khuyenmai kmsp ON kmsp.MaSanPham = s.MaSanPham
             LEFT JOIN khuyenmai km ON km.MaKhuyenMai = kmsp.MaKhuyenMai
                 AND NOW() BETWEEN km.NgayBatDau AND km.NgayKetThuc
             {$where}",
            $params
        );

        $totalPages = max(1, (int)ceil($total / $perPage));

        // Fetch products
        $products = self::fetchAll(
            "SELECT 
                s.MaSanPham,
                s.TenSanPham,
                s.Gia,
                s.GiaCu,
                s.SoLuongTon,
                s.HinhAnh,
                dm.MaDanhMuc,
                dm.TenDanhMuc,
                IF(km.MaKhuyenMai IS NOT NULL, 1, 0) as IsPromo
             FROM sanpham s
             LEFT JOIN sanpham_danhmuc sdm ON s.MaSanPham = sdm.MaSanPham
             LEFT JOIN danhmucsanpham dm ON sdm.MaDanhMuc = dm.MaDanhMuc
             LEFT JOIN sanpham_khuyenmai kmsp ON kmsp.MaSanPham = s.MaSanPham
             LEFT JOIN khuyenmai km ON km.MaKhuyenMai = kmsp.MaKhuyenMai
                 AND NOW() BETWEEN km.NgayBatDau AND km.NgayKetThuc
             {$where}
             GROUP BY s.MaSanPham
             ORDER BY s.MaSanPham DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        // Get categories for filter dropdown
        $categories = self::fetchAll(
            "SELECT DISTINCT TenDanhMuc FROM danhmucsanpham ORDER BY TenDanhMuc"
        );

        return [
            'product_list_paginated' => $products,
            'category_options' => $categories,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_products' => $total,
        ];
    }

    /**
     * Build WHERE clause for filters
     *
     * @return array{string, array<int, mixed>}
     */
    private static function buildWhereClause(
        string $search,
        string $category,
        string $promo
    ): array {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = 's.TenSanPham LIKE ?';
            $params[] = "%{$search}%";
        }

        if ($category !== '' && $category !== 'All') {
            if (ctype_digit($category)) {
                $where[] = 'dm.MaDanhMuc = ?';
                $params[] = (int)$category;
            } else {
                $where[] = 'dm.TenDanhMuc = ?';
                $params[] = $category;
            }
        }

        if ($promo === '1') {
            $where[] = 'km.MaKhuyenMai IS NOT NULL';
        } elseif ($promo === '0') {
            $where[] = 'km.MaKhuyenMai IS NULL';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    /**
     * Get single product
     *
     * @return never
     */
    private static function getOne(): never
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            self::error('Thiếu ID sản phẩm');
        }

        $product = self::fetchRow(
            "SELECT 
                s.*,
                dm.MaDanhMuc,
                dm.TenDanhMuc,
                IF(km.MaKhuyenMai IS NOT NULL, 1, 0) as IsPromo
             FROM sanpham s
             LEFT JOIN sanpham_danhmuc sdm ON s.MaSanPham = sdm.MaSanPham
             LEFT JOIN danhmucsanpham dm ON sdm.MaDanhMuc = dm.MaDanhMuc
             LEFT JOIN sanpham_khuyenmai kmsp ON kmsp.MaSanPham = s.MaSanPham
             LEFT JOIN khuyenmai km ON km.MaKhuyenMai = kmsp.MaKhuyenMai
                 AND NOW() BETWEEN km.NgayBatDau AND km.NgayKetThuc
             WHERE s.MaSanPham = ?
             LIMIT 1",
            [$id]
        );

        if (!$product) {
            self::error('Không tìm thấy sản phẩm');
        }

        self::success('Lấy thông tin sản phẩm thành công', [
            'product' => $product
        ]);
    }

    /**
     * Save product (create or update)
     *
     * @return never
     */
    private static function save(): never
    {
        $id = (int)($_POST['id'] ?? 0);
        $name = self::sanitize($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $oldPrice = !empty($_POST['old-price']) ? (float)$_POST['old-price'] : null;
        $quantity = (int)($_POST['quantity'] ?? 0);
        $category = self::sanitize($_POST['category'] ?? '');
        $promo = (int)($_POST['promo'] ?? 0);

        // Validate
        if ($name === '' || $price <= 0 || $category === '') {
            self::error('Vui lòng điền đầy đủ thông tin sản phẩm');
        }

        try {
            self::transaction(function() use ($id, $name, $price, $oldPrice, $quantity, $category, $promo) {
                // Handle image upload
                $imagePath = self::handleImageUpload($id);

                if ($id > 0) {
                    // Update existing product
                    self::updateProduct($id, $name, $price, $oldPrice, $quantity, $imagePath);
                } else {
                    // Create new product
                    $id = self::createProduct($name, $price, $oldPrice, $quantity, $imagePath);
                }

                // Update category relationship
                self::updateCategory($id, $category);

                // Update promotion status
                if ($promo) {
                    self::addPromotion($id);
                } else {
                    self::removePromotion($id);
                }

                self::log('save_product', [
                    'product_id' => $id,
                    'name' => $name,
                    'action' => $id > 0 ? 'update' : 'create'
                ]);
            });

            self::success(
                $id > 0 ? 'Cập nhật sản phẩm thành công!' : 'Thêm sản phẩm thành công!',
                ['product_id' => $id]
            );

        } catch (Throwable $e) {
            error_log("Save Product Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }

    /**
     * Handle image upload
     */
    private static function handleImageUpload(int $productId): ?string
    {
        // No new file uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            // Keep old image if editing
            return !empty($_POST['image_old']) ? $_POST['image_old'] : null;
        }

        $file = $_FILES['image'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Lỗi upload file: ' . $file['error']);
        }

        // Validate file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new RuntimeException('File quá lớn (tối đa 5MB)');
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            throw new RuntimeException('Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)');
        }

        // Create upload directory if not exists
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, recursive: true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
        $destination = self::UPLOAD_DIR . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Không thể lưu file');
        }

        // Delete old image if exists
        if ($productId > 0 && !empty($_POST['image_old'])) {
            $oldImage = self::UPLOAD_DIR . '/' . basename($_POST['image_old']);
            if (file_exists($oldImage)) {
                @unlink($oldImage);
            }
        }

        return self::UPLOAD_URL . '/' . $filename;
    }

    /**
     * Create new product
     */
    private static function createProduct(
        string $name,
        float $price,
        ?float $oldPrice,
        int $quantity,
        ?string $image
    ): int {
        self::query(
            "INSERT INTO sanpham (TenSanPham, Gia, GiaCu, SoLuongTon, HinhAnh, NgayTao)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$name, $price, $oldPrice, $quantity, $image]
        );

        return (int)self::db()->insert_id;
    }

    /**
     * Update existing product
     */
    private static function updateProduct(
        int $id,
        string $name,
        float $price,
        ?float $oldPrice,
        int $quantity,
        ?string $image
    ): void {
        if ($image !== null) {
            self::query(
                "UPDATE sanpham 
                 SET TenSanPham = ?, Gia = ?, GiaCu = ?, SoLuongTon = ?, HinhAnh = ?
                 WHERE MaSanPham = ?",
                [$name, $price, $oldPrice, $quantity, $image, $id]
            );
        } else {
            self::query(
                "UPDATE sanpham 
                 SET TenSanPham = ?, Gia = ?, GiaCu = ?, SoLuongTon = ?
                 WHERE MaSanPham = ?",
                [$name, $price, $oldPrice, $quantity, $id]
            );
        }
    }

    /**
     * Update product category
     */
    private static function updateCategory(int $productId, string $categoryName): void
    {
        // Get or create category
        $categoryId = self::fetchOne(
            "SELECT MaDanhMuc FROM danhmucsanpham WHERE TenDanhMuc = ? LIMIT 1",
            [$categoryName]
        );

        if (!$categoryId) {
            self::query(
                "INSERT INTO danhmucsanpham (TenDanhMuc) VALUES (?)",
                [$categoryName]
            );
            $categoryId = self::db()->insert_id;
        }

        // Delete old relationships
        self::query(
            "DELETE FROM sanpham_danhmuc WHERE MaSanPham = ?",
            [$productId]
        );

        // Create new relationship
        self::query(
            "INSERT INTO sanpham_danhmuc (MaSanPham, MaDanhMuc) VALUES (?, ?)",
            [$productId, $categoryId]
        );
    }

    /**
     * Add product to current promotion
     */
    private static function addPromotion(int $productId): void
    {
        // Get active promotion
        $promoId = self::fetchOne(
            "SELECT MaKhuyenMai FROM khuyenmai 
             WHERE NOW() BETWEEN NgayBatDau AND NgayKetThuc 
             LIMIT 1"
        );

        if ($promoId) {
            self::query(
                "INSERT IGNORE INTO sanpham_khuyenmai (MaSanPham, MaKhuyenMai) 
                 VALUES (?, ?)",
                [$productId, $promoId]
            );
        }
    }

    /**
     * Remove product from promotions
     */
    private static function removePromotion(int $productId): void
    {
        self::query(
            "DELETE FROM sanpham_khuyenmai WHERE MaSanPham = ?",
            [$productId]
        );
    }

    /**
     * Delete product
     *
     * @return never
     */
    private static function delete(): never
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            self::error('Thiếu ID sản phẩm');
        }

        try {
            self::transaction(function() use ($id) {
                // Get product info for logging
                $product = self::fetchRow(
                    "SELECT TenSanPham, HinhAnh FROM sanpham WHERE MaSanPham = ?",
                    [$id]
                );

                if (!$product) {
                    throw new RuntimeException('Không tìm thấy sản phẩm');
                }

                // Delete relationships
                self::query("DELETE FROM sanpham_danhmuc WHERE MaSanPham = ?", [$id]);
                self::query("DELETE FROM sanpham_khuyenmai WHERE MaSanPham = ?", [$id]);

                // Delete product
                self::query("DELETE FROM sanpham WHERE MaSanPham = ?", [$id]);

                // Delete image file
                if (!empty($product['HinhAnh'])) {
                    $imagePath = self::UPLOAD_DIR . '/' . basename($product['HinhAnh']);
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }

                self::log('delete_product', [
                    'product_id' => $id,
                    'name' => $product['TenSanPham']
                ]);
            });

            self::success('Xóa sản phẩm thành công!');

        } catch (Throwable $e) {
            error_log("Delete Product Error: " . $e->getMessage());
            self::error($e->getMessage());
        }
    }
}