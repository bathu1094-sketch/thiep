<?php
/**
 * File Cấu Hình Kết Nối MySQL Cho InfinityFree
 * Đặt file này ở thư mục gốc (root) của website trên InfinityFree (trong thư mục htdocs)
 */

// =========================================================================
// THÔNG TIN CẤU HÌNH DATABASE TRÊN INFINITYFREE
// Hãy thay đổi các thông số bên dưới theo thông tin trong Control Panel (cPanel) của InfinityFree
// =========================================================================
define('DB_HOST', 'sqlXXX.infinityfree.com'); // Thay sqlXXX bằng MySQL Hostname trong cPanel
define('DB_NAME', 'if0_xxxxxxxx_carddb');     // Tên Database bạn đã tạo
define('DB_USER', 'if0_xxxxxxxx');            // MySQL Username từ InfinityFree
define('DB_PASS', 'YOUR_CPANEL_PASSWORD');     // Mật khẩu tài khoản InfinityFree của bạn
define('DB_PORT', '3306');

// =========================================================================
// MẬT KHẨU QUẢN TRỊ ADMIN (CHỈ ĐỔI ĐƯỢC TRONG CODE TẠI ĐÂY)
// Bạn có thể sửa '123456' thành mật khẩu 6 số bạn muốn
// =========================================================================
define('ADMIN_PASSWORD', '123456');

