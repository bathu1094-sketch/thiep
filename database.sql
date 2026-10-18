-- ==============================================================
-- DATABASE SQL CHO HỆ THỐNG THIỆP CHÚC MỪNG HOÀNG GIA & ADMIN PANEL
-- Hỗ trợ MySQL 5.7+ / MariaDB (Tương thích 100% InfinityFree phpMyAdmin)
-- ==============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. BẢNG CÀI ĐẶT (SETTINGS)
-- Lưu trữ cấu hình 2 cánh cửa, mã PIN, avatar modal PIN, nhạc nền, tiêu đề thiệp
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG DANH SÁCH LỜI CHÚC (WISHES)
-- Lưu trữ tất cả lời chúc, người nhận, ảnh đại diện lời chúc và thứ tự hiển thị
CREATE TABLE IF NOT EXISTS `wishes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `recipient` VARCHAR(255) NOT NULL,
  `msg` TEXT NOT NULL,
  `img` TEXT NOT NULL,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BẢNG HỘP THƯ LỜI NHẮN (LETTERS)
-- Lưu trữ các lá thư / lời nhắn yêu thương người xem gửi về trang admin
CREATE TABLE IF NOT EXISTS `letters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_name` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_read` TINYINT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG THIỆP TÙY CHỈNH THEO LINK RIÊNG (CUSTOM_CARDS)
-- Cho phép tạo không giới hạn các link thiệp độc lập: tenmien.com/?card=tenthiep hoặc tenmien.com/tenthiep
CREATE TABLE IF NOT EXISTS `custom_cards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `settings_json` MEDIUMTEXT NOT NULL,
  `wishes_json` MEDIUMTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_card_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- DỮ LIỆU MẪU BAN ĐẦU CHO BẢNG SETTINGS
-- ==============================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('door_left_image', 'https://cdn.phototourl.com/member/2026-09-26-82093f1d-b873-458c-9297-6e06ee4ff636.png', 'Ảnh nền cánh cửa bên trái'),
('door_right_image', 'https://cdn.phototourl.com/member/2026-09-26-b8856a42-8f8e-4205-8056-871721a27340.png', 'Ảnh nền cánh cửa bên phải'),
('pin_code', '1234', 'Mật khẩu 4 số mở cửa'),
('pin_avatar_image', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300', 'Ảnh tròn hiển thị trong modal nhập mã PIN'),
('pin_title', 'Mật Khẩu Bí Mật ~', 'Tiêu đề modal nhập PIN'),
('pin_subtitle', 'Nhập mã bí mật 4 số để mở cửa', 'Phụ đề hướng dẫn modal nhập PIN'),
('card_title', 'Chúc Mừng Một Nửa Thế Giới', 'Tiêu đề chính trên thiệp hoa'),
('card_subtitle', 'Rạng rỡ, hạnh phúc & tràn đầy yêu thương', 'Phụ đề dưới tiêu đề thiệp'),
('bg_music_url', '1.mp3', 'Đường dẫn file MP3 nhạc nền đang kích hoạt'),
('bg_music_type', 'file', 'Chế độ nguồn nhạc (file: nội bộ, url: online)'),
('bg_music_file', '1.mp3', 'Tên file MP3 nội bộ trên hosting'),
('bg_music_online_url', 'https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3', 'Link URL nhạc MP3 trực tuyến'),
('cat_quotes', 'Meo meo~ Chúc một ngày thật rực rỡ và hạnh phúc! 🐾💖\nChúc người đẹp luôn rạng ngời như đóa hoa sớm mai! 🌸✨\nBé mèo chúc bạn nhận được thật nhiều quà và yêu thương! 🌷❤️\nNụ cười của bạn là điều kỳ diệu nhất hôm nay! 🌼💕\nPurrr~ Ngọt ngào, bình an và mãi tươi trẻ nhé! 🎁🎀', 'Danh sách lời chúc của bé mèo (mỗi dòng một câu)'),
('letter_modal_title', 'Hộp Thư Yêu Thương', 'Tiêu đề hộp thư gửi lời nhắn'),
('letter_modal_subtitle', 'Hãy để lại tên và những lời chúc, cảm nghĩ ngọt ngào của bạn nhé! Lời nhắn sẽ được gửi thẳng về trang quản trị 💌', 'Lời dẫn mô tả trong hộp thư'),
('letter_sender_label', 'Tên hoặc Biệt danh của bạn:', 'Nhãn ô nhập tên người gửi'),
('letter_sender_placeholder', 'Ví dụ: Bé Út, Bạn thân, Người giấu tên...', 'Placeholder ô nhập tên'),
('letter_content_label', 'Nội dung lời nhắn gửi trao:', 'Nhãn ô nhập nội dung thư'),
('letter_content_placeholder', 'Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây...', 'Placeholder ô nhập nội dung thư'),
('letter_submit_btn_text', 'Gửi Thư Ngay 💖', 'Chữ trên nút gửi thư'),
('letter_success_toast', 'Thư của bạn đã được gửi thành công đến người nhận! Cảm ơn bạn rất nhiều 💖', 'Thông báo khi gửi thư thành công'),
('letter_cat_speech', '💌 Bé mèo đã chuyển thư của "{sender}" trao tận tay rồi nhé meo meo~ 🐾💖', 'Câu thoại của bé mèo khi gửi thư'),
('event_counter_enabled', '1', 'Bật / Tắt bộ đếm ngược hoặc bộ đếm ngày trên thiệp chính (1: Bật, 0: Tắt)'),
('event_counter_title', 'Đếm ngược đến Ngày Phụ Nữ Việt Nam 20-10 🌸', 'Tiêu đề bộ đếm sự kiện'),
('event_counter_badge', 'Sự Kiện Đặc Biệt ✨', 'Nhãn hiển thị nhỏ góc trên bộ đếm'),
('event_counter_target_date', '2026-10-20T00:00', 'Mốc thời gian sự kiện (định dạng YYYY-MM-DDTHH:mm)'),
('event_counter_mode', 'auto', 'Chế độ đếm: auto (tự động), countdown (đếm ngược), countup (đếm ngày đã qua)'),
('event_counter_message', 'Hôm nay là Ngày Phụ Nữ Việt Nam 20-10! Chúc bạn ngập tràn niềm vui, rạng rỡ và luôn được yêu thương trọn vẹn! 💖🎉', 'Thông điệp chúc mừng khi đến ngày hoặc hoàn thành đếm ngược')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- ==============================================================
-- DỮ LIỆU MẪU BAN ĐẦU CHO BẢNG WISHES (8 LỜI CHÚC MẪU)
-- ==============================================================
DELETE FROM `wishes`;

INSERT INTO `wishes` (`recipient`, `msg`, `img`, `sort_order`) VALUES
('Gửi Mẹ yêu dấu', 'Chúc mẹ ngày 20-10 luôn dồi dào sức khỏe, ngập tràn niềm vui và luôn mỉm cười hạnh phúc bên gia đình!', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=500&auto=format&fit=crop&q=80', 1),
('Gửi Người thương', 'Chúc em một ngày 20/10 ngọt ngào, rực rỡ và luôn nở nụ cười xinh đẹp nhất trên môi!', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&auto=format&fit=crop&q=80', 2),
('Gửi Vợ yêu thương', 'Cảm ơn em đã luôn là hậu phương ấm áp, người bạn đời tuyệt vời. Chúc vợ 20-10 ngập tràn hạnh phúc và luôn an yên!', 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=500&auto=format&fit=crop&q=80', 3),
('Gửi Cô giáo kính yêu', 'Kính chúc Cô ngày 20/10 thật nhiều niềm vui, sức khỏe và luôn tràn đầy nhiệt huyết dìu dắt bao thế hệ học trò!', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=500&auto=format&fit=crop&q=80', 4),
('Gửi Chị / Em gái', 'Chúc chị/em ngày càng xinh đẹp, tự tin, công việc hanh thông và đón nhận vạn điều may mắn!', 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=500&auto=format&fit=crop&q=80', 5),
('Gửi Bạn thân', 'Chúc bạn 20/10 rạng ngời, tiền vô như nước, luôn yêu đời và sớm có người thương cưng chiều nhé!', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500&auto=format&fit=crop&q=80', 6),
('Gửi Nữ đồng nghiệp', 'Chúc phái đẹp văn phòng ngày 20/10 luôn tươi trẻ, công việc thuận lợi, nhiều năng lượng và rạng rỡ mỗi ngày!', 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=500&auto=format&fit=crop&q=80', 7),
('Dành trọn yêu thương', 'Cảm ơn những hy sinh thầm lặng của phái đẹp. Chúc một nửa thế giới luôn được trân trọng, bình an và hạnh phúc!', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=500&auto=format&fit=crop&q=80', 8);

-- ==============================================================
-- DỮ LIỆU MẪU BAN ĐẦU CHO BẢNG CUSTOM_CARDS (CÁC LINK THIỆP RIÊNG)
-- ==============================================================
INSERT INTO `custom_cards` (`slug`, `title`, `settings_json`, `wishes_json`) VALUES
('tang-me', 'Kính Chúc Mẹ Yêu Vạn Sự An Lành', '{"door_left_image":"https://cdn.phototourl.com/member/2026-09-26-82093f1d-b873-458c-9297-6e06ee4ff636.png","door_right_image":"https://cdn.phototourl.com/member/2026-09-26-b8856a42-8f8e-4205-8056-871721a27340.png","pin_code":"2010","pin_avatar_image":"https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300","pin_title":"Mật Khẩu Bí Mật ~","pin_subtitle":"Nhập mã bí mật 4 số để mở cửa","card_title":"Kính Chúc Mẹ Yêu Vạn Sự An Lành","card_subtitle":"Cảm ơn Mẹ vì tất cả tình yêu thương bao la và dịu dàng","bg_music_url":"1.mp3","bg_music_type":"file","bg_music_file":"1.mp3","bg_music_online_url":"https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3"}', NULL),
('ngoc-anh', 'Chúc Ngọc Ánh Luôn Rạng Rỡ & Xinh Đẹp', '{"door_left_image":"https://cdn.phototourl.com/member/2026-09-26-82093f1d-b873-458c-9297-6e06ee4ff636.png","door_right_image":"https://cdn.phototourl.com/member/2026-09-26-b8856a42-8f8e-4205-8056-871721a27340.png","pin_code":"8888","pin_avatar_image":"https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300","pin_title":"Mật Khẩu Bí Mật ~","pin_subtitle":"Nhập mã bí mật 4 số để mở cửa","card_title":"Chúc Ngọc Ánh Luôn Rạng Rỡ & Xinh Đẹp","card_subtitle":"Mỗi ngày đều ngọt ngào, hạnh phúc và tràn đầy năng lượng tích cực","bg_music_url":"1.mp3","bg_music_type":"file","bg_music_file":"1.mp3","bg_music_online_url":"https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3"}', NULL)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `settings_json` = VALUES(`settings_json`);

SET FOREIGN_KEY_CHECKS = 1;
