<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$pdo = getDbConnection();
$message = '';
$messageType = '';
$activeTab = 'tab-custom-links';

// Tải trọn bộ file ZIP triển khai InfinityFree trực tiếp từ admin
if (isset($_GET['action']) && $_GET['action'] === 'download_zip') {
    if (class_exists('ZipArchive')) {
        $zipFile = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $rootDir = dirname(__DIR__);
            $files = [
                'HUONG_DAN_CAI_DAT_INFINITYFREE.txt',
                '.htaccess',
                'config.php',
                'database.sql',
                'index.php',
                'api_send_letter.php',
                'css.css',
                'script.js',
                'tailwind.css',
                'custom_cards.json',
                'settings.json',
                'wishes.json',
                'letters.json',
                'admin/index.php',
                'admin/login.php',
                'admin/auth.php',
                'admin/logout.php',
                'admin/tailwind.css'
            ];
            foreach ($files as $f) {
                $fullPath = $rootDir . '/' . $f;
                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, $f);
                }
            }
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="thiep_2_canh_cua_infinityfree.zip"');
            header('Content-Length: ' . filesize($zipFile));
            readfile($zipFile);
            @unlink($zipFile);
            exit;
        }
    }
}

// Xử lý các Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!empty($_POST['current_tab'])) {
        $activeTab = trim($_POST['current_tab']);
    }

    // 1. CẬP NHẬT CẤU HÌNH CỬA & MẬT KHẨU & GIAO DIỆN & NHẠC NỀN
    if ($action === 'update_settings') {
        $activeTab = !empty($_POST['current_tab']) ? trim($_POST['current_tab']) : 'tab-doors-pin';
        $doorLeft = trim($_POST['door_left_image'] ?? '');
        $doorRight = trim($_POST['door_right_image'] ?? '');
        $pinCode = trim($_POST['pin_code'] ?? '1234');
        $pinAvatar = trim($_POST['pin_avatar_image'] ?? '');
        $pinTitle = trim($_POST['pin_title'] ?? 'Mật Khẩu Bí Mật ~');
        $pinSubtitle = trim($_POST['pin_subtitle'] ?? 'Nhập mã bí mật 4 số để mở cửa');
        $cardTitle = trim($_POST['card_title'] ?? 'Chúc Mừng Một Nửa Thế Giới');
        $cardSubtitle = trim($_POST['card_subtitle'] ?? 'Rạng rỡ, hạnh phúc & tràn đầy yêu thương');
        
        $bgMusicType = trim($_POST['bg_music_type'] ?? 'file');
        $bgMusicFile = trim($_POST['bg_music_file'] ?? '1.mp3');
        $bgMusicOnlineUrl = trim($_POST['bg_music_online_url'] ?? '');
        
        // Tránh xung đột: Lấy đúng bài hát theo nguồn đang được chọn kích hoạt
        if ($bgMusicType === 'url' && !empty($bgMusicOnlineUrl)) {
            $bgMusic = $bgMusicOnlineUrl;
        } else {
            $bgMusic = !empty($bgMusicFile) ? $bgMusicFile : '1.mp3';
            $bgMusicType = 'file';
        }

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                
                $data = [
                    'door_left_image' => $doorLeft,
                    'door_right_image' => $doorRight,
                    'pin_code' => $pinCode,
                    'pin_avatar_image' => $pinAvatar,
                    'pin_title' => $pinTitle,
                    'pin_subtitle' => $pinSubtitle,
                    'card_title' => $cardTitle,
                    'card_subtitle' => $cardSubtitle,
                    'bg_music_url' => $bgMusic,
                    'bg_music_type' => $bgMusicType,
                    'bg_music_file' => $bgMusicFile,
                    'bg_music_online_url' => $bgMusicOnlineUrl,
                ];

                foreach ($data as $key => $val) {
                    $stmt->execute([$key, $val, $val]);
                }

                $message = 'Cập nhật cài đặt giao diện, âm nhạc và tiêu đề thành công!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Lỗi lưu database: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Không thể kết nối đến Database! Vui lòng kiểm tra config.php';
            $messageType = 'error';
        }
    }

    // 2. THÊM LỜI CHÚC MỚI
    elseif ($action === 'add_wish') {
        $activeTab = 'tab-wishes';
        $recipient = trim($_POST['recipient'] ?? '');
        $msg = trim($_POST['msg'] ?? '');
        $img = trim($_POST['img'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 1);

        if (empty($recipient) || empty($msg) || empty($img)) {
            $message = 'Vui lòng nhập đầy đủ người nhận, nội dung và link ảnh lời chúc!';
            $messageType = 'error';
        } elseif ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO wishes (recipient, msg, img, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$recipient, $msg, $img, $sortOrder]);
                $message = 'Thêm lời chúc mới thành công!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Lỗi thêm lời chúc: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // 3. CẬP NHẬT LỜI CHÚC
    elseif ($action === 'update_wish') {
        $activeTab = 'tab-wishes';
        $id = intval($_POST['wish_id'] ?? 0);
        $recipient = trim($_POST['recipient'] ?? '');
        $msg = trim($_POST['msg'] ?? '');
        $img = trim($_POST['img'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 1);

        if ($id > 0 && $pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE wishes SET recipient = ?, msg = ?, img = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$recipient, $msg, $img, $sortOrder, $id]);
                $message = 'Cập nhật lời chúc ID #' . $id . ' thành công!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Lỗi cập nhật lời chúc: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // 4. XÓA LỜI CHÚC
    elseif ($action === 'delete_wish') {
        $activeTab = 'tab-wishes';
        $id = intval($_POST['wish_id'] ?? 0);
        if ($id > 0 && $pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM wishes WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Đã xóa lời chúc ID #' . $id . ' thành công!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Lỗi xóa lời chúc: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // 5. KHÔI PHỤC DANH SÁCH LỜI CHÚC MẶC ĐỊNH
    elseif ($action === 'reset_default_wishes') {
        $activeTab = 'tab-wishes';
        if ($pdo) {
            try {
                $pdo->exec("DELETE FROM wishes");
                $defaults = getDefaultWishes();
                $stmt = $pdo->prepare("INSERT INTO wishes (recipient, msg, img, sort_order) VALUES (?, ?, ?, ?)");
                foreach ($defaults as $w) {
                    $stmt->execute([$w['recipient'], $w['msg'], $w['img'], $w['sort_order']]);
                }
                $message = 'Đã khôi phục toàn bộ 8 lời chúc mẫu ban đầu thành công!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Lỗi khôi phục: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // 6. CẬP NHẬT LỜI CHÚC KHI NHẤN VÀO BÉ MÈO
    elseif ($action === 'update_cat_quotes') {
        $activeTab = 'tab-cat-quotes';
        $catQuotes = trim($_POST['cat_quotes'] ?? '');
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('cat_quotes', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$catQuotes, $catQuotes]);
                $message = 'Đã cập nhật danh sách lời chúc khi nhấn vào bé mèo thành công!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Lỗi lưu lời chúc của bé mèo: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Không thể kết nối đến Database! Vui lòng kiểm tra file config.php';
            $messageType = 'error';
        }
    }

    // 6.5 CẬP NHẬT TẤT CẢ NỘI DUNG HỘP THƯ YÊU THƯƠNG
    elseif ($action === 'update_letter_settings') {
        $activeTab = 'tab-letter-settings';
        $title = trim($_POST['letter_modal_title'] ?? 'Hộp Thư Yêu Thương');
        $subtitle = trim($_POST['letter_modal_subtitle'] ?? '');
        $senderLabel = trim($_POST['letter_sender_label'] ?? '');
        $senderPlaceholder = trim($_POST['letter_sender_placeholder'] ?? '');
        $contentLabel = trim($_POST['letter_content_label'] ?? '');
        $contentPlaceholder = trim($_POST['letter_content_placeholder'] ?? '');
        $submitBtnText = trim($_POST['letter_submit_btn_text'] ?? 'Gửi Thư Ngay 💖');
        $catSpeech = trim($_POST['letter_cat_speech'] ?? '');

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $data = [
                    'letter_modal_title' => $title,
                    'letter_modal_subtitle' => $subtitle,
                    'letter_sender_label' => $senderLabel,
                    'letter_sender_placeholder' => $senderPlaceholder,
                    'letter_content_label' => $contentLabel,
                    'letter_content_placeholder' => $contentPlaceholder,
                    'letter_submit_btn_text' => $submitBtnText,
                    'letter_cat_speech' => $catSpeech,
                ];

                foreach ($data as $key => $val) {
                    $stmt->execute([$key, $val, $val]);
                }

                $message = 'Cập nhật toàn bộ nội dung Hộp Thư Yêu Thương thành công!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Lỗi lưu cài đặt hộp thư: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Không thể kết nối đến Database! Vui lòng kiểm tra config.php';
            $messageType = 'error';
        }
    }

    // 6.8 CẬP NHẬT BỘ ĐẾM SỰ KIỆN / NGÀY KỶ NIỆM (COUNTDOWN / COUNT-UP)
    elseif ($action === 'update_event_counter_settings') {
        $activeTab = 'tab-event-counter';
        $enabled = isset($_POST['event_counter_enabled']) ? '1' : '0';
        $title = trim($_POST['event_counter_title'] ?? 'Đếm ngược đến Ngày 20-10 🌸');
        $badge = trim($_POST['event_counter_badge'] ?? 'Sự Kiện Đặc Biệt ✨');
        $targetDate = trim($_POST['event_counter_target_date'] ?? '2026-10-20T00:00');
        $mode = trim($_POST['event_counter_mode'] ?? 'auto');
        $finishedMsg = trim($_POST['event_counter_message'] ?? 'Hôm nay là ngày đặc biệt! Chúc bạn ngập tràn niềm vui & hạnh phúc! 💖');

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $data = [
                    'event_counter_enabled' => $enabled,
                    'event_counter_title' => $title,
                    'event_counter_badge' => $badge,
                    'event_counter_target_date' => $targetDate,
                    'event_counter_mode' => $mode,
                    'event_counter_message' => $finishedMsg,
                ];

                foreach ($data as $key => $val) {
                    $stmt->execute([$key, $val, $val]);
                }

                $message = 'Cập nhật Bộ đếm sự kiện / Ngày kỷ niệm thành công!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Lỗi lưu cài đặt bộ đếm sự kiện: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Không thể kết nối đến Database! Vui lòng kiểm tra config.php';
            $messageType = 'error';
        }
    }

    // 7. XÓA THƯ
    elseif ($action === 'delete_letter') {
        $activeTab = 'tab-letters';
        $letterId = trim($_POST['letter_id'] ?? '');
        if ($letterId !== '' && deleteLetter($letterId)) {
            $message = 'Đã xóa lá thư thành công!';
            $messageType = 'success';
        } else {
            $message = 'Không thể xóa thư hoặc thư không tồn tại.';
            $messageType = 'error';
        }
    }

    // 8. ĐÁNH DẤU THƯ ĐÃ ĐỌC
    elseif ($action === 'mark_letter_read') {
        $activeTab = 'tab-letters';
        $letterId = trim($_POST['letter_id'] ?? '');
        if ($letterId !== '' && markLetterRead($letterId)) {
            $message = 'Đã đánh dấu thư là đã đọc!';
            $messageType = 'success';
        }
    }

    // 9. XÓA TẤT CẢ THƯ
    elseif ($action === 'delete_all_letters') {
        $activeTab = 'tab-letters';
        deleteAllLetters();
        $message = 'Đã dọn sạch tất cả thư trong hòm thư!';
        $messageType = 'success';
    }

    // 10. LƯU / TẠO LINK THIỆP MỚI (CUSTOM CARD LINK GENERATOR / CREATOR STUDIO)
    elseif ($action === 'save_custom_card') {
        $activeTab = 'tab-custom-links';
        $slug = trim($_POST['card_slug'] ?? '');
        $cardId = !empty($_POST['card_id']) ? (int)$_POST['card_id'] : null;

        // Toàn bộ cài đặt hiện tại từ form hoặc database
        $cardSettings = getAllSettings();

        // Nếu gửi từ Creator Studio bằng JSON:
        if (!empty($_POST['card_settings_json'])) {
            $parsedSettings = json_decode($_POST['card_settings_json'], true);
            if (is_array($parsedSettings)) {
                $cardSettings = array_merge($cardSettings, $parsedSettings);
            }
        }

        // Cũng hỗ trợ các trường POST đơn lẻ
        $fields = [
            'door_left_image', 'door_right_image', 'pin_code', 'pin_avatar_image',
            'pin_title', 'pin_subtitle', 'card_subtitle',
            'bg_music_url', 'bg_music_online_url', 'bg_music_type', 'cat_quotes', 'event_counter_enabled',
            'event_counter_title', 'event_counter_target_date', 'event_counter_message',
            'letter_modal_title', 'letter_modal_subtitle', 'letter_sender_label', 'letter_sender_placeholder',
            'letter_content_label', 'letter_content_placeholder', 'letter_submit_btn_text', 'letter_success_toast', 'letter_cat_speech'
        ];
        foreach ($fields as $f) {
            if (isset($_POST[$f]) && $_POST[$f] !== '') {
                $cardSettings[$f] = trim($_POST[$f]);
            }
        }
        if (isset($_POST['card_title_val']) && !empty($_POST['card_title_val'])) {
            $cardSettings['card_title'] = trim($_POST['card_title_val']);
        }
        if (isset($_POST['card_subtitle_val']) && !empty($_POST['card_subtitle_val'])) {
            $cardSettings['card_subtitle'] = trim($_POST['card_subtitle_val']);
        }

        // TIÊU ĐỀ THIỆP CHÍNH LÀ TIÊU ĐỀ SAU KHI MỞ CỬA (BỎ HOÀN TOÀN TÊN GỢI NHỚ)
        $title = !empty($cardSettings['card_title']) ? $cardSettings['card_title'] : ('Thiệp /' . $slug);

        // Lời chúc:
        $cardWishes = getAllWishes();
        if (!empty($_POST['card_wishes_json'])) {
            $parsedWishes = json_decode($_POST['card_wishes_json'], true);
            if (is_array($parsedWishes) && count($parsedWishes) > 0) {
                $cardWishes = $parsedWishes;
            }
        }

        $res = saveCustomCard($slug, $title, $cardSettings, $cardWishes, $cardId);
        $isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($res && !empty($res['success'])) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'slug' => $res['slug'],
                    'title' => $res['title'],
                    'id' => $res['id'],
                    'card' => [
                        'id' => $res['id'],
                        'slug' => $res['slug'],
                        'title' => $res['title'],
                        'settings' => $cardSettings,
                        'wishes' => $cardWishes,
                        'created_at' => date('Y-m-d H:i:s')
                    ],
                    'message' => 'Tạo và lưu link thiệp "/' . $res['slug'] . '" thành công!'
                ]);
                exit;
            }
            $message = 'Tạo và lưu link thiệp "/' . htmlspecialchars($res['slug']) . '" thành công! Bạn có thể sao chép và gửi ngay 💖';
            $messageType = 'success';
        } else {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Lỗi tạo link thiệp, vui lòng kiểm tra lại!']);
                exit;
            }
            $message = 'Lỗi tạo link thiệp, vui lòng kiểm tra lại!';
            $messageType = 'error';
        }
    }

    // 11. XÓA LINK THIỆP
    elseif ($action === 'delete_custom_card') {
        $activeTab = 'tab-custom-links';
        $cardId = trim($_POST['card_id'] ?? '');
        $isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($cardId !== '') {
            deleteCustomCard($cardId);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Bạn đã xóa thành công!']);
                exit;
            }
            $message = 'Bạn đã xóa thành công!';
            $messageType = 'success';
        }
    }

    // 12. NHÂN BẢN LINK THIỆP
    elseif ($action === 'duplicate_custom_card') {
        $activeTab = 'tab-custom-links';
        $sourceSlug = trim($_POST['source_slug'] ?? '');
        $newSlug = trim($_POST['new_slug'] ?? '');
        $sourceCard = getCustomCardBySlug($sourceSlug);
        if ($sourceCard) {
            $sourceSettings = $sourceCard['settings'] ?? [];
            $primaryTitle = !empty($sourceSettings['card_title']) ? $sourceSettings['card_title'] : ($sourceCard['title'] ?? 'Thiệp');
            $newTitle = $primaryTitle . ' (Bản sao)';
            $sourceSettings['card_title'] = $newTitle;
            saveCustomCard($newSlug, $newTitle, $sourceSettings, $sourceCard['wishes'] ?? null);
            $message = 'Nhân bản thiệp thành công với link mới: "/' . htmlspecialchars(sanitizeCardSlug($newSlug)) . '"!';
            $messageType = 'success';
        }
    }
}

// Lấy lại dữ liệu mới nhất
$settings = getAllSettings();
if (empty($settings['bg_music_file'])) {
    $settings['bg_music_file'] = (!empty($settings['bg_music_url']) && !preg_match('/^https?:\/\//i', $settings['bg_music_url'])) ? $settings['bg_music_url'] : '1.mp3';
}
if (empty($settings['bg_music_online_url'])) {
    $settings['bg_music_online_url'] = (!empty($settings['bg_music_url']) && preg_match('/^https?:\/\//i', $settings['bg_music_url'])) ? $settings['bg_music_url'] : 'https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3';
}
if (empty($settings['bg_music_type'])) {
    $settings['bg_music_type'] = (!empty($settings['bg_music_url']) && preg_match('/^https?:\/\//i', $settings['bg_music_url'])) ? 'url' : 'file';
}

$wishes = getAllWishes();
$letters = getAllLetters();
$unreadLettersCount = 0;
foreach ($letters as $l) {
    if (empty($l['is_read'])) $unreadLettersCount++;
}

// Danh sách các link thiệp tùy chỉnh đã tạo
$customCards = getAllCustomCards();

