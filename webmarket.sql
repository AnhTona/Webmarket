-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 08:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webmarket`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_recalc_user_tier` (IN `p_MaNguoiDung` BIGINT UNSIGNED)   BEGIN
  DECLARE v_total DECIMAL(14,2) DEFAULT 0;
  DECLARE v_hang  ENUM('Mới','Bronze','Silver','Gold');

  -- Tổng theo khoản thanh toán hợp lệ (đơn KHÔNG bị CANCELLED)
  SELECT COALESCE(SUM(tt.SoTien),0) INTO v_total
  FROM donhang d
  JOIN thanhtoan tt ON tt.MaDonHang = d.MaDonHang
  WHERE d.MaNguoiDung = p_MaNguoiDung
    AND d.TrangThai <> 'CANCELLED';

  -- Tìm hạng theo ngưỡng cao nhất thỏa mãn
  SELECT ch.TenHang INTO v_hang
  FROM cauhinh_hang ch
  WHERE v_total >= ch.MinChiTieu
  ORDER BY ch.MinChiTieu DESC
  LIMIT 1;

  IF v_hang IS NULL THEN
    SET v_hang = 'Mới';
  END IF;

  -- Cập nhật về bảng người dùng
  UPDATE nguoidung
  SET TongChiTieu = v_total,
      Hang        = v_hang
  WHERE MaNguoiDung = p_MaNguoiDung;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_user_tier_by_order` (IN `p_MaDonHang` BIGINT UNSIGNED)   BEGIN
  DECLARE v_uid BIGINT UNSIGNED;

  SELECT MaNguoiDung INTO v_uid
  FROM donhang
  WHERE MaDonHang = p_MaDonHang
  LIMIT 1;

  IF v_uid IS NOT NULL THEN
    CALL sp_recalc_user_tier(v_uid);
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bantrongquan`
--

