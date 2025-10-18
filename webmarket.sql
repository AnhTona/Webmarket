-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 13, 2025 lúc 08:20 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `webmarket`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bantrongquan`
--

CREATE TABLE `bantrongquan` (
  `MaBan` bigint(20) UNSIGNED NOT NULL,
  `SoLuongBan` int(10) UNSIGNED DEFAULT NULL,
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chamsockhachhang`
--

CREATE TABLE `chamsockhachhang` (
  `MaYeuCau` bigint(20) UNSIGNED NOT NULL,
  `MaNguoiDung` bigint(20) UNSIGNED NOT NULL,
  `NoiDung` text NOT NULL,
  `TraLoi` text DEFAULT NULL,
  `TrangThai` enum('OPEN','IN_PROGRESS','RESOLVED','CLOSED') NOT NULL DEFAULT 'OPEN',
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietdonhang`
--

CREATE TABLE `chitietdonhang` (
  `MaChiTietDonHang` bigint(20) UNSIGNED NOT NULL,
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `MaSanPham` bigint(20) UNSIGNED NOT NULL,
  `SoLuong` int(10) UNSIGNED NOT NULL,
  `DonGia` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietgiohang`
--

CREATE TABLE `chitietgiohang` (
  `MaChiTietGioHang` bigint(20) UNSIGNED NOT NULL,
  `MaGioHang` bigint(20) UNSIGNED NOT NULL,
  `MaSanPham` bigint(20) UNSIGNED NOT NULL,
  `SoLuong` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhgiasanpham`
--

CREATE TABLE `danhgiasanpham` (
  `MaDanhGia` bigint(20) UNSIGNED NOT NULL,
  `MaSanPham` bigint(20) UNSIGNED NOT NULL,
  `MaNguoiDung` bigint(20) UNSIGNED NOT NULL,
  `SoSao` tinyint(3) UNSIGNED NOT NULL,
  `BinhLuan` text DEFAULT NULL,
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhmucsanpham`
--

CREATE TABLE `danhmucsanpham` (
  `MaDanhMuc` bigint(20) UNSIGNED NOT NULL,
  `TenDanhMuc` varchar(150) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `Loai` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `danhmucsanpham`
--

INSERT INTO `danhmucsanpham` (`MaDanhMuc`, `TenDanhMuc`, `MoTa`, `Loai`) VALUES
(1, 'Trà', 'Các loại trà truyền thống và hiện đại', NULL),
(2, 'Bánh', 'Bánh Trung Thu và các loại bánh khác', NULL),
(3, 'Combo', 'Bộ sản phẩm kết hợp trà và bánh', NULL),
(4, 'Khuyến Mãi', 'Sản phẩm đang được khuyến mãi', NULL),
(5, 'Lục Trà', 'Trà xanh tự nhiên', 'Trà'),
(6, 'Hồng Trà', 'Trà đen thơm ngon', 'Trà'),
(7, 'Bạch Trà', 'Trà trắng tinh tế', 'Trà'),
(8, 'Oolong Trà', 'Trà Ô Long đặc trưng', 'Trà'),
(9, 'Phổ Nhĩ', 'Trà Phổ Nhĩ lâu năm', 'Trà'),
(10, 'Bánh Nướng', 'Bánh Trung Thu nướng truyền thống', 'Bánh'),
(11, 'Bánh Dẻo', 'Bánh Trung Thu dẻo', 'Bánh'),
(12, 'Bánh Ăn Kèm', 'Bánh ăn kèm trà', 'Bánh'),
(13, 'Combo', 'Danh mục con cho các combo', 'Combo'),
(14, 'Khuyến Mãi', 'Danh mục con cho sản phẩm khuyến mãi', 'Khuyến Mãi');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang`
--

CREATE TABLE `donhang` (
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `MaNguoiDung` bigint(20) UNSIGNED NOT NULL,
  `MaGioHang` bigint(20) UNSIGNED DEFAULT NULL,
  `MaBan` bigint(20) UNSIGNED DEFAULT NULL,
  `NgayDat` datetime NOT NULL DEFAULT current_timestamp(),
  `TongTien` decimal(14,2) NOT NULL DEFAULT 0.00,
  `TrangThai` enum('DRAFT','PLACED','CONFIRMED','SHIPPING','DONE','CANCELLED') NOT NULL DEFAULT 'PLACED'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang_voucher`
--

CREATE TABLE `donhang_voucher` (
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `MaVoucher` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giohang`
--

CREATE TABLE `giohang` (
  `MaGioHang` bigint(20) UNSIGNED NOT NULL,
  `MaNguoiDung` bigint(20) UNSIGNED NOT NULL,
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khuyenmai`
--

CREATE TABLE `khuyenmai` (
  `MaKhuyenMai` bigint(20) UNSIGNED NOT NULL,
  `TenKhuyenMai` varchar(150) DEFAULT NULL,
  `PhanTramGiam` decimal(5,2) NOT NULL,
  `NgayBatDau` datetime NOT NULL,
  `NgayKetThuc` datetime NOT NULL,
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `khuyenmai`
--

INSERT INTO `khuyenmai` (`MaKhuyenMai`, `TenKhuyenMai`, `PhanTramGiam`, `NgayBatDau`, `NgayKetThuc`, `TrangThai`) VALUES
(1, 'Khuyến Mãi Trung Thu', 20.00, '2025-09-01 00:00:00', '2025-10-30 23:59:59', 1),
(2, 'Khuyến Mãi Trà Mới', 15.00, '2025-10-01 00:00:00', '2025-11-30 23:59:59', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsutrangthaidonhang`
--

CREATE TABLE `lichsutrangthaidonhang` (
  `MaLichSu` bigint(20) UNSIGNED NOT NULL,
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `TrangThai` enum('PLACED','CONFIRMED','SHIPPING','DONE','CANCELLED') NOT NULL,
  `NgayCapNhat` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoidung`
--

CREATE TABLE `nguoidung` (
  `MaNguoiDung` bigint(20) UNSIGNED NOT NULL,
  `Username` varchar(50) NOT NULL,
  `HoTen` varchar(150) NOT NULL,
  `Email` varchar(191) NOT NULL,
  `MatKhau` varchar(255) NOT NULL,
  `SoDienThoai` varchar(30) DEFAULT NULL,
  `DiaChi` varchar(255) DEFAULT NULL,
  `VaiTro` enum('USER','ADMIN','STAFF') DEFAULT 'USER',
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1,
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham`
--

CREATE TABLE `sanpham` (
  `MaSanPham` bigint(20) UNSIGNED NOT NULL,
  `TenSanPham` varchar(200) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `Gia` decimal(12,2) NOT NULL,
  `GiaCu` decimal(12,2) DEFAULT NULL,
  `HinhAnh` varchar(255) DEFAULT NULL,
  `SoLuongTon` int(10) UNSIGNED NOT NULL DEFAULT 100,
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1,
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp(),
  `IsPromo` tinyint(4) NOT NULL DEFAULT 0,
  `Loai` varchar(150) DEFAULT NULL,
  `Popularity` int(10) UNSIGNED DEFAULT 0,
  `NewProduct` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham`
--

INSERT INTO `sanpham` (`MaSanPham`, `TenSanPham`, `MoTa`, `Gia`, `GiaCu`, `HinhAnh`, `SoLuongTon`, `TrangThai`, `NgayTao`, `IsPromo`, `Loai`, `Popularity`, `NewProduct`) VALUES
(1, 'Lục Trà Lài Thượng Hạng', 'Trà xanh tự nhiên với hương lài', 250000.00, NULL, 'image/sp5.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Lục Trà', 150, 0),
(2, 'Hồng Trà Cổ Thụ', 'Trà đen cổ thụ', 382500.00, 450000.00, 'image/sp17.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Hồng Trà', 210, 1),
(3, 'Trà Sen Tây Hồ', 'Trà sen cao cấp khuyến mãi', 240000.00, 320000.00, 'image/sp56.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 400, 1),
(6, 'Phổ Nhĩ Thụ Tuổi', 'Trà Phổ Nhĩ lâu năm', 950000.00, NULL, 'image/sp30.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 50, 0),
(7, 'Trà Xanh Thái Nguyên', 'Trà xanh từ Thái Nguyên', 210000.00, 300000.00, 'image/sp8.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Lục Trà', 350, 1),
(8, 'Bánh Trung Thu Trứng Muối', 'Bánh nướng nhân trứng muối', 590000.00, NULL, 'image/sp35.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 450, 0),
(10, 'Bánh Trung Thu Trà Xanh', 'Bánh nướng nhân trà xanh', 185000.00, NULL, 'image/sp36.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 110, 1),
(11, 'Bánh Thập Cẩm Cao Cấp', 'Bánh thập cẩm khuyến mãi', 552500.00, 650000.00, 'image/sp57.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 320, 0),
(14, 'Bánh Thỏ Ngọc (Bánh Dẻo)', 'Bánh dẻo truyền thống', 160000.00, NULL, 'image/sp41.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Dẻo', 90, 0),
(15, 'Combo Trăng Vàng', 'Combo trà và bánh cao cấp', 1200000.00, NULL, 'image/sp48.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 500, 1),
(16, 'Combo Hội Ngộ', 'Combo trà và bánh', 850000.00, NULL, 'image/sp49.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 300, 0),
(17, 'Combo Thưởng Nguyệt', 'Combo trà và bánh', 550000.00, NULL, 'image/sp50.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 100, 0),
(22, 'Hồng Trà Thượng Hạng', 'Trà đen cao cấp', 400000.00, NULL, 'image/sp18.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 180, 0),
(24, 'Bạch Trà Cao Cấp', 'Trà trắng tinh tế', 620000.00, NULL, 'image/sp23.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bạch Trà', 70, 0),
(26, 'Phổ Nhĩ Đặc Biệt', 'Trà Phổ Nhĩ đặc biệt', 980000.00, NULL, 'imagesp301.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 60, 0),
(29, 'Bánh Dẻo Sầu Riêng', 'Bánh dẻo nhân sầu riêng', 170000.00, NULL, 'image/sp42.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Dẻo', 100, 0),
(31, 'Combo Trà Lài và Bánh Dẻo', 'Combo trà lài và bánh dẻo', 450000.00, NULL, 'image/sp51.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 200, 0),
(34, 'Combo Cao Cấp', 'Combo trà và bánh cao cấp', 1000000.00, NULL, 'image/sp52.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 250, 0),
(35, 'Combo Mua 3 Tặng 1 Trà Xanh', 'Combo khuyến mãi trà xanh', 630000.00, 900000.00, 'image/sp64.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 250, 0),
(36, 'Bánh Nướng Giảm 20%', 'Bánh nướng khuyến mãi', 472000.00, 590000.00, 'image/sp37.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 380, 1),
(37, 'Lục Trà Mộc Châu', 'Trà xanh từ Mộc Châu', 230000.00, NULL, 'image/sp14.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Lục Trà', 160, 0),
(39, 'Lục Trà Ốc Đỉnh', 'Trà xanh cao cấp', 300000.00, NULL, 'image/sp15.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Lục Trà', 110, 0),
(40, 'Lục Trà Hương Lài Đặc Biệt', 'Trà xanh với hương lài đặc biệt', 260000.00, NULL, 'image/sp16.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Lục Trà', 100, 0),
(43, 'Hồng Trà Bảo Lộc', 'Trà đen từ Bảo Lộc', 370000.00, NULL, 'image/sp19.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 150, 0),
(44, 'Hồng Trà Kim Cương', 'Trà đen cao cấp', 390000.00, NULL, 'image/sp20.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 130, 0),
(45, 'Hồng Trà Phú Sĩ', 'Trà đen đặc biệt', 410000.00, NULL, 'image/sp21.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 120, 0),
(46, 'Hồng Trà Thảo Nguyên', 'Trà đen thảo nguyên', 360000.00, NULL, 'image/sp22.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 110, 0),
(51, 'Bạch Trà Thượng Uyển', 'Trà trắng cao cấp', 640000.00, NULL, 'image/sp24.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bạch Trà', 65, 0),
(52, 'Bạch Trà Long Tỉnh', 'Trà trắng Long Tỉnh', 620000.00, NULL, 'image/sp25.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bạch Trà', 70, 0),
(53, 'Oolong Trà Đá', 'Trà Ô Long đặc trưng', 460000.00, NULL, 'image/sp26.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Oolong Trà', 180, 0),
(54, 'Oolong Trà Thượng Hạng', 'Trà Ô Long cao cấp', 480000.00, NULL, 'image/sp27.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Oolong Trà', 170, 0),
(57, 'Oolong Trà Đài Loan', 'Trà Ô Long từ Đài Loan', 490000.00, NULL, 'image/sp28.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Oolong Trà', 160, 0),
(58, 'Oolong Trà Hoàng Gia', 'Trà Ô Long hoàng gia', 500000.00, NULL, 'image/sp29.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Oolong Trà', 150, 0),
(59, 'Phổ Nhĩ Cổ Thụ', 'Trà Phổ Nhĩ cổ thụ', 970000.00, NULL, 'image/sp2.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 55, 0),
(63, 'Phổ Nhĩ Nguyên Chất', 'Trà Phổ Nhĩ nguyên chất', 940000.00, NULL, 'image/sp33.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 70, 0),
(64, 'Phổ Nhĩ Hương Thơm', 'Trà Phổ Nhĩ thơm ngon', 950000.00, NULL, 'image/sp34.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 55, 0),
(65, 'Bánh Nướng Khoai Môn', 'Bánh nướng nhân khoai môn', 180000.00, NULL, 'image/sp37.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 120, 0),
(66, 'Bánh Nướng Hạt Dẻ', 'Bánh nướng nhân hạt dẻ', 190000.00, NULL, 'image/sp38.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 110, 0),
(67, 'Bánh Nướng Cacao', 'Bánh nướng nhân cacao', 170000.00, NULL, 'image/sp39.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 100, 0),
(68, 'Bánh Nướng Đậu Đỏ', 'Bánh nướng nhân đậu đỏ', 160000.00, NULL, 'image/sp40.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 90, 0),
(69, 'Bánh Dẻo Đậu Xanh', 'Bánh dẻo nhân đậu xanh', 130000.00, NULL, 'image/sp43.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Dẻo', 110, 0),
(70, 'Bánh Dẻo Hạt Sen', 'Bánh dẻo nhân hạt sen', 140000.00, NULL, 'image/sp44.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Dẻo', 120, 0),
(78, 'Bánh Ăn Kèm Trà Xanh', 'Bánh ăn kèm trà xanh', 105000.00, NULL, 'image/sp45.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Ăn Kèm', 70, 0),
(79, 'Bánh Ăn Kèm Hạt Dẻ', 'Bánh ăn kèm hạt dẻ', 120000.00, NULL, 'image/sp46.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Ăn Kèm', 80, 0),
(80, 'Bánh Ăn Kèm Cacao', 'Bánh ăn kèm cacao', 110000.00, NULL, 'image/sp47.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Ăn Kèm', 60, 0),
(81, 'Combo Trà Đen và Bánh Dẻo', 'Combo trà đen và bánh dẻo', 480000.00, NULL, 'image/sp53.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 190, 0),
(82, 'Combo Oolong và Bánh Nướng', 'Combo trà oolong và bánh nướng', 520000.00, NULL, 'image/sp54.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 210, 0),
(83, 'Trà Oolong Khuyến Mãi', 'Trà oolong khuyến mãi', 360000.00, 450000.00, 'image/sp28.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 200, 0),
(84, 'Bánh Dẻo Giảm 15%', 'Bánh dẻo khuyến mãi', 127500.00, 150000.00, 'image/sp43.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 220, 0),
(85, 'Combo Giảm 10%', 'Combo khuyến mãi', 900000.00, 1000000.00, 'image/sp53.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 230, 0),
(86, 'Hồng Trà Cổ Thụ (KM)', 'Trà đen cổ thụ khuyến mãi', 382500.00, 450000.00, 'image/sp55.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 210, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham_danhmuc`
--

CREATE TABLE `sanpham_danhmuc` (
  `MaSanPham` bigint(20) UNSIGNED NOT NULL,
  `MaDanhMuc` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham_danhmuc`
--

INSERT INTO `sanpham_danhmuc` (`MaSanPham`, `MaDanhMuc`) VALUES
(1, 1),
(1, 5),
(2, 1),
(2, 6),
(3, 4),
(3, 14),
(6, 1),
(6, 9),
(7, 1),
(7, 4),
(7, 5),
(8, 2),
(8, 10),
(10, 2),
(10, 10),
(11, 4),
(11, 14),
(14, 2),
(14, 11),
(15, 3),
(15, 13),
(16, 3),
(16, 13),
(17, 3),
(17, 13),
(22, 1),
(22, 6),
(24, 1),
(24, 7),
(26, 1),
(26, 9),
(29, 2),
(29, 11),
(31, 3),
(31, 13),
(34, 3),
(34, 13),
(35, 4),
(35, 14),
(36, 4),
(36, 14),
(37, 1),
(37, 5),
(39, 1),
(39, 5),
(40, 1),
(40, 5),
(43, 1),
(43, 6),
(44, 1),
(44, 6),
(45, 1),
(45, 6),
(46, 1),
(46, 6),
(51, 1),
(51, 7),
(52, 1),
(52, 7),
(53, 1),
(53, 8),
(54, 1),
(54, 8),
(57, 1),
(57, 8),
(58, 1),
(58, 8),
(59, 1),
(59, 9),
(63, 1),
(63, 9),
(64, 1),
(64, 9),
(65, 2),
(65, 10),
(66, 2),
(66, 10),
(67, 2),
(67, 10),
(68, 2),
(68, 10),
(69, 2),
(69, 11),
(70, 2),
(70, 11),
(78, 2),
(78, 12),
(79, 2),
(79, 12),
(80, 2),
(80, 12),
(81, 3),
(81, 13),
(82, 3),
(82, 13),
(83, 4),
(83, 14),
(84, 4),
(84, 14),
(85, 4),
(85, 14),
(86, 4),
(86, 14);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham_khuyenmai`
--

CREATE TABLE `sanpham_khuyenmai` (
  `MaKhuyenMai` bigint(20) UNSIGNED NOT NULL,
  `MaSanPham` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham_khuyenmai`
--

INSERT INTO `sanpham_khuyenmai` (`MaKhuyenMai`, `MaSanPham`) VALUES
(1, 11),
(1, 36),
(1, 86),
(2, 3),
(2, 7),
(2, 35),
(2, 83),
(2, 84),
(2, 85);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thanhtoan`
--

CREATE TABLE `thanhtoan` (
  `MaThanhToan` bigint(20) UNSIGNED NOT NULL,
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `PhuongThuc` enum('CASH','CARD','BANKING','EWALLET') NOT NULL,
  `SoTien` decimal(14,2) NOT NULL,
  `NgayThanhToan` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thongbaoemail`
--

CREATE TABLE `thongbaoemail` (
  `MaEmail` bigint(20) UNSIGNED NOT NULL,
  `TieuDe` varchar(200) NOT NULL,
  `NoiDung` text NOT NULL,
  `NgayGui` datetime NOT NULL DEFAULT current_timestamp(),
  `MaNguoiDung` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher`
--

CREATE TABLE `voucher` (
  `MaVoucher` bigint(20) UNSIGNED NOT NULL,
  `MaCode` varchar(64) NOT NULL,
  `LoaiGiamGia` enum('PERCENT','AMOUNT') NOT NULL,
  `GiaTri` decimal(12,2) NOT NULL,
  `SoLuongConLai` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `NgayBatDau` datetime NOT NULL,
  `NgayKetThuc` datetime NOT NULL,
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bantrongquan`
--
ALTER TABLE `bantrongquan`
  ADD PRIMARY KEY (`MaBan`);

--
-- Chỉ mục cho bảng `chamsockhachhang`
--
ALTER TABLE `chamsockhachhang`
  ADD PRIMARY KEY (`MaYeuCau`),
  ADD KEY `idx_cskh_user` (`MaNguoiDung`);

--
-- Chỉ mục cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD PRIMARY KEY (`MaChiTietDonHang`),
  ADD UNIQUE KEY `uq_dh_sp` (`MaDonHang`,`MaSanPham`),
  ADD KEY `idx_ctdh_sp` (`MaSanPham`);

--
-- Chỉ mục cho bảng `chitietgiohang`
--
ALTER TABLE `chitietgiohang`
  ADD PRIMARY KEY (`MaChiTietGioHang`),
  ADD UNIQUE KEY `uq_cart_item` (`MaGioHang`,`MaSanPham`),
  ADD KEY `idx_ctgh_sp` (`MaSanPham`);

--
-- Chỉ mục cho bảng `danhgiasanpham`
--
ALTER TABLE `danhgiasanpham`
  ADD PRIMARY KEY (`MaDanhGia`),
  ADD KEY `idx_dg_sp` (`MaSanPham`),
  ADD KEY `idx_dg_user` (`MaNguoiDung`);

--
-- Chỉ mục cho bảng `danhmucsanpham`
--
ALTER TABLE `danhmucsanpham`
  ADD PRIMARY KEY (`MaDanhMuc`);

--
-- Chỉ mục cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD PRIMARY KEY (`MaDonHang`),
  ADD KEY `idx_dh_user` (`MaNguoiDung`),
  ADD KEY `idx_dh_gh` (`MaGioHang`),
  ADD KEY `fk_dh_ban` (`MaBan`);

--
-- Chỉ mục cho bảng `donhang_voucher`
--
ALTER TABLE `donhang_voucher`
  ADD PRIMARY KEY (`MaDonHang`,`MaVoucher`),
  ADD KEY `idx_dhvc_vc` (`MaVoucher`);

--
-- Chỉ mục cho bảng `giohang`
--
ALTER TABLE `giohang`
  ADD PRIMARY KEY (`MaGioHang`),
  ADD KEY `idx_giohang_user` (`MaNguoiDung`);

--
-- Chỉ mục cho bảng `khuyenmai`
--
ALTER TABLE `khuyenmai`
  ADD PRIMARY KEY (`MaKhuyenMai`);

--
-- Chỉ mục cho bảng `lichsutrangthaidonhang`
--
ALTER TABLE `lichsutrangthaidonhang`
  ADD PRIMARY KEY (`MaLichSu`),
  ADD KEY `idx_ls_dh` (`MaDonHang`);

--
-- Chỉ mục cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`MaNguoiDung`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Chỉ mục cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`MaSanPham`);

--
-- Chỉ mục cho bảng `sanpham_danhmuc`
--
ALTER TABLE `sanpham_danhmuc`
  ADD PRIMARY KEY (`MaSanPham`,`MaDanhMuc`),
  ADD KEY `idx_spdm_dm` (`MaDanhMuc`);

--
-- Chỉ mục cho bảng `sanpham_khuyenmai`
--
ALTER TABLE `sanpham_khuyenmai`
  ADD PRIMARY KEY (`MaKhuyenMai`,`MaSanPham`),
  ADD KEY `idx_spkm_sp` (`MaSanPham`);

--
-- Chỉ mục cho bảng `thanhtoan`
--
ALTER TABLE `thanhtoan`
  ADD PRIMARY KEY (`MaThanhToan`),
  ADD KEY `idx_tt_dh` (`MaDonHang`);

--
-- Chỉ mục cho bảng `thongbaoemail`
--
ALTER TABLE `thongbaoemail`
  ADD PRIMARY KEY (`MaEmail`),
  ADD KEY `fk_email_user` (`MaNguoiDung`);

--
-- Chỉ mục cho bảng `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`MaVoucher`),
  ADD UNIQUE KEY `MaCode` (`MaCode`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bantrongquan`
--
ALTER TABLE `bantrongquan`
  MODIFY `MaBan` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chamsockhachhang`
--
ALTER TABLE `chamsockhachhang`
  MODIFY `MaYeuCau` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  MODIFY `MaChiTietDonHang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietgiohang`
--
ALTER TABLE `chitietgiohang`
  MODIFY `MaChiTietGioHang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `danhgiasanpham`
--
ALTER TABLE `danhgiasanpham`
  MODIFY `MaDanhGia` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `danhmucsanpham`
--
ALTER TABLE `danhmucsanpham`
  MODIFY `MaDanhMuc` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `donhang`
--
ALTER TABLE `donhang`
  MODIFY `MaDonHang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `giohang`
--
ALTER TABLE `giohang`
  MODIFY `MaGioHang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `khuyenmai`
--
ALTER TABLE `khuyenmai`
  MODIFY `MaKhuyenMai` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `lichsutrangthaidonhang`
--
ALTER TABLE `lichsutrangthaidonhang`
  MODIFY `MaLichSu` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  MODIFY `MaNguoiDung` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `MaSanPham` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT cho bảng `thanhtoan`
--
ALTER TABLE `thanhtoan`
  MODIFY `MaThanhToan` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `thongbaoemail`
--
ALTER TABLE `thongbaoemail`
  MODIFY `MaEmail` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `voucher`
--
ALTER TABLE `voucher`
  MODIFY `MaVoucher` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chamsockhachhang`
--
ALTER TABLE `chamsockhachhang`
  ADD CONSTRAINT `fk_cskh_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD CONSTRAINT `fk_ctdh_dh` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctdh_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chitietgiohang`
--
ALTER TABLE `chitietgiohang`
  ADD CONSTRAINT `fk_ctgh_gh` FOREIGN KEY (`MaGioHang`) REFERENCES `giohang` (`MaGioHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctgh_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `danhgiasanpham`
--
ALTER TABLE `danhgiasanpham`
  ADD CONSTRAINT `fk_dg_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dg_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `fk_dh_ban` FOREIGN KEY (`MaBan`) REFERENCES `bantrongquan` (`MaBan`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dh_gh` FOREIGN KEY (`MaGioHang`) REFERENCES `giohang` (`MaGioHang`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dh_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `donhang_voucher`
--
ALTER TABLE `donhang_voucher`
  ADD CONSTRAINT `fk_dhvc_dh` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dhvc_vc` FOREIGN KEY (`MaVoucher`) REFERENCES `voucher` (`MaVoucher`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `giohang`
--
ALTER TABLE `giohang`
  ADD CONSTRAINT `fk_giohang_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `lichsutrangthaidonhang`
--
ALTER TABLE `lichsutrangthaidonhang`
  ADD CONSTRAINT `fk_ls_dh` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `sanpham_danhmuc`
--
ALTER TABLE `sanpham_danhmuc`
  ADD CONSTRAINT `fk_spdm_dm` FOREIGN KEY (`MaDanhMuc`) REFERENCES `danhmucsanpham` (`MaDanhMuc`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_spdm_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `sanpham_khuyenmai`
--
ALTER TABLE `sanpham_khuyenmai`
  ADD CONSTRAINT `fk_spkm_km` FOREIGN KEY (`MaKhuyenMai`) REFERENCES `khuyenmai` (`MaKhuyenMai`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_spkm_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `thanhtoan`
--
ALTER TABLE `thanhtoan`
  ADD CONSTRAINT `fk_tt_dh` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `thongbaoemail`
--
ALTER TABLE `thongbaoemail`
  ADD CONSTRAINT `fk_email_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