// Tự động xác định Base URL của website để tạo link chuẩn
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'tenmien.com';
$adminDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$siteDir = rtrim(dirname($adminDir), '/');
$baseUrl = $protocol . $host . ($siteDir !== '' ? $siteDir : '') . '/';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bảng Điều Khiển Quản Trị - Thiệp Chúc Mừng Hoàng Gia</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@600;700&family=Great+Vibes&family=Montserrat:wght@400;600;700&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="tailwind.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="../tailwind.css?v=<?= time() ?>" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Quicksand', 'sans-serif'],
            comfortaa: ['Comfortaa', 'cursive'],
            vibes: ['Great Vibes', 'cursive']
          }
        }
      }
    };
  </script>
  <style>
    body { font-family: 'Quicksand', sans-serif; }
    .font-comfortaa { font-family: 'Comfortaa', cursive, sans-serif; }
    .font-vibes { font-family: 'Great Vibes', cursive; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
      animation: fadeIn 0.3s ease-out forwards;
    }
  </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 font-sans pb-16">

  <!-- TOP BAR (100% GIỐNG PREVIEW) -->
  <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur-md border-b border-pink-500/20 px-4 sm:px-8 py-3.5 flex items-center justify-between shadow-xl">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-500 to-amber-400 flex items-center justify-center text-white text-lg shadow-md shadow-rose-500/20">
        <i class="fa-solid fa-heart"></i>
      </div>
      <div>
        <h1 class="text-base sm:text-lg font-bold font-comfortaa text-pink-100 leading-tight">Quản Trị Thiệp</h1>
        <p class="text-[11px] text-pink-300/70">Tùy biến Cửa, Mật khẩu PIN, Avatar, Lời chúc & Tạo Link Website</p>
      </div>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap">
      <!-- Nút Tạo & Lấy Link Nhanh -->
      <button type="button" onclick="openCreatorStudioForNew()" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-amber-400 via-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white text-xs font-bold shadow-lg shadow-pink-500/25 flex items-center gap-1.5 transition-all" title="Mở bảng tạo link thiệp mới với trọn bộ tất cả các bước chỉnh sửa">
        <i class="fa-solid fa-wand-magic-sparkles text-amber-200"></i>
        <span>Tạo & Lấy Link Website 🔗</span>
      </button>

      <!-- Nút Tải ZIP InfinityFree Đồng Bộ Dữ Liệu -->
      <button type="button" onclick="downloadAdminZip(this)" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-95 text-slate-200 hover:text-white text-xs font-semibold shadow-md flex items-center gap-1.5 transition-all border border-slate-700">
        <i class="fa-solid fa-download text-amber-400"></i>
        <span>Tải ZIP InfinityFree</span>
      </button>

      <a href="../" class="px-3.5 py-1.5 rounded-xl bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Về Trang Thiệp</span>
      </a>

      <a href="logout.php" class="px-3.5 py-1.5 rounded-xl bg-rose-600/30 hover:bg-rose-600/50 text-rose-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Đăng Xuất</span>
      </a>
    </div>
  </header>

  <!-- NỘI DUNG CHÍNH (MAX-W-6XL NHƯ PREVIEW) -->
  <main class="max-w-6xl mx-auto px-4 sm:px-6 mt-6 space-y-6">

    <!-- THÔNG BÁO ALERT NẾU CÓ -->
    <?php if (!empty($message)): ?>
      <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-950/90 border border-emerald-500/60 text-emerald-200 shadow-xl' : 'bg-rose-950/90 border border-rose-500/60 text-rose-200 shadow-xl' ?> animate-fadeIn">
        <i class="<?= $messageType === 'success' ? 'fa-solid fa-circle-check text-emerald-400 text-lg' : 'fa-solid fa-triangle-exclamation text-rose-400 text-lg' ?>"></i>
        <span class="text-xs sm:text-sm font-semibold"><?= htmlspecialchars($message) ?></span>
      </div>
    <?php endif; ?>

    <!-- TÌNH TRẠNG KẾT NỐI DATABASE -->
    <?php if (!$pdo): ?>
      <div class="p-4 rounded-2xl bg-amber-950/60 border border-amber-500/40 text-amber-200 text-xs sm:text-sm flex items-start gap-3">
        <i class="fa-solid fa-circle-info text-amber-400 text-lg mt-0.5"></i>
        <div>
          <p class="font-bold text-amber-100">Chưa kết nối Database MySQL!</p>
          <p class="mt-0.5 opacity-90">Hệ thống đang lưu tạm vào file JSON backup. Hãy mở file <code class="bg-black/40 px-1 py-0.5 rounded text-amber-300">config.php</code> và điền đúng thông tin MySQL từ InfinityFree (DB_HOST, DB_NAME, DB_USER, DB_PASS), sau đó nhập file <code class="bg-black/40 px-1 py-0.5 rounded text-amber-300">database.sql</code> qua phpMyAdmin.</p>
        </div>
      </div>
    <?php endif; ?>

    <!-- THANH ĐIỀU HƯỚNG TAB (100% GIỐNG PREVIEW) -->
    <div class="flex flex-wrap gap-2 border-b border-slate-800 pb-3">
      <button onclick="switchTab('tab-custom-links')" id="btn-tab-custom-links" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-gradient-to-r from-amber-500 to-rose-500 text-white shadow-md shadow-amber-500/20">
        <i class="fa-solid fa-link text-amber-300"></i> 0. Quản Lý Link & Nhiều Thiệp (<?= count($customCards) ?>)
      </button>

      <button onclick="switchTab('tab-doors-pin')" id="btn-tab-doors-pin" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800">
        <i class="fa-solid fa-door-open"></i> 1. Hai Cánh Cửa & Mật Khẩu PIN
      </button>

      <button onclick="switchTab('tab-wishes')" id="btn-tab-wishes" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800">
        <i class="fa-solid fa-gift"></i> 2. Tất Cả Lời Chúc (<?= count($wishes) ?>)
      </button>

      <button onclick="switchTab('tab-cat-quotes')" id="btn-tab-cat-quotes" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800">
        <i class="fa-solid fa-cat text-pink-400"></i> 3. Lời Chúc Bé Mèo 🐾
      </button>

      <button onclick="switchTab('tab-music-titles')" id="btn-tab-music-titles" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800">
        <i class="fa-solid fa-music"></i> 4. Nhạc Nền & Tiêu Đề
      </button>

      <button onclick="switchTab('tab-letter-settings')" id="btn-tab-letter-settings" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800">
        <i class="fa-solid fa-pen-nib text-rose-400"></i> 5. Tùy Chỉnh Hộp Thư 💌
      </button>

      <button onclick="switchTab('tab-event-counter')" id="btn-tab-event-counter" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800">
        <i class="fa-solid fa-hourglass-half text-amber-400"></i> 6. Đếm Ngược Mở Cửa ⏳
      </button>

      <button onclick="switchTab('tab-letters')" id="btn-tab-letters" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800 relative">
        <i class="fa-solid fa-envelope text-pink-400"></i> 7. Hộp Thư Đến 📬 (<?= count($letters) ?>)
        <?php if ($unreadLettersCount > 0): ?>
          <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
        <?php endif; ?>
      </button>

      <button onclick="switchTab('tab-security')" id="btn-tab-security" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800">
        <i class="fa-solid fa-shield-halved"></i> 8. Mật Khẩu Admin
      </button>

      <button onclick="switchTab('tab-deploy-guide')" id="btn-tab-deploy-guide" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all bg-slate-900 text-slate-300 hover:bg-slate-800">
        <i class="fa-solid fa-cloud-arrow-up text-amber-400"></i> 9. 📥 Deploy InfinityFree
      </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 0: QUẢN LÝ LINK WEBSITE & TẠO NHIỀU THIỆP RIÊNG BIỆT                   -->
    <!-- ========================================================================= -->
    <div id="tab-custom-links" class="tab-content space-y-6">
      
      <!-- BANNER GIỚI THIỆU TÍNH NĂNG TẠO LINK KHÔNG GIỚI HẠN -->
      <div class="bg-gradient-to-r from-slate-900 via-rose-950/40 to-slate-900 border border-pink-500/30 rounded-3xl p-5 sm:p-7 shadow-xl">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-400 via-rose-500 to-pink-600 flex items-center justify-center text-white text-xl shadow-lg shadow-rose-500/25 shrink-0">
              <i class="fa-solid fa-link"></i>
            </div>
            <div>
              <h2 class="text-base sm:text-lg font-bold text-pink-200">Hệ Thống Tạo Nhiều Link Thiệp Riêng Biệt (Không Giới Hạn Số Lượng)</h2>
              <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                Bạn có thể tạo bao nhiêu thiệp tùy thích! Mỗi thiệp có một đường link riêng (ví dụ: <code class="text-amber-300 font-mono font-bold"><?= htmlspecialchars($baseUrl) ?>tenthiep</code> hoặc <code class="text-amber-300 font-mono font-bold"><?= htmlspecialchars($baseUrl) ?>?card=tenthiep</code>). Mỗi link là tùy giao diện của bạn chỉnh riêng (2 ảnh cửa, mã PIN, avatar, tiêu đề, nhạc nền...). Toàn bộ hiệu ứng hoa rơi & mở cửa <strong>giữ nguyên 100%</strong> lộng lẫy!
              </p>
            </div>
          </div>

          <button type="button" onclick="openCreatorStudioForNew()" class="shrink-0 px-5 py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-rose-500 hover:brightness-110 active:scale-95 text-white text-xs sm:text-sm font-bold shadow-lg shadow-rose-500/20 flex items-center gap-2 transition-all">
            <i class="fa-solid fa-plus-circle text-base"></i>
            <span>Tạo Link Thiệp Mới Ngay</span>
          </button>
        </div>
      </div>

      <!-- DANH SÁCH TẤT CẢ LINK THIỆP ĐÃ TẠO (KHÔNG CÒN Ô FORM LẤY LINK BỊ THỪA) -->
      <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-pink-200 flex items-center gap-2">
              <i class="fa-solid fa-folder-tree text-rose-400"></i>
              Danh Sách Các Link Thiệp Của Bạn
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Hiện có <span id="custom_cards_total_count"><?= count($customCards) + 1 ?></span> đường link thiệp hoạt động độc lập (mỗi link có giao diện riêng biệt)</p>
          </div>
        </div>

        <div id="cards_grid_container" class="grid grid-cols-1 md:grid-cols-2 gap-5">

          <!-- 1. THIỆP GỐC MẶC ĐỊNH (TRANG CHỦ) -->
          <div class="bg-slate-950/90 border-2 border-pink-500/30 rounded-2xl p-5 relative flex flex-col justify-between shadow-lg">
            <div>
              <div class="flex items-center justify-between gap-2 mb-3">
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-0.5 rounded-full bg-pink-500/20 text-pink-300 border border-pink-500/30 text-[10px] font-bold uppercase tracking-wider">
                    Thiệp Mặc Định
                  </span>
                  <span class="text-[10px] text-slate-500 font-mono">Trang chủ chính</span>
                </div>
                <span class="text-xs font-mono font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-lg">
                  PIN: <?= htmlspecialchars($settings['pin_code']) ?>
                </span>
              </div>

              <h4 class="text-base font-bold text-white mb-1"><?= htmlspecialchars($settings['card_title']) ?></h4>
              <p class="text-xs text-pink-300/70 mb-4 truncate"><?= htmlspecialchars($settings['card_subtitle']) ?></p>

              <!-- Preview 2 ảnh cửa thu nhỏ -->
              <div class="flex items-center gap-2 mb-3">
                <div class="w-12 h-16 rounded-lg overflow-hidden border border-slate-800 bg-black shrink-0 relative">
                  <img src="<?= htmlspecialchars($settings['door_left_image']) ?>" alt="Cửa trái" class="w-full h-full object-cover" />
                  <span class="absolute bottom-0 inset-x-0 bg-black/60 text-[8px] text-center text-slate-300">Trái</span>
                </div>
                <div class="w-12 h-16 rounded-lg overflow-hidden border border-slate-800 bg-black shrink-0 relative">
                  <img src="<?= htmlspecialchars($settings['door_right_image']) ?>" alt="Cửa phải" class="w-full h-full object-cover" />
                  <span class="absolute bottom-0 inset-x-0 bg-black/60 text-[8px] text-center text-slate-300">Phải</span>
                </div>
                <div class="text-[11px] text-slate-400 pl-1">
                  <div>🎵 Nhạc: <span class="text-pink-300"><?= htmlspecialchars($settings['bg_music_type'] === 'file' ? ($settings['bg_music_file'] ?: '1.mp3') : 'Online URL') ?></span></div>
                  <div>💌 Lời chúc: <span class="text-pink-300"><?= count($wishes) ?> lời chúc</span></div>
                </div>
              </div>

              <!-- Link URL box -->
              <div class="bg-black/50 border border-slate-800 rounded-xl p-2.5 mb-4">
                <div class="text-[10px] text-slate-400 uppercase font-semibold mb-1">Link Trang Chủ:</div>
                <div class="font-mono text-xs text-amber-300 truncate select-all">
                  <?= htmlspecialchars($baseUrl) ?>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 pt-3 border-t border-slate-800/80">
              <button type="button" onclick="copyCardLink('<?= htmlspecialchars($baseUrl) ?>', this)" class="flex-1 py-2 px-3 rounded-xl bg-pink-500/20 hover:bg-pink-500/30 text-pink-200 text-xs font-bold flex items-center justify-center gap-1.5 transition-all">
                <i class="fa-solid fa-copy"></i> <span>Copy Link</span>
              </button>
              <a href="<?= htmlspecialchars($baseUrl) ?>" target="_blank" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem Thử
              </a>
            </div>
          </div>

          <!-- 2. CÁC THIỆP TÙY CHỈNH THEO LINK RIÊNG -->
          <?php foreach ($customCards as $card): 
            $cSlug = $card['slug'];
            $cTitle = $card['title'];
            $cPin = $card['settings']['pin_code'] ?? '1234';
            $cCardTitle = $card['settings']['card_title'] ?? $cTitle;
            $cCardSub = $card['settings']['card_subtitle'] ?? '';
            $directUrl = $baseUrl . '?card=' . urlencode($cSlug);
            $cleanUrl = $baseUrl . urlencode($cSlug);
            $cDoorLeft = !empty($card['settings']['door_left_image']) ? $card['settings']['door_left_image'] : $settings['door_left_image'];
            $cDoorRight = !empty($card['settings']['door_right_image']) ? $card['settings']['door_right_image'] : $settings['door_right_image'];
            $cWishesCount = !empty($card['wishes']) && is_array($card['wishes']) ? count($card['wishes']) : count($wishes);
          ?>
            <div id="card_box_<?= (int)$card['id'] ?>" class="bg-slate-950/80 border border-slate-800 hover:border-pink-500/40 rounded-2xl p-5 relative flex flex-col justify-between shadow-lg transition-all">
              <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                  <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Hoạt động
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono font-bold">/<?= htmlspecialchars($cSlug) ?></span>
                  </div>
                  <span class="text-xs font-mono font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-lg" title="Mật khẩu PIN mở cửa riêng của thiệp này">
                    PIN: <?= htmlspecialchars($cPin) ?>
                  </span>
                </div>

                <h4 class="text-base font-bold text-pink-100 mb-1"><?= htmlspecialchars($cCardTitle) ?></h4>
                <p class="text-xs text-pink-300/70 mb-3 truncate"><?= $cCardSub ? htmlspecialchars($cCardSub) : 'Giao diện tùy chỉnh riêng' ?></p>

                <!-- 2 Ảnh Cửa Thu Nhỏ -->
                <div class="flex items-center gap-2 mb-3">
                  <div class="w-12 h-16 rounded-lg overflow-hidden border border-slate-800 bg-black shrink-0 relative">
                    <img src="<?= htmlspecialchars($cDoorLeft) ?>" alt="Cửa trái" class="w-full h-full object-cover" />
                    <span class="absolute bottom-0 inset-x-0 bg-black/60 text-[8px] text-center text-slate-300">Trái</span>
                  </div>
                  <div class="w-12 h-16 rounded-lg overflow-hidden border border-slate-800 bg-black shrink-0 relative">
                    <img src="<?= htmlspecialchars($cDoorRight) ?>" alt="Cửa phải" class="w-full h-full object-cover" />
                    <span class="absolute bottom-0 inset-x-0 bg-black/60 text-[8px] text-center text-slate-300">Phải</span>
                  </div>
                  <div class="text-[11px] text-slate-400 pl-1">
                    <div>📅 Tạo: <span class="text-slate-300"><?= htmlspecialchars($card['created_at']) ?></span></div>
                    <div>💌 Lời chúc: <span class="text-pink-300"><?= $cWishesCount ?> lời chúc</span></div>
                  </div>
                </div>

                <!-- Link URLs -->
                <div class="space-y-2 mb-4">
                  <div class="bg-black/50 border border-slate-800/90 rounded-xl p-2">
                    <div class="text-[9px] text-slate-400 uppercase font-bold flex items-center justify-between">
                      <span>Link Chuẩn (Mọi hosting PHP):</span>
                      <span class="text-emerald-400 text-[10px]">Khuyên Dùng ✨</span>
                    </div>
                    <div class="font-mono text-xs text-amber-300 truncate select-all mt-0.5">
                      <?= htmlspecialchars($directUrl) ?>
                    </div>
                  </div>

                  <div class="bg-black/30 border border-slate-800/60 rounded-xl p-2">
                    <div class="text-[9px] text-slate-400 uppercase font-bold">Link Ngắn (Qua file .htaccess):</div>
                    <div class="font-mono text-xs text-slate-300 truncate select-all mt-0.5">
                      <?= htmlspecialchars($cleanUrl) ?>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action buttons -->
              <div class="pt-3 border-t border-slate-800/80 flex items-center gap-1.5 flex-wrap">
                <button type="button" onclick="copyCardLink('<?= htmlspecialchars($cleanUrl) ?>', this)" class="flex-1 py-2 px-3 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition-all shadow-md shadow-pink-500/15">
                  <i class="fa-solid fa-copy"></i> <span>Copy Link</span>
                </button>

                <a href="<?= htmlspecialchars($directUrl) ?>" target="_blank" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1 transition-all" title="Mở xem thử thiệp này">
                  <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem
                </a>

                <button type="button" onclick='openCreatorStudioForEdit(<?= json_encode($card, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)' class="py-2 px-2.5 rounded-xl bg-gradient-to-r from-amber-500/20 to-rose-500/20 hover:from-amber-500/30 hover:to-rose-500/30 text-amber-300 text-xs font-bold transition-all border border-amber-500/30 flex items-center gap-1" title="Mở bảng chỉnh sửa tất cả các bước & lấy link thiệp này">
                  <i class="fa-solid fa-wand-magic-sparkles"></i> <span>Sửa Các Bước</span>
                </button>

                <button type="button" onclick="deleteCard(<?= (int)$card['id'] ?>, '<?= htmlspecialchars(addslashes($cTitle)) ?>')" class="py-2 px-2.5 rounded-xl bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 text-xs font-semibold transition-all flex items-center gap-1" title="Xóa link thiệp này">
                  <i class="fa-solid fa-trash-can"></i> <span>Xóa</span>
                </button>
              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: CHỈNH ẢNH 2 CÁNH CỬA & MẬT KHẨU PIN & ẢNH MODAL MẬT KHẨU             -->
    <!-- ========================================================================= -->
    <div id="tab-doors-pin" class="tab-content hidden space-y-6">
      <form method="POST" action="" class="space-y-6">
        <input type="hidden" name="action" value="update_settings" />

        <!-- KHỐI 1: ẢNH 2 CÁNH CỬA -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-base sm:text-lg font-bold text-pink-200 flex items-center gap-2">
                <i class="fa-solid fa-image text-rose-400"></i> Ảnh 2 Cánh Cửa (Mở Trái & Mở Phải)
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Dán đường dẫn URL ảnh hoặc ảnh bạn đã upload (khuyên dùng tỷ lệ đứng ~9:16)</p>
            </div>
            <button type="button" onclick="resetDefaultDoorImages()" class="text-xs text-pink-400 hover:text-pink-300 underline font-medium">
              Khôi phục ảnh mẫu
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Cánh cửa trái -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col justify-between">
              <div>
                <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-2">
                  <i class="fa-solid fa-arrow-left mr-1"></i> Ảnh Cánh Cửa Trái (Left Door)
                </label>
                <input type="url" id="door_left_input" name="door_left_image" required
                       value="<?= htmlspecialchars($settings['door_left_image']) ?>"
                       oninput="updateImagePreview('door_left_input', 'door_left_preview')"
                       placeholder="https://..."
                       class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-mono" />
              </div>
              <div class="mt-4">
                <div class="text-[11px] text-slate-400 mb-1.5 font-medium">Xem trước cánh cửa trái:</div>
                <div class="w-full h-48 sm:h-64 rounded-xl overflow-hidden border border-amber-400/40 relative bg-black/40">
                  <img id="door_left_preview" src="<?= htmlspecialchars($settings['door_left_image']) ?>"
                       alt="Cửa trái" class="w-full h-full object-cover object-center"
                       onerror="this.src='https://via.placeholder.com/300x500?text=Loi+Anh'" />
                  <span class="absolute bottom-2 left-2 bg-black/60 px-2 py-0.5 rounded text-[10px] text-amber-300">Cửa Trái</span>
                </div>
              </div>
            </div>

            <!-- Cánh cửa phải -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col justify-between">
              <div>
                <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-2">
                  <i class="fa-solid fa-arrow-right mr-1"></i> Ảnh Cánh Cửa Phải (Right Door)
                </label>
                <input type="url" id="door_right_input" name="door_right_image" required
                       value="<?= htmlspecialchars($settings['door_right_image']) ?>"
                       oninput="updateImagePreview('door_right_input', 'door_right_preview')"
                       placeholder="https://..."
                       class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-mono" />
              </div>
              <div class="mt-4">
                <div class="text-[11px] text-slate-400 mb-1.5 font-medium">Xem trước cánh cửa phải:</div>
                <div class="w-full h-48 sm:h-64 rounded-xl overflow-hidden border border-amber-400/40 relative bg-black/40">
                  <img id="door_right_preview" src="<?= htmlspecialchars($settings['door_right_image']) ?>"
                       alt="Cửa phải" class="w-full h-full object-cover object-center"
                       onerror="this.src='https://via.placeholder.com/300x500?text=Loi+Anh'" />
                  <span class="absolute bottom-2 right-2 bg-black/60 px-2 py-0.5 rounded text-[10px] text-amber-300">Cửa Phải</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- KHỐI 2: MẬT KHẨU PIN & ẢNH MODAL NHẬP MẬT KHẨU -->
        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl">
          <div class="mb-4">
            <h2 class="text-base sm:text-lg font-bold text-pink-200 flex items-center gap-2">
              <i class="fa-solid fa-key text-amber-400"></i> Cài Đặt Mật Khẩu PIN & Ảnh Trang Mật Khẩu
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Tùy chỉnh mã PIN 4 số mở cửa và ảnh avatar tròn trên cửa sổ nhập mã</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Mã PIN mở cửa -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4">
              <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-2">
                Mật Khẩu PIN Mở Cửa (4 Số)
              </label>
              <div class="relative">
                <input type="text" name="pin_code" maxlength="6" required
                       value="<?= htmlspecialchars($settings['pin_code']) ?>"
                       placeholder="1234"
                       class="w-full px-4 py-3 bg-slate-900 border-2 border-pink-400/40 rounded-xl text-xl sm:text-2xl font-bold tracking-widest text-center text-pink-300 focus:outline-none focus:border-pink-400 font-mono" />
              </div>
              <p class="text-[11px] text-slate-400 mt-2">
                <i class="fa-solid fa-circle-info text-pink-400 mr-1"></i> Người nhận chỉ cần bấm 4 số này trên bàn phím là 2 cánh cửa sẽ mở ra!
              </p>
            </div>

            <!-- Ảnh Avatar tròn trên modal mật khẩu -->
            <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 md:col-span-2">
              <div class="flex flex-col sm:flex-row gap-4 items-center">
                <div class="flex-shrink-0 text-center">
                  <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden border-2 border-pink-400 shadow-lg shadow-pink-500/30 mx-auto bg-black/40">
                    <img id="pin_avatar_preview" src="<?= htmlspecialchars($settings['pin_avatar_image']) ?>"
                         alt="Avatar PIN" class="w-full h-full object-cover"
                         onerror="this.src='https://via.placeholder.com/150?text=Avatar'" />
                  </div>
                  <span class="text-[10px] text-pink-300/70 mt-1 block">Avatar Modal PIN</span>
                </div>

                <div class="flex-1 w-full space-y-3">
                  <div>
                    <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">
                      Link Ảnh Đại Diện Trang Mật Khẩu (URL)
                    </label>
                    <input type="url" id="pin_avatar_input" name="pin_avatar_image" required
                           value="<?= htmlspecialchars($settings['pin_avatar_image']) ?>"
                           oninput="updateImagePreview('pin_avatar_input', 'pin_avatar_preview')"
                           placeholder="https://..."
                           class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-mono" />
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="block text-[11px] font-semibold text-slate-300 mb-1">Tiêu Đề Modal PIN</label>
                      <input type="text" name="pin_title"
                             value="<?= htmlspecialchars($settings['pin_title']) ?>"
                             placeholder="Mật Khẩu Bí Mật ~"
                             class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-white" />
                    </div>
                    <div>
                      <label class="block text-[11px] font-semibold text-slate-300 mb-1">Phụ Đề Hướng Dẫn</label>
                      <input type="text" name="pin_subtitle"
                             value="<?= htmlspecialchars($settings['pin_subtitle']) ?>"
                             placeholder="Nhập mã bí mật 4 số để mở cửa"
                             class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-white" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- NÚT LƯU THAY ĐỔI -->
        <div class="flex justify-end">
          <button type="submit" class="px-6 py-3 bg-gradient-to-r from-rose-500 via-pink-500 to-amber-500 hover:from-rose-600 hover:to-amber-600 text-white font-bold rounded-2xl shadow-lg shadow-pink-500/25 flex items-center gap-2 hover:scale-[1.01] active:scale-[0.99] transition-all">
            <i class="fa-solid fa-floppy-disk"></i> Lưu Cài Đặt Cánh Cửa & Mật Khẩu
          </button>
        </div>
      </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: CHỈNH ALL LỜI CHÚC & CHỈNH ẢNH LỜI CHÚC                             -->
    <!-- ========================================================================= -->
    <div id="tab-wishes" class="tab-content hidden space-y-6">

      <!-- THANH CÔNG CỤ QUẢN LÝ LỜI CHÚC -->
      <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-base sm:text-lg font-bold text-pink-200 flex items-center gap-2">
            <i class="fa-solid fa-gift text-pink-400"></i> Quản Lý Danh Sách Tất Cả Lời Chúc (<?= count($wishes) ?>)
          </h2>
          <p class="text-xs text-slate-400 mt-0.5">
            Khi người nhận chạm vào hộp quà hoặc phong thư rơi, thiệp sẽ mở ngẫu nhiên từng lời chúc này
          </p>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <button type="button" onclick="openAddWishModal()"
                  class="flex-1 sm:flex-none px-4 py-2.5 bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white text-xs sm:text-sm font-bold rounded-xl shadow-md shadow-pink-500/20 flex items-center justify-center gap-2 transition-all">
            <i class="fa-solid fa-plus"></i> Thêm Lời Chúc Mới
          </button>
          <form method="POST" action="" onsubmit="return confirm('Bạn có chắc muốn khôi phục 8 lời chúc mẫu ban đầu không?');">
            <input type="hidden" name="action" value="reset_default_wishes" />
            <button type="submit" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-all" title="Khôi phục 8 lời chúc mẫu">
              <i class="fa-solid fa-rotate-left"></i> Khôi phục mẫu
            </button>
          </form>
        </div>
      </div>

      <!-- DANH SÁCH TẤT CẢ LỜI CHÚC DẠNG GRID -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($wishes as $index => $w): ?>
          <div class="bg-slate-900/80 border border-slate-800 hover:border-pink-500/40 rounded-2xl p-4 shadow-lg flex flex-col justify-between transition-all group">
            <div>
              <!-- Phần Header Thẻ -->
              <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-800/80">
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-pink-500/20 text-pink-300 border border-pink-400/30">
                  #<?= htmlspecialchars($w['sort_order'] ?? ($index + 1)) ?>: <?= htmlspecialchars($w['recipient']) ?>
                </span>
                <span class="text-[10px] text-slate-500">ID: <?= $w['id'] ?></span>
              </div>

              <!-- Ảnh Lời Chúc -->
              <div class="w-full h-40 rounded-xl overflow-hidden border border-slate-800 bg-black/40 relative mb-3">
                <img src="<?= htmlspecialchars($w['img']) ?>"
                     alt="<?= htmlspecialchars($w['recipient']) ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                     onerror="this.src='https://via.placeholder.com/300x200?text=Loi+Anh'" />
              </div>

              <!-- Nội dung lời chúc -->
              <p class="text-xs sm:text-sm text-slate-200 line-clamp-3 leading-relaxed mb-4">
                "<?= htmlspecialchars($w['msg']) ?>"
              </p>
            </div>

            <!-- Các nút hành động: Sửa & Xóa -->
            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between gap-2">
              <button type="button"
                      onclick="openEditWishModal(<?= htmlspecialchars(json_encode($w), ENT_QUOTES, 'UTF-8') ?>)"
                      class="flex-1 py-1.5 px-3 rounded-lg bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors">
                <i class="fa-solid fa-pen-to-square"></i> Chỉnh Sửa
              </button>

              <form method="POST" action="" onsubmit="return confirm('Bạn có chắc muốn xóa lời chúc này?');">
                <input type="hidden" name="action" value="delete_wish" />
                <input type="hidden" name="wish_id" value="<?= $w['id'] ?>" />
                <button type="submit" class="py-1.5 px-3 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 text-xs font-semibold flex items-center gap-1 transition-colors" title="Xóa lời chúc">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: NHẠC NỀN & TIÊU ĐỀ THIỆP                                            -->
    <!-- ========================================================================= -->
    <div id="tab-music-titles" class="tab-content hidden space-y-6">
      <form method="POST" action="" class="space-y-6" id="musicTitlesForm">
        <input type="hidden" name="action" value="update_settings" />
        <input type="hidden" name="current_tab" value="tab-music-titles" />
        <!-- Giữ lại cấu hình cửa và pin hiện tại khi submit tab này -->
        <input type="hidden" name="door_left_image" value="<?= htmlspecialchars($settings['door_left_image']) ?>" />
        <input type="hidden" name="door_right_image" value="<?= htmlspecialchars($settings['door_right_image']) ?>" />
        <input type="hidden" name="pin_code" value="<?= htmlspecialchars($settings['pin_code']) ?>" />
        <input type="hidden" name="pin_avatar_image" value="<?= htmlspecialchars($settings['pin_avatar_image']) ?>" />
        <input type="hidden" name="pin_title" value="<?= htmlspecialchars($settings['pin_title']) ?>" />
        <input type="hidden" name="pin_subtitle" value="<?= htmlspecialchars($settings['pin_subtitle']) ?>" />

        <!-- Hidden input điều khiển nguồn nhạc và giá trị cuối cùng tránh xung đột -->
        <input type="hidden" id="bg_music_type" name="bg_music_type" value="<?= htmlspecialchars($settings['bg_music_type'] ?? 'file') ?>" />
        <input type="hidden" id="bg_music_url_final" name="bg_music_url" value="<?= htmlspecialchars($settings['bg_music_url']) ?>" />

        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
            <div>
              <h2 class="text-base sm:text-lg font-bold text-pink-200 flex items-center gap-2">
                <i class="fa-solid fa-music text-amber-400"></i> Cài Đặt Nhạc Nền MP3 & Tiêu Đề Thiệp
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Hỗ trợ 2 phương thức: Dùng File MP3 có sẵn hoặc Dán Link URL Online mà không bị xung đột</p>
            </div>

            <!-- Trạng thái nguồn đang kích hoạt -->
            <div id="activeSourceBadge" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-pink-500/20 border border-pink-400/40 text-pink-300 text-xs font-bold">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span id="activeSourceBadgeText"><?= ($settings['bg_music_type'] ?? 'file') === 'url' ? 'Đang dùng: Link URL Online' : 'Đang dùng: File MP3 Nội Bộ' ?></span>
            </div>
          </div>

          <!-- ================= CHỌN PHƯƠNG THỨC NGUỒN PHÁT NHẠC ================= -->
          <div>
            <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-3">
              1. Chọn Phương Thức Nguồn Phát Nhạc Cho Thiệp:
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Nút Nguồn 1: File MP3 Nội Bộ -->
              <button type="button" onclick="selectMusicSourceType('file')" id="sourceBtnFile"
                      class="p-4 rounded-2xl border text-left transition-all relative <?= ($settings['bg_music_type'] ?? 'file') === 'file' ? 'bg-gradient-to-r from-pink-950/70 to-rose-950/70 border-pink-500 ring-2 ring-pink-500/30' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' ?>">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-pink-500/20 text-pink-300 flex items-center justify-center text-sm font-bold">
                      <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <span class="text-sm font-bold text-white">Nguồn 1: File MP3 Trên Hosting</span>
                  </div>
                  <span id="fileCheckIcon" class="<?= ($settings['bg_music_type'] ?? 'file') === 'file' ? 'text-pink-400' : 'text-slate-600' ?>">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                  </span>
                </div>
                <p class="text-[11px] text-slate-400 leading-relaxed">
                  Tải file <code class="text-pink-300 font-bold">.mp3</code> lên thư mục gốc <code class="text-pink-300">htdocs</code> và nhập tên file (ví dụ: <code class="text-amber-300">1.mp3</code>).
                </p>
              </button>

              <!-- Nút Nguồn 2: Link URL Online Trực Tiếp -->
              <button type="button" onclick="selectMusicSourceType('url')" id="sourceBtnUrl"
                      class="p-4 rounded-2xl border text-left transition-all relative <?= ($settings['bg_music_type'] ?? 'file') === 'url' ? 'bg-gradient-to-r from-pink-950/70 to-rose-950/70 border-pink-500 ring-2 ring-pink-500/30' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' ?>">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center text-sm font-bold">
                      <i class="fa-solid fa-globe"></i>
                    </div>
                    <span class="text-sm font-bold text-white">Nguồn 2: Dán Link URL Nhạc Online</span>
                  </div>
                  <span id="urlCheckIcon" class="<?= ($settings['bg_music_type'] ?? 'file') === 'url' ? 'text-pink-400' : 'text-slate-600' ?>">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                  </span>
                </div>
                <p class="text-[11px] text-slate-400 leading-relaxed">
                  Dán đường dẫn trực tiếp (Link Direct <code class="text-pink-300">https://...mp3</code>) từ Zing, Cloudinary, Drive hoặc kho nhạc online.
                </p>
              </button>
            </div>
          </div>

          <!-- ================= CHI TIẾT 2 NGUỒN NHẠC (ĐỘC LẬP, KHÔNG XUNG ĐỘT) ================= -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- CỘT 1: CẤU HÌNH FILE NỘI BỘ (HOSTING) -->
            <div id="fileSourceCard" class="bg-slate-950/70 border <?= ($settings['bg_music_type'] ?? 'file') === 'file' ? 'border-pink-500/40 ring-1 ring-pink-500/20' : 'border-slate-800/80 opacity-75' ?> rounded-2xl p-5 space-y-4 transition-all">
              <div class="flex items-center justify-between">
                <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-file-audio text-pink-400"></i> Tên File MP3 Trên Hosting:
                </label>
                <?php if (($settings['bg_music_type'] ?? 'file') === 'file'): ?>
                  <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold">Đang Chọn</span>
                <?php endif; ?>
              </div>

              <div>
                <input type="text" id="bg_music_file" name="bg_music_file"
                       value="<?= htmlspecialchars($settings['bg_music_file'] ?? '1.mp3') ?>"
                       oninput="onFileParamChange()"
                       placeholder="1.mp3"
                       class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-mono" />
              </div>

              <!-- Chọn nhanh file có sẵn -->
              <div>
                <span class="text-[11px] text-slate-400 block mb-1.5">Chọn nhanh tên file phổ biến:</span>
                <div class="flex flex-wrap gap-2">
                  <button type="button" onclick="setPresetFile('1.mp3')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-mono transition-all">
                    🎵 1.mp3 (Mặc định)
                  </button>
                  <button type="button" onclick="setPresetFile('2.mp3')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-mono transition-all">
                    🎵 2.mp3
                  </button>
                  <button type="button" onclick="setPresetFile('music.mp3')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-mono transition-all">
                    🎵 music.mp3
                  </button>
                </div>
              </div>

              <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-[11px] text-slate-400 space-y-1">
                <p class="text-amber-300 font-semibold flex items-center gap-1">
                  <i class="fa-solid fa-lightbulb"></i> Mẹo tải file lên InfinityFree:
                </p>
                <p>Mở File Manager / FTP trên InfinityFree -> vào thư mục <code class="text-pink-300">htdocs</code> -> Tải file nhạc của bạn lên và đặt tên là <code class="text-pink-300">1.mp3</code>.</p>
              </div>
            </div>

            <!-- CỘT 2: CẤU HÌNH LINK URL ONLINE -->
            <div id="urlSourceCard" class="bg-slate-950/70 border <?= ($settings['bg_music_type'] ?? 'file') === 'url' ? 'border-pink-500/40 ring-1 ring-pink-500/20' : 'border-slate-800/80 opacity-75' ?> rounded-2xl p-5 space-y-4 transition-all">
              <div class="flex items-center justify-between">
                <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-link text-amber-400"></i> Link URL Trực Tiếp (Online Direct MP3):
                </label>
                <?php if (($settings['bg_music_type'] ?? 'file') === 'url'): ?>
                  <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold">Đang Chọn</span>
                <?php endif; ?>
              </div>

              <div>
                <input type="url" id="bg_music_online_url" name="bg_music_online_url"
                       value="<?= htmlspecialchars($settings['bg_music_online_url'] ?? '') ?>"
                       oninput="onUrlParamChange()"
                       placeholder="https://domain.com/path/to/song.mp3"
                       class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-mono" />
              </div>

              <!-- Gợi ý link nhạc online chất lượng cao mẫu -->
              <div>
                <span class="text-[11px] text-slate-400 block mb-1.5">Gợi ý nhạc online lãng mạn (Bấm để chọn):</span>
                <div class="space-y-1.5">
                  <div class="flex items-center justify-between p-2 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-pink-500/40 text-xs transition-all">
                    <div class="truncate mr-2">
                      <span class="font-semibold text-pink-200">🌸 Piano Romantic Moments</span>
                      <span class="text-[10px] text-slate-400 block truncate">Bản hòa tấu piano êm dịu, ngọt ngào</span>
                    </div>
                    <button type="button" onclick="applySampleMusicUrl('https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3', 'Piano Romantic Moments')"
                            class="flex-shrink-0 px-2.5 py-1 rounded-lg bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 font-semibold text-[11px]">
                      Dùng link này
                    </button>
                  </div>

                  <div class="flex items-center justify-between p-2 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-pink-500/40 text-xs transition-all">
                    <div class="truncate mr-2">
                      <span class="font-semibold text-pink-200">💖 Sweet Love Acoustic Guitar</span>
                      <span class="text-[10px] text-slate-400 block truncate">Guitar mộc mạc, nhẹ nhàng, sâu lắng</span>
                    </div>
                    <button type="button" onclick="applySampleMusicUrl('https://assets.mixkit.co/music/preview/mixkit-sweet-love-121.mp3', 'Sweet Love Acoustic Guitar')"
                            class="flex-shrink-0 px-2.5 py-1 rounded-lg bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 font-semibold text-[11px]">
                      Dùng link này
                    </button>
                  </div>

                  <div class="flex items-center justify-between p-2 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-pink-500/40 text-xs transition-all">
                    <div class="truncate mr-2">
                      <span class="font-semibold text-pink-200">🌼 Serene Garden Melodies</span>
                      <span class="text-[10px] text-slate-400 block truncate">Giai điệu vui tươi, trong trẻo ngày lễ</span>
                    </div>
                    <button type="button" onclick="applySampleMusicUrl('https://assets.mixkit.co/music/preview/mixkit-serene-view-443.mp3', 'Serene Garden Melodies')"
                            class="flex-shrink-0 px-2.5 py-1 rounded-lg bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 font-semibold text-[11px]">
                      Dùng link này
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ================= BỘ PHÁT NGHE THỬ ÂM THANH TRỰC QUAN ================= -->
          <div class="bg-slate-950/80 border border-pink-500/30 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-amber-400 flex items-center justify-center text-white text-xl shadow-lg shadow-rose-500/30 flex-shrink-0">
                <i id="playerIcon" class="fa-solid fa-music"></i>
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xs sm:text-sm font-bold text-white">Nghe Thử Bài Hát Đang Chọn:</span>
                  <span id="playerStatus" class="text-[11px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">Sẵn sàng</span>
                </div>
                <p id="playerCurrentTrack" class="text-xs font-mono text-pink-300 mt-0.5 truncate max-w-[280px] sm:max-w-md">
                  <?= htmlspecialchars($settings['bg_music_url']) ?>
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2.5 self-end sm:self-center">
              <audio id="adminAudioPreview" preload="none" onended="onAudioEnded()" onerror="onAudioError()" onplay="onAudioPlay()" onpause="onAudioPause()"></audio>
              <button type="button" id="btnPlayPreview" onclick="toggleAudioPreview()"
                      class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white text-xs font-bold shadow-md shadow-pink-500/20 flex items-center gap-2 transition-all">
                <i id="btnPlayPreviewIcon" class="fa-solid fa-play"></i>
                <span id="btnPlayPreviewText">Nghe Thử</span>
              </button>
            </div>
          </div>

          <!-- ================= CÁC Ô TIÊU ĐỀ THIỆP ================= -->
          <div class="pt-4 border-t border-slate-800">
            <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-4">
              2. Cài Đặt Tiêu Đề & Phụ Đề Thiệp:
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Tiêu Đề Chính -->
              <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4">
                <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1.5">
                  Tiêu Đề Chính Trên Thiệp
                </label>
                <input type="text" name="card_title" required
                       value="<?= htmlspecialchars($settings['card_title']) ?>"
                       placeholder="Chúc Mừng Một Nửa Thế Giới"
                       class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-comfortaa font-bold" />
              </div>

              <!-- Phụ Đề Dưới -->
              <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4">
                <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1.5">
                  Phụ Đề Dưới Tiêu Đề
                </label>
                <input type="text" name="card_subtitle" required
                       value="<?= htmlspecialchars($settings['card_subtitle']) ?>"
                       placeholder="Rạng rỡ, hạnh phúc & tràn đầy yêu thương"
                       class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-rose-500 to-amber-500 text-white font-bold rounded-xl shadow-lg flex items-center gap-2 hover:scale-[1.01] transition-all">
              <i class="fa-solid fa-floppy-disk"></i> Lưu Nhạc & Tiêu Đề
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: CHỈNH LỜI CHÚC KHI BẤM VÀO BÉ MÈO                                   -->
    <!-- ========================================================================= -->
    <div id="tab-cat-quotes" class="tab-content hidden space-y-6">
      <form method="POST" action="" class="space-y-6">
        <input type="hidden" name="action" value="update_cat_quotes" />

        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h2 class="text-base sm:text-lg font-bold text-pink-200 flex items-center gap-2">
                <i class="fa-solid fa-cat text-rose-400"></i> Lời Chúc Khi Nhấn Vào Bé Mèo Bắt Bướm
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">
                Mỗi dòng là một câu chúc riêng biệt. Khi người nhận bấm vào bé mèo nhảy trên thiệp, bé mèo sẽ kêu "Meow" và hiển thị ngẫu nhiên một câu chúc!
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" onclick="insertSampleCatQuote()" class="px-3 py-1.5 bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 text-xs font-semibold rounded-xl transition-all">
                <i class="fa-solid fa-plus mr-1"></i> Thêm câu mẫu
              </button>
              <button type="button" onclick="resetDefaultCatQuotes()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition-all">
                <i class="fa-solid fa-rotate-left mr-1"></i> Mặc định
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Cột soạn thảo nội dung -->
            <div class="lg:col-span-7 bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col justify-between">
              <div>
                <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-2">
                  Danh Sách Câu Chúc Của Bé Mèo (Mỗi dòng 1 câu):
                </label>
                <textarea id="cat_quotes_textarea" name="cat_quotes" rows="8" required
                          oninput="updateCatPreview()"
                          placeholder="Nhập mỗi dòng một câu chúc..."
                          class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-pink-100 font-sans leading-relaxed focus:outline-none focus:ring-2 focus:ring-pink-400 shadow-inner"><?= htmlspecialchars($settings['cat_quotes'] ?? '') ?></textarea>
                <p class="text-[11px] text-slate-400 mt-2">
                  <i class="fa-solid fa-lightbulb text-amber-400 mr-1"></i> Bạn có thể thêm icon emoji (🌸, 💖, 🐾, ✨) để câu chúc của bé mèo thêm dễ thương và sống động!
                </p>
              </div>
            </div>

            <!-- Cột xem trước hoạt họa bé mèo -->
            <div class="lg:col-span-5 bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 flex flex-col items-center justify-center text-center relative overflow-hidden min-h-[220px]">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 block self-start">
                <i class="fa-solid fa-eye text-pink-400 mr-1"></i> Xem trước hiển thị trên thiệp:
              </span>
              
              <!-- Bong bóng lời chúc của mèo -->
              <div id="cat_preview_bubble" class="max-w-[280px] bg-gradient-to-r from-rose-500/90 to-pink-500/90 text-white text-xs font-semibold py-2.5 px-3.5 rounded-2xl shadow-xl shadow-pink-500/20 mb-3 border border-pink-300/40 relative transform transition-all duration-300 animate-bounce">
                <?= htmlspecialchars(explode("\n", $settings['cat_quotes'] ?? 'Meo meo~ Chúc một ngày thật rực rỡ và hạnh phúc! 🐾💖')[0]) ?>
              </div>

              <!-- Hình bé mèo -->
              <div class="w-16 h-16 sm:w-20 sm:h-20">
                <img src="https://panbap.github.io/WomensDay/assets/mewmew.gif" alt="Bé mèo" class="w-full h-full object-contain filter drop-shadow-[0_5px_10px_rgba(244,63,94,0.4)]" />
              </div>
              <p class="text-[10px] text-pink-300/70 mt-2">Bấm lưu bên dưới để cập nhật ngay vào thiệp</p>
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-rose-500 to-amber-500 hover:from-rose-600 hover:to-amber-600 text-white font-bold rounded-xl shadow-lg flex items-center gap-2 hover:scale-[1.01] transition-all text-xs sm:text-sm">
              <i class="fa-solid fa-floppy-disk"></i> Lưu Lời Chúc Của Bé Mèo
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 4: BẢO MẬT & MẬT KHẨU ADMIN (CHỈ ĐỔI TRONG CODE THEO YÊU CẦU)          -->
    <!-- ========================================================================= -->
    <div id="tab-security" class="tab-content hidden space-y-6">
      <div class="max-w-2xl mx-auto bg-slate-900/70 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl">
        <div class="flex items-center gap-3.5 mb-6 pb-4 border-b border-slate-800">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-amber-400 flex items-center justify-center text-white text-xl shadow-lg shadow-rose-500/20">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div>
            <h2 class="text-base sm:text-lg font-bold text-pink-200">Mật Khẩu Quản Trị Hệ Thống</h2>
            <p class="text-xs text-slate-400">Bảo mật tuyệt đối - Chỉ đổi được trong file mã nguồn</p>
          </div>
        </div>

        <div class="space-y-4">
          <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex items-center justify-between">
            <div>
              <span class="text-xs text-slate-400 block mb-0.5">Mật khẩu admin hiện tại:</span>
              <span class="text-2xl font-mono font-bold tracking-widest text-pink-300">123456</span>
            </div>
            <span class="px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs font-semibold flex items-center gap-1.5">
              <i class="fa-solid fa-circle-check"></i> Đang hoạt động
            </span>
          </div>

          <div class="p-4 rounded-2xl bg-amber-950/40 border border-amber-500/30 text-amber-200/90 text-xs sm:text-sm space-y-2">
            <div class="flex items-center gap-2 font-bold text-amber-100">
              <i class="fa-solid fa-code text-amber-400"></i> Hướng Dẫn Đổi Mật Khẩu Trong Code:
            </div>
            <p class="leading-relaxed">
              Theo quy tắc bảo mật của bạn, mật khẩu admin không thể bị đổi từ giao diện web. Để đổi mật khẩu, bạn chỉ cần mở file <code class="bg-black/50 px-1.5 py-0.5 rounded text-pink-300 font-mono">config.php</code> và sửa dòng số 21:
            </p>
            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 font-mono text-xs text-pink-200 overflow-x-auto select-all">
              define('ADMIN_PASSWORD', '123456'); // Thay 123456 bằng mật khẩu mới của bạn
            </div>
            <p class="text-[11px] text-amber-300/80">
              Sau khi lưu file <code class="font-mono text-pink-300">config.php</code>, mật khẩu mới sẽ có hiệu lực tức thì!
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 5: TÙY CHỈNH TẤT CẢ CHỮ TRONG HỘP THƯ YÊU THƯƠNG                       -->
    <!-- ========================================================================= -->
    <div id="tab-letter-settings" class="tab-content hidden space-y-6">
      <form method="POST" action="" class="space-y-6">
        <input type="hidden" name="action" value="update_letter_settings" />

        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-pink-500 to-rose-600 flex items-center justify-center text-white text-xl shadow-lg shadow-pink-500/20">
                <i class="fa-solid fa-pen-nib"></i>
              </div>
              <div>
                <h2 class="text-base sm:text-lg font-bold text-pink-200">Tùy Chỉnh Toàn Bộ Chữ Trong Hộp Thư Yêu Thương</h2>
                <p class="text-xs text-slate-400 mt-0.5">Thay đổi linh hoạt tiêu đề, nhãn ô nhập, placeholder gợi ý, chữ trên nút gửi và câu chúc của bé mèo</p>
              </div>
            </div>
            <button type="button" onclick="resetDefaultLetterBoxTexts()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition-all self-start sm:self-auto flex items-center gap-1.5">
              <i class="fa-solid fa-rotate-left"></i> Khôi phục chữ mẫu
            </button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- CỘT BÊN TRÁI: CÁC TRƯỜNG NHẬP CHỮ -->
            <div class="lg:col-span-7 space-y-4">
              <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-4">
                <h3 class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-heading text-rose-400"></i> 1. Tiêu Đề & Lời Dẫn Hộp Thư:
                </h3>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Tiêu Đề Badge Hộp Thư:</label>
                  <input type="text" id="input_letter_modal_title" name="letter_modal_title" required
                         value="<?= htmlspecialchars($settings['letter_modal_title'] ?? 'Hộp Thư Yêu Thương') ?>"
                         oninput="updateLetterLivePreview()"
                         class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-bold" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Đoạn Lời Dẫn / Mô Tả Hướng Dẫn:</label>
                  <textarea id="input_letter_modal_subtitle" name="letter_modal_subtitle" rows="2" required
                            oninput="updateLetterLivePreview()"
                            class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 leading-relaxed"><?= htmlspecialchars($settings['letter_modal_subtitle'] ?? 'Hãy để lại tên và những lời chúc, cảm nghĩ ngọt ngào của bạn nhé! Lời nhắn sẽ được gửi thẳng về trang quản trị 💌') ?></textarea>
                </div>
              </div>

              <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-4">
                <h3 class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-font text-rose-400"></i> 2. Ô Nhập Tên Người Gửi:
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nhãn Ô Nhập Tên:</label>
                    <input type="text" id="input_letter_sender_label" name="letter_sender_label" required
                           value="<?= htmlspecialchars($settings['letter_sender_label'] ?? 'Tên hoặc Biệt danh của bạn:') ?>"
                           oninput="updateLetterLivePreview()"
                           class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Gợi Ý (Placeholder) Ô Nhập Tên:</label>
                    <input type="text" id="input_letter_sender_placeholder" name="letter_sender_placeholder" required
                           value="<?= htmlspecialchars($settings['letter_sender_placeholder'] ?? 'Ví dụ: Bé Út, Bạn thân, Người giấu tên...') ?>"
                           oninput="updateLetterLivePreview()"
                           class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                  </div>
                </div>
              </div>

              <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-4">
                <h3 class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-message text-rose-400"></i> 3. Ô Viết Nội Dung & Nút Gửi:
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nhãn Ô Viết Lời Nhắn:</label>
                    <input type="text" id="input_letter_content_label" name="letter_content_label" required
                           value="<?= htmlspecialchars($settings['letter_content_label'] ?? 'Nội dung lời nhắn gửi trao:') ?>"
                           oninput="updateLetterLivePreview()"
                           class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Chữ Trên Nút Bấm Gửi Thư:</label>
                    <input type="text" id="input_letter_submit_btn_text" name="letter_submit_btn_text" required
                           value="<?= htmlspecialchars($settings['letter_submit_btn_text'] ?? 'Gửi Thư Ngay 💖') ?>"
                           oninput="updateLetterLivePreview()"
                           class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-bold" />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Gợi Ý (Placeholder) Ô Viết Lời Nhắn:</label>
                  <input type="text" id="input_letter_content_placeholder" name="letter_content_placeholder" required
                         value="<?= htmlspecialchars($settings['letter_content_placeholder'] ?? 'Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây...') ?>"
                         oninput="updateLetterLivePreview()"
                         class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                </div>
              </div>

              <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-3">
                <h3 class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-cat text-rose-400"></i> 4. Câu Bé Mèo Chúc Mừng Sau Khi Gửi Xong:
                </h3>
                <div>
                  <input type="text" id="input_letter_cat_speech" name="letter_cat_speech" required
                         value="<?= htmlspecialchars($settings['letter_cat_speech'] ?? '💌 Bé mèo đã chuyển thư của "{sender}" trao tận tay rồi nhé meo meo~ 🐾💖') ?>"
                         class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-400" />
                  <p class="text-[11px] text-slate-400 mt-1.5">
                    <i class="fa-solid fa-circle-info text-amber-400 mr-1"></i> Dùng ký hiệu <code class="bg-black/50 px-1 py-0.5 rounded text-pink-300 font-mono">{sender}</code> để tự động thay bằng tên người gửi!
                  </p>
                </div>
              </div>
            </div>

            <!-- CỘT BÊN PHẢI: XEM TRƯỚC HỘP THƯ TRỰC QUAN (LIVE PREVIEW) -->
            <div class="lg:col-span-5 bg-slate-950/80 border border-slate-800 rounded-3xl p-5 flex flex-col justify-between">
              <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                  <i class="fa-solid fa-eye text-pink-400"></i> Xem trước hiển thị thực tế trên thiệp:
                </span>

                <!-- MÔ PHỎNG MODAL HỘP THƯ YÊU THƯƠNG -->
                <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xl border border-pink-100 text-slate-800 space-y-3.5 select-none">
                  <div class="flex items-center justify-between">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-50 border border-pink-200 text-rose-600 text-xs font-bold">
                      <i class="fa-solid fa-heart text-rose-500"></i>
                      <span id="preview_letter_modal_title"><?= htmlspecialchars($settings['letter_modal_title'] ?? 'Hộp Thư Yêu Thương') ?></span>
                    </div>
                    <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xs">✕</span>
                  </div>

                  <p id="preview_letter_modal_subtitle" class="text-[11px] text-slate-500 leading-relaxed">
                    <?= htmlspecialchars($settings['letter_modal_subtitle'] ?? 'Hãy để lại tên và những lời chúc, cảm nghĩ ngọt ngào của bạn nhé! Lời nhắn sẽ được gửi thẳng về trang quản trị 💌') ?>
                  </p>

                  <div class="space-y-2.5">
                    <div>
                      <label id="preview_letter_sender_label" class="block text-[11px] font-bold text-slate-700 mb-1">
                        <?= htmlspecialchars($settings['letter_sender_label'] ?? 'Tên hoặc Biệt danh của bạn:') ?>
                      </label>
                      <div id="preview_letter_sender_placeholder" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-400">
                        <?= htmlspecialchars($settings['letter_sender_placeholder'] ?? 'Ví dụ: Bé Út, Bạn thân, Người giấu tên...') ?>
                      </div>
                    </div>

                    <div>
                      <label id="preview_letter_content_label" class="block text-[11px] font-bold text-slate-700 mb-1">
                        <?= htmlspecialchars($settings['letter_content_label'] ?? 'Nội dung lời nhắn gửi trao:') ?>
                      </label>
                      <div id="preview_letter_content_placeholder" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-400 h-16">
                        <?= htmlspecialchars($settings['letter_content_placeholder'] ?? 'Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây...') ?>
                      </div>
                    </div>

                    <div class="w-full py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 text-white font-bold text-xs shadow-md shadow-pink-500/30 flex items-center justify-center gap-1.5">
                      <i class="fa-solid fa-paper-plane"></i>
                      <span id="preview_letter_submit_btn_text"><?= htmlspecialchars($settings['letter_submit_btn_text'] ?? 'Gửi Thư Ngay 💖') ?></span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="pt-4 mt-4 border-t border-slate-800 text-center">
                <p class="text-[11px] text-pink-300/80">Mọi thay đổi sẽ cập nhật trực tiếp vào thiệp khi bạn bấm nút Lưu bên dưới</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-3 border-t border-slate-800">
            <button type="submit" class="px-7 py-3 bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-500/20 flex items-center gap-2 hover:scale-[1.01] transition-all text-xs sm:text-sm">
              <i class="fa-solid fa-floppy-disk"></i> Lưu Tất Cả Cài Đặt Hộp Thư
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 6: ĐẾM NGƯỢC KHÓA MỞ THIỆP TẠI 2 CÁNH CỬA                             -->
    <!-- ========================================================================= -->
    <div id="tab-event-counter" class="tab-content hidden space-y-6">
      <form method="POST" action="" class="space-y-6">
        <input type="hidden" name="action" value="update_event_counter_settings" />

        <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800 mb-6">
            <div class="flex items-center gap-3.5">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-400 via-rose-500 to-pink-600 flex items-center justify-center text-white text-xl shadow-lg shadow-rose-500/20">
                <i class="fa-solid fa-hourglass-half"></i>
              </div>
              <div>
                <h2 class="text-base sm:text-lg font-bold text-pink-200">Đếm Ngược Khóa Mở Cửa Thiệp Ban Đầu</h2>
                <p class="text-xs text-slate-400 mt-0.5">Hiển thị đồng hồ đếm ngược tại màn hình 2 cánh cửa ban đầu. Khi hết giờ đếm ngược thì người xem mới được mở cửa nhập mật khẩu!</p>
              </div>
            </div>

            <!-- NÚT BẬT / TẮT BỘ ĐẾM -->
            <label class="relative inline-flex items-center cursor-pointer bg-slate-950/80 px-4 py-2.5 rounded-2xl border border-pink-500/30 hover:border-pink-500/60 transition-all">
              <input type="checkbox" name="event_counter_enabled" value="1" class="sr-only peer" id="counter_enable_toggle"
                     <?= (!isset($settings['event_counter_enabled']) || $settings['event_counter_enabled'] === '1' || $settings['event_counter_enabled'] === true || $settings['event_counter_enabled'] === 'true') ? 'checked' : '' ?>
                     onchange="updateCounterLivePreview()" />
              <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[12px] after:left-[18px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-rose-500 peer-checked:to-amber-400"></div>
              <span class="ml-3 text-xs font-bold text-pink-200">Bật đếm ngược khóa cửa</span>
            </label>
          </div>

          <!-- NÚT CHỌN NHANH CÁC DỊP LỄ / SỰ KIỆN PHỔ BIẾN -->
          <div class="mb-6 bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4">
            <span class="text-[11px] font-bold text-pink-300 uppercase tracking-wider block mb-2.5">
              <i class="fa-solid fa-wand-magic-sparkles text-amber-400 mr-1"></i> Chọn nhanh mẫu sự kiện phổ biến:
            </span>
            <div class="flex flex-wrap gap-2">
              <button type="button" onclick="applyCounterPreset('20-10')" class="px-3 py-1.5 rounded-xl bg-pink-950/60 hover:bg-pink-900/80 border border-pink-500/40 text-pink-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <span>🌸</span> 20/10 Phụ Nữ VN
              </button>
              <button type="button" onclick="applyCounterPreset('8-3')" class="px-3 py-1.5 rounded-xl bg-rose-950/60 hover:bg-rose-900/80 border border-rose-500/40 text-rose-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <span>🌷</span> 8/3 Quốc Tế Phụ Nữ
              </button>
              <button type="button" onclick="applyCounterPreset('14-2')" class="px-3 py-1.5 rounded-xl bg-red-950/60 hover:bg-red-900/80 border border-red-500/40 text-red-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <span>💖</span> 14/2 Valentine
              </button>
              <button type="button" onclick="applyCounterPreset('birthday')" class="px-3 py-1.5 rounded-xl bg-amber-950/60 hover:bg-amber-900/80 border border-amber-500/40 text-amber-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <span>🎂</span> Sinh Nhật Sắp Tới
              </button>
              <button type="button" onclick="applyCounterPreset('anniversary')" class="px-3 py-1.5 rounded-xl bg-purple-950/60 hover:bg-purple-900/80 border border-purple-500/40 text-purple-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <span>💍</span> Kỷ Niệm Ngày Yêu (Đếm Ngày Đã Qua)
              </button>
              <button type="button" onclick="applyCounterPreset('noel')" class="px-3 py-1.5 rounded-xl bg-emerald-950/60 hover:bg-emerald-900/80 border border-emerald-500/40 text-emerald-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <span>🎄</span> Giáng Sinh 25/12
              </button>
              <button type="button" onclick="applyCounterPreset('newyear')" class="px-3 py-1.5 rounded-xl bg-yellow-950/60 hover:bg-yellow-900/80 border border-yellow-500/40 text-yellow-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <span>🎉</span> Năm Mới 01/01
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- CỘT BÊN TRÁI: FORM CÀI ĐẶT -->
            <div class="lg:col-span-7 space-y-4">
              
              <!-- 1. Tiêu đề & Nhãn phụ -->
              <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-3">
                <div>
                  <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">
                    Tiêu Đề Bộ Đếm Sự Kiện:
                  </label>
                  <input type="text" id="input_counter_title" name="event_counter_title" required
                         value="<?= htmlspecialchars($settings['event_counter_title'] ?? 'Đếm ngược đến Ngày 20-10 🌸') ?>"
                         oninput="updateCounterLivePreview()"
                         placeholder="Ví dụ: Đếm ngược đến Ngày 20-10 🌸 hoặc Kỷ niệm 500 ngày bên nhau 💕"
                         class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Nhãn Nhỏ Phụ (Badge):</label>
                  <input type="text" id="input_counter_badge" name="event_counter_badge"
                         value="<?= htmlspecialchars($settings['event_counter_badge'] ?? 'Sự Kiện Đặc Biệt ✨') ?>"
                         oninput="updateCounterLivePreview()"
                         placeholder="Ví dụ: Sự Kiện Đặc Biệt ✨, Kỷ Niệm Yêu Thương 💕..."
                         class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                </div>
              </div>

              <!-- 2. Chọn Ngày Giờ & Chế độ đếm -->
              <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">
                      <i class="fa-solid fa-calendar-day mr-1"></i> Ngày & Giờ Mốc Sự Kiện:
                    </label>
                    <input type="datetime-local" id="input_counter_target_date" name="event_counter_target_date" required
                           value="<?= htmlspecialchars($settings['event_counter_target_date'] ?? '2026-10-20T00:00') ?>"
                           onchange="updateCounterLivePreview()"
                           class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-mono" />
                  </div>

                  <div>
                    <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">
                      <i class="fa-solid fa-arrows-rotate mr-1"></i> Chế Độ Đếm:
                    </label>
                    <select id="select_counter_mode" name="event_counter_mode" onchange="updateCounterLivePreview()"
                            class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400">
                      <option value="auto" <?= (empty($settings['event_counter_mode']) || $settings['event_counter_mode'] === 'auto') ? 'selected' : '' ?>>
                        🔄 Tự động (Tương lai: Đếm ngược, Quá khứ: Đếm ngày đã qua)
                      </option>
                      <option value="countdown" <?= (!empty($settings['event_counter_mode']) && $settings['event_counter_mode'] === 'countdown') ? 'selected' : '' ?>>
                        ⏳ Luôn Đếm Ngược (Countdown)
                      </option>
                      <option value="countup" <?= (!empty($settings['event_counter_mode']) && $settings['event_counter_mode'] === 'countup') ? 'selected' : '' ?>>
                        📈 Đếm Số Ngày Đã Qua (Kỷ niệm / Count-Up)
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- 3. Thông điệp chúc mừng khi chạm mốc -->
              <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-2">
                <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider">
                  <i class="fa-solid fa-gift text-rose-400 mr-1"></i> Thông Điệp Chúc Mừng Khi Đến Ngày:
                </label>
                <textarea id="input_counter_message" name="event_counter_message" rows="2"
                          oninput="updateCounterLivePreview()"
                          placeholder="Ví dụ: Hôm nay là Ngày Phụ Nữ Việt Nam 20-10! Chúc bạn ngập tràn niềm vui & hạnh phúc! 💖"
                          class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-400"><?= htmlspecialchars($settings['event_counter_message'] ?? 'Hôm nay là ngày đặc biệt! Chúc bạn ngập tràn niềm vui & hạnh phúc! 💖') ?></textarea>
              </div>

            </div>

            <!-- CỘT BÊN PHẢI: XEM TRƯỚC BỘ ĐẾM THỜI GIAN THỰC (LIVE PREVIEW) -->
            <div class="lg:col-span-5 bg-slate-950/80 border border-slate-800 rounded-3xl p-5 flex flex-col justify-between">
              <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                  <i class="fa-solid fa-eye text-pink-400"></i> Xem trước hiển thị trên thiệp:
                </span>

                <!-- MÔ PHỎNG GIAO DIỆN BỘ ĐẾM SỰ KIỆN TRỰC TIẾP -->
                <div class="bg-gradient-to-b from-pink-50 to-rose-100/80 rounded-2xl p-4 shadow-xl border border-pink-200 text-center select-none space-y-2.5">
                  <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-white/90 border border-pink-200 shadow-sm text-[10px] font-bold text-rose-700 uppercase">
                    <i class="fa-solid fa-clock-rotate-left text-amber-500"></i>
                    <span id="preview_counter_badge"><?= htmlspecialchars($settings['event_counter_badge'] ?? 'Sự Kiện Đặc Biệt ✨') ?></span>
                  </div>

                  <h3 id="preview_counter_title" class="font-comfortaa text-xs sm:text-sm font-bold text-rose-950">
                    <?= htmlspecialchars($settings['event_counter_title'] ?? 'Đếm ngược đến Ngày 20-10 🌸') ?>
                  </h3>

                  <!-- CÁC Ô ĐẾM THỜI GIAN SỐ NHẢY TRỰC TIẾP -->
                  <div id="preview_units_row" class="flex items-center justify-center gap-1.5 pt-1">
                    <div class="bg-white/95 border border-pink-300 rounded-xl px-2 py-1 min-w-[42px] shadow-sm">
                      <div id="prev_cnt_days" class="font-mono text-sm sm:text-base font-extrabold text-rose-600 leading-none">00</div>
                      <div class="text-[8px] font-bold text-pink-600 uppercase mt-0.5">Ngày</div>
                    </div>
                    <span class="text-rose-500 font-bold text-xs">:</span>
                    <div class="bg-white/95 border border-pink-300 rounded-xl px-2 py-1 min-w-[42px] shadow-sm">
                      <div id="prev_cnt_hours" class="font-mono text-sm sm:text-base font-extrabold text-rose-600 leading-none">00</div>
                      <div class="text-[8px] font-bold text-pink-600 uppercase mt-0.5">Giờ</div>
                    </div>
                    <span class="text-rose-500 font-bold text-xs">:</span>
                    <div class="bg-white/95 border border-pink-300 rounded-xl px-2 py-1 min-w-[42px] shadow-sm">
                      <div id="prev_cnt_minutes" class="font-mono text-sm sm:text-base font-extrabold text-rose-600 leading-none">00</div>
                      <div class="text-[8px] font-bold text-pink-600 uppercase mt-0.5">Phút</div>
                    </div>
                    <span class="text-rose-500 font-bold text-xs">:</span>
                    <div class="bg-white/95 border border-pink-300 rounded-xl px-2 py-1 min-w-[42px] shadow-sm">
                      <div id="prev_cnt_seconds" class="font-mono text-sm sm:text-base font-extrabold text-rose-600 leading-none">00</div>
                      <div class="text-[8px] font-bold text-pink-600 uppercase mt-0.5">Giây</div>
                    </div>
                  </div>

                  <div id="preview_counter_message_box" class="hidden p-2 rounded-xl bg-rose-500 text-white text-[11px] font-bold shadow-md">
                    <span id="preview_counter_message_text"><?= htmlspecialchars($settings['event_counter_message'] ?? '') ?></span>
                  </div>
                </div>

                <div id="preview_disabled_notice" class="hidden mt-3 p-3 rounded-xl bg-amber-950/40 border border-amber-500/30 text-amber-200 text-xs text-center font-medium">
                  <i class="fa-solid fa-eye-slash mr-1"></i> Bộ đếm đang bị tắt và sẽ không xuất hiện trên thiệp
                </div>
              </div>

              <div class="pt-4 mt-4 border-t border-slate-800 text-center">
                <p class="text-[11px] text-pink-300/80">Bộ đếm cập nhật mượt mà theo từng giây thực tế</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-3 border-t border-slate-800">
            <button type="submit" class="px-7 py-3 bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-500/20 flex items-center gap-2 hover:scale-[1.01] transition-all text-xs sm:text-sm">
              <i class="fa-solid fa-floppy-disk"></i> Lưu Cài Đặt Bộ Đếm
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 7: HỘP THƯ ĐẾN - LỜI NHẮN NGƯỜI XEM GỬI VỀ                              -->
    <!-- ========================================================================= -->
    <div id="tab-letters" class="tab-content hidden space-y-6">
      <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800 mb-6">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 to-rose-600 flex items-center justify-center text-white text-xl shadow-lg shadow-pink-500/20">
              <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
              <h2 class="text-base sm:text-lg font-bold text-pink-200">Hộp Thư Đến & Lời Nhắn Yêu Thương</h2>
              <p class="text-xs text-slate-400">Danh sách các lá thư, tâm tư và lời chúc do người xem gửi từ nút "Gửi Thư 💌" trên thiệp</p>
            </div>
          </div>

          <?php if (!empty($letters)): ?>
            <form method="POST" action="" onsubmit="return confirm('Bạn có chắc chắn muốn xóa TẤT CẢ thư trong hòm thư này?');">
              <input type="hidden" name="action" value="delete_all_letters" />
              <button type="submit" class="px-3.5 py-2 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-trash-can"></i> Xóa Tất Cả Thư
              </button>
            </form>
          <?php endif; ?>
        </div>

        <?php if (empty($letters)): ?>
          <div class="p-12 rounded-2xl bg-slate-950/60 border border-dashed border-slate-800 text-center space-y-3">
            <div class="w-14 h-14 rounded-full bg-pink-500/10 text-pink-400 text-2xl flex items-center justify-center mx-auto">
              <i class="fa-regular fa-envelope"></i>
            </div>
            <p class="text-sm font-bold text-pink-200">Hòm thư hiện tại đang trống</p>
            <p class="text-xs text-slate-400 max-w-md mx-auto">
              Khi bạn bè, người thân mở thiệp và bấm vào nút <strong class="text-white">"Gửi Thư 💌"</strong> ở góc trái trên cùng, những lời nhắn yêu thương sẽ xuất hiện ngay tại đây!
            </p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($letters as $let): ?>
              <div class="bg-slate-950/80 border <?= empty($let['is_read']) ? 'border-pink-500/60 ring-1 ring-pink-500/30' : 'border-slate-800' ?> rounded-2xl p-5 flex flex-col justify-between space-y-4 shadow-lg transition-all hover:border-pink-400/60">
                <div class="space-y-2.5">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-pink-500 to-rose-500 flex items-center justify-center text-white text-xs font-bold">
                        <?= mb_substr($let['sender_name'], 0, 1, 'UTF-8') ?>
                      </div>
                      <div>
                        <h3 class="text-sm font-bold text-pink-100 flex items-center gap-1.5">
                          <?= htmlspecialchars($let['sender_name']) ?>
                          <?php if (empty($let['is_read'])): ?>
                            <span class="px-1.5 py-0.5 rounded-full bg-rose-500/20 text-rose-300 text-[10px] font-bold">Mới</span>
                          <?php endif; ?>
                        </h3>
                        <span class="text-[11px] text-slate-400 flex items-center gap-1">
                          <i class="fa-regular fa-clock text-[10px]"></i> <?= htmlspecialchars($let['created_at']) ?>
                        </span>
                      </div>
                    </div>

                    <form method="POST" action="" onsubmit="return confirm('Bạn có chắc chắn muốn xóa lá thư này?');">
                      <input type="hidden" name="action" value="delete_letter" />
                      <input type="hidden" name="letter_id" value="<?= $let['id'] ?>" />
                      <button type="submit" title="Xóa thư" class="w-7 h-7 rounded-lg bg-slate-900 hover:bg-rose-950/60 text-slate-400 hover:text-rose-400 flex items-center justify-center transition-all text-xs">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </div>

                  <div class="p-3.5 bg-slate-900/90 rounded-xl border border-slate-800 text-xs text-pink-100 font-sans leading-relaxed whitespace-pre-wrap select-text">
                    <?= htmlspecialchars($let['content']) ?>
                  </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-900 text-[11px] text-slate-500">
                  <span>Mã thư #<?= $let['id'] ?></span>
                  <?php if (empty($let['is_read'])): ?>
                    <form method="POST" action="">
                      <input type="hidden" name="action" value="mark_letter_read" />
                      <input type="hidden" name="letter_id" value="<?= $let['id'] ?>" />
                      <button type="submit" class="text-pink-400 hover:text-pink-300 font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-check"></i> Đánh dấu đã đọc
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-slate-500 flex items-center gap-1">
                      <i class="fa-solid fa-check-double text-emerald-500"></i> Đã đọc
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
    <!-- ========================================================================= -->
    <!-- TAB 9: HƯỚNG DẪN UP LÊN INFINITYFREE TỪ A-Z                              -->
    <!-- ========================================================================= -->
    <div id="tab-deploy-guide" class="tab-content hidden space-y-6">
      <div class="max-w-3xl mx-auto bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
        <div class="flex items-center gap-3.5 pb-4 border-b border-slate-800">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-rose-500 flex items-center justify-center text-white text-xl shadow-lg shadow-amber-500/20">
            <i class="fa-solid fa-cloud-arrow-up"></i>
          </div>
          <div>
            <h2 class="text-base sm:text-lg font-bold text-pink-200">Hướng Dẫn Up Lên InfinityFree Từ A-Z</h2>
            <p class="text-xs text-slate-400">Trọn bộ các bước đưa thiệp chúc mừng lên hosting miễn phí</p>
          </div>
        </div>

        <div class="space-y-5 text-xs sm:text-sm text-slate-300 leading-relaxed">
          <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
            <h3 class="font-bold text-pink-300 flex items-center gap-2 text-sm">
              <span class="w-6 h-6 rounded-full bg-pink-500/20 text-pink-400 flex items-center justify-center text-xs">1</span>
              Tải file ZIP mã nguồn
            </h3>
            <p>
              Bấm nút <strong class="text-white">"Tải ZIP InfinityFree"</strong> ở góc trên thanh công cụ để tải file <code class="text-pink-300 font-mono">thiep_2_canh_cua_infinityfree.zip</code> về máy. File này chứa toàn bộ các file: <code class="text-pink-300 font-mono">index.php</code>, <code class="text-pink-300 font-mono">config.php</code>, <code class="text-pink-300 font-mono">database.sql</code>, <code class="text-pink-300 font-mono">css.css</code>, <code class="text-pink-300 font-mono">script.js</code> và thư mục <code class="text-pink-300 font-mono">admin/</code>.
            </p>
            <div class="pt-2">
              <button type="button" onclick="downloadAdminZip(this)" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-rose-500 hover:brightness-110 active:scale-95 text-white font-bold text-xs shadow-md flex items-center gap-2 transition-all">
                <i class="fa-solid fa-download"></i> Tải Ngay File ZIP Mã Nguồn
              </button>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
            <h3 class="font-bold text-pink-300 flex items-center gap-2 text-sm">
              <span class="w-6 h-6 rounded-full bg-pink-500/20 text-pink-400 flex items-center justify-center text-xs">2</span>
              Upload lên thư mục htdocs
            </h3>
            <p>
              Đăng nhập vào trang quản trị InfinityFree (Client Area), mở <strong class="text-white">Online File Manager</strong> của tên miền bạn muốn chạy. Đi vào thư mục <code class="text-pink-300 font-mono">htdocs</code> và giải nén (Unzip) toàn bộ file trong ZIP vào đây (hoặc upload các file nằm ngay trong thư mục gốc của `htdocs`).
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
            <h3 class="font-bold text-pink-300 flex items-center gap-2 text-sm">
              <span class="w-6 h-6 rounded-full bg-pink-500/20 text-pink-400 flex items-center justify-center text-xs">3</span>
              Tạo Database MySQL
            </h3>
            <p>
              Vào cPanel của hosting, chọn <strong class="text-white">MySQL Databases</strong>. Tạo một Database mới. Ghi lại các thông số: <strong class="text-amber-300">MySQL Hostname</strong> (vd: <code class="text-pink-300 font-mono">sql200.infinityfree.com</code>), <strong class="text-amber-300">Database Name</strong>, <strong class="text-amber-300">MySQL User</strong>, và <strong class="text-amber-300">MySQL Password</strong>.
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
            <h3 class="font-bold text-pink-300 flex items-center gap-2 text-sm">
              <span class="w-6 h-6 rounded-full bg-pink-500/20 text-pink-400 flex items-center justify-center text-xs">4</span>
              Cấu hình file config.php
            </h3>
            <p>
              Mở file <code class="text-pink-300 font-mono">config.php</code> trên File Manager, điền chính xác thông số database của bạn vào các dòng đầu:
            </p>
            <div class="bg-black/60 p-3 rounded-xl border border-slate-800 font-mono text-xs text-pink-200 overflow-x-auto select-all">
              define('DB_HOST', 'sqlXXX.infinityfree.com');<br/>
              define('DB_NAME', 'tên_database_của_bạn');<br/>
              define('DB_USER', 'tên_user_của_bạn');<br/>
              define('DB_PASS', 'mật_khẩu_cpanel_của_bạn');
            </div>
            <p class="text-[11px] text-amber-300/90 mt-1">
              * Tại đây bạn cũng có thể đổi mật khẩu admin bằng cách sửa <code class="font-mono text-pink-300">define('ADMIN_PASSWORD', '123456');</code> thành mật khẩu 6 số bạn muốn.
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
            <h3 class="font-bold text-pink-300 flex items-center gap-2 text-sm">
              <span class="w-6 h-6 rounded-full bg-pink-500/20 text-pink-400 flex items-center justify-center text-xs">5</span>
              Nhập file database.sql qua phpMyAdmin
            </h3>
            <p>
              Vào cPanel &rarr; chọn <strong class="text-white">phpMyAdmin</strong>. Chọn database của bạn ở cột bên trái, bấm vào tab <strong class="text-amber-300">Import</strong>, chọn file <code class="text-pink-300 font-mono">database.sql</code> và bấm <strong class="text-white">Go</strong> để nạp dữ liệu mẫu (cánh cửa, mã PIN, lời chúc).
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
            <h3 class="font-bold text-pink-300 flex items-center gap-2 text-sm">
              <span class="w-6 h-6 rounded-full bg-pink-500/20 text-pink-400 flex items-center justify-center text-xs">6</span>
              Đường dẫn link rút gọn đẹp mắt
            </h3>
            <p>
              File <code class="text-pink-300 font-mono">.htaccess</code> đã được tạo sẵn trong thư mục gốc giúp bạn truy cập trực tiếp bằng đường dẫn <code class="font-mono text-amber-300">tenmien.com/tenthiep</code> cực kỳ sang trọng và tiện lợi!
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-gradient-to-r from-rose-950/50 to-pink-950/50 border border-pink-500/30 text-pink-200 space-y-1.5">
            <div class="font-bold text-pink-100 flex items-center gap-2">
              <i class="fa-solid fa-circle-check text-emerald-400"></i> Hoàn tất!
            </div>
            <p class="text-xs">
              Truy cập trang chủ <code class="font-mono text-white">http://tenmien-cua-ban.epizy.com</code> (với điện thoại sẽ yêu cầu xoay ngang màn hình). Truy cập quản trị tại <code class="font-mono text-white">http://tenmien-cua-ban.epizy.com/admin</code> và nhập mật khẩu 6 số để quản lý.
            </p>
          </div>
        </div>
      </div>
    </div>

  </main>

  <!-- ========================================================================= -->
  <!-- MODAL: THÊM LỜI CHÚC MỚI                                                  -->
  <!-- ========================================================================= -->
  <div id="addWishModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-pink-400/40 rounded-3xl p-6 max-w-lg w-full shadow-2xl relative">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
        <h3 class="text-base font-bold text-pink-200 flex items-center gap-2">
          <i class="fa-solid fa-circle-plus text-rose-400"></i> Thêm Lời Chúc Mới
        </h3>
        <button type="button" onclick="closeAddWishModal()" class="text-slate-400 hover:text-white text-lg">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form method="POST" action="" class="space-y-4">
        <input type="hidden" name="action" value="add_wish" />

        <div>
          <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">Người nhận (Tên / Danh xưng)</label>
          <input type="text" name="recipient" required placeholder="Vd: Gửi Mẹ yêu dấu, Gửi Vợ yêu thương..."
                 class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
        </div>

        <div>
          <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">Link Ảnh Lời Chúc (URL)</label>
          <input type="url" id="add_wish_img_input" name="img" required
                 oninput="updateImagePreview('add_wish_img_input', 'add_wish_img_preview')"
                 placeholder="https://..."
                 class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-mono" />
          <div class="mt-2 flex items-center gap-3">
            <div class="w-16 h-16 rounded-xl overflow-hidden border border-slate-700 bg-black/40 flex-shrink-0">
              <img id="add_wish_img_preview" src="https://via.placeholder.com/150?text=Preview" alt="Preview" class="w-full h-full object-cover" />
            </div>
            <span class="text-[11px] text-slate-400">Xem trước ảnh lời chúc</span>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">Nội Dung Lời Chúc</label>
          <textarea name="msg" rows="3" required placeholder="Chúc bạn một ngày tràn đầy niềm vui và hạnh phúc..."
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400"></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">Số thứ tự hiển thị</label>
          <input type="number" name="sort_order" value="<?= count($wishes) + 1 ?>" min="1"
                 class="w-24 px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white" />
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
          <button type="button" onclick="closeAddWishModal()" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">
            Hủy Bỏ
          </button>
          <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 text-white text-xs font-bold shadow-md shadow-pink-500/20">
            Thêm Lời Chúc
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- MODAL: SỬA LỜI CHÚC                                                       -->
  <!-- ========================================================================= -->
  <div id="editWishModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-pink-400/40 rounded-3xl p-6 max-w-lg w-full shadow-2xl relative">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
        <h3 class="text-base font-bold text-pink-200 flex items-center gap-2">
          <i class="fa-solid fa-pen-to-square text-pink-400"></i> Chỉnh Sửa Lời Chúc
        </h3>
        <button type="button" onclick="closeEditWishModal()" class="text-slate-400 hover:text-white text-lg">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form method="POST" action="" class="space-y-4">
        <input type="hidden" name="action" value="update_wish" />
        <input type="hidden" id="edit_wish_id" name="wish_id" value="" />

        <div>
          <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">Người nhận (Tên / Danh xưng)</label>
          <input type="text" id="edit_recipient" name="recipient" required
                 class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
        </div>

        <div>
          <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">Link Ảnh Lời Chúc (URL)</label>
          <input type="url" id="edit_img" name="img" required
                 oninput="updateImagePreview('edit_img', 'edit_wish_img_preview')"
                 class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-mono" />
          <div class="mt-2 flex items-center gap-3">
            <div class="w-16 h-16 rounded-xl overflow-hidden border border-slate-700 bg-black/40 flex-shrink-0">
              <img id="edit_wish_img_preview" src="" alt="Preview" class="w-full h-full object-cover" />
            </div>
            <span class="text-[11px] text-slate-400">Xem trước ảnh lời chúc</span>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">Nội Dung Lời Chúc</label>
          <textarea id="edit_msg" name="msg" rows="3" required
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400"></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-pink-300 uppercase tracking-wider mb-1">Số thứ tự hiển thị</label>
          <input type="number" id="edit_sort_order" name="sort_order" min="1"
                 class="w-24 px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white" />
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
          <button type="button" onclick="closeEditWishModal()" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">
            Hủy Bỏ
          </button>
          <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 text-white text-xs font-bold shadow-md shadow-pink-500/20">
            Lưu Thay Đổi
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Tab switching (100% đồng bộ giao diện như preview)
    function switchTab(tabId) {
      if (!tabId || !document.getElementById(tabId)) tabId = 'tab-custom-links';
      document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-gradient-to-r', 'from-rose-500', 'to-pink-500', 'from-amber-500', 'to-rose-500', 'text-white', 'shadow-md', 'shadow-pink-500/20', 'shadow-amber-500/20');
        btn.classList.add('bg-slate-900', 'text-slate-300');
      });

      const activeTab = document.getElementById(tabId);
      const activeBtn = document.getElementById('btn-' + tabId);
      if (activeTab) activeTab.classList.remove('hidden');
      if (activeBtn) {
        activeBtn.classList.remove('bg-slate-900', 'text-slate-300');
        if (tabId === 'tab-custom-links') {
          activeBtn.classList.add('bg-gradient-to-r', 'from-amber-500', 'to-rose-500', 'text-white', 'shadow-md', 'shadow-amber-500/20');
        } else {
          activeBtn.classList.add('bg-gradient-to-r', 'from-rose-500', 'to-pink-500', 'text-white', 'shadow-md', 'shadow-pink-500/20');
        }
      }
      try {
        localStorage.setItem('admin_last_tab', tabId);
        window.history.replaceState(null, '', '#' + tabId);
      } catch (e) {}
    }

    // Auto restore active tab
    document.addEventListener('DOMContentLoaded', function() {
      const phpTab = '<?= $activeTab ?>';
      const hashTab = window.location.hash.replace('#', '');
      const savedTab = localStorage.getItem('admin_last_tab');
      const targetTab = (phpTab && phpTab !== 'tab-custom-links') ? phpTab : (hashTab || savedTab || 'tab-custom-links');
      switchTab(targetTab);
    });

    // Tải trọn bộ ZIP InfinityFree trực tiếp từ admin
    function downloadAdminZip(btn) {
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Đang nén ZIP...</span>';
      }
      window.location.href = '?action=download_zip';
      setTimeout(() => {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-download text-amber-400"></i> <span>Tải ZIP InfinityFree</span>';
        }
        showGlobalAdminToast('Đang tải xuống bộ file ZIP cho InfinityFree!');
      }, 2500);
    }

    // Live preview ảnh khi gõ hoặc paste URL
    function updateImagePreview(inputId, previewId) {
      const input = document.getElementById(inputId);
      const preview = document.getElementById(previewId);
      if (input && preview) {
        preview.src = input.value;
      }
    }

    // Khôi phục link ảnh 2 cánh cửa mặc định
    function resetDefaultDoorImages() {
      const leftUrl = 'https://cdn.phototourl.com/member/2026-09-26-82093f1d-b873-458c-9297-6e06ee4ff636.png';
      const rightUrl = 'https://cdn.phototourl.com/member/2026-09-26-b8856a42-8f8e-4205-8056-871721a27340.png';
      document.getElementById('door_left_input').value = leftUrl;
      document.getElementById('door_right_input').value = rightUrl;
      updateImagePreview('door_left_input', 'door_left_preview');
      updateImagePreview('door_right_input', 'door_right_preview');
    }

    // Modal Thêm Lời Chúc
    function openAddWishModal() {
      document.getElementById('addWishModal').classList.remove('hidden');
    }
    function closeAddWishModal() {
      document.getElementById('addWishModal').classList.add('hidden');
    }

    // Modal Sửa Lời Chúc
    function openEditWishModal(wish) {
      document.getElementById('edit_wish_id').value = wish.id;
      document.getElementById('edit_recipient').value = wish.recipient;
      document.getElementById('edit_msg').value = wish.msg;
      document.getElementById('edit_img').value = wish.img;
      document.getElementById('edit_sort_order').value = wish.sort_order || 1;
      updateImagePreview('edit_img', 'edit_wish_img_preview');
      document.getElementById('editWishModal').classList.remove('hidden');
    }
    function closeEditWishModal() {
      document.getElementById('editWishModal').classList.add('hidden');
    }

    // Bé mèo: Live preview và helper
    function updateCatPreview() {
      const textarea = document.getElementById('cat_quotes_textarea');
      const bubble = document.getElementById('cat_preview_bubble');
      if (textarea && bubble) {
        const lines = textarea.value.split('\n').map(s => s.trim()).filter(Boolean);
        if (lines.length > 0) {
          bubble.textContent = lines[0];
        } else {
          bubble.textContent = 'Meo meo~ Hãy nhập câu chúc ở bên!';
        }
      }
    }

    // =========================================================================
    // XỬ LÝ NGUỒN PHÁT NHẠC (DUAL MUSIC SOURCE - NO CONFLICT)
    // =========================================================================
    let currentMusicSource = '<?= ($settings['bg_music_type'] ?? 'file') ?>';

    function selectMusicSourceType(type) {
      currentMusicSource = type;
      document.getElementById('bg_music_type').value = type;

      const btnFile = document.getElementById('sourceBtnFile');
      const btnUrl = document.getElementById('sourceBtnUrl');
      const iconFile = document.getElementById('fileCheckIcon');
      const iconUrl = document.getElementById('urlCheckIcon');
      const cardFile = document.getElementById('fileSourceCard');
      const cardUrl = document.getElementById('urlSourceCard');
      const badgeText = document.getElementById('activeSourceBadgeText');

      if (type === 'file') {
        btnFile.className = "p-4 rounded-2xl border text-left transition-all relative bg-gradient-to-r from-pink-950/70 to-rose-950/70 border-pink-500 ring-2 ring-pink-500/30";
        btnUrl.className = "p-4 rounded-2xl border text-left transition-all relative bg-slate-950/60 border-slate-800 hover:border-slate-700";
        iconFile.className = "text-pink-400";
        iconUrl.className = "text-slate-600";
        cardFile.className = "bg-slate-950/70 border border-pink-500/40 ring-1 ring-pink-500/20 rounded-2xl p-5 space-y-4 transition-all";
        cardUrl.className = "bg-slate-950/70 border border-slate-800/80 opacity-75 rounded-2xl p-5 space-y-4 transition-all";
        badgeText.textContent = "Đang dùng: File MP3 Nội Bộ";
      } else {
        btnUrl.className = "p-4 rounded-2xl border text-left transition-all relative bg-gradient-to-r from-pink-950/70 to-rose-950/70 border-pink-500 ring-2 ring-pink-500/30";
        btnFile.className = "p-4 rounded-2xl border text-left transition-all relative bg-slate-950/60 border-slate-800 hover:border-slate-700";
        iconUrl.className = "text-pink-400";
        iconFile.className = "text-slate-600";
        cardUrl.className = "bg-slate-950/70 border border-pink-500/40 ring-1 ring-pink-500/20 rounded-2xl p-5 space-y-4 transition-all";
        cardFile.className = "bg-slate-950/70 border border-slate-800/80 opacity-75 rounded-2xl p-5 space-y-4 transition-all";
        badgeText.textContent = "Đang dùng: Link URL Online";
      }

      syncMusicFinal();
    }

    function setPresetFile(filename) {
      document.getElementById('bg_music_file').value = filename;
      selectMusicSourceType('file');
      syncMusicFinal();
    }

    function applySampleMusicUrl(url, title) {
      document.getElementById('bg_music_online_url').value = url;
      selectMusicSourceType('url');
      syncMusicFinal();
    }

    function onFileParamChange() {
      selectMusicSourceType('file');
      syncMusicFinal();
    }

    function onUrlParamChange() {
      selectMusicSourceType('url');
      syncMusicFinal();
    }

    function syncMusicFinal() {
      const type = document.getElementById('bg_music_type').value;
      const fileVal = document.getElementById('bg_music_file').value.trim() || '1.mp3';
      const urlVal = document.getElementById('bg_music_online_url').value.trim();

      const finalVal = (type === 'url' && urlVal) ? urlVal : fileVal;
      document.getElementById('bg_music_url_final').value = finalVal;
      document.getElementById('playerCurrentTrack').textContent = finalVal;

      // Cập nhật audio player nếu đang phát
      const audio = document.getElementById('adminAudioPreview');
      if (audio && !audio.paused) {
        audio.pause();
        onAudioPause();
      }
    }

    // Audio Preview Controls
    function toggleAudioPreview() {
      const audio = document.getElementById('adminAudioPreview');
      const finalVal = document.getElementById('bg_music_url_final').value;
      const playerStatus = document.getElementById('playerStatus');

      if (!audio) return;

      if (!audio.paused) {
        audio.pause();
      } else {
        let src = finalVal;
        // Nếu là file nội bộ tương đối (vd 1.mp3) thì dẫn tới thư mục gốc
        if (!src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('/')) {
          src = '../' + src;
        }
        audio.src = src;
        playerStatus.textContent = 'Đang tải nhạc...';
        playerStatus.className = 'text-[11px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300';
        audio.play().catch(e => {
          playerStatus.textContent = 'Không phát được file/link này';
          playerStatus.className = 'text-[11px] px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300';
          onAudioPause();
        });
      }
    }

    function onAudioPlay() {
      const btn = document.getElementById('btnPlayPreview');
      const icon = document.getElementById('btnPlayPreviewIcon');
      const text = document.getElementById('btnPlayPreviewText');
      const status = document.getElementById('playerStatus');
      const playerIcon = document.getElementById('playerIcon');

      if (btn) btn.className = "px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-2 transition-all";
      if (icon) icon.className = "fa-solid fa-pause";
      if (text) text.textContent = "Tạm Dừng";
      if (status) {
        status.textContent = "Đang phát thử";
        status.className = "text-[11px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold";
      }
      if (playerIcon) playerIcon.className = "fa-solid fa-compact-disc animate-spin";
    }

    function onAudioPause() {
      const btn = document.getElementById('btnPlayPreview');
      const icon = document.getElementById('btnPlayPreviewIcon');
      const text = document.getElementById('btnPlayPreviewText');
      const status = document.getElementById('playerStatus');
      const playerIcon = document.getElementById('playerIcon');

      if (btn) btn.className = "px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white text-xs font-bold shadow-md shadow-pink-500/20 flex items-center gap-2 transition-all";
      if (icon) icon.className = "fa-solid fa-play";
      if (text) text.textContent = "Nghe Thử";
      if (status && status.textContent !== 'Không phát được file/link này') {
        status.textContent = "Đã dừng";
        status.className = "text-[11px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300";
      }
      if (playerIcon) playerIcon.className = "fa-solid fa-music";
    }

    function onAudioEnded() {
      onAudioPause();
    }

    function onAudioError() {
      const status = document.getElementById('playerStatus');
      if (status) {
        status.textContent = "Lỗi đường dẫn audio";
        status.className = "text-[11px] px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 font-bold";
      }
      onAudioPause();
    }

    function insertSampleCatQuote() {
      const textarea = document.getElementById('cat_quotes_textarea');
      if (!textarea) return;
      const samples = [
        "Meo meo~ Mong mọi muộn phiền tan biến, chỉ còn niềm vui ở lại! 🌸✨",
        "Bé mèo mang đến cho bạn cả một bầu trời may mắn và hạnh phúc! 🐾💖",
        "Hôm nay bạn là bông hoa xinh đẹp và rạng ngời nhất! 🌷👑",
        "Purrr~ Cười thật tươi nhé, nụ cười của bạn thắp sáng cả thế giới! 🌼💕"
      ];
      const randomQuote = samples[Math.floor(Math.random() * samples.length)];
      if (textarea.value.trim().length > 0) {
        textarea.value = textarea.value.trim() + '\n' + randomQuote;
      } else {
        textarea.value = randomQuote;
      }
      updateCatPreview();
    }

    function resetDefaultCatQuotes() {
      const defaultQuotes = `Meo meo~ Chúc một ngày thật rực rỡ và hạnh phúc! 🐾💖\nChúc người đẹp luôn rạng ngời như đóa hoa sớm mai! 🌸✨\nBé mèo chúc bạn nhận được thật nhiều quà và yêu thương! 🌷❤️\nNụ cười của bạn là điều kỳ diệu nhất hôm nay! 🌼💕\nPurrr~ Ngọt ngào, bình an và mãi tươi trẻ nhé! 🎁🎀`;
      const textarea = document.getElementById('cat_quotes_textarea');
      if (textarea) {
        textarea.value = defaultQuotes;
        updateCatPreview();
      }
    }

    // =========================================================================
    // HỘP THƯ YÊU THƯƠNG: LIVE PREVIEW & RESET
    // =========================================================================
    function updateLetterLivePreview() {
      const titleInput = document.getElementById('input_letter_modal_title');
      const subInput = document.getElementById('input_letter_modal_subtitle');
      const senderLblInput = document.getElementById('input_letter_sender_label');
      const senderPlhInput = document.getElementById('input_letter_sender_placeholder');
      const contentLblInput = document.getElementById('input_letter_content_label');
      const contentPlhInput = document.getElementById('input_letter_content_placeholder');
      const btnTextInput = document.getElementById('input_letter_submit_btn_text');

      if (titleInput && document.getElementById('preview_letter_modal_title')) {
        document.getElementById('preview_letter_modal_title').textContent = titleInput.value || 'Hộp Thư Yêu Thương';
      }
      if (subInput && document.getElementById('preview_letter_modal_subtitle')) {
        document.getElementById('preview_letter_modal_subtitle').textContent = subInput.value || '';
      }
      if (senderLblInput && document.getElementById('preview_letter_sender_label')) {
        document.getElementById('preview_letter_sender_label').textContent = senderLblInput.value || 'Tên của bạn:';
      }
      if (senderPlhInput && document.getElementById('preview_letter_sender_placeholder')) {
        document.getElementById('preview_letter_sender_placeholder').textContent = senderPlhInput.value || '';
      }
      if (contentLblInput && document.getElementById('preview_letter_content_label')) {
        document.getElementById('preview_letter_content_label').textContent = contentLblInput.value || 'Nội dung:';
      }
      if (contentPlhInput && document.getElementById('preview_letter_content_placeholder')) {
        document.getElementById('preview_letter_content_placeholder').textContent = contentPlhInput.value || '';
      }
      if (btnTextInput && document.getElementById('preview_letter_submit_btn_text')) {
        document.getElementById('preview_letter_submit_btn_text').textContent = btnTextInput.value || 'Gửi Thư Ngay 💖';
      }
    }

    function resetDefaultLetterBoxTexts() {
      const defaults = {
        title: 'Hộp Thư Yêu Thương',
        subtitle: 'Hãy để lại tên và những lời chúc, cảm nghĩ ngọt ngào của bạn nhé! Lời nhắn sẽ được gửi thẳng về trang quản trị 💌',
        senderLabel: 'Tên hoặc Biệt danh của bạn:',
        senderPlaceholder: 'Ví dụ: Bé Út, Bạn thân, Người giấu tên...',
        contentLabel: 'Nội dung lời nhắn gửi trao:',
        contentPlaceholder: 'Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây...',
        btnText: 'Gửi Thư Ngay 💖',
        catSpeech: '💌 Bé mèo đã chuyển thư của "{sender}" trao tận tay rồi nhé meo meo~ 🐾💖'
      };

      if (document.getElementById('input_letter_modal_title')) document.getElementById('input_letter_modal_title').value = defaults.title;
      if (document.getElementById('input_letter_modal_subtitle')) document.getElementById('input_letter_modal_subtitle').value = defaults.subtitle;
      if (document.getElementById('input_letter_sender_label')) document.getElementById('input_letter_sender_label').value = defaults.senderLabel;
      if (document.getElementById('input_letter_sender_placeholder')) document.getElementById('input_letter_sender_placeholder').value = defaults.senderPlaceholder;
      if (document.getElementById('input_letter_content_label')) document.getElementById('input_letter_content_label').value = defaults.contentLabel;
      if (document.getElementById('input_letter_content_placeholder')) document.getElementById('input_letter_content_placeholder').value = defaults.contentPlaceholder;
      if (document.getElementById('input_letter_submit_btn_text')) document.getElementById('input_letter_submit_btn_text').value = defaults.btnText;
      if (document.getElementById('input_letter_cat_speech')) document.getElementById('input_letter_cat_speech').value = defaults.catSpeech;

      updateLetterLivePreview();
    }

    // =========================================================================
    // BỘ ĐẾM SỰ KIỆN: LIVE PREVIEW & PRESETS
    // =========================================================================
    function updateCounterLivePreview() {
      const isEnabled = document.getElementById('counter_enable_toggle')?.checked;
      const title = document.getElementById('input_counter_title')?.value || 'Đếm ngược sự kiện';
      const badge = document.getElementById('input_counter_badge')?.value || 'Sự Kiện Đặc Biệt ✨';
      const targetDateStr = document.getElementById('input_counter_target_date')?.value || '2026-10-20T00:00';
      const mode = document.getElementById('select_counter_mode')?.value || 'auto';
      const message = document.getElementById('input_counter_message')?.value || '';

      const prevBadge = document.getElementById('preview_counter_badge');
      const prevTitle = document.getElementById('preview_counter_title');
      const prevDisabledNotice = document.getElementById('preview_disabled_notice');
      const prevUnitsRow = document.getElementById('preview_units_row');
      const prevMsgBox = document.getElementById('preview_counter_message_box');
      const prevMsgText = document.getElementById('preview_counter_message_text');

      if (prevBadge) prevBadge.textContent = badge;
      if (prevTitle) prevTitle.textContent = title;

      if (prevDisabledNotice) {
        if (!isEnabled) {
          prevDisabledNotice.classList.remove('hidden');
        } else {
          prevDisabledNotice.classList.add('hidden');
        }
      }

      const targetTime = new Date(targetDateStr).getTime();
      const now = Date.now();
      let diff = isNaN(targetTime) ? 0 : targetTime - now;
      let isCountUp = false;

      if (mode === 'countup') {
        isCountUp = true;
        diff = now - targetTime;
      } else if (mode === 'countdown') {
        isCountUp = false;
        diff = targetTime - now;
      } else {
        if (diff < 0) {
          isCountUp = true;
          diff = now - targetTime;
        }
      }

      if (prevUnitsRow && prevMsgBox) {
        if (!isCountUp && diff <= 0) {
          prevUnitsRow.classList.add('hidden');
          prevMsgBox.classList.remove('hidden');
          if (prevMsgText) prevMsgText.innerHTML = '<strong>✨ Đã đến giờ mở thiệp!</strong><br><span class="text-[10px] opacity-90">Khung đếm ngược này sẽ tự động biến mất hoàn toàn trên thiệp chính, nhường chỗ cho nút trái tim mở cửa.</span>';
        } else {
          prevUnitsRow.classList.remove('hidden');
          prevMsgBox.classList.add('hidden');
        }
      }

      const positiveDiff = Math.max(0, diff);
      const totalSeconds = Math.floor(positiveDiff / 1000);
      const days = Math.floor(totalSeconds / 86400);
      const hours = Math.floor((totalSeconds % 86400) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = Math.floor(totalSeconds % 60);

      const pad = (n) => String(n).padStart(2, '0');

      if (document.getElementById('prev_cnt_days')) document.getElementById('prev_cnt_days').textContent = days >= 100 ? String(days) : pad(days);
      if (document.getElementById('prev_cnt_hours')) document.getElementById('prev_cnt_hours').textContent = pad(hours);
      if (document.getElementById('prev_cnt_minutes')) document.getElementById('prev_cnt_minutes').textContent = pad(minutes);
      if (document.getElementById('prev_cnt_seconds')) document.getElementById('prev_cnt_seconds').textContent = pad(seconds);
    }

    function applyCounterPreset(presetKey) {
      const now = new Date();
      const currentYear = now.getFullYear();
      let title = '';
      let badge = 'Sự Kiện Đặc Biệt ✨';
      let targetDate = '';
      let mode = 'auto';
      let message = '';

      if (presetKey === '20-10') {
        let year = currentYear;
        const targetThisYear = new Date(`${year}-10-20T00:00:00`);
        if (now > targetThisYear) year += 1;
        title = 'Đếm ngược đến Ngày Phụ Nữ Việt Nam 20-10 🌸';
        badge = 'Ngày Phụ Nữ VN 20/10 🌷';
        targetDate = `${year}-10-20T00:00`;
        mode = 'countdown';
        message = 'Hôm nay là Ngày Phụ Nữ Việt Nam 20-10! Chúc một nửa thế giới luôn ngập tràn niềm vui, rạng rỡ và hạnh phúc! 💖🎉';
      } else if (presetKey === '8-3') {
        let year = currentYear;
        const targetThisYear = new Date(`${year}-03-08T00:00:00`);
        if (now > targetThisYear) year += 1;
        title = 'Đếm ngược đến Ngày Quốc Tế Phụ Nữ 8-3 🌷';
        badge = 'Quốc Tế Phụ Nữ 8/3 💐';
        targetDate = `${year}-03-08T00:00`;
        mode = 'countdown';
        message = 'Hôm nay là ngày 8-3! Chúc bạn nhận được vạn điều yêu thương, xinh đẹp và luôn nở nụ cười rực rỡ! 🌹✨';
      } else if (presetKey === '14-2') {
        let year = currentYear;
        const targetThisYear = new Date(`${year}-02-14T00:00:00`);
        if (now > targetThisYear) year += 1;
        title = 'Đếm ngược đến Lễ Tình Nhân Valentine 14-2 💖';
        badge = 'Valentine 14/2 💕';
        targetDate = `${year}-02-14T00:00`;
        mode = 'countdown';
        message = 'Happy Valentine! Chúc tình yêu của chúng ta mãi bền chặt, ngọt ngào và ấm áp như ngày đầu! 💑❤️';
      } else if (presetKey === 'birthday') {
        title = 'Đếm ngược đến Sinh Nhật Nàng Thơ 🎂✨';
        badge = 'Sinh Nhật Yêu Thương 🎁';
        targetDate = `${currentYear}-12-31T00:00`;
        mode = 'countdown';
        message = 'Chúc mừng sinh nhật! Chúc em tuổi mới luôn xinh đẹp, hạnh phúc và vạn điều như ý! 🎂🎉💖';
      } else if (presetKey === 'anniversary') {
        title = 'Kỷ Niệm Ngày Chúng Mình Yêu Nhau 💕';
        badge = 'Đã Cùng Nhau 💍';
        targetDate = `${currentYear - 1}-01-01T00:00`;
        mode = 'countup';
        message = 'Cảm ơn em vì đã luôn ở bên anh suốt những ngày tháng qua! Mãi yêu em! 💖✨';
      } else if (presetKey === 'noel') {
        let year = currentYear;
        const targetThisYear = new Date(`${year}-12-25T00:00:00`);
        if (now > targetThisYear) year += 1;
        title = 'Đếm ngược đến Giáng Sinh Noel 25-12 🎄✨';
        badge = 'Giáng Sinh Ấm Áp ❄️';
        targetDate = `${year}-12-25T00:00`;
        mode = 'countdown';
        message = 'Merry Christmas! Chúc bạn và gia đình một mùa Giáng sinh an lành, ấm áp và ngập tràn niềm vui! 🎅🎁';
      } else if (presetKey === 'newyear') {
        const nextYear = currentYear + 1;
        title = `Đếm ngược đến Năm Mới Tết Dương Lịch ${nextYear} 🎉✨`;
        badge = `Chúc Mừng Năm Mới ${nextYear} 🎆`;
        targetDate = `${nextYear}-01-01T00:00`;
        mode = 'countdown';
        message = `Chào đón Năm Mới ${nextYear}! Kính chúc vạn sự như ý, an khang thịnh vượng và tràn đầy may mắn! 🥂🎉`;
      }

      if (document.getElementById('input_counter_title')) document.getElementById('input_counter_title').value = title;
      if (document.getElementById('input_counter_badge')) document.getElementById('input_counter_badge').value = badge;
      if (document.getElementById('input_counter_target_date')) document.getElementById('input_counter_target_date').value = targetDate;
      if (document.getElementById('select_counter_mode')) document.getElementById('select_counter_mode').value = mode;
      if (document.getElementById('input_counter_message')) document.getElementById('input_counter_message').value = message;
      if (document.getElementById('counter_enable_toggle')) document.getElementById('counter_enable_toggle').checked = true;

      updateCounterLivePreview();
    }

    // Interval cập nhật live preview mỗi giây trong admin
    setInterval(updateCounterLivePreview, 1000);
    updateCounterLivePreview();

    // =========================================================================
    // HỖ TRỢ QUẢN LÝ LINK WEBSITE, SAO CHÉP 1-CHẠM & FULL-SCREEN CREATOR STUDIO
    // =========================================================================
    const SITE_BASE_URL = <?= json_encode($baseUrl) ?>;
    let existingSlugs = <?= json_encode(array_map(function($c) { return $c['slug']; }, $customCards)) ?>;
    const SERVER_INITIAL_SETTINGS = <?= json_encode($settings, JSON_UNESCAPED_UNICODE) ?>;
    const SERVER_INITIAL_WISHES = <?= json_encode($wishes, JSON_UNESCAPED_UNICODE) ?>;

    function sanitizeSlugJs(str) {
      if (!str) return '';
      str = String(str).toLowerCase();
      // Bỏ dấu tiếng Việt
      str = str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
      str = str.replace(/[đĐ]/g, "d");
      str = str.replace(/[^a-z0-9_-]+/g, "-");
      return str.replace(/^-+|-+$/g, "");
    }

    function updateQuickLinkPreview(val) {
      const slug = sanitizeSlugJs(val) || 'tenthiep';
      const el = document.getElementById('quick_link_display');
      if (el) el.textContent = SITE_BASE_URL + slug;
    }

    function handleQuickCreateClick() {
      const slugInput = document.getElementById('quick_card_slug');
      const titleInput = document.getElementById('quick_card_title');
      const rawSlug = slugInput ? slugInput.value : '';
      const title = titleInput ? titleInput.value : '';

      const clean = sanitizeSlugJs(rawSlug);
      if (!clean) {
        showGlobalAdminToast('Vui lòng nhập tên định danh link!');
        if (slugInput) slugInput.focus();
        return;
      }
      if (existingSlugs.includes(clean)) {
        showGlobalAdminToast('Tên đường dẫn web này đã trùng với danh sách các link thiệp của bạn! Vui lòng đổi tên khác.');
        if (slugInput) slugInput.focus();
        return;
      }

      openCreatorStudioForNew(clean, title);
      if (slugInput) slugInput.value = '';
      if (titleInput) titleInput.value = '';
      updateQuickLinkPreview('');
    }

    // Sao chép link với dấu tích xanh và thông báo
    function copyCardLink(url, btn) {
      if (!url) return;
      
      const onSuccess = () => {
        if (btn) {
          const originalHtml = btn.innerHTML;
          btn.innerHTML = '<i class="fa-solid fa-check text-emerald-200"></i> <span>Đã Sao Chép ✓</span>';
          btn.classList.add('bg-emerald-600', 'text-white');
          setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('bg-emerald-600', 'text-white');
          }, 2500);
        }
        showGlobalAdminToast('Đã sao chép: ' + url);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(onSuccess).catch(() => {
          fallbackCopyText(url, onSuccess);
        });
      } else {
        fallbackCopyText(url, onSuccess);
      }
    }

    function fallbackCopyText(text, cb) {
      const textArea = document.createElement('textarea');
      textArea.value = text;
      textArea.style.position = 'fixed';
      textArea.style.left = '-9999px';
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      try {
        document.execCommand('copy');
        if (cb) cb();
      } catch (err) {}
      document.body.removeChild(textArea);
    }

    // Xóa link thiệp với thông báo "Bạn đã xóa thành công!"
    function deleteCard(cardId, cardTitle) {
      if (!confirm('Bạn có chắc chắn muốn xóa link thiệp "' + cardTitle + '"?')) {
        return;
      }

      const formData = new FormData();
      formData.append('action', 'delete_custom_card');
      formData.append('card_id', cardId);
      formData.append('ajax', '1');

      fetch('', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (data && data.success) {
          showGlobalAdminToast('Bạn đã xóa thành công!');
          const el = document.getElementById('card_box_' + cardId);
          if (el) el.remove();
          const countEl = document.getElementById('custom_cards_total_count');
          if (countEl) {
            const current = parseInt(countEl.textContent, 10);
            if (current > 1) countEl.textContent = current - 1;
          }
        } else {
          location.reload();
        }
      })
      .catch(() => {
        // Fallback standard submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        const a = document.createElement('input');
        a.type = 'hidden'; a.name = 'action'; a.value = 'delete_custom_card';
        form.appendChild(a);
        const cid = document.createElement('input');
        cid.type = 'hidden'; cid.name = 'card_id'; cid.value = cardId;
        form.appendChild(cid);
        document.body.appendChild(form);
        form.submit();
      });
    }

    function showGlobalAdminToast(msg) {
      let toast = document.getElementById('admin-global-toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.id = 'admin-global-toast';
        toast.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-[200] px-5 py-2.5 rounded-2xl bg-slate-900 border border-emerald-500/60 text-emerald-300 text-xs sm:text-sm font-semibold shadow-2xl flex items-center gap-2 pointer-events-none transition-all duration-300 opacity-0 translate-y-3';
        toast.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-400"></i><span id="admin-global-toast-msg"></span>';
        document.body.appendChild(toast);
      }
      const textEl = document.getElementById('admin-global-toast-msg');
      if (textEl) textEl.textContent = msg;
      toast.classList.remove('opacity-0', 'translate-y-3');
      toast.classList.add('opacity-100', 'translate-y-0');
      if (window._adminToastTimer) clearTimeout(window._adminToastTimer);
      window._adminToastTimer = setTimeout(() => {
        toast.classList.remove('opacity-100', 'translate-y-0');
        toast.classList.add('opacity-0', 'translate-y-3');
      }, 3000);
    }

    // =========================================================================
    // TRẠNG THÁI & ĐIỀU KHIỂN CREATOR STUDIO FULL-SCREEN (GIỐNG 100% PREVIEW)
    // =========================================================================
    let csCard = {
      id: null,
      slug: '',
      title: '',
      settings: JSON.parse(JSON.stringify(SERVER_INITIAL_SETTINGS)),
      wishes: JSON.parse(JSON.stringify(SERVER_INITIAL_WISHES))
    };
    let csActiveTab = 'doors';
    let csIsPreview = false;
    let csJustCreated = null;
    let csAudioPreview = null;
    let csIsAddingWish = false;
    let csEditingWish = null;

    function openCreatorStudioForNew(slug = '') {
      const initSettings = JSON.parse(JSON.stringify(SERVER_INITIAL_SETTINGS));
      csCard = {
        id: null,
        slug: sanitizeSlugJs(slug),
        title: initSettings.card_title || (slug ? 'Thiệp /' + slug : ''),
        settings: initSettings,
        wishes: JSON.parse(JSON.stringify(SERVER_INITIAL_WISHES))
      };
      csJustCreated = null;
      csIsPreview = false;
      csActiveTab = 'doors';
      renderCreatorStudio();
      const modal = document.getElementById('creatorStudioModal');
      if (modal) modal.classList.remove('hidden');
    }

    function openCreatorStudioForEdit(cardData) {
      if (!cardData) return;
      const initialSettings = Object.assign({}, SERVER_INITIAL_SETTINGS, cardData.settings || {});
      csCard = {
        id: cardData.id || null,
        slug: cardData.slug || '',
        title: initialSettings.card_title || cardData.title || '',
        settings: initialSettings,
        wishes: (cardData.wishes && cardData.wishes.length > 0) ? JSON.parse(JSON.stringify(cardData.wishes)) : JSON.parse(JSON.stringify(SERVER_INITIAL_WISHES))
      };
      csJustCreated = null;
      csIsPreview = false;
      csActiveTab = 'doors';
      renderCreatorStudio();
      const modal = document.getElementById('creatorStudioModal');
      if (modal) modal.classList.remove('hidden');
    }

    function closeCreatorStudio() {
      if (csAudioPreview) {
        csAudioPreview.pause();
        csAudioPreview = null;
      }
      const modal = document.getElementById('creatorStudioModal');
      if (modal) modal.classList.add('hidden');
    }

    function switchCreatorTab(tabKey) {
      csActiveTab = tabKey;
      csIsPreview = false;
      renderCreatorStudio();
    }

    function toggleCreatorPreview() {
      csIsPreview = !csIsPreview;
      renderCreatorStudio();
    }

    function renderCreatorStudio() {
      // 1. Cập nhật header inputs
      const slugInput = document.getElementById('cs_slug_input');
      const titleInput = document.getElementById('cs_title_input');
      if (slugInput) slugInput.value = csCard.slug;
      if (titleInput) titleInput.value = csCard.title;

      // Cập nhật footer preview slug
      const footerDisplay = document.getElementById('cs_footer_slug_display');
      if (footerDisplay) {
        footerDisplay.textContent = SITE_BASE_URL + (csCard.slug || 'tenthiep');
      }

      // 2. Chuyển đổi giữa 3 màn hình: Success vs Preview vs Edit
      const successView = document.getElementById('cs_success_view');
      const previewView = document.getElementById('cs_preview_view');
      const editView = document.getElementById('cs_edit_view');
      const footerBar = document.getElementById('cs_footer_bar');
      const btnTogglePreview = document.getElementById('cs_btn_toggle_preview');

      if (btnTogglePreview) {
        btnTogglePreview.innerHTML = csIsPreview
          ? '<i class="fa-solid fa-pen-to-square"></i> <span class="hidden sm:inline">Quay Lại Sửa Các Bước</span><span class="sm:hidden">Sửa</span>'
          : '<i class="fa-solid fa-eye"></i> <span class="hidden sm:inline">Xem Thử Giao Diện</span><span class="sm:hidden">Xem</span>';
      }

      if (csJustCreated) {
        if (successView) successView.classList.remove('hidden');
        if (previewView) previewView.classList.add('hidden');
        if (editView) editView.classList.add('hidden');
        if (footerBar) footerBar.classList.add('hidden');

        const cleanLink = SITE_BASE_URL + csJustCreated.slug;
        const directLink = SITE_BASE_URL + '?card=' + encodeURIComponent(csJustCreated.slug);
        
        const titleEl = document.getElementById('cs_success_card_title');
        if (titleEl) titleEl.textContent = csJustCreated.title;
        
        const cleanEl = document.getElementById('cs_success_clean_link');
        if (cleanEl) cleanEl.textContent = cleanLink;
        
        const directEl = document.getElementById('cs_success_direct_link');
        if (directEl) directEl.textContent = directLink;

        const pinEl = document.getElementById('cs_success_pin');
        if (pinEl) pinEl.textContent = csCard.settings.pin_code || '1234';

        const wishesCountEl = document.getElementById('cs_success_wishes_count');
        if (wishesCountEl) wishesCountEl.textContent = csCard.wishes.length;

        const copyCleanBtn = document.getElementById('cs_btn_copy_clean');
        if (copyCleanBtn) {
          copyCleanBtn.onclick = () => copyCardLink(cleanLink, copyCleanBtn);
        }
        const copyDirectBtn = document.getElementById('cs_btn_copy_direct');
        if (copyDirectBtn) {
          copyDirectBtn.onclick = () => copyCardLink(directLink, copyDirectBtn);
        }
        return;
      }

      if (successView) successView.classList.add('hidden');
      if (footerBar) footerBar.classList.remove('hidden');

      if (csIsPreview) {
        if (previewView) previewView.classList.remove('hidden');
        if (editView) editView.classList.add('hidden');

        const previewUrlEl = document.getElementById('cs_preview_url_display');
        if (previewUrlEl) previewUrlEl.textContent = SITE_BASE_URL + (csCard.slug || 'tenthiep');

        const iframe = document.getElementById('cs_preview_iframe');
        if (iframe) {
          // Nạp trang thiệp với tham số preview
          const previewTargetUrl = '../?card=' + encodeURIComponent(csCard.slug || 'tenthiep');
          if (iframe.src !== previewTargetUrl) {
            iframe.src = previewTargetUrl;
          }
        }
      } else {
        if (previewView) previewView.classList.add('hidden');
        if (editView) editView.classList.remove('hidden');

        // Cập nhật tab active button style
        const tabs = ['doors', 'wishes', 'cat_quotes', 'titles_music', 'event_counter', 'letters'];
        tabs.forEach(t => {
          const btn = document.getElementById('cs_tab_btn_' + t);
          const panel = document.getElementById('cs_tab_panel_' + t);
          if (btn) {
            if (t === csActiveTab) {
              btn.className = 'px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-md shadow-pink-500/20';
            } else {
              btn.className = 'px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-slate-950 text-slate-300 hover:bg-slate-800';
            }
          }
          if (panel) {
            if (t === csActiveTab) panel.classList.remove('hidden');
            else panel.classList.add('hidden');
          }
        });

        // Nạp dữ liệu các inputs theo csCard
        loadTabDoorsData();
        renderCreatorWishes();
        loadTabCatQuotesData();
        loadTabTitlesMusicData();
        loadTabEventCounterData();
        loadTabLettersData();
      }
    }

    // 1. Tab Doors & PIN
    function loadTabDoorsData() {
      const leftInput = document.getElementById('cs_door_left');
      const rightInput = document.getElementById('cs_door_right');
      const leftPreview = document.getElementById('cs_door_left_img');
      const rightPreview = document.getElementById('cs_door_right_img');
      const pinInput = document.getElementById('cs_pin_code');
      const pinLength = document.getElementById('cs_pin_length');
      const pinTitle = document.getElementById('cs_pin_title');
      const pinSub = document.getElementById('cs_pin_subtitle');
      const avatarInput = document.getElementById('cs_pin_avatar');
      const avatarImg = document.getElementById('cs_pin_avatar_img');

      if (leftInput) leftInput.value = csCard.settings.door_left_image || '';
      if (rightInput) rightInput.value = csCard.settings.door_right_image || '';
      if (leftPreview) leftPreview.src = csCard.settings.door_left_image || 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=800';
      if (rightPreview) rightPreview.src = csCard.settings.door_right_image || 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800';
      if (pinInput) pinInput.value = csCard.settings.pin_code || '1234';
      if (pinLength) pinLength.textContent = (csCard.settings.pin_code || '1234').length;
      if (pinTitle) pinTitle.value = csCard.settings.pin_title || '';
      if (pinSub) pinSub.value = csCard.settings.pin_subtitle || '';
      if (avatarInput) avatarInput.value = csCard.settings.pin_avatar_image || '';
      if (avatarImg) avatarImg.src = csCard.settings.pin_avatar_image || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300';
    }

    function setDoorPreset(side, url) {
      if (side === 'left') {
        csCard.settings.door_left_image = url;
      } else {
        csCard.settings.door_right_image = url;
      }
      loadTabDoorsData();
    }

    // 2. Tab Wishes
    function renderCreatorWishes() {
      const badge = document.getElementById('cs_wishes_badge');
      if (badge) badge.textContent = csCard.wishes.length;

      const container = document.getElementById('cs_wishes_grid');
      if (!container) return;
      container.innerHTML = '';

      csCard.wishes.forEach((w, idx) => {
        const itemEl = document.createElement('div');
        itemEl.className = 'bg-slate-900/80 border border-slate-800 hover:border-pink-500/40 rounded-2xl p-4 shadow-lg flex flex-col justify-between';
        itemEl.innerHTML = `
          <div>
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-800">
              <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-pink-500/20 text-pink-300 border border-pink-400/30 truncate max-w-[200px]">
                #${idx + 1}: ${escapeHtml(w.recipient)}
              </span>
              <span class="text-[10px] text-slate-500">ID: ${w.id}</span>
            </div>
            <div class="w-full h-36 rounded-xl overflow-hidden border border-slate-800 bg-black mb-3">
              <img src="${escapeHtml(w.img)}" alt="${escapeHtml(w.recipient)}" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/300x200?text=Loi+Anh'" />
            </div>
            <p class="text-xs text-slate-200 line-clamp-3 leading-relaxed mb-4">
              "${escapeHtml(w.msg)}"
            </p>
          </div>
          <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
            <button type="button" onclick="openEditWishModal(${w.id})" class="flex-1 py-1.5 px-3 rounded-lg bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 text-xs font-semibold flex items-center justify-center gap-1.5 transition-all">
              <i class="fa-solid fa-pen-to-square"></i> Chỉnh Sửa
            </button>
            <button type="button" onclick="deleteWishInCreator(${w.id})" class="py-1.5 px-2.5 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 text-xs font-semibold transition-all" title="Xóa lời chúc">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        `;
        container.appendChild(itemEl);
      });
    }

    function toggleAddWishForm(show) {
      csIsAddingWish = show;
      const form = document.getElementById('cs_add_wish_form');
      if (form) {
        if (show) form.classList.remove('hidden');
        else form.classList.add('hidden');
      }
    }

    function submitNewWishInCreator() {
      const recipient = document.getElementById('cs_new_wish_recipient').value.trim();
      const img = document.getElementById('cs_new_wish_img').value.trim() || 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=500';
      const msg = document.getElementById('cs_new_wish_msg').value.trim();

      if (!recipient || !msg) {
        showGlobalAdminToast('Vui lòng nhập người nhận và nội dung lời chúc!');
        return;
      }

      const newId = Date.now();
      csCard.wishes.push({
        id: newId,
        recipient,
        img,
        msg,
        sort_order: csCard.wishes.length + 1
      });

      document.getElementById('cs_new_wish_recipient').value = '';
      document.getElementById('cs_new_wish_img').value = '';
      document.getElementById('cs_new_wish_msg').value = '';
      toggleAddWishForm(false);
      renderCreatorWishes();
      showGlobalAdminToast('Đã thêm lời chúc mới vào thiệp!');
    }

    function deleteWishInCreator(id) {
      if (csCard.wishes.length <= 1) {
        showGlobalAdminToast('Thiệp cần có ít nhất 1 lời chúc!');
        return;
      }
      csCard.wishes = csCard.wishes.filter(w => w.id !== id);
      renderCreatorWishes();
      showGlobalAdminToast('Đã xóa lời chúc!');
    }

    function resetWishesInCreator() {
      csCard.wishes = JSON.parse(JSON.stringify(SERVER_INITIAL_WISHES));
      renderCreatorWishes();
      showGlobalAdminToast('Đã khôi phục toàn bộ lời chúc mẫu!');
    }

    function openEditWishModal(id) {
      const wish = csCard.wishes.find(w => w.id === id);
      if (!wish) return;
      csEditingWish = Object.assign({}, wish);
      document.getElementById('cs_edit_wish_id').value = wish.id;
      document.getElementById('cs_edit_wish_recipient').value = wish.recipient;
      document.getElementById('cs_edit_wish_img').value = wish.img;
      document.getElementById('cs_edit_wish_img_preview').src = wish.img;
      document.getElementById('cs_edit_wish_msg').value = wish.msg;
      document.getElementById('cs_wish_edit_modal').classList.remove('hidden');
    }

    function closeEditWishModal() {
      csEditingWish = null;
      document.getElementById('cs_wish_edit_modal').classList.add('hidden');
    }

    function saveEditWishInCreator() {
      const id = parseInt(document.getElementById('cs_edit_wish_id').value, 10);
      const recipient = document.getElementById('cs_edit_wish_recipient').value.trim();
      const img = document.getElementById('cs_edit_wish_img').value.trim();
      const msg = document.getElementById('cs_edit_wish_msg').value.trim();

      if (!recipient || !msg) {
        showGlobalAdminToast('Vui lòng nhập người nhận và nội dung!');
        return;
      }

      const idx = csCard.wishes.findIndex(w => w.id === id);
      if (idx !== -1) {
        csCard.wishes[idx].recipient = recipient;
        csCard.wishes[idx].img = img;
        csCard.wishes[idx].msg = msg;
      }
      closeEditWishModal();
      renderCreatorWishes();
      showGlobalAdminToast('Đã cập nhật lời chúc thành công!');
    }

    // 3. Tab Cat Quotes
    function loadTabCatQuotesData() {
      const ta = document.getElementById('cs_cat_quotes');
      if (ta) ta.value = csCard.settings.cat_quotes || '';
    }

    // 4. Tab Titles & Music
    function loadTabTitlesMusicData() {
      const t = document.getElementById('cs_card_title');
      const sub = document.getElementById('cs_card_subtitle');
      const music = document.getElementById('cs_bg_music_url');
      if (t) t.value = csCard.settings.card_title || '';
      if (sub) sub.value = csCard.settings.card_subtitle || '';
      if (music) music.value = csCard.settings.bg_music_url || '1.mp3';
    }

    function toggleCsAudioPreview() {
      const musicInput = document.getElementById('cs_bg_music_url');
      const url = musicInput ? musicInput.value.trim() : '1.mp3';
      const btn = document.getElementById('cs_btn_preview_audio');

      if (csAudioPreview) {
        csAudioPreview.pause();
        csAudioPreview = null;
        if (btn) btn.innerHTML = '<i class="fa-solid fa-play"></i> <span>Nghe Thử</span>';
        return;
      }

      csAudioPreview = new Audio(url);
      csAudioPreview.play().then(() => {
        if (btn) btn.innerHTML = '<i class="fa-solid fa-pause"></i> <span>Dừng</span>';
      }).catch(() => {
        showGlobalAdminToast('Không thể phát file nhạc này.');
        csAudioPreview = null;
        if (btn) btn.innerHTML = '<i class="fa-solid fa-play"></i> <span>Nghe Thử</span>';
      });

      csAudioPreview.onended = () => {
        csAudioPreview = null;
        if (btn) btn.innerHTML = '<i class="fa-solid fa-play"></i> <span>Nghe Thử</span>';
      };
    }

    // 5. Tab Event Counter
    function loadTabEventCounterData() {
      const enabled = document.getElementById('cs_event_counter_enabled');
      const title = document.getElementById('cs_event_counter_title');
      const targetDate = document.getElementById('cs_event_counter_target_date');
      const msg = document.getElementById('cs_event_counter_message');

      if (enabled) enabled.checked = csCard.settings.event_counter_enabled === '1' || csCard.settings.event_counter_enabled === true;
      if (title) title.value = csCard.settings.event_counter_title || 'Đếm ngược đến Ngày 20-10 🌸';
      if (targetDate) targetDate.value = csCard.settings.event_counter_target_date || '2026-10-20T00:00';
      if (msg) msg.value = csCard.settings.event_counter_message || 'Chúc bạn ngập tràn niềm vui và yêu thương trọn vẹn! 💖';
    }

    // 6. Tab Letters
    function loadTabLettersData() {
      const t = document.getElementById('cs_letter_modal_title');
      const sub = document.getElementById('cs_letter_modal_subtitle');
      const senderLbl = document.getElementById('cs_letter_sender_label');
      const senderPlh = document.getElementById('cs_letter_sender_placeholder');
      const contentLbl = document.getElementById('cs_letter_content_label');
      const contentPlh = document.getElementById('cs_letter_content_placeholder');
      const btnText = document.getElementById('cs_letter_submit_btn_text');
      const speech = document.getElementById('cs_letter_cat_speech');

      if (t) t.value = csCard.settings.letter_modal_title || 'Hộp Thư Yêu Thương';
      if (sub) sub.value = csCard.settings.letter_modal_subtitle !== undefined ? csCard.settings.letter_modal_subtitle : 'Hãy để lại tên và những lời chúc, cảm nghĩ ngọt ngào của bạn nhé! Lời nhắn sẽ được gửi thẳng về trang quản trị 💌';
      if (senderLbl) senderLbl.value = csCard.settings.letter_sender_label || 'Tên hoặc Biệt danh của bạn:';
      if (senderPlh) senderPlh.value = csCard.settings.letter_sender_placeholder || 'Ví dụ: Bé Út, Bạn thân, Người giấu tên...';
      if (contentLbl) contentLbl.value = csCard.settings.letter_content_label || 'Nội dung lời nhắn gửi trao:';
      if (contentPlh) contentPlh.value = csCard.settings.letter_content_placeholder || 'Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây...';
      if (btnText) btnText.value = csCard.settings.letter_submit_btn_text || 'Gửi Thư Ngay 💖';
      if (speech) speech.value = csCard.settings.letter_cat_speech || '💌 Bé mèo đã chuyển thư của "{sender}" trao tận tay rồi nhé meo meo~ 🐾💖';

      updateCsLetterLivePreview();
    }

    function updateCsLetterLivePreview() {
      const titleInput = document.getElementById('cs_letter_modal_title');
      const subInput = document.getElementById('cs_letter_modal_subtitle');
      const senderLblInput = document.getElementById('cs_letter_sender_label');
      const senderPlhInput = document.getElementById('cs_letter_sender_placeholder');
      const contentLblInput = document.getElementById('cs_letter_content_label');
      const contentPlhInput = document.getElementById('cs_letter_content_placeholder');
      const btnTextInput = document.getElementById('cs_letter_submit_btn_text');

      if (titleInput && document.getElementById('cs_preview_letter_modal_title')) {
        document.getElementById('cs_preview_letter_modal_title').textContent = titleInput.value || 'Hộp Thư Yêu Thương';
      }
      if (subInput && document.getElementById('cs_preview_letter_modal_subtitle')) {
        document.getElementById('cs_preview_letter_modal_subtitle').textContent = subInput.value || '';
      }
      if (senderLblInput && document.getElementById('cs_preview_letter_sender_label')) {
        document.getElementById('cs_preview_letter_sender_label').textContent = senderLblInput.value || 'Tên hoặc Biệt danh của bạn:';
      }
      if (senderPlhInput && document.getElementById('cs_preview_letter_sender_placeholder')) {
        document.getElementById('cs_preview_letter_sender_placeholder').textContent = senderPlhInput.value || '';
      }
      if (contentLblInput && document.getElementById('cs_preview_letter_content_label')) {
        document.getElementById('cs_preview_letter_content_label').textContent = contentLblInput.value || 'Nội dung lời nhắn gửi trao:';
      }
      if (contentPlhInput && document.getElementById('cs_preview_letter_content_placeholder')) {
        document.getElementById('cs_preview_letter_content_placeholder').textContent = contentPlhInput.value || '';
      }
      if (btnTextInput && document.getElementById('cs_preview_letter_submit_btn_text')) {
        document.getElementById('cs_preview_letter_submit_btn_text').textContent = btnTextInput.value || 'Gửi Thư Ngay 💖';
      }
    }

    function resetCsDefaultLetterBoxTexts() {
      const defaults = {
        title: 'Hộp Thư Yêu Thương',
        subtitle: 'Hãy để lại tên và những lời chúc, cảm nghĩ ngọt ngào của bạn nhé! Lời nhắn sẽ được gửi thẳng về trang quản trị 💌',
        senderLabel: 'Tên hoặc Biệt danh của bạn:',
        senderPlaceholder: 'Ví dụ: Bé Út, Bạn thân, Người giấu tên...',
        contentLabel: 'Nội dung lời nhắn gửi trao:',
        contentPlaceholder: 'Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây...',
        btnText: 'Gửi Thư Ngay 💖',
        catSpeech: '💌 Bé mèo đã chuyển thư của "{sender}" trao tận tay rồi nhé meo meo~ 🐾💖'
      };

      if (document.getElementById('cs_letter_modal_title')) document.getElementById('cs_letter_modal_title').value = defaults.title;
      if (document.getElementById('cs_letter_modal_subtitle')) document.getElementById('cs_letter_modal_subtitle').value = defaults.subtitle;
      if (document.getElementById('cs_letter_sender_label')) document.getElementById('cs_letter_sender_label').value = defaults.senderLabel;
      if (document.getElementById('cs_letter_sender_placeholder')) document.getElementById('cs_letter_sender_placeholder').value = defaults.senderPlaceholder;
      if (document.getElementById('cs_letter_content_label')) document.getElementById('cs_letter_content_label').value = defaults.contentLabel;
      if (document.getElementById('cs_letter_content_placeholder')) document.getElementById('cs_letter_content_placeholder').value = defaults.contentPlaceholder;
      if (document.getElementById('cs_letter_submit_btn_text')) document.getElementById('cs_letter_submit_btn_text').value = defaults.btnText;
      if (document.getElementById('cs_letter_cat_speech')) document.getElementById('cs_letter_cat_speech').value = defaults.catSpeech;

      if (csCard && csCard.settings) {
        csCard.settings.letter_modal_title = defaults.title;
        csCard.settings.letter_modal_subtitle = defaults.subtitle;
        csCard.settings.letter_sender_label = defaults.senderLabel;
        csCard.settings.letter_sender_placeholder = defaults.senderPlaceholder;
        csCard.settings.letter_content_label = defaults.contentLabel;
        csCard.settings.letter_content_placeholder = defaults.contentPlaceholder;
        csCard.settings.letter_submit_btn_text = defaults.btnText;
        csCard.settings.letter_cat_speech = defaults.catSpeech;
      }

      updateCsLetterLivePreview();
      showGlobalAdminToast('Đã khôi phục toàn bộ chữ mẫu ban đầu của Hộp Thư cho thiệp này!');
    }

    // Cập nhật giá trị vào csCard khi người dùng gõ
    function syncInputsToCsCard() {
      const slugInput = document.getElementById('cs_slug_input');
      if (slugInput) csCard.slug = sanitizeSlugJs(slugInput.value);

      // Doors & PIN
      const dLeft = document.getElementById('cs_door_left');
      const dRight = document.getElementById('cs_door_right');
      const pin = document.getElementById('cs_pin_code');
      const pinTitle = document.getElementById('cs_pin_title');
      const pinSub = document.getElementById('cs_pin_subtitle');
      const avatar = document.getElementById('cs_pin_avatar');

      if (dLeft) csCard.settings.door_left_image = dLeft.value.trim();
      if (dRight) csCard.settings.door_right_image = dRight.value.trim();
      if (pin) csCard.settings.pin_code = pin.value.trim();
      if (pinTitle) csCard.settings.pin_title = pinTitle.value.trim();
      if (pinSub) csCard.settings.pin_subtitle = pinSub.value.trim();
      if (avatar) csCard.settings.pin_avatar_image = avatar.value.trim();

      // Cat Quotes
      const cq = document.getElementById('cs_cat_quotes');
      if (cq) csCard.settings.cat_quotes = cq.value;

      // Titles & Music
      const cTitle = document.getElementById('cs_card_title');
      const cSub = document.getElementById('cs_card_subtitle');
      const music = document.getElementById('cs_bg_music_url');
      if (cTitle) csCard.settings.card_title = cTitle.value.trim();
      if (cSub) csCard.settings.card_subtitle = cSub.value.trim();
      if (music) csCard.settings.bg_music_url = music.value.trim();

      // Tiêu đề thiệp đồng bộ trực tiếp với Tiêu đề thiệp sau khi mở cửa
      csCard.title = csCard.settings.card_title || ('Thiệp /' + (csCard.slug || 'tenthiep'));

      // Event Counter
      const ecEnabled = document.getElementById('cs_event_counter_enabled');
      const ecTitle = document.getElementById('cs_event_counter_title');
      const ecDate = document.getElementById('cs_event_counter_target_date');
      const ecMsg = document.getElementById('cs_event_counter_message');
      if (ecEnabled) csCard.settings.event_counter_enabled = ecEnabled.checked ? '1' : '0';
      if (ecTitle) csCard.settings.event_counter_title = ecTitle.value.trim();
      if (ecDate) csCard.settings.event_counter_target_date = ecDate.value.trim();
      if (ecMsg) csCard.settings.event_counter_message = ecMsg.value.trim();

      // Letters (8 trường tùy chỉnh hoàn chỉnh)
      const ltTitle = document.getElementById('cs_letter_modal_title');
      const ltSub = document.getElementById('cs_letter_modal_subtitle');
      const ltSenderLbl = document.getElementById('cs_letter_sender_label');
      const ltSenderPlh = document.getElementById('cs_letter_sender_placeholder');
      const ltContentLbl = document.getElementById('cs_letter_content_label');
      const ltContentPlh = document.getElementById('cs_letter_content_placeholder');
      const ltBtnText = document.getElementById('cs_letter_submit_btn_text');
      const ltSpeech = document.getElementById('cs_letter_cat_speech');

      if (ltTitle) csCard.settings.letter_modal_title = ltTitle.value.trim();
      if (ltSub) csCard.settings.letter_modal_subtitle = ltSub.value.trim();
      if (ltSenderLbl) csCard.settings.letter_sender_label = ltSenderLbl.value.trim();
      if (ltSenderPlh) csCard.settings.letter_sender_placeholder = ltSenderPlh.value.trim();
      if (ltContentLbl) csCard.settings.letter_content_label = ltContentLbl.value.trim();
      if (ltContentPlh) csCard.settings.letter_content_placeholder = ltContentPlh.value.trim();
      if (ltBtnText) csCard.settings.letter_submit_btn_text = ltBtnText.value.trim();
      if (ltSpeech) csCard.settings.letter_cat_speech = ltSpeech.value.trim();
    }

    // LƯU VÀ TẠO LINK THIỆP MỜI (AJAX)
    function submitCreatorStudio() {
      syncInputsToCsCard();

      if (!csCard.slug) {
        showGlobalAdminToast('Vui lòng nhập tên đường dẫn web (slug)!');
        const slugInput = document.getElementById('cs_slug_input');
        if (slugInput) slugInput.focus();
        return;
      }

      // Kiểm tra trùng lặp slug
      const isDuplicate = existingSlugs.some(s => s === csCard.slug && (!csCard.id || s !== csCard.originalSlug));
      if (isDuplicate && !csCard.id) {
        showGlobalAdminToast('Tên đường dẫn web này đã trùng với danh sách các link thiệp của bạn! Vui lòng đổi tên khác.');
        const slugInput = document.getElementById('cs_slug_input');
        if (slugInput) slugInput.focus();
        return;
      }

      csCard.title = csCard.settings.card_title || ('Thiệp /' + csCard.slug);

      const btn = document.getElementById('cs_btn_submit');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Đang Lưu Link...</span>';
      }

      const formData = new FormData();
      formData.append('action', 'save_custom_card');
      formData.append('ajax', '1');
      if (csCard.id) formData.append('card_id', csCard.id);
      formData.append('card_slug', csCard.slug);
      formData.append('card_title_val', csCard.settings.card_title || '');
      formData.append('card_subtitle_val', csCard.settings.card_subtitle || '');
      formData.append('card_title', csCard.settings.card_title || ('Thiệp /' + csCard.slug));
      formData.append('card_settings_json', JSON.stringify(csCard.settings));
      formData.append('card_wishes_json', JSON.stringify(csCard.wishes));

      fetch('', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
      })
      .then(r => r.json())
      .then(data => {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-link"></i> <span>Lấy Link Thiệp Mời 🔗</span>';
        }

        if (data && data.success) {
          const cleanLink = SITE_BASE_URL + data.slug;
          copyCardLink(cleanLink);
          if (!existingSlugs.includes(data.slug)) {
            existingSlugs.push(data.slug);
          }
          csJustCreated = {
            slug: data.slug,
            title: data.title
          };
          renderCreatorStudio();
          showGlobalAdminToast('Đã tạo và sao chép link thiệp thành công!');
        } else {
          showGlobalAdminToast(data.message || 'Lỗi lưu thiệp, vui lòng kiểm tra lại!');
        }
      })
      .catch(err => {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-link"></i> <span>Lấy Link Thiệp Mời 🔗</span>';
        }
        showGlobalAdminToast('Lỗi mạng hoặc máy chủ!');
      });
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  </script>

  <!-- ========================================================================= -->
  <!-- MODAL TẠO & LẤY LINK THIỆP MỜI - FULL MÀN HÌNH (CREATOR STUDIO CHO PHP)    -->
  <!-- ========================================================================= -->
  <div id="creatorStudioModal" class="fixed inset-0 z-50 bg-slate-950 flex flex-col w-screen h-screen m-0 p-0 overflow-hidden hidden animate-fadeIn">
    <div class="bg-slate-900 w-full h-full flex flex-col overflow-hidden rounded-none border-0 shadow-none">
      
      <!-- HEADER THANH TIÊU ĐỀ CỦA BẢNG TẠO LINK -->
      <div class="bg-gradient-to-r from-slate-950 via-rose-950/40 to-slate-950 px-5 sm:px-7 py-3 border-b border-pink-500/30 flex items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-400 via-rose-500 to-pink-500 flex items-center justify-center text-white text-base shadow-lg shadow-rose-500/30 shrink-0">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
          </div>
          <div>
            <h3 class="text-sm sm:text-base font-bold text-pink-100 flex items-center gap-2">
              Tạo Link Thiệp Mời Riêng Biệt (Full Màn Hình)
              <span class="text-[10px] font-normal px-2 py-0.5 rounded-full bg-pink-500/20 text-pink-300 border border-pink-500/30">
                100% Giao Diện Gốc
              </span>
            </h3>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Nút Lưu Thiệp Ngay Trên Header -->
          <button type="button" onclick="submitCreatorStudio()" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:brightness-110 active:scale-95 text-white font-bold text-xs shadow-md shadow-emerald-500/25 flex items-center gap-1.5 transition-all" title="Lưu lại toàn bộ các bước của thiệp này">
            <i class="fa-solid fa-floppy-disk text-emerald-100"></i>
            <span>Lưu Thiệp 💾</span>
          </button>

          <!-- Nút Bật / Tắt Xem Thử Ngay Trên Header -->
          <button type="button" id="cs_btn_toggle_preview" onclick="toggleCreatorPreview()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-pink-200 text-xs font-bold flex items-center gap-1.5 transition-all shadow-md border border-slate-700">
            <i class="fa-solid fa-eye"></i>
            <span class="hidden sm:inline">Xem Thử Giao Diện</span>
            <span class="sm:hidden">Xem</span>
          </button>

          <button type="button" onclick="closeCreatorStudio()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all text-sm" title="Đóng bảng">
            ✕
          </button>
        </div>
      </div>

      <!-- THANH ĐỊA CHỈ: TÊN MIỀN WEB + TÊN TỰ NHẬP SAU DẤU / -->
      <div class="bg-slate-950 px-4 py-2 border-b border-slate-800/80 shrink-0 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2 w-full flex-1">
          <span class="text-[11px] font-bold text-pink-300 uppercase shrink-0">Đường Dẫn Web:</span>
          <div class="flex items-center bg-slate-900 border border-pink-500/40 focus-within:border-amber-400 rounded-lg overflow-hidden flex-1 max-w-xl">
            <span class="text-[11px] text-slate-400 font-mono bg-slate-950 px-2.5 py-1.5 border-r border-slate-800 select-none">
              <?= htmlspecialchars($baseUrl) ?>
            </span>
            <span class="text-xs font-bold text-amber-400 font-mono px-0.5">/</span>
            <input type="text" id="cs_slug_input"
                   oninput="csCard.slug = sanitizeSlugJs(this.value); const f=document.getElementById('cs_footer_slug_display'); if(f) f.textContent=SITE_BASE_URL+(csCard.slug||'tenthiep');"
                   placeholder="ten-thiep"
                   class="w-full bg-transparent text-xs text-amber-300 font-bold font-mono px-2 py-1.5 focus:outline-none" />
          </div>
        </div>
      </div>

      <!-- NỘI DUNG CHÍNH CỦA BẢNG TẠO LINK -->
      <div class="p-4 sm:p-6 overflow-y-auto flex-1 space-y-5">
        
        <!-- 1. MÀN HÌNH TẠO THÀNH CÔNG (SUCCESS VIEW) -->
        <div id="cs_success_view" class="space-y-6 animate-fadeIn py-2 hidden">
          <div class="p-5 rounded-3xl bg-gradient-to-r from-emerald-950/90 to-teal-950/80 border-2 border-emerald-500/60 shadow-xl flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-400 to-teal-500 flex items-center justify-center text-slate-950 text-2xl font-bold shadow-lg shadow-emerald-500/30 shrink-0">
              ✓
            </div>
            <div class="flex-1">
              <h4 class="text-base sm:text-lg font-bold text-emerald-100">
                Tạo & Lấy Link Thiệp Thành Công! 🎉
              </h4>
              <p class="text-xs sm:text-sm text-emerald-200/90 mt-1 leading-relaxed">
                Thiệp <strong id="cs_success_card_title" class="text-white"></strong> đã được lưu thành công với toàn bộ các bước bạn vừa tùy biến. Giao diện website giữ nguyên 100% hiệu ứng hoàng gia lộng lẫy!
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- LINK 1: TÊN MIỀN WEB / TÊN BẠN TỰ NHẬP -->
            <div class="bg-slate-950 border-2 border-pink-500/40 rounded-2xl p-4 space-y-3 shadow-lg">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-300 font-bold uppercase text-[11px] flex items-center gap-1.5">
                  <i class="fa-solid fa-link text-pink-400"></i>
                  1. Link Website Của Bạn (Rút gọn .htaccess)
                </span>
                <span class="px-2 py-0.5 rounded-full bg-pink-500/20 text-pink-300 font-bold text-[10px]">
                  Đẹp nhất ✨
                </span>
              </div>

              <div id="cs_success_clean_link" class="font-mono text-amber-300 text-sm font-bold bg-black/60 p-3 rounded-xl border border-slate-800 break-all select-all">
                ...
              </div>

              <button type="button" id="cs_btn_copy_clean" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-500 via-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white font-bold text-xs shadow-md shadow-pink-500/20 flex items-center justify-center gap-2 transition-all">
                <i class="fa-solid fa-copy"></i>
                <span>Sao Chép Link Này</span>
              </button>
            </div>

            <!-- LINK 2: LINK CHUẨN TOÀN CẦU (?card=) -->
            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 space-y-3 shadow-lg">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-300 font-bold uppercase text-[11px] flex items-center gap-1.5">
                  <i class="fa-solid fa-globe text-emerald-400"></i>
                  2. Link Chuẩn Toàn Cầu (Mọi hosting PHP)
                </span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold text-[10px]">
                  100% tương thích ✨
                </span>
              </div>

              <div id="cs_success_direct_link" class="font-mono text-slate-300 text-sm font-bold bg-black/60 p-3 rounded-xl border border-slate-800 break-all select-all">
                ...
              </div>

              <button type="button" id="cs_btn_copy_direct" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-95 text-slate-200 text-xs font-semibold flex items-center justify-center gap-2 transition-all border border-slate-700">
                <i class="fa-solid fa-copy"></i>
                <span>Sao Chép Link Chuẩn (?card=)</span>
              </button>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs text-slate-300 flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-400 text-base shrink-0"></i>
            <span>
              Mã PIN mở cửa của thiệp này là: <strong id="cs_success_pin" class="text-amber-300 font-mono text-sm px-2 py-0.5 rounded bg-black">1234</strong>. Giao diện hoa rơi, nhạc nền và <span id="cs_success_wishes_count"></span> lời chúc đã được lưu riêng cho link này!
            </span>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-800">
            <button type="button" onclick="openCreatorStudioForNew()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-pink-300 text-xs font-bold flex items-center gap-2 transition-all">
              <i class="fa-solid fa-plus"></i>
              <span>Tạo Thêm Link Thiệp Khác</span>
            </button>

            <div class="flex items-center gap-2">
              <button type="button" onclick="csJustCreated = null; renderCreatorStudio();" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-all">
                Tiếp Tục Chỉnh Sửa
              </button>
              <button type="button" onclick="window.open(SITE_BASE_URL + csJustCreated.slug, '_blank');" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:brightness-110 active:scale-95 text-white font-bold text-xs sm:text-sm shadow-lg shadow-teal-500/25 flex items-center gap-2 transition-all">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Mở Xem Thử Thiệp Ngay</span>
              </button>
            </div>
          </div>
        </div>

        <!-- 2. MÀN HÌNH XEM THỬ GIAO DIỆN (PREVIEW VIEW) -->
        <div id="cs_preview_view" class="space-y-4 animate-fadeIn hidden">
          <div class="p-3.5 rounded-2xl bg-gradient-to-r from-amber-950/60 via-slate-900 to-pink-950/60 border border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 text-xs text-amber-200">
              <i class="fa-solid fa-eye text-amber-400 text-base shrink-0"></i>
              <div>
                <strong>Đang Xem Thử Thiệp:</strong> <span id="cs_preview_url_display" class="font-mono text-amber-300"></span>
                <span class="block text-[11px] text-slate-400">Giao diện giữ nguyên 100% hiệu ứng hoàng gia! Thử gõ mã PIN mở cửa để vào xem thiệp.</span>
              </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
              <button type="button" onclick="toggleCreatorPreview()" class="flex-1 sm:flex-none px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center justify-center gap-1.5 transition-all">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Quay Lại Chỉnh Sửa</span>
              </button>
              <button type="button" onclick="submitCreatorStudio()" class="flex-1 sm:flex-none px-4 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-rose-500 hover:brightness-110 active:scale-95 text-white font-bold text-xs shadow-md shadow-rose-500/25 flex items-center justify-center gap-1.5 transition-all">
                <i class="fa-solid fa-link"></i>
                <span>Lấy Link Này 🔗</span>
              </button>
            </div>
          </div>

          <div class="rounded-3xl overflow-hidden border-2 border-pink-500/30 shadow-2xl bg-black relative">
            <iframe id="cs_preview_iframe" title="Xem thử thiệp" class="w-full h-[550px] sm:h-[620px] border-0" sandbox="allow-scripts allow-same-origin allow-forms allow-downloads allow-modals" allow="autoplay *; fullscreen"></iframe>
          </div>
        </div>

        <!-- 3. MÀN HÌNH TẤT CẢ CÁC BƯỚC CHỈNH SỬA (EDIT VIEW) -->
        <div id="cs_edit_view" class="space-y-5 animate-fadeIn">
          <!-- THANH ĐIỀU HƯỚNG CÁC BƯỚC -->
          <div class="flex flex-wrap gap-2 border-b border-slate-800 pb-3">
            <button type="button" id="cs_tab_btn_doors" onclick="switchCreatorTab('doors')" class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-md shadow-pink-500/20">
              <i class="fa-solid fa-door-open"></i>
              <span>1. Hai Cánh Cửa & Mật Khẩu PIN</span>
            </button>

            <button type="button" id="cs_tab_btn_wishes" onclick="switchCreatorTab('wishes')" class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-slate-950 text-slate-300 hover:bg-slate-800">
              <i class="fa-solid fa-gift"></i>
              <span>2. Tất Cả Lời Chúc (<span id="cs_wishes_badge">0</span>)</span>
            </button>

            <button type="button" id="cs_tab_btn_cat_quotes" onclick="switchCreatorTab('cat_quotes')" class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-slate-950 text-slate-300 hover:bg-slate-800">
              <i class="fa-solid fa-cat text-pink-400"></i>
              <span>3. Lời Chúc Bé Mèo 🐾</span>
            </button>

            <button type="button" id="cs_tab_btn_titles_music" onclick="switchCreatorTab('titles_music')" class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-slate-950 text-slate-300 hover:bg-slate-800">
              <i class="fa-solid fa-music text-amber-400"></i>
              <span>4. Nhạc Nền & Tiêu Đề</span>
            </button>

            <button type="button" id="cs_tab_btn_event_counter" onclick="switchCreatorTab('event_counter')" class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-slate-950 text-slate-300 hover:bg-slate-800">
              <i class="fa-solid fa-hourglass-half text-amber-300"></i>
              <span>5. Đếm Ngược Mở Cửa ⏳</span>
            </button>

            <button type="button" id="cs_tab_btn_letters" onclick="switchCreatorTab('letters')" class="px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-slate-950 text-slate-300 hover:bg-slate-800">
              <i class="fa-solid fa-envelope text-pink-400"></i>
              <span>6. Tùy Chỉnh Hộp Thư 💌</span>
            </button>

            <!-- Nút Lưu Nhanh Ngay Trên Thanh Tabs -->
            <button type="button" onclick="submitCreatorStudio()" class="ml-auto px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-500/25 active:scale-95 shrink-0" title="Bấm để lưu toàn bộ các bước của thiệp này">
              <i class="fa-solid fa-floppy-disk text-emerald-100"></i>
              <span>Lưu Thiệp 💾</span>
            </button>
          </div>

          <!-- BƯỚC 1: HAI CÁNH CỬA & MẬT KHẨU PIN -->
          <div id="cs_tab_panel_doors" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- CỬA TRÁI -->
              <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 space-y-3">
                <label class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-image text-rose-400"></i>
                  Ảnh Cánh Cửa Trái (Mở Sang Trái):
                </label>
                <input type="text" id="cs_door_left"
                       oninput="csCard.settings.door_left_image = this.value.trim(); const img = document.getElementById('cs_door_left_img'); if(img) img.src = this.value;"
                       placeholder="Dán link ảnh cửa trái..."
                       class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                <div class="flex items-center gap-3">
                  <div class="w-16 h-24 rounded-lg overflow-hidden border border-slate-700 bg-black shrink-0">
                    <img id="cs_door_left_img" src="" alt="Cửa trái" class="w-full h-full object-cover" />
                  </div>
                  <div class="text-[11px] text-slate-400 space-y-1">
                    <div>Ảnh xuất hiện ở cánh cửa bên trái.</div>
                    <div class="flex gap-1.5 flex-wrap">
                      <button type="button" onclick="setDoorPreset('left', 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=800')" class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-pink-300 hover:bg-slate-700">Hoa Hồng Gốc</button>
                      <button type="button" onclick="setDoorPreset('left', 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=600')" class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-amber-300 hover:bg-slate-700">Cổng Vàng</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- CỬA PHẢI -->
              <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 space-y-3">
                <label class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-image text-rose-400"></i>
                  Ảnh Cánh Cửa Phải (Mở Sang Phải):
                </label>
                <input type="text" id="cs_door_right"
                       oninput="csCard.settings.door_right_image = this.value.trim(); const img = document.getElementById('cs_door_right_img'); if(img) img.src = this.value;"
                       placeholder="Dán link ảnh cửa phải..."
                       class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                <div class="flex items-center gap-3">
                  <div class="w-16 h-24 rounded-lg overflow-hidden border border-slate-700 bg-black shrink-0">
                    <img id="cs_door_right_img" src="" alt="Cửa phải" class="w-full h-full object-cover" />
                  </div>
                  <div class="text-[11px] text-slate-400 space-y-1">
                    <div>Ảnh xuất hiện ở cánh cửa bên phải.</div>
                    <div class="flex gap-1.5 flex-wrap">
                      <button type="button" onclick="setDoorPreset('right', 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800')" class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-pink-300 hover:bg-slate-700">Hoa Hồng Gốc</button>
                      <button type="button" onclick="setDoorPreset('right', 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=600')" class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-amber-300 hover:bg-slate-700">Cổng Vàng</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- MẬT KHẨU PIN & BÀN PHÍM -->
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-5 space-y-4">
              <h4 class="text-xs font-bold text-pink-200 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-key text-amber-400"></i>
                Cài Đặt Mật Khẩu PIN Mở Cửa Cho Thiệp Này
              </h4>

              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                  <label class="text-[11px] font-bold text-pink-300 block mb-1">
                    Mật Khẩu PIN (Ví dụ: 123456 hoặc 1234):
                  </label>
                  <input type="text" id="cs_pin_code" maxlength="8"
                         oninput="this.value = this.value.replace(/\D/g, ''); csCard.settings.pin_code = this.value; const l = document.getElementById('cs_pin_length'); if(l) l.textContent = this.value.length;"
                         placeholder="1234"
                         class="w-full px-3.5 py-2.5 bg-slate-900 border-2 border-amber-500/40 rounded-xl text-base font-mono font-bold text-amber-300 focus:outline-none focus:border-amber-400" />
                  <p class="text-[10px] text-slate-500 mt-1">Độ dài <span id="cs_pin_length">4</span> số. Người nhận bấm số này trên cửa để mở.</p>
                </div>

                <div>
                  <label class="text-[11px] font-bold text-pink-300 block mb-1">Tiêu Đề Bảng PIN:</label>
                  <input type="text" id="cs_pin_title"
                         oninput="csCard.settings.pin_title = this.value;"
                         placeholder="Mật Khẩu Bí Mật ~"
                         class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                </div>

                <div>
                  <label class="text-[11px] font-bold text-pink-300 block mb-1">Phụ Đề Hướng Dẫn:</label>
                  <input type="text" id="cs_pin_subtitle"
                         oninput="csCard.settings.pin_subtitle = this.value;"
                         placeholder="Nhập mã bí mật để mở cửa"
                         class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                </div>
              </div>

              <div>
                <label class="text-[11px] font-bold text-pink-300 block mb-1">Link Avatar Tròn Trên Bàn Phím PIN:</label>
                <div class="flex items-center gap-3">
                  <img id="cs_pin_avatar_img" src="" alt="Avatar" class="w-10 h-10 rounded-full border border-pink-400 object-cover shrink-0" />
                  <input type="text" id="cs_pin_avatar"
                         oninput="csCard.settings.pin_avatar_image = this.value.trim(); const img = document.getElementById('cs_pin_avatar_img'); if(img) img.src = this.value;"
                         placeholder="Link ảnh avatar tròn..."
                         class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                </div>
              </div>
            </div>

            <!-- THANH THAO TÁC BƯỚC 1: LƯU & TIẾP TỤC -->
            <div class="pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/70 p-4 rounded-2xl">
              <div class="text-xs text-slate-400 flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-pink-500/20 text-pink-300 flex items-center justify-center font-bold text-xs">1</span>
                <span>Bước 1 / 6: Hai Cánh Cửa & PIN</span>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" onclick="submitCreatorStudio()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition-all">
                  <i class="fa-solid fa-floppy-disk"></i>
                  <span>Lưu Bước Này 💾</span>
                </button>
                <button type="button" onclick="switchCreatorTab('wishes')" class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white text-xs font-bold shadow-md shadow-pink-500/20 flex items-center gap-1.5 transition-all">
                  <span>Sang Bước 2: Lời Chúc</span>
                  <i class="fa-solid fa-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- BƯỚC 2: TẤT CẢ LỜI CHÚC -->
          <div id="cs_tab_panel_wishes" class="space-y-6 hidden">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div>
                <h4 class="text-base font-bold text-pink-200 flex items-center gap-2">
                  <i class="fa-solid fa-gift text-pink-400"></i> Quản Lý & Chỉnh Sửa Tất Cả Lời Chúc
                </h4>
                <p class="text-xs text-slate-400 mt-0.5">
                  Chỉnh ảnh lời chúc, đổi người nhận, đổi nội dung hoặc thêm lời chúc mới cho thiệp này
                </p>
              </div>

              <div class="flex items-center gap-2">
                <button type="button" onclick="toggleAddWishForm(true)" class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 text-white text-xs font-bold shadow-md flex items-center gap-1.5">
                  <i class="fa-solid fa-plus"></i> Thêm Lời Chúc Mới
                </button>
                <button type="button" onclick="resetWishesInCreator()" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1">
                  <i class="fa-solid fa-rotate-left"></i> Khôi phục mẫu
                </button>
              </div>
            </div>

            <!-- FORM THÊM LỜI CHÚC MỚI INLINE -->
            <div id="cs_add_wish_form" class="bg-slate-950 border border-pink-500/40 rounded-3xl p-5 space-y-4 shadow-xl animate-fadeIn hidden">
              <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <h5 class="text-xs font-bold text-pink-300 uppercase">Thêm Lời Chúc Mới Vào Thiệp</h5>
                <button type="button" onclick="toggleAddWishForm(false)" class="text-slate-400 hover:text-white">✕</button>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-semibold text-pink-300 block mb-1">Tên Người Nhận (Ví dụ: Mẹ yêu, Lan Anh...):</label>
                  <input type="text" id="cs_new_wish_recipient" placeholder="Gửi tặng..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                </div>
                <div>
                  <label class="text-xs font-semibold text-pink-300 block mb-1">Link Ảnh Đính Kèm (URL):</label>
                  <input type="text" id="cs_new_wish_img" placeholder="https://..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-mono text-white focus:outline-none focus:border-pink-400" />
                </div>
              </div>
              <div>
                <label class="text-xs font-semibold text-pink-300 block mb-1">Nội Dung Lời Chúc Yêu Thương:</label>
                <textarea id="cs_new_wish_msg" rows="3" placeholder="Viết những lời chúc ngọt ngào nhất..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400"></textarea>
              </div>
              <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" onclick="toggleAddWishForm(false)" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold">Hủy</button>
                <button type="button" onclick="submitNewWishInCreator()" class="px-5 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 text-white text-xs font-bold shadow-md">Thêm Lời Chúc</button>
              </div>
            </div>

            <!-- DANH SÁCH LỜI CHÚC (GRID 100% GIỐNG ADMIN) -->
            <div id="cs_wishes_grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"></div>

            <!-- THANH THAO TÁC BƯỚC 2: LƯU & CHUYỂN BƯỚC -->
            <div class="pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/70 p-4 rounded-2xl">
              <button type="button" onclick="switchCreatorTab('doors')" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-arrow-left"></i>
                <span>← Bước 1: Cửa & PIN</span>
              </button>
              <div class="flex items-center gap-2">
                <button type="button" onclick="submitCreatorStudio()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition-all">
                  <i class="fa-solid fa-floppy-disk"></i>
                  <span>Lưu Bước Này 💾</span>
                </button>
                <button type="button" onclick="switchCreatorTab('cat_quotes')" class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white text-xs font-bold shadow-md shadow-pink-500/20 flex items-center gap-1.5 transition-all">
                  <span>Sang Bước 3: Bé Mèo</span>
                  <i class="fa-solid fa-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- BƯỚC 3: LỜI CHÚC BÉ MÈO -->
          <div id="cs_tab_panel_cat_quotes" class="space-y-4 hidden">
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-5 space-y-4">
              <div class="flex items-center gap-3 pb-3 border-b border-slate-800">
                <i class="fa-solid fa-cat text-pink-400 text-2xl"></i>
                <div>
                  <h4 class="text-sm font-bold text-pink-200">Lời Chúc Bé Mèo Khi Chạm Vào 🐾</h4>
                  <p class="text-[11px] text-slate-400">Mỗi dòng là 1 câu chúc khác nhau. Mỗi khi người nhận bấm vào bé mèo thì bé mèo sẽ nói 1 câu ngẫu nhiên!</p>
                </div>
              </div>

              <div>
                <label class="text-xs font-bold text-pink-300 block mb-1.5">Danh Sách Câu Nói Của Bé Mèo (Mỗi dòng 1 câu):</label>
                <textarea id="cs_cat_quotes" rows="6" oninput="csCard.settings.cat_quotes = this.value;" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-mono leading-relaxed focus:outline-none focus:border-pink-400"></textarea>
              </div>
            </div>

            <!-- THANH THAO TÁC BƯỚC 3: LƯU & CHUYỂN BƯỚC -->
            <div class="pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/70 p-4 rounded-2xl">
              <button type="button" onclick="switchCreatorTab('wishes')" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-arrow-left"></i>
                <span>← Bước 2: Lời Chúc</span>
              </button>
              <div class="flex items-center gap-2">
                <button type="button" onclick="submitCreatorStudio()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition-all">
                  <i class="fa-solid fa-floppy-disk"></i>
                  <span>Lưu Bước Này 💾</span>
                </button>
                <button type="button" onclick="switchCreatorTab('titles_music')" class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white text-xs font-bold shadow-md shadow-pink-500/20 flex items-center gap-1.5 transition-all">
                  <span>Sang Bước 4: Nhạc Nền</span>
                  <i class="fa-solid fa-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- BƯỚC 4: NHẠC NỀN & TIÊU ĐỀ -->
          <div id="cs_tab_panel_titles_music" class="space-y-4 hidden">
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 space-y-3">
              <h4 class="text-xs font-bold text-pink-200 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-heading text-rose-400"></i> Tiêu Đề Thiệp Sau Khi Mở Cửa
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="text-[11px] font-bold text-pink-300 block mb-1">Tiêu Đề Chính:</label>
                  <input type="text" id="cs_card_title" oninput="csCard.settings.card_title = this.value;" placeholder="Chúc Mừng..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                </div>
                <div>
                  <label class="text-[11px] font-bold text-pink-300 block mb-1">Phụ Đề Dưới:</label>
                  <input type="text" id="cs_card_subtitle" oninput="csCard.settings.card_subtitle = this.value;" placeholder="Rạng rỡ & hạnh phúc..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                </div>
              </div>
            </div>

            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 space-y-3">
              <h4 class="text-xs font-bold text-pink-200 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-music text-amber-400"></i> Nhạc Nền Phát Khi Mở Cửa
              </h4>
              <div class="space-y-3">
                <div>
                  <label class="text-[11px] font-bold text-pink-300 block mb-1">Link Hoặc File Nhạc MP3:</label>
                  <div class="flex items-center gap-2">
                    <input type="text" id="cs_bg_music_url" oninput="csCard.settings.bg_music_url = this.value.trim();" placeholder="1.mp3 hoặc https://.../bai-hat.mp3" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-amber-300 font-mono focus:outline-none focus:border-pink-400" />
                    <button type="button" id="cs_btn_preview_audio" onclick="toggleCsAudioPreview()" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-pink-300 text-xs font-bold shrink-0 flex items-center gap-1.5 transition-all">
                      <i class="fa-solid fa-play"></i> <span>Nghe Thử</span>
                    </button>
                  </div>
                </div>

                <div class="flex gap-2 flex-wrap">
                  <span class="text-[11px] text-slate-400">Chọn nhanh bài hát:</span>
                  <button type="button" onclick="document.getElementById('cs_bg_music_url').value = '1.mp3'; csCard.settings.bg_music_url = '1.mp3';" class="px-2 py-0.5 rounded bg-slate-900 border border-slate-700 text-[10px] text-pink-300 hover:bg-slate-800">1.mp3 (Nội bộ)</button>
                  <button type="button" onclick="document.getElementById('cs_bg_music_url').value = '2.mp3'; csCard.settings.bg_music_url = '2.mp3';" class="px-2 py-0.5 rounded bg-slate-900 border border-slate-700 text-[10px] text-pink-300 hover:bg-slate-800">2.mp3 (Nội bộ)</button>
                  <button type="button" onclick="document.getElementById('cs_bg_music_url').value = 'https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3'; csCard.settings.bg_music_url = 'https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3';" class="px-2 py-0.5 rounded bg-slate-900 border border-slate-700 text-[10px] text-amber-300 hover:bg-slate-800">Nhạc Lãng Mạn Online</button>
                </div>
              </div>
            </div>

            <!-- THANH THAO TÁC BƯỚC 4: LƯU & CHUYỂN BƯỚC -->
            <div class="pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/70 p-4 rounded-2xl">
              <button type="button" onclick="switchCreatorTab('cat_quotes')" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-arrow-left"></i>
                <span>← Bước 3: Bé Mèo</span>
              </button>
              <div class="flex items-center gap-2">
                <button type="button" onclick="submitCreatorStudio()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition-all">
                  <i class="fa-solid fa-floppy-disk"></i>
                  <span>Lưu Bước Này 💾</span>
                </button>
                <button type="button" onclick="switchCreatorTab('event_counter')" class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white text-xs font-bold shadow-md shadow-pink-500/20 flex items-center gap-1.5 transition-all">
                  <span>Sang Bước 5: Đếm Ngược</span>
                  <i class="fa-solid fa-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- BƯỚC 5: ĐẾM NGƯỢC MỞ CỬA -->
          <div id="cs_tab_panel_event_counter" class="space-y-4 hidden">
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-5 space-y-4">
              <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                  <i class="fa-solid fa-hourglass-half text-amber-400 text-lg"></i>
                  <div>
                    <h4 class="text-sm font-bold text-pink-200">Đếm Ngược Mở Cửa ⏳</h4>
                    <p class="text-[11px] text-slate-400">Hiển thị đồng hồ đếm ngược trước khi người nhận mở thiệp.</p>
                  </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                  <span class="text-xs text-slate-300">Bật đếm ngược:</span>
                  <input type="checkbox" id="cs_event_counter_enabled" onchange="csCard.settings.event_counter_enabled = this.checked ? '1' : '0';" class="w-4 h-4 accent-pink-500 rounded cursor-pointer" />
                </label>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="text-[11px] font-bold text-pink-300 block mb-1">Tiêu Đề Đồng Hồ Đếm Ngược:</label>
                  <input type="text" id="cs_event_counter_title" oninput="csCard.settings.event_counter_title = this.value;" placeholder="Đếm ngược đến Ngày 20-10 🌸" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                </div>
                <div>
                  <label class="text-[11px] font-bold text-pink-300 block mb-1">Thời Gian Mở Cửa (Ngày & Giờ):</label>
                  <input type="datetime-local" id="cs_event_counter_target_date" oninput="csCard.settings.event_counter_target_date = this.value;" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400" />
                </div>
              </div>

              <div>
                <label class="text-[11px] font-bold text-pink-300 block mb-1">Thông Điệp Khi Đã Đến Giờ Mở Cửa:</label>
                <textarea id="cs_event_counter_message" rows="2" oninput="csCard.settings.event_counter_message = this.value;" placeholder="Chúc bạn ngập tràn niềm vui và yêu thương trọn vẹn! 💖" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-pink-400"></textarea>
              </div>
            </div>

            <!-- THANH THAO TÁC BƯỚC 5: LƯU & CHUYỂN BƯỚC -->
            <div class="pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/70 p-4 rounded-2xl">
              <button type="button" onclick="switchCreatorTab('titles_music')" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-arrow-left"></i>
                <span>← Bước 4: Nhạc Nền</span>
              </button>
              <div class="flex items-center gap-2">
                <button type="button" onclick="submitCreatorStudio()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition-all">
                  <i class="fa-solid fa-floppy-disk"></i>
                  <span>Lưu Bước Này 💾</span>
                </button>
                <button type="button" onclick="switchCreatorTab('letters')" class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white text-xs font-bold shadow-md shadow-pink-500/20 flex items-center gap-1.5 transition-all">
                  <span>Sang Bước 6: Hộp Thư</span>
                  <i class="fa-solid fa-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- BƯỚC 6: TÙY CHỈNH HỘP THƯ (100% GIỐNG TAB QUẢN TRỊ ADMIN) -->
          <div id="cs_tab_panel_letters" class="space-y-6 hidden">
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xl space-y-6">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                <div class="flex items-center gap-3">
                  <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-pink-500 to-rose-600 flex items-center justify-center text-white text-xl shadow-lg shadow-pink-500/20">
                    <i class="fa-solid fa-pen-nib"></i>
                  </div>
                  <div>
                    <h4 class="text-base sm:text-lg font-bold text-pink-200">Tùy Chỉnh Toàn Bộ Chữ Trong Hộp Thư Yêu Thương</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Thay đổi linh hoạt tiêu đề, nhãn ô nhập, placeholder gợi ý, chữ trên nút gửi và câu chúc của bé mèo cho riêng link thiệp này</p>
                  </div>
                </div>
                <button type="button" onclick="resetCsDefaultLetterBoxTexts()" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition-all self-start sm:self-auto flex items-center gap-1.5">
                  <i class="fa-solid fa-rotate-left"></i> Khôi phục chữ mẫu
                </button>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- CỘT BÊN TRÁI: CÁC Ô NHẬP LIỆU -->
                <div class="lg:col-span-7 space-y-4">
                  <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-4">
                    <h5 class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                      <i class="fa-solid fa-heading text-rose-400"></i> 1. Tiêu Đề & Lời Dẫn Hộp Thư:
                    </h5>

                    <div>
                      <label class="block text-xs font-semibold text-slate-300 mb-1">Tiêu Đề Badge Hộp Thư:</label>
                      <input type="text" id="cs_letter_modal_title"
                             oninput="csCard.settings.letter_modal_title = this.value; updateCsLetterLivePreview();"
                             placeholder="Hộp Thư Yêu Thương"
                             class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-bold" />
                    </div>

                    <div>
                      <label class="block text-xs font-semibold text-slate-300 mb-1">Đoạn Lời Dẫn / Mô Tả Hướng Dẫn:</label>
                      <textarea id="cs_letter_modal_subtitle" rows="2"
                                oninput="csCard.settings.letter_modal_subtitle = this.value; updateCsLetterLivePreview();"
                                placeholder="Hãy để lại tên và những lời chúc ngọt ngào..."
                                class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-pink-400 leading-relaxed"></textarea>
                    </div>
                  </div>

                  <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-4">
                    <h5 class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                      <i class="fa-solid fa-font text-rose-400"></i> 2. Ô Nhập Tên Người Gửi:
                    </h5>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nhãn Ô Nhập Tên:</label>
                        <input type="text" id="cs_letter_sender_label"
                               oninput="csCard.settings.letter_sender_label = this.value; updateCsLetterLivePreview();"
                               placeholder="Tên hoặc Biệt danh của bạn:"
                               class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Gợi Ý (Placeholder) Ô Nhập Tên:</label>
                        <input type="text" id="cs_letter_sender_placeholder"
                               oninput="csCard.settings.letter_sender_placeholder = this.value; updateCsLetterLivePreview();"
                               placeholder="Ví dụ: Bé Út, Bạn thân, Người giấu tên..."
                               class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                      </div>
                    </div>
                  </div>

                  <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-4">
                    <h5 class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                      <i class="fa-solid fa-message text-rose-400"></i> 3. Ô Viết Nội Dung & Nút Gửi:
                    </h5>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nhãn Ô Viết Lời Nhắn:</label>
                        <input type="text" id="cs_letter_content_label"
                               oninput="csCard.settings.letter_content_label = this.value; updateCsLetterLivePreview();"
                               placeholder="Nội dung lời nhắn gửi trao:"
                               class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                      </div>
                      <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Chữ Trên Nút Bấm Gửi Thư:</label>
                        <input type="text" id="cs_letter_submit_btn_text"
                               oninput="csCard.settings.letter_submit_btn_text = this.value; updateCsLetterLivePreview();"
                               placeholder="Gửi Thư Ngay 💖"
                               class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400 font-bold" />
                      </div>
                    </div>

                    <div>
                      <label class="block text-xs font-semibold text-slate-300 mb-1">Gợi Ý (Placeholder) Ô Viết Lời Nhắn:</label>
                      <input type="text" id="cs_letter_content_placeholder"
                             oninput="csCard.settings.letter_content_placeholder = this.value; updateCsLetterLivePreview();"
                             placeholder="Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây..."
                             class="w-full px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:ring-2 focus:ring-pink-400" />
                    </div>
                  </div>

                  <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-3">
                    <h5 class="text-xs font-bold text-pink-300 uppercase tracking-wider flex items-center gap-1.5">
                      <i class="fa-solid fa-cat text-rose-400"></i> 4. Câu Bé Mèo Chúc Mừng Sau Khi Gửi Xong:
                    </h5>
                    <div>
                      <input type="text" id="cs_letter_cat_speech"
                             oninput="csCard.settings.letter_cat_speech = this.value;"
                             placeholder='💌 Bé mèo đã chuyển thư của "{sender}" trao tận tay rồi nhé meo meo~ 🐾💖'
                             class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-400" />
                      <p class="text-[11px] text-slate-400 mt-1.5">
                        <i class="fa-solid fa-circle-info text-amber-400 mr-1"></i> Dùng ký hiệu <code class="bg-black/50 px-1 py-0.5 rounded text-pink-300 font-mono">{sender}</code> để tự động thay bằng tên người gửi!
                      </p>
                    </div>
                  </div>
                </div>

                <!-- CỘT BÊN PHẢI: XEM TRƯỚC HỘP THƯ TRỰC QUAN (LIVE PREVIEW) -->
                <div class="lg:col-span-5 bg-slate-950/80 border border-slate-800 rounded-3xl p-5 flex flex-col justify-between">
                  <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                      <i class="fa-solid fa-eye text-pink-400"></i> Xem trước hiển thị thực tế trên thiệp này:
                    </span>

                    <!-- MÔ PHỎNG MODAL HỘP THƯ YÊU THƯƠNG -->
                    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xl border border-pink-100 text-slate-800 space-y-3.5 select-none">
                      <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-50 border border-pink-200 text-rose-600 text-xs font-bold">
                          <i class="fa-solid fa-heart text-rose-500"></i>
                          <span id="cs_preview_letter_modal_title">Hộp Thư Yêu Thương</span>
                        </div>
                        <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xs">✕</span>
                      </div>

                      <p id="cs_preview_letter_modal_subtitle" class="text-[11px] text-slate-500 leading-relaxed">
                        Hãy để lại tên và những lời chúc, cảm nghĩ ngọt ngào của bạn nhé! Lời nhắn sẽ được gửi thẳng về trang quản trị 💌
                      </p>

                      <div class="space-y-2.5">
                        <div>
                          <label id="cs_preview_letter_sender_label" class="block text-[11px] font-bold text-slate-700 mb-1">
                            Tên hoặc Biệt danh của bạn:
                          </label>
                          <div id="cs_preview_letter_sender_placeholder" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-400">
                            Ví dụ: Bé Út, Bạn thân, Người giấu tên...
                          </div>
                        </div>

                        <div>
                          <label id="cs_preview_letter_content_label" class="block text-[11px] font-bold text-slate-700 mb-1">
                            Nội dung lời nhắn gửi trao:
                          </label>
                          <div id="cs_preview_letter_content_placeholder" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-400 h-16">
                            Viết những lời yêu thương, cảm xúc hoặc điều bạn muốn nói tại đây...
                          </div>
                        </div>

                        <div class="w-full py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 text-white font-bold text-xs shadow-md shadow-pink-500/30 flex items-center justify-center gap-1.5">
                          <i class="fa-solid fa-paper-plane"></i>
                          <span id="cs_preview_letter_submit_btn_text">Gửi Thư Ngay 💖</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="pt-4 mt-4 border-t border-slate-800 text-center">
                    <p class="text-[11px] text-pink-300/80">Mọi thay đổi sẽ cập nhật trực tiếp vào link thiệp này khi bạn bấm Lưu Thiệp hoặc Lấy Link Thiệp Mời 🔗</p>
                  </div>
                </div>
              </div>

              <!-- THANH THAO TÁC BƯỚC 6: LƯU & XEM THỬ & LẤY LINK -->
              <div class="pt-3 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-950/70 p-4 rounded-2xl">
                <button type="button" onclick="switchCreatorTab('event_counter')" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1.5 transition-all">
                  <i class="fa-solid fa-arrow-left"></i>
                  <span>← Bước 5: Đếm Ngược</span>
                </button>
                <div class="flex items-center gap-2 flex-wrap">
                  <button type="button" onclick="toggleCreatorPreview()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-pink-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-eye"></i>
                    <span>👁️ Xem Thử Thiệp</span>
                  </button>
                  <button type="button" onclick="submitCreatorStudio()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Lưu Bước Này 💾</span>
                  </button>
                  <button type="button" onclick="submitCreatorStudio()" class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 via-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white font-bold text-xs shadow-lg shadow-pink-500/25 flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-link"></i>
                    <span>Lấy Link Thiệp Mời 🔗</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- FOOTER CỦA BẢNG TẠO LINK: XEM THỬ & LẤY LINK -->
      <div id="cs_footer_bar" class="bg-slate-950 px-5 sm:px-7 py-3.5 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
        <div class="text-xs text-slate-400 flex items-center gap-2">
          <span>Link sẽ tạo ra:</span>
          <span id="cs_footer_slug_display" class="font-mono font-bold text-amber-300 select-all">
            <?= htmlspecialchars($baseUrl) ?>tenthiep
          </span>
        </div>

        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
          <button type="button" onclick="toggleCreatorPreview()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-pink-200 text-xs font-semibold flex items-center gap-1.5 transition-all">
            <i class="fa-solid fa-eye"></i>
            <span>👁️ Xem Thử Thiệp</span>
          </button>

          <!-- Nút Lưu Thiệp ở Footer -->
          <button type="button" onclick="submitCreatorStudio()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold shadow-md shadow-emerald-500/20 flex items-center gap-1.5 transition-all">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Lưu Thiệp 💾</span>
          </button>

          <button type="button" id="cs_btn_submit" onclick="submitCreatorStudio()" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 via-rose-500 to-pink-500 hover:brightness-110 active:scale-95 text-white font-bold text-xs sm:text-sm shadow-lg shadow-pink-500/25 flex items-center gap-2 transition-all">
            <i class="fa-solid fa-link"></i>
            <span>Lấy Link Thiệp Mời 🔗</span>
          </button>
        </div>
      </div>

    </div>

    <!-- MODAL SỬA LỜI CHÚC TRONG CREATOR STUDIO -->
    <div id="cs_wish_edit_modal" class="fixed inset-0 z-60 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 hidden">
      <div class="bg-slate-900 border border-pink-400/40 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
          <h3 class="text-sm font-bold text-pink-200">Chỉnh Sửa Lời Chúc</h3>
          <button type="button" onclick="closeEditWishModal()" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <input type="hidden" id="cs_edit_wish_id" />

        <div>
          <label class="block text-xs font-semibold text-pink-300 mb-1">Người nhận</label>
          <input type="text" id="cs_edit_wish_recipient" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-pink-300 mb-1">Link Ảnh Lời Chúc (URL)</label>
          <input type="url" id="cs_edit_wish_img" oninput="document.getElementById('cs_edit_wish_img_preview').src = this.value;" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs font-mono text-white" />
          <div class="mt-2 w-16 h-16 rounded-xl overflow-hidden border border-slate-700 bg-black">
            <img id="cs_edit_wish_img_preview" src="" alt="Preview" class="w-full h-full object-cover" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-pink-300 mb-1">Nội dung lời chúc</label>
          <textarea id="cs_edit_wish_msg" rows="3" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white"></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
          <button type="button" onclick="closeEditWishModal()" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300">Hủy</button>
          <button type="button" onclick="saveEditWishInCreator()" class="px-5 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 text-white text-xs font-bold shadow-md">Lưu Lời Chúc</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