// Thiết lập múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Khởi tạo kết nối PDO với timeout cực nhanh
function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Nếu chưa cấu hình thông tin DB trên InfinityFree, trả về null ngay lập tức (0ms delay, không bị treo)
    if (strpos(DB_HOST, 'sqlXXX') !== false || strpos(DB_USER, 'xxxxxxxx') !== false || strpos(DB_PASS, 'YOUR_CPANEL_PASSWORD') !== false) {
        return null;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 1, // Timeout 1s giúp load tức thì không bao giờ treo
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

// Dữ liệu mặc định khi database chưa kết nối (giúp web luôn chạy mượt)
function getDefaultSettings() {
    return [
        'door_left_image' => 'https://cdn.phototourl.com/member/2026-09-26-82093f1d-b873-458c-9297-6e06ee4ff636.png',
        'door_right_image' => 'https://cdn.phototourl.com/member/2026-09-26-b8856a42-8f8e-4205-8056-871721a27340.png',
        'pin_code' => '1234',
        'pin_avatar_image' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300',
        'pin_title' => 'Mật Khẩu Bí Mật ~',
        'pin_subtitle' => 'Nhập mã bí mật 4 số để mở cửa',
        'card_title' => 'Chúc Mừng Một Nửa Thế Giới',
        'card_subtitle' => 'Rạng rỡ, hạnh phúc & tràn đầy yêu thương',
        'bg_music_url' => '1.mp3',
        'bg_music_type' => 'file',
        'bg_music_file' => '1.mp3',
        'bg_music_online_url' => 'https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3',
        'cat_quotes' => "Meo meo~ Chúc một ngày thật rực rỡ và hạnh phúc! 🐾💖\nChúc người đẹp luôn rạng ngời như đóa hoa sớm mai! 🌸✨\nBé mèo chúc bạn nhận được thật nhiều quà và yêu thương! 🌷❤️\nNụ cười của bạn là điều kỳ diệu nhất hôm nay! 🌼💕\nPurrr~ Ngọt ngào, bình an và mãi tươi trẻ nhé! 🎁🎀",
        'letter_modal_title' => 'Hộp Thư Yêu Thương',
        'letter_modal_subtitle' => 'Hãy để lại tên và những lời chúc, cảm nghĩ ngọt ngào của bạn nhé! Lời nhắn sẽ được gửi thẳng về trang quản trị 💌',
        'letter_sender_label' => 'Tên hoặc Biệt danh của bạn:',
        'letter_sender_placeholder' => 'Ví dụ: Bé Út, Bạn thân, Người giấu tên...',
        'letter_content_label' => 'Nội dung lời nhắn gửi trao:',
        'letter_content_placeholder' => 'Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây...',
        'letter_submit_btn_text' => 'Gửi Thư Ngay 💖',
        'letter_success_toast' => 'Thư của bạn đã được gửi thành công đến người nhận! Cảm ơn bạn rất nhiều 💖',
        'letter_cat_speech' => '💌 Bé mèo đã chuyển thư của "{sender}" trao tận tay rồi nhé meo meo~ 🐾💖',
        'event_counter_enabled' => '0',
        'event_counter_title' => 'Đếm ngược đến Ngày Phụ Nữ Việt Nam 20-10 🌸',
        'event_counter_badge' => 'Sự Kiện Đặc Biệt ✨',
        'event_counter_target_date' => '2026-10-20T00:00',
        'event_counter_mode' => 'auto',
        'event_counter_message' => 'Hôm nay là Ngày Phụ Nữ Việt Nam 20-10! Chúc bạn ngập tràn niềm vui, rạng rỡ và luôn được yêu thương trọn vẹn! 💖🎉'
    ];
}

function getDefaultWishes() {
    return [
        [
            'id' => 1,
            'recipient' => 'Gửi Mẹ yêu dấu',
            'msg' => 'Chúc mẹ ngày 20-10 luôn dồi dào sức khỏe, ngập tràn niềm vui và luôn mỉm cười hạnh phúc bên gia đình!',
            'img' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=500&auto=format&fit=crop&q=80',
            'sort_order' => 1
        ],
        [
            'id' => 2,
            'recipient' => 'Gửi Người thương',
            'msg' => 'Chúc em một ngày ngọt ngào, rực rỡ và luôn nở nụ cười xinh đẹp nhất trên môi!',
            'img' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&auto=format&fit=crop&q=80',
            'sort_order' => 2
        ],
        [
            'id' => 3,
            'recipient' => 'Gửi Vợ yêu thương',
            'msg' => 'Cảm ơn em đã luôn là hậu phương ấm áp, người bạn đời tuyệt vời. Chúc vợ 20-10 ngập tràn hạnh phúc và luôn an yên!',
            'img' => 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=500&auto=format&fit=crop&q=80',
            'sort_order' => 3
        ],
        [
            'id' => 4,
            'recipient' => 'Gửi Cô giáo kính yêu',
            'msg' => 'Kính chúc Cô ngày lễ thật nhiều niềm vui, sức khỏe và luôn tràn đầy nhiệt huyết dìu dắt bao thế hệ học trò!',
            'img' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=500&auto=format&fit=crop&q=80',
            'sort_order' => 4
        ],
        [
            'id' => 5,
            'recipient' => 'Gửi Chị / Em gái',
            'msg' => 'Chúc chị/em ngày càng xinh đẹp, tự tin, công việc hanh thông và đón nhận vạn điều may mắn!',
            'img' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=500&auto=format&fit=crop&q=80',
            'sort_order' => 5
        ],
        [
            'id' => 6,
            'recipient' => 'Gửi Bạn thân',
            'msg' => 'Chúc bạn luôn rạng ngời, tiền vô như nước, luôn yêu đời và sớm có người thương cưng chiều nhé!',
            'img' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500&auto=format&fit=crop&q=80',
            'sort_order' => 6
        ],
        [
            'id' => 7,
            'recipient' => 'Gửi Nữ đồng nghiệp',
            'msg' => 'Chúc phái đẹp văn phòng luôn tươi trẻ, công việc thuận lợi, nhiều năng lượng và rạng rỡ mỗi ngày!',
            'img' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=500&auto=format&fit=crop&q=80',
            'sort_order' => 7
        ],
        [
            'id' => 8,
            'recipient' => 'Dành trọn yêu thương',
            'msg' => 'Cảm ơn những hy sinh thầm lặng của phái đẹp. Chúc một nửa thế giới luôn được trân trọng, bình an và hạnh phúc!',
            'img' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=500&auto=format&fit=crop&q=80',
            'sort_order' => 8
        ]
    ];
}

// Lấy tất cả cài đặt
function getAllSettings() {
    $defaults = getDefaultSettings();
    $pdo = getDbConnection();
    if (!$pdo) {
        if (file_exists(__DIR__ . '/settings.json')) {
            $json = @file_get_contents(__DIR__ . '/settings.json');
            if ($json) {
                $saved = json_decode($json, true);
                if (is_array($saved)) {
                    return array_merge($defaults, $saved);
                }
            }
        }
        return $defaults;
    }

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $defaults[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        if (file_exists(__DIR__ . '/settings.json')) {
            $json = @file_get_contents(__DIR__ . '/settings.json');
            if ($json) {
                $saved = json_decode($json, true);
                if (is_array($saved)) {
                    return array_merge($defaults, $saved);
                }
            }
        }
    }
    return $defaults;
}

// Lấy danh sách tất cả lời chúc
function getAllWishes() {
    $pdo = getDbConnection();
    if (!$pdo) {
        if (file_exists(__DIR__ . '/wishes.json')) {
            $json = @file_get_contents(__DIR__ . '/wishes.json');
            if ($json) {
                $saved = json_decode($json, true);
                if (is_array($saved) && !empty($saved)) {
                    return $saved;
                }
            }
        }
        return getDefaultWishes();
    }

    try {
        $stmt = $pdo->query("SELECT id, recipient, msg, img, sort_order FROM wishes ORDER BY sort_order ASC, id ASC");
        $wishes = $stmt->fetchAll();
        if (!empty($wishes)) {
            return $wishes;
        }
    } catch (Exception $e) {
        // Fallback
    }

    if (file_exists(__DIR__ . '/wishes.json')) {
        $json = @file_get_contents(__DIR__ . '/wishes.json');
        if ($json) {
            $saved = json_decode($json, true);
            if (is_array($saved) && !empty($saved)) {
                return $saved;
            }
        }
    }
    return getDefaultWishes();
}

// =========================================================================
// HÀM QUẢN LÝ HỘP THƯ LỜI NHẮN (LETTERS) - TỐC ĐỘ SIÊU NHANH & DỰ PHÒNG TỨC THÌ
// =========================================================================

// File lưu trữ dự phòng khi MySQL chưa kết nối được
define('LETTERS_BACKUP_FILE', __DIR__ . '/letters.json');

// Lấy danh sách tất cả thư gửi về
function getAllLetters() {
    $pdo = getDbConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT id, sender_name, content, created_at, is_read FROM letters ORDER BY created_at DESC, id DESC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (Exception $e) {
            // Tự tạo bảng nếu chưa có
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `letters` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `sender_name` VARCHAR(255) NOT NULL,
                  `content` TEXT NOT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  `is_read` TINYINT DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            } catch (Exception $ex) {}
        }
    }

    // Đọc từ file backup JSON nếu không có DB
    if (file_exists(LETTERS_BACKUP_FILE)) {
        $json = @file_get_contents(LETTERS_BACKUP_FILE);
        if ($json) {
            $data = json_decode($json, true);
            if (is_array($data)) return $data;
        }
    }

    return [];
}