CREATE TABLE `bantrongquan` (
  `MaBan` bigint(20) UNSIGNED NOT NULL,
  `SoGhe` int(10) UNSIGNED DEFAULT NULL,
  `TrangThai` tinyint(4) NOT NULL DEFAULT 1,
  `SoLanSuDung` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bantrongquan`
--

INSERT INTO `bantrongquan` (`MaBan`, `SoGhe`, `TrangThai`, `SoLanSuDung`) VALUES
(1, 2, 1, 0),
(2, 4, 1, 0),
(3, 6, 1, 0),
(4, 2, 1, 0),
(5, 2, 1, 0),
(6, 2, 1, 0),
(7, 2, 1, 0),
(8, 2, 1, 0),
(9, 2, 1, 0),
(10, 2, 1, 0),
(11, 4, 1, 0),
(12, 4, 1, 0),
(13, 4, 1, 0),
(14, 4, 1, 0),
(15, 4, 1, 0),
(16, 4, 1, 0),
(17, 4, 1, 0),
(18, 4, 1, 0),
(19, 6, 1, 0),
(20, 6, 1, 0),
(21, 6, 1, 0),
(22, 6, 1, 0),
(23, 8, 1, 0),
(24, 8, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `cauhinh_hang`
--

CREATE TABLE `cauhinh_hang` (
  `TenHang` enum('Mới','Bronze','Silver','Gold') NOT NULL,
  `MinChiTieu` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cauhinh_hang`
--

INSERT INTO `cauhinh_hang` (`TenHang`, `MinChiTieu`) VALUES
('Mới', 0.00),
('Bronze', 1000000.00),
('Silver', 5000000.00),
('Gold', 10000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `chamsockhachhang`
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
-- Table structure for table `chitietdonhang`
--

CREATE TABLE `chitietdonhang` (
  `MaChiTietDonHang` bigint(20) UNSIGNED NOT NULL,
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `MaSanPham` bigint(20) UNSIGNED NOT NULL,
  `SoLuong` int(10) UNSIGNED NOT NULL,
  `DonGia` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chitietdonhang`
--

INSERT INTO `chitietdonhang` (`MaChiTietDonHang`, `MaDonHang`, `MaSanPham`, `SoLuong`, `DonGia`) VALUES
(1, 1, 78, 1, 105000.00),
(2, 2, 78, 1, 105000.00),
(3, 3, 78, 1, 105000.00),
(4, 4, 79, 1, 120000.00),
(5, 5, 78, 1, 105000.00),
(6, 6, 78, 1, 105000.00),
(7, 7, 78, 1, 105000.00),
(8, 8, 79, 1, 120000.00),
(9, 9, 79, 1, 120000.00),
(10, 10, 78, 1, 105000.00),
(11, 11, 51, 2, 640000.00),
(12, 11, 52, 1, 620000.00),
(13, 11, 59, 1, 970000.00),
(14, 12, 83, 1, 360000.00),
(15, 13, 85, 1, 900000.00),
(16, 13, 83, 1, 360000.00),
(17, 14, 80, 1, 110000.00),
(18, 15, 80, 1, 110000.00);

-- --------------------------------------------------------

--
-- Table structure for table `chitietgiohang`
--

CREATE TABLE `chitietgiohang` (
  `MaChiTietGioHang` bigint(20) UNSIGNED NOT NULL,
  `MaGioHang` bigint(20) UNSIGNED NOT NULL,
  `MaSanPham` bigint(20) UNSIGNED NOT NULL,
  `SoLuong` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `danhgiasanpham`
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
-- Table structure for table `danhmucsanpham`
--

CREATE TABLE `danhmucsanpham` (
  `MaDanhMuc` bigint(20) UNSIGNED NOT NULL,
  `TenDanhMuc` varchar(150) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `Loai` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `danhmucsanpham`
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
(13, 'Combo', 'Danh mục con cho các combo', 'Combo');

-- --------------------------------------------------------

--
-- Table structure for table `donhang`
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

--
-- Dumping data for table `donhang`
--

INSERT INTO `donhang` (`MaDonHang`, `MaNguoiDung`, `MaGioHang`, `MaBan`, `NgayDat`, `TongTien`, `TrangThai`) VALUES
(1, 3, NULL, 1, '2025-10-22 06:59:35', 105000.00, 'CANCELLED'),
(2, 3, NULL, 1, '2025-10-22 07:01:21', 105000.00, 'CANCELLED'),
(3, 3, 1, 1, '2025-10-22 10:53:55', 111300.00, 'CANCELLED'),
(4, 3, 1, 1, '2025-10-22 10:54:36', 127200.00, 'CANCELLED'),
(5, 3, 1, 1, '2025-10-22 10:55:05', 111300.00, 'CANCELLED'),
(6, 3, 1, 1, '2025-10-22 10:55:58', 111300.00, 'CANCELLED'),
(7, 3, 1, 1, '2025-10-22 10:56:28', 111300.00, 'CANCELLED'),
(8, 3, 1, 1, '2025-10-22 11:04:45', 127200.00, 'DONE'),
(9, 3, 1, 1, '2025-10-22 11:25:48', 127200.00, 'CANCELLED'),
(10, 3, 1, 1, '2025-10-22 11:28:19', 111300.00, 'CANCELLED'),
(11, 3, 1, 1, '2025-10-22 12:11:31', 3042200.00, 'CANCELLED'),
(12, 3, 1, 1, '2025-10-22 12:11:49', 381600.00, 'CANCELLED'),
(13, 3, 1, 1, '2025-10-22 12:49:32', 1335600.00, 'CONFIRMED'),
(14, 3, 1, 1, '2025-10-22 13:28:33', 116600.00, 'PLACED'),
(15, 3, 1, 1, '2025-10-22 13:39:21', 116600.00, 'PLACED');

-- --------------------------------------------------------

--
-- Table structure for table `donhang_voucher`
--

CREATE TABLE `donhang_voucher` (
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `MaVoucher` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `giohang`
--

CREATE TABLE `giohang` (
  `MaGioHang` bigint(20) UNSIGNED NOT NULL,
  `MaNguoiDung` bigint(20) UNSIGNED NOT NULL,
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giohang`
--

INSERT INTO `giohang` (`MaGioHang`, `MaNguoiDung`, `NgayTao`) VALUES
(1, 3, '2025-10-22 10:53:55');

-- --------------------------------------------------------

--
-- Table structure for table `hoadon`
--

CREATE TABLE `hoadon` (
  `MaHoaDon` bigint(20) UNSIGNED NOT NULL,
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `NoiDungHTML` longtext NOT NULL,
  `NgayTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hoadon`
--

INSERT INTO `hoadon` (`MaHoaDon`, `MaDonHang`, `NoiDungHTML`, `NgayTao`) VALUES
(1, 12, '<!DOCTYPE html>\r\n<html lang=\"vi\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Hóa Đơn #12</title>\r\n    <style>\r\n        * { margin: 0; padding: 0; box-sizing: border-box; }\r\n        body { \r\n            font-family: Arial, sans-serif; \r\n            line-height: 1.6; \r\n            color: #333;\r\n            background: #f5f5f5;\r\n            padding: 20px;\r\n        }\r\n        .invoice-container {\r\n            max-width: 800px;\r\n            margin: 0 auto;\r\n            background: white;\r\n            padding: 30px;\r\n            box-shadow: 0 0 10px rgba(0,0,0,0.1);\r\n        }\r\n        .header {\r\n            text-align: center;\r\n            border-bottom: 3px solid #8f2c24;\r\n            padding-bottom: 20px;\r\n            margin-bottom: 30px;\r\n        }\r\n        .header h1 {\r\n            color: #8f2c24;\r\n            font-size: 28px;\r\n            margin-bottom: 10px;\r\n            text-transform: uppercase;\r\n        }\r\n        .header .company-name {\r\n            font-size: 18px;\r\n            font-weight: bold;\r\n            color: #4d0702;\r\n            margin-bottom: 5px;\r\n        }\r\n        .header .company-info {\r\n            font-size: 12px;\r\n            color: #666;\r\n            line-height: 1.8;\r\n        }\r\n        .info-section {\r\n            display: grid;\r\n            grid-template-columns: 1fr 1fr;\r\n            gap: 20px;\r\n            margin-bottom: 30px;\r\n        }\r\n        .info-box {\r\n            background: #f9f9f9;\r\n            padding: 15px;\r\n            border-left: 4px solid #8f2c24;\r\n        }\r\n        .info-box h3 {\r\n            color: #8f2c24;\r\n            font-size: 14px;\r\n            margin-bottom: 10px;\r\n            text-transform: uppercase;\r\n        }\r\n        .info-row {\r\n            display: flex;\r\n            padding: 5px 0;\r\n            font-size: 13px;\r\n        }\r\n        .info-label {\r\n            font-weight: bold;\r\n            width: 120px;\r\n            color: #555;\r\n        }\r\n        .info-value {\r\n            flex: 1;\r\n            color: #333;\r\n        }\r\n        table {\r\n            width: 100%;\r\n            border-collapse: collapse;\r\n            margin: 20px 0;\r\n        }\r\n        th {\r\n            background: #8f2c24;\r\n            color: white;\r\n            padding: 12px 8px;\r\n            text-align: left;\r\n            font-size: 13px;\r\n            text-transform: uppercase;\r\n        }\r\n        td {\r\n            padding: 10px 8px;\r\n            border-bottom: 1px solid #ddd;\r\n            font-size: 13px;\r\n        }\r\n        tr:hover td {\r\n            background: #f9f9f9;\r\n        }\r\n        .summary-table {\r\n            margin-top: 30px;\r\n            border: none;\r\n        }\r\n        .summary-table td {\r\n            border: none;\r\n            padding: 8px;\r\n        }\r\n        .summary-row {\r\n            font-size: 14px;\r\n        }\r\n        .total-row {\r\n            background: #8f2c24;\r\n            color: white;\r\n            font-size: 18px;\r\n            font-weight: bold;\r\n        }\r\n        .total-row td {\r\n            padding: 15px 8px;\r\n        }\r\n        .footer {\r\n            margin-top: 40px;\r\n            padding-top: 20px;\r\n            border-top: 2px solid #ddd;\r\n            text-align: center;\r\n            font-size: 12px;\r\n            color: #666;\r\n        }\r\n        .footer .signature {\r\n            display: flex;\r\n            justify-content: space-around;\r\n            margin-top: 30px;\r\n        }\r\n        .signature div {\r\n            text-align: center;\r\n        }\r\n        .signature-line {\r\n            width: 200px;\r\n            border-top: 1px solid #333;\r\n            margin: 50px auto 10px;\r\n        }\r\n        .print-button {\r\n            position: fixed;\r\n            top: 20px;\r\n            right: 20px;\r\n            background: #8f2c24;\r\n            color: white;\r\n            border: none;\r\n            padding: 12px 24px;\r\n            border-radius: 5px;\r\n            cursor: pointer;\r\n            font-size: 14px;\r\n            font-weight: bold;\r\n            box-shadow: 0 2px 5px rgba(0,0,0,0.2);\r\n        }\r\n        .print-button:hover {\r\n            background: #6d1f18;\r\n        }\r\n        @media print {\r\n            body { \r\n                background: white; \r\n                padding: 0; \r\n            }\r\n            .invoice-container {\r\n                box-shadow: none;\r\n                padding: 0;\r\n            }\r\n            .print-button {\r\n                display: none;\r\n            }\r\n        }\r\n        @media (max-width: 600px) {\r\n            .info-section {\r\n                grid-template-columns: 1fr;\r\n            }\r\n            .invoice-container {\r\n                padding: 15px;\r\n            }\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n    <button class=\"print-button\" onclick=\"window.print()\">🖨️ In Hóa Đơn</button>\r\n    \r\n    <div class=\"invoice-container\">\r\n        <div class=\"header\">\r\n            <h1>Hóa Đơn Bán Hàng</h1>\r\n            <div class=\"company-name\">HƯƠNG TRÀ RESTAURANT</div>\r\n            <div class=\"company-info\">\r\n                Địa chỉ: 123 Đường ABC, Quận XYZ, TP.HCM<br>\r\n                Điện thoại: 0123-456-789 | Email: contact@huongtra.com\r\n            </div>\r\n        </div>\r\n\r\n        <div class=\"info-section\">\r\n            <div class=\"info-box\">\r\n                <h3>Thông Tin Đơn Hàng</h3>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Mã đơn hàng:</span>\r\n                    <span class=\"info-value\">#12</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Ngày đặt:</span>\r\n                    <span class=\"info-value\">22/10/2025 12:11</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Bàn:</span>\r\n                    <span class=\"info-value\">Bàn 1</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Thanh toán:</span>\r\n                    <span class=\"info-value\">Tiền mặt</span>\r\n                </div>\r\n            </div>\r\n\r\n            <div class=\"info-box\">\r\n                <h3>Thông Tin Khách Hàng</h3>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Họ tên:</span>\r\n                    <span class=\"info-value\">FortNight</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Email:</span>\r\n                    <span class=\"info-value\">trananhhung12345@gmail.com</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Số điện thoại:</span>\r\n                    <span class=\"info-value\">0354942664</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Hạng thành viên:</span>\r\n                    <span class=\"info-value\">Bronze</span>\r\n                </div>\r\n            </div>\r\n        </div>\r\n\r\n        <table>\r\n            <thead>\r\n                <tr>\r\n                    <th style=\"width: 50px; text-align: center;\">STT</th>\r\n                    <th>Sản Phẩm</th>\r\n                    <th style=\"width: 80px; text-align: center;\">SL</th>\r\n                    <th style=\"width: 120px; text-align: right;\">Đơn Giá</th>\r\n                    <th style=\"width: 130px; text-align: right;\">Thành Tiền</th>\r\n                </tr>\r\n            </thead>\r\n            <tbody>\r\n                <tr>\r\n                <td style=\"text-align: center;\">1</td>\r\n                <td>Trà Oolong Khuyến Mãi</td>\r\n                <td style=\"text-align: center;\">1</td>\r\n                <td style=\"text-align: right;\">360.000 đ</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">360.000 đ</td>\r\n            </tr>\r\n            </tbody>\r\n        </table>\r\n\r\n        <table class=\"summary-table\">\r\n            <tr class=\"summary-row\">\r\n                <td style=\"text-align: right; width: 70%;\">Tạm tính:</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">360.000 đ</td>\r\n            </tr>\r\n            <tr>\r\n                <td colspan=\"4\" style=\"text-align: right; padding: 8px; background: #fff3e0; color: #e65100; font-weight: 600;\">\r\n                    Giảm giá (Hạng Bronze - 2%):\r\n                </td>\r\n                <td style=\"text-align: right; padding: 8px; background: #fff3e0; color: #d84315; font-weight: bold;\">\r\n                    - 7.200 đ\r\n                </td>\r\n            </tr>\r\n            <tr class=\"summary-row\">\r\n                <td style=\"text-align: right;\">VAT (8%):</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">28.800 đ</td>\r\n            </tr>\r\n            <tr class=\"total-row\">\r\n                <td style=\"text-align: right;\">TỔNG THANH TOÁN:</td>\r\n                <td style=\"text-align: right;\">381.600 đ</td>\r\n            </tr>\r\n        </table>\r\n\r\n        <div class=\"footer\">\r\n            <p><strong>Cảm ơn quý khách đã sử dụng dịch vụ!</strong></p>\r\n            <p>Hóa đơn này được tạo tự động bởi hệ thống.</p>\r\n            \r\n            <div class=\"signature\">\r\n                <div>\r\n                    <div class=\"signature-line\"></div>\r\n                    <strong>Khách hàng</strong>\r\n                </div>\r\n                <div>\r\n                    <div class=\"signature-line\"></div>\r\n                    <strong>Thu ngân</strong>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n</body>\r\n</html>', '2025-10-22 12:47:14'),
(2, 13, '<!DOCTYPE html>\n<html lang=\"vi\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Hóa Đơn #13</title>\n    <style>\n        * { margin: 0; padding: 0; box-sizing: border-box; }\n        body { \n            font-family: Arial, sans-serif; \n            line-height: 1.6; \n            color: #333;\n            background: #f5f5f5;\n            padding: 20px;\n        }\n        .invoice-container {\n            max-width: 800px;\n            margin: 0 auto;\n            background: white;\n            padding: 30px;\n            box-shadow: 0 0 10px rgba(0,0,0,0.1);\n        }\n        .header {\n            text-align: center;\n            border-bottom: 3px solid #8f2c24;\n            padding-bottom: 20px;\n            margin-bottom: 30px;\n        }\n        .header h1 {\n            color: #8f2c24;\n            font-size: 28px;\n            margin-bottom: 10px;\n            text-transform: uppercase;\n        }\n        .header .company-name {\n            font-size: 18px;\n            font-weight: bold;\n            color: #4d0702;\n            margin-bottom: 5px;\n        }\n        .header .company-info {\n            font-size: 12px;\n            color: #666;\n            line-height: 1.8;\n        }\n        .info-section {\n            display: grid;\n            grid-template-columns: 1fr 1fr;\n            gap: 20px;\n            margin-bottom: 30px;\n        }\n        .info-box {\n            background: #f9f9f9;\n            padding: 15px;\n            border-left: 4px solid #8f2c24;\n        }\n        .info-box h3 {\n            color: #8f2c24;\n            font-size: 14px;\n            margin-bottom: 10px;\n            text-transform: uppercase;\n        }\n        .info-row {\n            display: flex;\n            padding: 5px 0;\n            font-size: 13px;\n        }\n        .info-label {\n            font-weight: bold;\n            width: 120px;\n            color: #555;\n        }\n        .info-value {\n            flex: 1;\n            color: #333;\n        }\n        table {\n            width: 100%;\n            border-collapse: collapse;\n            margin: 20px 0;\n        }\n        th {\n            background: #8f2c24;\n            color: white;\n            padding: 12px 8px;\n            text-align: left;\n            font-size: 13px;\n            text-transform: uppercase;\n        }\n        td {\n            padding: 10px 8px;\n            border-bottom: 1px solid #ddd;\n            font-size: 13px;\n        }\n        tr:hover td {\n            background: #f9f9f9;\n        }\n        .summary-table {\n            margin-top: 30px;\n            border: none;\n        }\n        .summary-table td {\n            border: none;\n            padding: 8px;\n        }\n        .summary-row {\n            font-size: 14px;\n        }\n        .total-row {\n            background: #8f2c24;\n            color: white;\n            font-size: 18px;\n            font-weight: bold;\n        }\n        .total-row td {\n            padding: 15px 8px;\n        }\n        .footer {\n            margin-top: 40px;\n            padding-top: 20px;\n            border-top: 2px solid #ddd;\n            text-align: center;\n            font-size: 12px;\n            color: #666;\n        }\n        .footer .signature {\n            display: flex;\n            justify-content: space-around;\n            margin-top: 30px;\n        }\n        .signature div {\n            text-align: center;\n        }\n        .signature-line {\n            width: 200px;\n            border-top: 1px solid #333;\n            margin: 50px auto 10px;\n        }\n        .print-button {\n            position: fixed;\n            top: 20px;\n            right: 20px;\n            background: #8f2c24;\n            color: white;\n            border: none;\n            padding: 12px 24px;\n            border-radius: 5px;\n            cursor: pointer;\n            font-size: 14px;\n            font-weight: bold;\n            box-shadow: 0 2px 5px rgba(0,0,0,0.2);\n        }\n        .print-button:hover {\n            background: #6d1f18;\n        }\n        @media print {\n            body { \n                background: white; \n                padding: 0; \n            }\n            .invoice-container {\n                box-shadow: none;\n                padding: 0;\n            }\n            .print-button {\n                display: none;\n            }\n        }\n        @media (max-width: 600px) {\n            .info-section {\n                grid-template-columns: 1fr;\n            }\n            .invoice-container {\n                padding: 15px;\n            }\n        }\n    </style>\n</head>\n<body>\n    <button class=\"print-button\" onclick=\"window.print()\">🖨️ In Hóa Đơn</button>\n    \n    <div class=\"invoice-container\">\n        <div class=\"header\">\n            <h1>Hóa Đơn Bán Hàng</h1>\n            <div class=\"company-name\">HƯƠNG TRÀ RESTAURANT</div>\n            <div class=\"company-info\">\n                Địa chỉ: 123 Đường ABC, Quận XYZ, TP.HCM<br>\n                Điện thoại: 0123-456-789 | Email: contact@huongtra.com\n            </div>\n        </div>\n\n        <div class=\"info-section\">\n            <div class=\"info-box\">\n                <h3>Thông Tin Đơn Hàng</h3>\n                <div class=\"info-row\">\n                    <span class=\"info-label\">Mã đơn hàng:</span>\n                    <span class=\"info-value\">#13</span>\n                </div>\n                <div class=\"info-row\">\n                    <span class=\"info-label\">Ngày đặt:</span>\n                    <span class=\"info-value\">22/10/2025 12:49</span>\n                </div>\n                <div class=\"info-row\">\n                    <span class=\"info-label\">Bàn:</span>\n                    <span class=\"info-value\">Bàn 1</span>\n                </div>\n                <div class=\"info-row\">\n                    <span class=\"info-label\">Thanh toán:</span>\n                    <span class=\"info-value\">Tiền mặt</span>\n                </div>\n            </div>\n\n            <div class=\"info-box\">\n                <h3>Thông Tin Khách Hàng</h3>\n                <div class=\"info-row\">\n                    <span class=\"info-label\">Họ tên:</span>\n                    <span class=\"info-value\">FortNight</span>\n                </div>\n                <div class=\"info-row\">\n                    <span class=\"info-label\">Email:</span>\n                    <span class=\"info-value\">trananhhung12345@gmail.com</span>\n                </div>\n                <div class=\"info-row\">\n                    <span class=\"info-label\">Số điện thoại:</span>\n                    <span class=\"info-value\">0354942664</span>\n                </div>\n                <div class=\"info-row\">\n                    <span class=\"info-label\">Hạng thành viên:</span>\n                    <span class=\"info-value\">Bronze</span>\n                </div>\n            </div>\n        </div>\n\n        <table>\n            <thead>\n                <tr>\n                    <th style=\"width: 50px; text-align: center;\">STT</th>\n                    <th>Sản Phẩm</th>\n                    <th style=\"width: 80px; text-align: center;\">SL</th>\n                    <th style=\"width: 120px; text-align: right;\">Đơn Giá</th>\n                    <th style=\"width: 130px; text-align: right;\">Thành Tiền</th>\n                </tr>\n            </thead>\n            <tbody>\n                <tr>\n                <td style=\"text-align: center;\">1</td>\n                <td>Trà Oolong Khuyến Mãi</td>\n                <td style=\"text-align: center;\">1</td>\n                <td style=\"text-align: right;\">360.000 đ</td>\n                <td style=\"text-align: right; font-weight: bold;\">360.000 đ</td>\n            </tr><tr>\n                <td style=\"text-align: center;\">2</td>\n                <td>Combo Giảm 10%</td>\n                <td style=\"text-align: center;\">1</td>\n                <td style=\"text-align: right;\">900.000 đ</td>\n                <td style=\"text-align: right; font-weight: bold;\">900.000 đ</td>\n            </tr>\n            </tbody>\n        </table>\n\n        <table class=\"summary-table\">\n            <tr class=\"summary-row\">\n                <td style=\"text-align: right; width: 70%;\">Tạm tính:</td>\n                <td style=\"text-align: right; font-weight: bold;\">1.260.000 đ</td>\n            </tr>\n            <tr>\n                <td colspan=\"4\" style=\"text-align: right; padding: 8px; background: #fff3e0; color: #e65100; font-weight: 600;\">\n                    Giảm giá (Hạng Bronze - 2%):\n                </td>\n                <td style=\"text-align: right; padding: 8px; background: #fff3e0; color: #d84315; font-weight: bold;\">\n                    - 25.200 đ\n                </td>\n            </tr>\n            <tr class=\"summary-row\">\n                <td style=\"text-align: right;\">VAT (8%):</td>\n                <td style=\"text-align: right; font-weight: bold;\">100.800 đ</td>\n            </tr>\n            <tr class=\"total-row\">\n                <td style=\"text-align: right;\">TỔNG THANH TOÁN:</td>\n                <td style=\"text-align: right;\">1.335.600 đ</td>\n            </tr>\n        </table>\n\n        <div class=\"footer\">\n            <p><strong>Cảm ơn quý khách đã sử dụng dịch vụ!</strong></p>\n            <p>Hóa đơn này được tạo tự động bởi hệ thống.</p>\n            \n            <div class=\"signature\">\n                <div>\n                    <div class=\"signature-line\"></div>\n                    <strong>Khách hàng</strong>\n                </div>\n                <div>\n                    <div class=\"signature-line\"></div>\n                    <strong>Thu ngân</strong>\n                </div>\n            </div>\n        </div>\n    </div>\n</body>\n</html>', '2025-10-22 12:49:45'),
(3, 14, '<!DOCTYPE html>\r\n<html lang=\"vi\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Hóa Đơn #14</title>\r\n    <style>\r\n        * { margin: 0; padding: 0; box-sizing: border-box; }\r\n        body { \r\n            font-family: Arial, sans-serif; \r\n            line-height: 1.6; \r\n            color: #333;\r\n            background: #f5f5f5;\r\n            padding: 20px;\r\n        }\r\n        .invoice-container {\r\n            max-width: 800px;\r\n            margin: 0 auto;\r\n            background: white;\r\n            padding: 30px;\r\n            box-shadow: 0 0 10px rgba(0,0,0,0.1);\r\n        }\r\n        .header {\r\n            text-align: center;\r\n            border-bottom: 3px solid #8f2c24;\r\n            padding-bottom: 20px;\r\n            margin-bottom: 30px;\r\n        }\r\n        .header h1 {\r\n            color: #8f2c24;\r\n            font-size: 28px;\r\n            margin-bottom: 10px;\r\n            text-transform: uppercase;\r\n        }\r\n        .header .company-name {\r\n            font-size: 18px;\r\n            font-weight: bold;\r\n            color: #4d0702;\r\n            margin-bottom: 5px;\r\n        }\r\n        .header .company-info {\r\n            font-size: 12px;\r\n            color: #666;\r\n            line-height: 1.8;\r\n        }\r\n        .info-section {\r\n            display: grid;\r\n            grid-template-columns: 1fr 1fr;\r\n            gap: 20px;\r\n            margin-bottom: 30px;\r\n        }\r\n        .info-box {\r\n            background: #f9f9f9;\r\n            padding: 15px;\r\n            border-left: 4px solid #8f2c24;\r\n        }\r\n        .info-box h3 {\r\n            color: #8f2c24;\r\n            font-size: 14px;\r\n            margin-bottom: 10px;\r\n            text-transform: uppercase;\r\n        }\r\n        .info-row {\r\n            display: flex;\r\n            padding: 5px 0;\r\n            font-size: 13px;\r\n        }\r\n        .info-label {\r\n            font-weight: bold;\r\n            width: 120px;\r\n            color: #555;\r\n        }\r\n        .info-value {\r\n            flex: 1;\r\n            color: #333;\r\n        }\r\n        table {\r\n            width: 100%;\r\n            border-collapse: collapse;\r\n            margin: 20px 0;\r\n        }\r\n        th {\r\n            background: #8f2c24;\r\n            color: white;\r\n            padding: 12px 8px;\r\n            text-align: left;\r\n            font-size: 13px;\r\n            text-transform: uppercase;\r\n        }\r\n        td {\r\n            padding: 10px 8px;\r\n            border-bottom: 1px solid #ddd;\r\n            font-size: 13px;\r\n        }\r\n        tr:hover td {\r\n            background: #f9f9f9;\r\n        }\r\n        .summary-table {\r\n            margin-top: 30px;\r\n            border: none;\r\n        }\r\n        .summary-table td {\r\n            border: none;\r\n            padding: 8px;\r\n        }\r\n        .summary-row {\r\n            font-size: 14px;\r\n        }\r\n        .total-row {\r\n            background: #8f2c24;\r\n            color: white;\r\n            font-size: 18px;\r\n            font-weight: bold;\r\n        }\r\n        .total-row td {\r\n            padding: 15px 8px;\r\n        }\r\n        .footer {\r\n            margin-top: 40px;\r\n            padding-top: 20px;\r\n            border-top: 2px solid #ddd;\r\n            text-align: center;\r\n            font-size: 12px;\r\n            color: #666;\r\n        }\r\n        .footer .signature {\r\n            display: flex;\r\n            justify-content: space-around;\r\n            margin-top: 30px;\r\n        }\r\n        .signature div {\r\n            text-align: center;\r\n        }\r\n        .signature-line {\r\n            width: 200px;\r\n            border-top: 1px solid #333;\r\n            margin: 50px auto 10px;\r\n        }\r\n        .print-button {\r\n            position: fixed;\r\n            top: 20px;\r\n            right: 20px;\r\n            background: #8f2c24;\r\n            color: white;\r\n            border: none;\r\n            padding: 12px 24px;\r\n            border-radius: 5px;\r\n            cursor: pointer;\r\n            font-size: 14px;\r\n            font-weight: bold;\r\n            box-shadow: 0 2px 5px rgba(0,0,0,0.2);\r\n        }\r\n        .print-button:hover {\r\n            background: #6d1f18;\r\n        }\r\n        @media print {\r\n            body { \r\n                background: white; \r\n                padding: 0; \r\n            }\r\n            .invoice-container {\r\n                box-shadow: none;\r\n                padding: 0;\r\n            }\r\n            .print-button {\r\n                display: none;\r\n            }\r\n        }\r\n        @media (max-width: 600px) {\r\n            .info-section {\r\n                grid-template-columns: 1fr;\r\n            }\r\n            .invoice-container {\r\n                padding: 15px;\r\n            }\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n    <button class=\"print-button\" onclick=\"window.print()\">🖨️ In Hóa Đơn</button>\r\n    \r\n    <div class=\"invoice-container\">\r\n        <div class=\"header\">\r\n            <h1>Hóa Đơn Bán Hàng</h1>\r\n            <div class=\"company-name\">HƯƠNG TRÀ RESTAURANT</div>\r\n            <div class=\"company-info\">\r\n                Địa chỉ: 88 Phan Xích Long, P.7, Q.Phú Nhuận, TPHCM<br>\r\n                Điện thoại: 1800 8287 | Email: contact@huongtra.com\r\n            </div>\r\n        </div>\r\n\r\n        <div class=\"info-section\">\r\n            <div class=\"info-box\">\r\n                <h3>Thông Tin Đơn Hàng</h3>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Mã đơn hàng:</span>\r\n                    <span class=\"info-value\">#14</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Ngày đặt:</span>\r\n                    <span class=\"info-value\">22/10/2025 13:28</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Bàn:</span>\r\n                    <span class=\"info-value\">Bàn 1</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Thanh toán:</span>\r\n                    <span class=\"info-value\">Tiền mặt</span>\r\n                </div>\r\n            </div>\r\n\r\n            <div class=\"info-box\">\r\n                <h3>Thông Tin Khách Hàng</h3>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Họ tên:</span>\r\n                    <span class=\"info-value\">FortNight</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Email:</span>\r\n                    <span class=\"info-value\">trananhhung12345@gmail.com</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Số điện thoại:</span>\r\n                    <span class=\"info-value\">0354942664</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Hạng thành viên:</span>\r\n                    <span class=\"info-value\">Bronze</span>\r\n                </div>\r\n            </div>\r\n        </div>\r\n\r\n        <table>\r\n            <thead>\r\n                <tr>\r\n                    <th style=\"width: 50px; text-align: center;\">STT</th>\r\n                    <th>Sản Phẩm</th>\r\n                    <th style=\"width: 80px; text-align: center;\">SL</th>\r\n                    <th style=\"width: 120px; text-align: right;\">Đơn Giá</th>\r\n                    <th style=\"width: 130px; text-align: right;\">Thành Tiền</th>\r\n                </tr>\r\n            </thead>\r\n            <tbody>\r\n                <tr>\r\n                <td style=\"text-align: center;\">1</td>\r\n                <td>Bánh Ăn Kèm Cacao</td>\r\n                <td style=\"text-align: center;\">1</td>\r\n                <td style=\"text-align: right;\">110.000 đ</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">110.000 đ</td>\r\n            </tr>\r\n            </tbody>\r\n        </table>\r\n\r\n        <table class=\"summary-table\">\r\n            <tr class=\"summary-row\">\r\n                <td style=\"text-align: right; width: 70%;\">Tạm tính:</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">110.000 đ</td>\r\n            </tr>\r\n            <tr>\r\n                <td colspan=\"4\" style=\"text-align: right; padding: 8px; background: #fff3e0; color: #e65100; font-weight: 600;\">\r\n                    Giảm giá (Hạng Bronze - 2%):\r\n                </td>\r\n                <td style=\"text-align: right; padding: 8px; background: #fff3e0; color: #d84315; font-weight: bold;\">\r\n                    - 2.200 đ\r\n                </td>\r\n            </tr>\r\n            <tr class=\"summary-row\">\r\n                <td style=\"text-align: right;\">VAT (8%):</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">8.800 đ</td>\r\n            </tr>\r\n            <tr class=\"total-row\">\r\n                <td style=\"text-align: right;\">TỔNG THANH TOÁN:</td>\r\n                <td style=\"text-align: right;\">116.600 đ</td>\r\n            </tr>\r\n        </table>\r\n\r\n        <div class=\"footer\">\r\n            <p><strong>Cảm ơn quý khách đã sử dụng dịch vụ!</strong></p>\r\n            <p>Hóa đơn này được tạo tự động bởi hệ thống.</p>\r\n            \r\n            <div class=\"signature\">\r\n                <div>\r\n                    <div class=\"signature-line\"></div>\r\n                    <strong>Khách hàng</strong>\r\n                </div>\r\n                <div>\r\n                    <div class=\"signature-line\"></div>\r\n                    <strong>Thu ngân</strong>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n</body>\r\n</html>', '2025-10-22 13:28:33'),
(4, 15, '<!DOCTYPE html>\r\n<html lang=\"vi\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Hóa Đơn #15</title>\r\n    <style>\r\n        * { margin: 0; padding: 0; box-sizing: border-box; }\r\n        body { \r\n            font-family: Arial, sans-serif; \r\n            line-height: 1.6; \r\n            color: #333;\r\n            background: #f5f5f5;\r\n            padding: 20px;\r\n        }\r\n        .invoice-container {\r\n            max-width: 800px;\r\n            margin: 0 auto;\r\n            background: white;\r\n            padding: 30px;\r\n            box-shadow: 0 0 10px rgba(0,0,0,0.1);\r\n        }\r\n        .header {\r\n            text-align: center;\r\n            border-bottom: 3px solid #8f2c24;\r\n            padding-bottom: 20px;\r\n            margin-bottom: 30px;\r\n        }\r\n        .header h1 {\r\n            color: #8f2c24;\r\n            font-size: 28px;\r\n            margin-bottom: 10px;\r\n            text-transform: uppercase;\r\n        }\r\n        .header .company-name {\r\n            font-size: 18px;\r\n            font-weight: bold;\r\n            color: #4d0702;\r\n            margin-bottom: 5px;\r\n        }\r\n        .header .company-info {\r\n            font-size: 12px;\r\n            color: #666;\r\n            line-height: 1.8;\r\n        }\r\n        .info-section {\r\n            display: grid;\r\n            grid-template-columns: 1fr 1fr;\r\n            gap: 20px;\r\n            margin-bottom: 30px;\r\n        }\r\n        .info-box {\r\n            background: #f9f9f9;\r\n            padding: 15px;\r\n            border-left: 4px solid #8f2c24;\r\n        }\r\n        .info-box h3 {\r\n            color: #8f2c24;\r\n            font-size: 14px;\r\n            margin-bottom: 10px;\r\n            text-transform: uppercase;\r\n        }\r\n        .info-row {\r\n            display: flex;\r\n            padding: 5px 0;\r\n            font-size: 13px;\r\n        }\r\n        .info-label {\r\n            font-weight: bold;\r\n            width: 120px;\r\n            color: #555;\r\n        }\r\n        .info-value {\r\n            flex: 1;\r\n            color: #333;\r\n        }\r\n        table {\r\n            width: 100%;\r\n            border-collapse: collapse;\r\n            margin: 20px 0;\r\n        }\r\n        th {\r\n            background: #8f2c24;\r\n            color: white;\r\n            padding: 12px 8px;\r\n            text-align: left;\r\n            font-size: 13px;\r\n            text-transform: uppercase;\r\n        }\r\n        td {\r\n            padding: 10px 8px;\r\n            border-bottom: 1px solid #ddd;\r\n            font-size: 13px;\r\n        }\r\n        tr:hover td {\r\n            background: #f9f9f9;\r\n        }\r\n        .summary-table {\r\n            margin-top: 30px;\r\n            border: none;\r\n        }\r\n        .summary-table td {\r\n            border: none;\r\n            padding: 8px;\r\n        }\r\n        .summary-row {\r\n            font-size: 14px;\r\n        }\r\n        .total-row {\r\n            background: #8f2c24;\r\n            color: white;\r\n            font-size: 18px;\r\n            font-weight: bold;\r\n        }\r\n        .total-row td {\r\n            padding: 15px 8px;\r\n        }\r\n        .footer {\r\n            margin-top: 40px;\r\n            padding-top: 20px;\r\n            border-top: 2px solid #ddd;\r\n            text-align: center;\r\n            font-size: 12px;\r\n            color: #666;\r\n        }\r\n        .footer .signature {\r\n            display: flex;\r\n            justify-content: space-around;\r\n            margin-top: 30px;\r\n        }\r\n        .signature div {\r\n            text-align: center;\r\n        }\r\n        .signature-line {\r\n            width: 200px;\r\n            border-top: 1px solid #333;\r\n            margin: 50px auto 10px;\r\n        }\r\n        .print-button {\r\n            position: fixed;\r\n            top: 20px;\r\n            right: 20px;\r\n            background: #8f2c24;\r\n            color: white;\r\n            border: none;\r\n            padding: 12px 24px;\r\n            border-radius: 5px;\r\n            cursor: pointer;\r\n            font-size: 14px;\r\n            font-weight: bold;\r\n            box-shadow: 0 2px 5px rgba(0,0,0,0.2);\r\n        }\r\n        .print-button:hover {\r\n            background: #6d1f18;\r\n        }\r\n        @media print {\r\n            body { \r\n                background: white; \r\n                padding: 0; \r\n            }\r\n            .invoice-container {\r\n                box-shadow: none;\r\n                padding: 0;\r\n            }\r\n            .print-button {\r\n                display: none;\r\n            }\r\n        }\r\n        @media (max-width: 600px) {\r\n            .info-section {\r\n                grid-template-columns: 1fr;\r\n            }\r\n            .invoice-container {\r\n                padding: 15px;\r\n            }\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n    <button class=\"print-button\" onclick=\"window.print()\">🖨️ In Hóa Đơn</button>\r\n    \r\n    <div class=\"invoice-container\">\r\n        <div class=\"header\">\r\n            <h1>Hóa Đơn Bán Hàng</h1>\r\n            <div class=\"company-name\">HƯƠNG TRÀ RESTAURANT</div>\r\n            <div class=\"company-info\">\r\n                Địa chỉ: 88 Phan Xích Long, P.7, Q.Phú Nhuận, TPHCM<br>\r\n                Điện thoại: 1800 8287 | Email: contact@huongtra.com\r\n            </div>\r\n        </div>\r\n\r\n        <div class=\"info-section\">\r\n            <div class=\"info-box\">\r\n                <h3>Thông Tin Đơn Hàng</h3>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Mã đơn hàng:</span>\r\n                    <span class=\"info-value\">#15</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Ngày đặt:</span>\r\n                    <span class=\"info-value\">22/10/2025 13:39</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Bàn:</span>\r\n                    <span class=\"info-value\">Bàn 1</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Thanh toán:</span>\r\n                    <span class=\"info-value\">Tiền mặt</span>\r\n                </div>\r\n            </div>\r\n\r\n            <div class=\"info-box\">\r\n                <h3>Thông Tin Khách Hàng</h3>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Họ tên:</span>\r\n                    <span class=\"info-value\">FortNight</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Email:</span>\r\n                    <span class=\"info-value\">trananhhung12345@gmail.com</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Số điện thoại:</span>\r\n                    <span class=\"info-value\">0354942664</span>\r\n                </div>\r\n                <div class=\"info-row\">\r\n                    <span class=\"info-label\">Hạng thành viên:</span>\r\n                    <span class=\"info-value\">Bronze</span>\r\n                </div>\r\n            </div>\r\n        </div>\r\n\r\n        <table>\r\n            <thead>\r\n                <tr>\r\n                    <th style=\"width: 50px; text-align: center;\">STT</th>\r\n                    <th>Sản Phẩm</th>\r\n                    <th style=\"width: 80px; text-align: center;\">SL</th>\r\n                    <th style=\"width: 120px; text-align: right;\">Đơn Giá</th>\r\n                    <th style=\"width: 130px; text-align: right;\">Thành Tiền</th>\r\n                </tr>\r\n            </thead>\r\n            <tbody>\r\n                <tr>\r\n                <td style=\"text-align: center;\">1</td>\r\n                <td>Bánh Ăn Kèm Cacao</td>\r\n                <td style=\"text-align: center;\">1</td>\r\n                <td style=\"text-align: right;\">110.000 đ</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">110.000 đ</td>\r\n            </tr>\r\n            </tbody>\r\n        </table>\r\n\r\n        <table class=\"summary-table\">\r\n            <tr class=\"summary-row\">\r\n                <td style=\"text-align: right; width: 70%;\">Tạm tính:</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">110.000 đ</td>\r\n            </tr>\r\n            <tr class=\"summary-row discount-row\">\r\n        <td style=\"text-align: right;\">Giảm giá (Hạng Bronze - 2%):</td>\r\n        <td style=\"text-align: right;\">- 2.200 đ</td>\r\n    </tr>\r\n            <tr class=\"summary-row\">\r\n                <td style=\"text-align: right;\">VAT (8%):</td>\r\n                <td style=\"text-align: right; font-weight: bold;\">8.800 đ</td>\r\n            </tr>\r\n            <tr class=\"total-row\">\r\n                <td style=\"text-align: right;\">TỔNG THANH TOÁN:</td>\r\n                <td style=\"text-align: right;\">116.600 đ</td>\r\n            </tr>\r\n        </table>\r\n\r\n        <div class=\"footer\">\r\n            <p><strong>Cảm ơn quý khách đã sử dụng dịch vụ!</strong></p>\r\n            <p>Hóa đơn này được tạo tự động bởi hệ thống.</p>\r\n            \r\n            <div class=\"signature\">\r\n                <div>\r\n                    <div class=\"signature-line\"></div>\r\n                    <strong>Khách hàng</strong>\r\n                </div>\r\n                <div>\r\n                    <div class=\"signature-line\"></div>\r\n                    <strong>Thu ngân</strong>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n</body>\r\n</html>', '2025-10-22 13:39:21');

-- --------------------------------------------------------

--
-- Table structure for table `khuyenmai`
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
-- Dumping data for table `khuyenmai`
--

INSERT INTO `khuyenmai` (`MaKhuyenMai`, `TenKhuyenMai`, `PhanTramGiam`, `NgayBatDau`, `NgayKetThuc`, `TrangThai`) VALUES
(1, 'Khuyến Mãi Trung Thu', 20.00, '2025-09-01 00:00:00', '2025-10-30 23:59:59', 1),
(2, 'Khuyến Mãi Trà Mới', 15.00, '2025-10-01 00:00:00', '2025-11-30 23:59:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `lichsutrangthaidonhang`
--

CREATE TABLE `lichsutrangthaidonhang` (
  `MaLichSu` bigint(20) UNSIGNED NOT NULL,
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `TrangThai` enum('PLACED','CONFIRMED','SHIPPING','DONE','CANCELLED') NOT NULL,
  `NgayCapNhat` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lichsutrangthaidonhang`
--

INSERT INTO `lichsutrangthaidonhang` (`MaLichSu`, `MaDonHang`, `TrangThai`, `NgayCapNhat`) VALUES
(1, 1, 'PLACED', '2025-10-22 06:59:35'),
(2, 2, 'PLACED', '2025-10-22 07:01:21');

-- --------------------------------------------------------

--
-- Table structure for table `nguoidung`
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
  `Hang` enum('Bronze','Silver','Gold','Mới') NOT NULL DEFAULT 'Mới',
  `TongChiTieu` decimal(14,2) NOT NULL DEFAULT 0.00,
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nguoidung`
--

INSERT INTO `nguoidung` (`MaNguoiDung`, `Username`, `HoTen`, `Email`, `MatKhau`, `SoDienThoai`, `DiaChi`, `VaiTro`, `TrangThai`, `Hang`, `TongChiTieu`, `NgayTao`) VALUES
(3, 'trananhhung12345', 'FortNight', 'trananhhung12345@gmail.com', '$2y$10$woc0DcX7CTNkeQnf4ywo3u1gnBeA3gYsu44m48K9OKm81x/7OdN.C', '0354942664', '42 Nguyễn Văn Của P13 Q8', 'USER', 1, 'Bronze', 365700.00, '2025-09-22 08:50:11'),
(4, 'nguyenvana', 'Nguyễn Văn A', 'a+seed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0909123456', 'TP. Hồ Chí Minh', 'USER', 1, 'Mới', 0.00, '2025-10-09 21:11:08'),
(5, 'lethib', 'Lê Thị B', 'b+seed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0912987654', 'Hà Nội', 'USER', 1, 'Mới', 0.00, '2025-10-09 21:11:08'),
(7, 'admin', 'Quản trị', 'admin@example.com', '654321', '0354942664', '', 'ADMIN', 1, '', 0.00, '2025-10-10 21:12:56'),
(8, 'trananhtoan2506', 'Trần Anh Toàn', 'trananhtoan2506@gmail.com', '$2y$10$F26U.fu42CJAt9aCLHI4DuoXj0rieqQE37uPLOPZ54Axu0Jdime0W', NULL, NULL, 'USER', 1, 'Mới', 0.00, '2025-10-22 11:58:21');

-- --------------------------------------------------------

--
-- Table structure for table `sanpham`
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
-- Dumping data for table `sanpham`
--

INSERT INTO `sanpham` (`MaSanPham`, `TenSanPham`, `MoTa`, `Gia`, `GiaCu`, `HinhAnh`, `SoLuongTon`, `TrangThai`, `NgayTao`, `IsPromo`, `Loai`, `Popularity`, `NewProduct`) VALUES
(1, 'Lục Trà Lài Thượng Hạng', '', 250000.00, NULL, '/Webmarket/image/sp5.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Lục Trà', 150, 0),
(2, 'Hồng Trà Cổ Thụ', 'Trà đen cổ thụ', 382500.00, 450000.00, '/Webmarket/image/sp17.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Hồng Trà', 210, 1),
(3, 'Trà Sen Tây Hồ', 'Trà sen cao cấp khuyến mãi', 240000.00, 320000.00, '/Webmarket/image/sp56.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 400, 1),
(6, 'Phổ Nhĩ Thụ Tuổi', 'Trà Phổ Nhĩ lâu năm', 950000.00, NULL, '/Webmarket/image/sp30.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 50, 0),
(7, 'Trà Xanh Thái Nguyên', 'Trà xanh từ Thái Nguyên', 210000.00, 300000.00, '/Webmarket/image/sp8.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Lục Trà', 350, 1),
(8, 'Bánh Trung Thu Trứng Muối', 'Bánh nướng nhân trứng muối', 590000.00, NULL, '/Webmarket/image/sp35.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 450, 0),
(10, 'Bánh Trung Thu Trà Xanh', 'Bánh nướng nhân trà xanh', 185000.00, NULL, '/Webmarket/image/sp36.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 110, 1),
(11, 'Bánh Thập Cẩm Cao Cấp', 'Bánh thập cẩm khuyến mãi', 552500.00, 650000.00, '/Webmarket/image/sp57.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 320, 0),
(14, 'Bánh Thỏ Ngọc (Bánh Dẻo)', 'Bánh dẻo truyền thống', 160000.00, NULL, '/Webmarket/image/sp41.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Dẻo', 90, 0),
(15, 'Combo Trăng Vàng', 'Combo trà và bánh cao cấp', 1200000.00, NULL, '/Webmarket/image/sp48.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 500, 1),
(16, 'Combo Hội Ngộ', 'Combo trà và bánh', 850000.00, NULL, '/Webmarket/image/sp49.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 300, 0),
(17, 'Combo Thưởng Nguyệt', 'Combo trà và bánh', 550000.00, NULL, '/Webmarket/image/sp50.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 100, 0),
(22, 'Hồng Trà Thượng Hạng', 'Trà đen cao cấp', 400000.00, NULL, '/Webmarket/image/sp18.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 180, 0),
(24, 'Bạch Trà Cao Cấp', 'Trà trắng tinh tế', 620000.00, NULL, '/Webmarket/image/sp23.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bạch Trà', 70, 0),
(29, 'Bánh Dẻo Sầu Riêng', 'Bánh dẻo nhân sầu riêng', 170000.00, NULL, '/Webmarket/image/sp42.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Dẻo', 100, 0),
(31, 'Combo Trà Lài và Bánh Dẻo', 'Combo trà lài và bánh dẻo', 450000.00, NULL, '/Webmarket/image/sp51.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 200, 0),
(34, 'Combo Cao Cấp', 'Combo trà và bánh cao cấp', 1000000.00, NULL, '/Webmarket/image/sp52.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 250, 0),
(35, 'Combo Mua 3 Tặng 1 Trà Xanh', 'Combo khuyến mãi trà xanh', 630000.00, 900000.00, '/Webmarket/image/sp64.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 250, 0),
(36, 'Bánh Nướng Giảm 20%', 'Bánh nướng khuyến mãi', 472000.00, 590000.00, '/Webmarket/image/sp37.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 380, 1),
(37, 'Lục Trà Mộc Châu', 'Trà xanh từ Mộc Châu', 230000.00, NULL, '/Webmarket/image/sp14.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Lục Trà', 160, 0),
(39, 'Lục Trà Ốc Đỉnh', 'Trà xanh cao cấp', 300000.00, NULL, '/Webmarket/image/sp15.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Lục Trà', 110, 0),
(43, 'Hồng Trà Bảo Lộc', 'Trà đen từ Bảo Lộc', 370000.00, NULL, '/Webmarket/image/sp19.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 150, 0),
(44, 'Hồng Trà Kim Cương', 'Trà đen cao cấp', 390000.00, NULL, '/Webmarket/image/sp20.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 130, 0),
(45, 'Hồng Trà Phú Sĩ', 'Trà đen đặc biệt', 410000.00, NULL, '/Webmarket/image/sp21.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 120, 0),
(46, 'Hồng Trà Thảo Nguyên', 'Trà đen thảo nguyên', 360000.00, NULL, '/Webmarket/image/sp22.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Hồng Trà', 110, 0),
(51, 'Bạch Trà Thượng Uyển', 'Trà trắng cao cấp', 640000.00, NULL, '/Webmarket/image/sp24.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bạch Trà', 65, 0),
(52, 'Bạch Trà Long Tỉnh', 'Trà trắng Long Tỉnh', 620000.00, NULL, '/Webmarket/image/sp25.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bạch Trà', 70, 0),
(53, 'Oolong Trà Đá', 'Trà Ô Long đặc trưng', 460000.00, NULL, '/Webmarket/image/sp26.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Oolong Trà', 180, 0),
(54, 'Oolong Trà Thượng Hạng', 'Trà Ô Long cao cấp', 480000.00, NULL, '/Webmarket/image/sp27.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Oolong Trà', 170, 0),
(57, 'Oolong Trà Đài Loan', 'Trà Ô Long từ Đài Loan', 490000.00, NULL, '/Webmarket/image/sp28.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Oolong Trà', 160, 0),
(58, 'Oolong Trà Hoàng Gia', 'Trà Ô Long hoàng gia', 500000.00, NULL, '/Webmarket/image/sp29.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Oolong Trà', 150, 0),
(59, 'Phổ Nhĩ Cổ Thụ', 'Trà Phổ Nhĩ cổ thụ', 970000.00, NULL, '/Webmarket/image/sp2.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 55, 0),
(63, 'Phổ Nhĩ Nguyên Chất', 'Trà Phổ Nhĩ nguyên chất', 940000.00, NULL, '/Webmarket/image/sp33.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 70, 0),
(64, 'Phổ Nhĩ Hương Thơm', 'Trà Phổ Nhĩ thơm ngon', 950000.00, NULL, '/Webmarket/image/sp34.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Phổ Nhĩ', 55, 0),
(65, 'Bánh Nướng Khoai Môn', 'Bánh nướng nhân khoai môn', 180000.00, NULL, '/Webmarket/image/sp37.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 120, 0),
(66, 'Bánh Nướng Hạt Dẻ', 'Bánh nướng nhân hạt dẻ', 190000.00, NULL, '/Webmarket/image/sp38.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 110, 0),
(67, 'Bánh Nướng Cacao', 'Bánh nướng nhân cacao', 170000.00, NULL, '/Webmarket/image/sp39.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 100, 0),
(68, 'Bánh Nướng Đậu Đỏ', 'Bánh nướng nhân đậu đỏ', 160000.00, NULL, '/Webmarket/image/sp40.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Nướng', 90, 0),
(69, 'Bánh Dẻo Đậu Xanh', 'Bánh dẻo nhân đậu xanh', 130000.00, NULL, '/Webmarket/image/sp43.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Dẻo', 110, 0),
(70, 'Bánh Dẻo Hạt Sen', 'Bánh dẻo nhân hạt sen', 140000.00, NULL, '/Webmarket/image/sp44.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Dẻo', 120, 0),
(78, 'Bánh Ăn Kèm Trà Xanh', 'Bánh ăn kèm trà xanh', 105000.00, NULL, '/Webmarket/image/sp45.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Ăn Kèm', 70, 0),
(79, 'Bánh Ăn Kèm Hạt Dẻ', 'Bánh ăn kèm hạt dẻ', 120000.00, NULL, '/Webmarket/image/sp46.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Ăn Kèm', 80, 0),
(80, 'Bánh Ăn Kèm Cacao', 'Bánh ăn kèm cacao', 110000.00, NULL, '/Webmarket/image/sp47.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Bánh Ăn Kèm', 60, 0),
(81, 'Combo Trà Đen và Bánh Dẻo', 'Combo trà đen và bánh dẻo', 480000.00, NULL, '/Webmarket/image/sp53.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 190, 0),
(82, 'Combo Oolong và Bánh Nướng', '', 520000.00, 600000.00, '/Webmarket/image/sp54.jpg', 100, 1, '2025-10-14 00:00:00', 0, 'Combo', 210, 0),
(83, 'Trà Oolong Khuyến Mãi', 'Trà oolong khuyến mãi', 360000.00, 450000.00, '/Webmarket/image/sp28.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 200, 0),
(84, 'Bánh Dẻo Giảm 15%', 'Bánh dẻo khuyến mãi', 127500.00, 150000.00, '/Webmarket/image/sp43.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 220, 0),
(85, 'Combo Giảm 10%', '', 900000.00, 1000000.00, '/Webmarket/image/sp53.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 230, 0),
(86, 'Hồng Trà Cổ Thụ (KM)', '', 383000.00, 450000.00, '/Webmarket/image/sp55.jpg', 100, 1, '2025-10-14 00:00:00', 1, 'Khuyến Mãi', 210, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sanpham_danhmuc`
--

CREATE TABLE `sanpham_danhmuc` (
  `MaSanPham` bigint(20) UNSIGNED NOT NULL,
  `MaDanhMuc` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sanpham_danhmuc`
--

INSERT INTO `sanpham_danhmuc` (`MaSanPham`, `MaDanhMuc`) VALUES
(1, 5),
(2, 1),
(2, 6),
(3, 4),
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
(29, 2),
(29, 11),
(31, 3),
(31, 13),
(34, 3),
(34, 13),
(35, 4),
(36, 4),
(37, 1),
(37, 5),
(39, 1),
(39, 5),
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
(83, 4),
(84, 4),
(85, 4),
(86, 12);

-- --------------------------------------------------------

--
-- Table structure for table `sanpham_khuyenmai`
--

CREATE TABLE `sanpham_khuyenmai` (
  `MaKhuyenMai` bigint(20) UNSIGNED NOT NULL,
  `MaSanPham` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sanpham_khuyenmai`
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
-- Table structure for table `thanhtoan`
--

CREATE TABLE `thanhtoan` (
  `MaThanhToan` bigint(20) UNSIGNED NOT NULL,
  `MaDonHang` bigint(20) UNSIGNED NOT NULL,
  `PhuongThuc` enum('CASH','CARD','BANKING','EWALLET') NOT NULL,
  `SoTien` decimal(14,2) NOT NULL,
  `NgayThanhToan` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thanhtoan`
--

INSERT INTO `thanhtoan` (`MaThanhToan`, `MaDonHang`, `PhuongThuc`, `SoTien`, `NgayThanhToan`) VALUES
(1, 3, 'CASH', 111300.00, '2025-10-22 10:53:55'),
(2, 4, 'CASH', 127200.00, '2025-10-22 10:54:36'),
(3, 5, 'CASH', 111300.00, '2025-10-22 10:55:05'),
(4, 6, 'CASH', 111300.00, '2025-10-22 10:55:58'),
(5, 7, 'CASH', 111300.00, '2025-10-22 10:56:28'),
(6, 8, 'CASH', 127200.00, '2025-10-22 11:04:45'),
(7, 9, 'CASH', 127200.00, '2025-10-22 11:25:48'),
(8, 10, 'CASH', 111300.00, '2025-10-22 11:28:19'),
(9, 11, 'CASH', 3042200.00, '2025-10-22 12:11:31'),
(10, 12, 'CASH', 381600.00, '2025-10-22 12:11:49'),
(11, 13, 'CASH', 1335600.00, '2025-10-22 12:49:32'),
(12, 14, 'CASH', 116600.00, '2025-10-22 13:28:33'),
(13, 15, 'CASH', 116600.00, '2025-10-22 13:39:21');

-- --------------------------------------------------------

--
-- Table structure for table `thongbaoemail`
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
-- Table structure for table `voucher`
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

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_user_spending`
-- (See below for the actual view)
--
CREATE TABLE `v_user_spending` (
`MaNguoiDung` bigint(20) unsigned
,`TongChiTieu` decimal(36,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_user_spending`
--
DROP TABLE IF EXISTS `v_user_spending`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_spending`  AS SELECT `u`.`MaNguoiDung` AS `MaNguoiDung`, coalesce(sum(case when `d`.`TrangThai` <> 'CANCELLED' then `tt`.`SoTien` else 0 end),0) AS `TongChiTieu` FROM ((`nguoidung` `u` left join `donhang` `d` on(`d`.`MaNguoiDung` = `u`.`MaNguoiDung`)) left join `thanhtoan` `tt` on(`tt`.`MaDonHang` = `d`.`MaDonHang`)) GROUP BY `u`.`MaNguoiDung` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bantrongquan`
--
ALTER TABLE `bantrongquan`
  ADD PRIMARY KEY (`MaBan`);

--
-- Indexes for table `cauhinh_hang`
--
ALTER TABLE `cauhinh_hang`
  ADD PRIMARY KEY (`TenHang`);

--
-- Indexes for table `chamsockhachhang`
--
ALTER TABLE `chamsockhachhang`
  ADD PRIMARY KEY (`MaYeuCau`),
  ADD KEY `idx_cskh_user` (`MaNguoiDung`);

--
-- Indexes for table `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD PRIMARY KEY (`MaChiTietDonHang`),
  ADD UNIQUE KEY `uq_dh_sp` (`MaDonHang`,`MaSanPham`),
  ADD KEY `idx_ctdh_sp` (`MaSanPham`);

--
-- Indexes for table `chitietgiohang`
--
ALTER TABLE `chitietgiohang`
  ADD PRIMARY KEY (`MaChiTietGioHang`),
  ADD UNIQUE KEY `uq_cart_item` (`MaGioHang`,`MaSanPham`),
  ADD KEY `idx_ctgh_sp` (`MaSanPham`);

--
-- Indexes for table `danhgiasanpham`
--
ALTER TABLE `danhgiasanpham`
  ADD PRIMARY KEY (`MaDanhGia`),
  ADD KEY `idx_dg_sp` (`MaSanPham`),
  ADD KEY `idx_dg_user` (`MaNguoiDung`);

--
-- Indexes for table `danhmucsanpham`
--
ALTER TABLE `danhmucsanpham`
  ADD PRIMARY KEY (`MaDanhMuc`);

--
-- Indexes for table `donhang`
--
ALTER TABLE `donhang`
  ADD PRIMARY KEY (`MaDonHang`),
  ADD KEY `idx_dh_user` (`MaNguoiDung`),
  ADD KEY `idx_dh_gh` (`MaGioHang`),
  ADD KEY `fk_dh_ban` (`MaBan`);

--
-- Indexes for table `donhang_voucher`
--
ALTER TABLE `donhang_voucher`
  ADD PRIMARY KEY (`MaDonHang`,`MaVoucher`),
  ADD KEY `idx_dhvc_vc` (`MaVoucher`);

--
-- Indexes for table `giohang`
--
ALTER TABLE `giohang`
  ADD PRIMARY KEY (`MaGioHang`),
  ADD KEY `idx_giohang_user` (`MaNguoiDung`);

--
-- Indexes for table `hoadon`
--
ALTER TABLE `hoadon`
  ADD PRIMARY KEY (`MaHoaDon`),
  ADD KEY `idx_madonhang` (`MaDonHang`);

--
-- Indexes for table `khuyenmai`
--
ALTER TABLE `khuyenmai`
  ADD PRIMARY KEY (`MaKhuyenMai`);

--
-- Indexes for table `lichsutrangthaidonhang`
--
ALTER TABLE `lichsutrangthaidonhang`
  ADD PRIMARY KEY (`MaLichSu`),
  ADD KEY `idx_ls_dh` (`MaDonHang`);

--
-- Indexes for table `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`MaNguoiDung`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`MaSanPham`);

--
-- Indexes for table `sanpham_danhmuc`
--
ALTER TABLE `sanpham_danhmuc`
  ADD PRIMARY KEY (`MaSanPham`,`MaDanhMuc`),
  ADD KEY `idx_spdm_dm` (`MaDanhMuc`);

--
-- Indexes for table `sanpham_khuyenmai`
--
ALTER TABLE `sanpham_khuyenmai`
  ADD PRIMARY KEY (`MaKhuyenMai`,`MaSanPham`),
  ADD KEY `idx_spkm_sp` (`MaSanPham`);

--
-- Indexes for table `thanhtoan`
--
ALTER TABLE `thanhtoan`
  ADD PRIMARY KEY (`MaThanhToan`),
  ADD KEY `idx_tt_dh` (`MaDonHang`);

--
-- Indexes for table `thongbaoemail`
--
ALTER TABLE `thongbaoemail`
  ADD PRIMARY KEY (`MaEmail`),
  ADD KEY `fk_email_user` (`MaNguoiDung`);

--
-- Indexes for table `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`MaVoucher`),
  ADD UNIQUE KEY `MaCode` (`MaCode`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bantrongquan`
--
ALTER TABLE `bantrongquan`
  MODIFY `MaBan` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `chamsockhachhang`
--
ALTER TABLE `chamsockhachhang`
  MODIFY `MaYeuCau` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  MODIFY `MaChiTietDonHang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `chitietgiohang`
--
ALTER TABLE `chitietgiohang`
  MODIFY `MaChiTietGioHang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danhgiasanpham`
--
ALTER TABLE `danhgiasanpham`
  MODIFY `MaDanhGia` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danhmucsanpham`
--
ALTER TABLE `danhmucsanpham`
  MODIFY `MaDanhMuc` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `donhang`
--
ALTER TABLE `donhang`
  MODIFY `MaDonHang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `giohang`
--
ALTER TABLE `giohang`
  MODIFY `MaGioHang` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hoadon`
--
ALTER TABLE `hoadon`
  MODIFY `MaHoaDon` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `khuyenmai`
--
ALTER TABLE `khuyenmai`
  MODIFY `MaKhuyenMai` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lichsutrangthaidonhang`
--
ALTER TABLE `lichsutrangthaidonhang`
  MODIFY `MaLichSu` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `nguoidung`
--
ALTER TABLE `nguoidung`
  MODIFY `MaNguoiDung` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `MaSanPham` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `thanhtoan`
--
ALTER TABLE `thanhtoan`
  MODIFY `MaThanhToan` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `thongbaoemail`
--
ALTER TABLE `thongbaoemail`
  MODIFY `MaEmail` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voucher`
--
ALTER TABLE `voucher`
  MODIFY `MaVoucher` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chamsockhachhang`
--
ALTER TABLE `chamsockhachhang`
  ADD CONSTRAINT `fk_cskh_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD CONSTRAINT `fk_ctdh_dh` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctdh_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON UPDATE CASCADE;

--
-- Constraints for table `chitietgiohang`
--
ALTER TABLE `chitietgiohang`
  ADD CONSTRAINT `fk_ctgh_gh` FOREIGN KEY (`MaGioHang`) REFERENCES `giohang` (`MaGioHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctgh_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON UPDATE CASCADE;

--
-- Constraints for table `danhgiasanpham`
--
ALTER TABLE `danhgiasanpham`
  ADD CONSTRAINT `fk_dg_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dg_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `fk_dh_ban` FOREIGN KEY (`MaBan`) REFERENCES `bantrongquan` (`MaBan`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dh_gh` FOREIGN KEY (`MaGioHang`) REFERENCES `giohang` (`MaGioHang`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dh_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON UPDATE CASCADE;

--
-- Constraints for table `donhang_voucher`
--
ALTER TABLE `donhang_voucher`
  ADD CONSTRAINT `fk_dhvc_dh` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dhvc_vc` FOREIGN KEY (`MaVoucher`) REFERENCES `voucher` (`MaVoucher`) ON UPDATE CASCADE;

--
-- Constraints for table `giohang`
--
ALTER TABLE `giohang`
  ADD CONSTRAINT `fk_giohang_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hoadon`
--
ALTER TABLE `hoadon`
  ADD CONSTRAINT `hoadon_ibfk_1` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lichsutrangthaidonhang`
--
ALTER TABLE `lichsutrangthaidonhang`
  ADD CONSTRAINT `fk_ls_dh` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sanpham_danhmuc`
--
ALTER TABLE `sanpham_danhmuc`
  ADD CONSTRAINT `fk_spdm_dm` FOREIGN KEY (`MaDanhMuc`) REFERENCES `danhmucsanpham` (`MaDanhMuc`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_spdm_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sanpham_khuyenmai`
--
ALTER TABLE `sanpham_khuyenmai`
  ADD CONSTRAINT `fk_spkm_km` FOREIGN KEY (`MaKhuyenMai`) REFERENCES `khuyenmai` (`MaKhuyenMai`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_spkm_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `sanpham` (`MaSanPham`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `thanhtoan`
--
ALTER TABLE `thanhtoan`
  ADD CONSTRAINT `fk_tt_dh` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `thongbaoemail`
--
ALTER TABLE `thongbaoemail`
  ADD CONSTRAINT `fk_email_user` FOREIGN KEY (`MaNguoiDung`) REFERENCES `nguoidung` (`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
