<?php
require_once __DIR__ . '/auth.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['admin_pin'] ?? '');

    if (empty($pin)) {
        $error = 'Vui lòng nhập mật khẩu 6 số!';
    } elseif (verifyAdminPin($pin)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Mật khẩu 6 số không đúng! (Mặc định: 123456)';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Đăng Nhập Quản Trị</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@600;700&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="tailwind.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="../tailwind.css?v=<?= time() ?>" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Quicksand', sans-serif; }
    .font-comfortaa { font-family: 'Comfortaa', cursive; }
  </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4">

  <div class="w-full max-w-sm bg-slate-900/90 backdrop-blur-xl border border-pink-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl text-center relative overflow-hidden">
    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-tr from-rose-500 to-amber-400 flex items-center justify-center text-white text-2xl shadow-lg shadow-rose-500/30">
      <i class="fa-solid fa-lock"></i>
    </div>

    <h1 class="text-xl sm:text-2xl font-bold font-comfortaa text-pink-100 mb-1">Mật Khẩu Quản Trị</h1>
    <p class="text-xs text-pink-300/70 mb-5">Nhập mã 6 số để vào bảng điều khiển</p>

    <?php if (!empty($error)): ?>
      <div class="mb-4 p-3 rounded-xl bg-rose-500/20 border border-rose-400/40 text-rose-200 text-xs font-semibold flex items-center justify-center gap-2">
        <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4" id="pinForm">
      <div>
        <input type="password" id="pinInput" name="admin_pin" maxlength="6" pattern="[0-9]*" inputmode="numeric" required autofocus
               placeholder="••••••"
               class="w-full py-3 px-4 bg-slate-950 border-2 border-pink-400/50 rounded-2xl text-center text-2xl tracking-[0.4em] font-bold text-pink-300 font-mono focus:outline-none focus:border-pink-400 shadow-inner" />
      </div>

      <button type="submit"
              class="w-full py-3 bg-gradient-to-r from-rose-500 via-pink-500 to-amber-500 hover:from-rose-600 hover:to-amber-600 text-white font-bold rounded-xl shadow-lg shadow-pink-500/30 active:scale-[0.98] transition-all text-sm">
        <i class="fa-solid fa-right-to-bracket mr-1.5"></i> Mở Bảng Quản Trị
      </button>
    </form>

    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-center">
      <a href="../" class="text-xs text-slate-400 hover:text-pink-300 flex items-center gap-1.5 transition-colors">
        <i class="fa-solid fa-arrow-left"></i> Về Trang Thiệp
      </a>
    </div>
  </div>

</body>
</html>