// Lưu một lá thư mới do người xem gửi - Phản hồi siêu tốc < 50ms
function saveLetter($senderName, $content) {
    $pdo = getDbConnection();
    $savedToDb = false;

    if ($pdo) {
        try {
            // Thử INSERT trực tiếp (cực nhanh không chờ CREATE TABLE)
            $stmt = $pdo->prepare("INSERT INTO letters (sender_name, content, is_read) VALUES (?, ?, 0)");
            $savedToDb = $stmt->execute([$senderName, $content]);
        } catch (Exception $e) {
            // Nếu bảng chưa có thì tạo bảng và thử lại
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `letters` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `sender_name` VARCHAR(255) NOT NULL,
                  `content` TEXT NOT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  `is_read` TINYINT DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                $stmt = $pdo->prepare("INSERT INTO letters (sender_name, content, is_read) VALUES (?, ?, 0)");
                $savedToDb = $stmt->execute([$senderName, $content]);
            } catch (Exception $ex) {
                $savedToDb = false;
            }
        }
    }

    // Luôn ghi vào file backup JSON để đảm bảo thư không bao giờ bị mất và phản hồi tức thì
    try {
        $letters = [];
        if (file_exists(LETTERS_BACKUP_FILE)) {
            $existing = @file_get_contents(LETTERS_BACKUP_FILE);
            if ($existing) {
                $letters = json_decode($existing, true) ?: [];
            }
        }
        $newLetter = [
            'id' => time() . rand(100, 999),
            'sender_name' => $senderName,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
            'is_read' => 0
        ];
        array_unshift($letters, $newLetter);
        @file_put_contents(LETTERS_BACKUP_FILE, json_encode($letters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return true;
    } catch (Exception $e) {
        return $savedToDb;
    }
}

// Xóa một lá thư
function deleteLetter($id) {
    $idStr = trim(strval($id));
    if ($idStr === '') return false;
    $deleted = false;
    $pdo = getDbConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM letters WHERE id = ?");
            $deleted = $stmt->execute([$idStr]);
        } catch (Exception $e) {}
    }

    // Xóa trong file backup JSON
    if (file_exists(LETTERS_BACKUP_FILE)) {
        $json = @file_get_contents(LETTERS_BACKUP_FILE);
        if ($json) {
            $letters = json_decode($json, true) ?: [];
            $initialCount = count($letters);
            $letters = array_values(array_filter($letters, function($item) use ($idStr) {
                return strval($item['id'] ?? '') !== $idStr;
            }));
            if (count($letters) < $initialCount) {
                @file_put_contents(LETTERS_BACKUP_FILE, json_encode($letters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                $deleted = true;
            }
        }
    }

    return $deleted;
}

// Xóa tất cả thư trong hòm thư
function deleteAllLetters() {
    $pdo = getDbConnection();
    if ($pdo) {
        try {
            $pdo->exec("DELETE FROM letters");
        } catch (Exception $e) {}
    }
    if (file_exists(LETTERS_BACKUP_FILE)) {
        @file_put_contents(LETTERS_BACKUP_FILE, json_encode([], JSON_UNESCAPED_UNICODE));
    }
    return true;
}

// Đánh dấu thư đã đọc
function markLetterRead($id) {
    $idStr = trim(strval($id));
    if ($idStr === '') return false;
    $updated = false;
    $pdo = getDbConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE letters SET is_read = 1 WHERE id = ?");
            $updated = $stmt->execute([$idStr]);
        } catch (Exception $e) {}
    }

    // Cập nhật trong file backup JSON
    if (file_exists(LETTERS_BACKUP_FILE)) {
        $json = @file_get_contents(LETTERS_BACKUP_FILE);
        if ($json) {
            $letters = json_decode($json, true) ?: [];
            foreach ($letters as &$item) {
                if (strval($item['id'] ?? '') === $idStr) {
                    $item['is_read'] = 1;
                    $updated = true;
                }
            }
            @file_put_contents(LETTERS_BACKUP_FILE, json_encode($letters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }

    return $updated;
}

// =========================================================================
// QUẢN LÝ NHIỀU THIỆP & TẠO LINK RIÊNG BIỆT (CUSTOM CARDS / SLUG LINKS)
// Hỗ trợ lưu trữ cả MySQL & File JSON backup (hoạt động 100% cả khi chưa có DB)
// =========================================================================
define('CUSTOM_CARDS_BACKUP_FILE', __DIR__ . '/custom_cards.json');

// Chuẩn hóa tên đường dẫn slug (ví dụ "Mẹ Yêu 20/10" -> "me-yeu-20-10")
function sanitizeCardSlug($str) {
    $str = trim((string)$str);
    if ($str === '') return 'thiep-' . substr(md5(uniqid()), 0, 6);
    
    // Chuyển tiếng Việt có dấu sang không dấu
    $vietnameseMap = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'D' => 'Đ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
    ];
    foreach ($vietnameseMap as $nonAcc => $acc) {
        $str = preg_replace("/($acc)/i", $nonAcc, $str);
    }
    
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9_-]+/', '-', $str);
    $str = trim($str, '-_');
    return $str !== '' ? $str : 'thiep-' . substr(md5(uniqid()), 0, 6);
}

// Khởi tạo bảng custom_cards nếu chưa có
function ensureCustomCardsTable($pdo) {
    if (!$pdo) return;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `custom_cards` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `title` VARCHAR(255) NOT NULL,
            `settings_json` MEDIUMTEXT NOT NULL,
            `wishes_json` MEDIUMTEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_card_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
}

// Lấy danh sách tất cả các thiệp tùy chỉnh
function getAllCustomCards() {
    $cards = [];
    $pdo = getDbConnection();

    if ($pdo) {
        try {
            ensureCustomCardsTable($pdo);
            $stmt = $pdo->query("SELECT * FROM custom_cards ORDER BY id DESC");
            $rows = $stmt->fetchAll();
            foreach ($rows as $r) {
                $cards[] = [
                    'id' => (int)$r['id'],
                    'slug' => $r['slug'],
                    'title' => $r['title'],
                    'settings' => json_decode($r['settings_json'], true) ?: [],
                    'wishes' => !empty($r['wishes_json']) ? (json_decode($r['wishes_json'], true) ?: null) : null,
                    'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
        } catch (Exception $e) {}
    }

    // Nếu DB chưa có hoặc rỗng, kiểm tra file backup JSON
    if (empty($cards) && file_exists(CUSTOM_CARDS_BACKUP_FILE)) {
        $json = @file_get_contents(CUSTOM_CARDS_BACKUP_FILE);
        if ($json) {
            $cards = json_decode($json, true) ?: [];
        }
    }

    return $cards;
}

// Lấy 1 thiệp theo slug (tên link)
function getCustomCardBySlug($slug) {
    $cleanSlug = sanitizeCardSlug($slug);
    if ($cleanSlug === '') return null;

    $pdo = getDbConnection();
    if ($pdo) {
        try {
            ensureCustomCardsTable($pdo);
            $stmt = $pdo->prepare("SELECT * FROM custom_cards WHERE slug = ? LIMIT 1");
            $stmt->execute([$cleanSlug]);
            $r = $stmt->fetch();
            if ($r) {
                return [
                    'id' => (int)$r['id'],
                    'slug' => $r['slug'],
                    'title' => $r['title'],
                    'settings' => json_decode($r['settings_json'], true) ?: [],
                    'wishes' => !empty($r['wishes_json']) ? (json_decode($r['wishes_json'], true) ?: null) : null,
                    'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
        } catch (Exception $e) {}
    }

    // Fallback file backup JSON
    if (file_exists(CUSTOM_CARDS_BACKUP_FILE)) {
        $json = @file_get_contents(CUSTOM_CARDS_BACKUP_FILE);
        if ($json) {
            $cards = json_decode($json, true) ?: [];
            foreach ($cards as $c) {
                if (($c['slug'] ?? '') === $cleanSlug) {
                    return $c;
                }
            }
        }
    }

    return null;
}

// Lưu hoặc cập nhật thiệp tùy chỉnh
function saveCustomCard($slug, $title, $settings, $wishes = null, $cardId = null) {
    $cleanSlug = sanitizeCardSlug($slug);
    $title = trim((string)$title);
    if ($title === '') $title = 'Thiệp ' . $cleanSlug;

    $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $wishesJson = ($wishes !== null) ? json_encode($wishes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null;
    $now = date('Y-m-d H:i:s');
    $savedId = null;

    $pdo = getDbConnection();
    if ($pdo) {
        try {
            ensureCustomCardsTable($pdo);
            if ($cardId) {
                $stmt = $pdo->prepare("UPDATE custom_cards SET slug = ?, title = ?, settings_json = ?, wishes_json = ? WHERE id = ?");
                $stmt->execute([$cleanSlug, $title, $settingsJson, $wishesJson, $cardId]);
                $savedId = (int)$cardId;
            } else {
                $stmt = $pdo->prepare("INSERT INTO custom_cards (slug, title, settings_json, wishes_json) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = ?, settings_json = ?, wishes_json = ?");
                $stmt->execute([$cleanSlug, $title, $settingsJson, $wishesJson, $title, $settingsJson, $wishesJson]);
                $savedId = (int)$pdo->lastInsertId();
            }
        } catch (Exception $e) {}
    }

    // Đồng bộ vào file backup JSON
    $cards = [];
    if (file_exists(CUSTOM_CARDS_BACKUP_FILE)) {
        $json = @file_get_contents(CUSTOM_CARDS_BACKUP_FILE);
        if ($json) {
            $cards = json_decode($json, true) ?: [];
        }
    }

    $found = false;
    foreach ($cards as &$item) {
        if (($cardId && (int)($item['id'] ?? 0) === (int)$cardId) || ($item['slug'] ?? '') === $cleanSlug) {
            $item['slug'] = $cleanSlug;
            $item['title'] = $title;
            $item['settings'] = $settings;
            if ($wishes !== null) $item['wishes'] = $wishes;
            $item['updated_at'] = $now;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $newId = $savedId ?: (count($cards) > 0 ? max(array_column($cards, 'id')) + 1 : 1);
        array_unshift($cards, [
            'id' => $newId,
            'slug' => $cleanSlug,
            'title' => $title,
            'settings' => $settings,
            'wishes' => $wishes,
            'created_at' => $now
        ]);
    }
    @file_put_contents(CUSTOM_CARDS_BACKUP_FILE, json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return [
        'success' => true,
        'slug' => $cleanSlug,
        'title' => $title,
        'id' => $savedId
    ];
}

// Xóa thiệp tùy chỉnh
function deleteCustomCard($idOrSlug) {
    $pdo = getDbConnection();
    if ($pdo) {
        try {
            ensureCustomCardsTable($pdo);
            if (is_numeric($idOrSlug)) {
                $stmt = $pdo->prepare("DELETE FROM custom_cards WHERE id = ?");
                $stmt->execute([(int)$idOrSlug]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM custom_cards WHERE slug = ?");
                $stmt->execute([$idOrSlug]);
            }
        } catch (Exception $e) {}
    }

    if (file_exists(CUSTOM_CARDS_BACKUP_FILE)) {
        $json = @file_get_contents(CUSTOM_CARDS_BACKUP_FILE);
        if ($json) {
            $cards = json_decode($json, true) ?: [];
            $cards = array_values(array_filter($cards, function($item) use ($idOrSlug) {
                if (is_numeric($idOrSlug)) {
                    return (int)($item['id'] ?? 0) !== (int)$idOrSlug;
                }
                return ($item['slug'] ?? '') !== $idOrSlug;
            }));
            @file_put_contents(CUSTOM_CARDS_BACKUP_FILE, json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
    return true;
}

