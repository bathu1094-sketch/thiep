/* ========================================================================= */
/* THIỆP CHÚC MỪNG HOÀNG GIA - SCRIPT CHÍNH (DYNAMIC PHP/MYSQL READY)         */
/* ========================================================================= */

let isInitialized = false;

// Đảm bảo DOM đã tải xong trước khi gắn sự kiện
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    initApp();
  });
} else {
  initApp();
}

function initApp() {
  if (isInitialized) return;
  isInitialized = true;

  /* ================= 1. KIỂM TRA XOAY MÀN HÌNH (BẮT BUỘC XOAY NGANG) ================= */
  const rotateOverlay = document.getElementById('rotate-screen-overlay');

  if (rotateOverlay) {
    // Chặn mọi thao tác khi đang ở chế độ dọc để nhắc xoay ngang
    ['click', 'touchstart', 'touchend', 'touchmove', 'mousedown', 'mouseup', 'pointerdown', 'pointerup'].forEach(ev => {
      rotateOverlay.addEventListener(ev, (e) => {
        e.stopPropagation();
      }, { capture: true, passive: true });
    });
  }

  function checkDeviceOrientation() {
    if (!rotateOverlay) return;
    const w = window.innerWidth || document.documentElement.clientWidth;
    const h = window.innerHeight || document.documentElement.clientHeight;
    
    // Nếu màn hình đang ở chiều dọc (width <= height)
    const isPortrait = w <= h;
    
    if (isPortrait) {
      rotateOverlay.style.setProperty('display', 'flex', 'important');
    } else {
      rotateOverlay.style.setProperty('display', 'none', 'important');
    }
  }

  window.checkOrientation = checkDeviceOrientation;
  window.addEventListener('resize', checkDeviceOrientation);
  window.addEventListener('orientationchange', () => {
    setTimeout(checkDeviceOrientation, 100);
    setTimeout(checkDeviceOrientation, 300);
  });
  if (window.screen && window.screen.orientation) {
    window.screen.orientation.addEventListener('change', checkDeviceOrientation);
  }
  checkDeviceOrientation();

  /* ================= CHỐNG PHÓNG TO KHI NHẤP NHIỀU LẦN (DOUBLE-TAP ZOOM) ================= */
  let lastTouchEndTime = 0;
  document.addEventListener('touchend', function(e) {
    const now = Date.now();
    if (now - lastTouchEndTime <= 320) {
      e.preventDefault();
    }
    lastTouchEndTime = now;
  }, { passive: false });

  document.addEventListener('gesturestart', function(e) {
    e.preventDefault();
  });
  document.addEventListener('dblclick', function(e) {
    e.preventDefault();
  });

  /* ================= BẢO VỆ MÃ NGUỒN: CHỐNG NHẤN F12, CHUỘT PHẢI & MỞ DEVTOOLS ================= */
  function showDevtoolsBlockedNotice() {
    let toast = document.getElementById('devtools-blocked-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'devtools-blocked-toast';
      toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:rgba(15,23,42,0.95);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border:1.5px solid rgba(244,63,94,0.7);color:#ffe4e6;padding:10px 22px;border-radius:999px;font-size:12.5px;font-weight:700;z-index:999999;box-shadow:0 12px 32px rgba(0,0,0,0.65),0 0 20px rgba(244,63,94,0.4);display:flex;align-items:center;gap:8px;pointer-events:none;transition:all 0.3s cubic-bezier(0.16,1,0.3,1);opacity:0;transform:translate(-50%,15px);user-select:none;';
      toast.innerHTML = '<span style="font-size:15px;">🔒</span><span>Trang web đã được bảo vệ bản quyền, không thể xem mã nguồn! 💖</span>';
      document.body.appendChild(toast);
    }
    toast.style.opacity = '1';
    toast.style.transform = 'translate(-50%,0)';
    if (window._devToastTimeout) clearTimeout(window._devToastTimeout);
    window._devToastTimeout = setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translate(-50%,15px)';
    }, 2800);
  }

  // 1. Chống nhấp chuột phải (Inspect / Kiểm tra mã nguồn)
  document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
    showDevtoolsBlockedNotice();
    return false;
  }, true);

  // 2. Chống nhấn phím F12 và các tổ hợp phím tắt mở DevTools
  window.addEventListener('keydown', function(e) {
    const key = e.key ? e.key.toUpperCase() : '';
    const keyCode = e.keyCode || e.which;

    // Phím F12
    if (key === 'F12' || keyCode === 123) {
      e.preventDefault();
      e.stopPropagation();
      showDevtoolsBlockedNotice();
      return false;
    }

    // Tổ hợp Ctrl+Shift+I / Cmd+Option+I (Inspect)
    // Tổ hợp Ctrl+Shift+J / Cmd+Option+J (Console)
    // Tổ hợp Ctrl+Shift+C / Cmd+Option+C (Inspect Element)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && (key === 'I' || key === 'J' || key === 'C' || keyCode === 73 || keyCode === 74 || keyCode === 67)) {
      e.preventDefault();
      e.stopPropagation();
      showDevtoolsBlockedNotice();
      return false;
    }

    // Tổ hợp Ctrl+U / Cmd+U (Xem mã nguồn trang - View Source)
    if ((e.ctrlKey || e.metaKey) && !e.shiftKey && (key === 'U' || keyCode === 85)) {
      e.preventDefault();
      e.stopPropagation();
      showDevtoolsBlockedNotice();
      return false;
    }

    // Tổ hợp Ctrl+S / Cmd+S (Lưu trang về máy)
    if ((e.ctrlKey || e.metaKey) && !e.shiftKey && (key === 'S' || keyCode === 83)) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  }, true);

  /* ================= 2. CANVAS HOA RƠI (CODE 1) ================= */
  const petalCanvas = document.getElementById('petal-canvas');
  if (petalCanvas) {
    const petalCtx = petalCanvas.getContext('2d');
    const petals = [];

    function resizePetalCanvas() {
      petalCanvas.width = window.innerWidth;
      petalCanvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resizePetalCanvas);
    resizePetalCanvas();

    const petalColors = ['#f472b6', '#fb7185', '#fda4af', '#f9a8d4', '#f43f5e'];
    for (let i = 0; i < 50; i++) {
      petals.push({
        x: Math.random() * window.innerWidth,
        y: Math.random() * window.innerHeight,
        size: Math.random() * 7 + 4,
        speedY: Math.random() * 1.5 + 0.8,
        speedX: Math.random() * 1.5 - 0.75,
        rot: Math.random() * 360,
        rotSpeed: Math.random() * 2 - 1,
        alpha: Math.random() * 0.6 + 0.35,
        color: petalColors[i % petalColors.length]
      });
    }

    function renderPetals() {
      petalCtx.clearRect(0, 0, petalCanvas.width, petalCanvas.height);
      petals.forEach(p => {
        p.y += p.speedY;
        p.x += p.speedX + Math.sin(p.y / 25) * 0.4;
        p.rot += p.rotSpeed;
        if (p.y > petalCanvas.height + 20) {
          p.y = -10;
          p.x = Math.random() * petalCanvas.width;
        }
        petalCtx.save();
        petalCtx.translate(p.x, p.y);
        petalCtx.rotate((p.rot * Math.PI) / 180);
        petalCtx.globalAlpha = p.alpha;
        petalCtx.fillStyle = p.color;
        petalCtx.beginPath();
        petalCtx.ellipse(0, 0, p.size, p.size / 2, 0, 0, Math.PI * 2);
        petalCtx.fill();
        petalCtx.restore();
      });
      requestAnimationFrame(renderPetals);
    }
    renderPetals();
  }

  /* ================= 3. ĐIỀU KHIỂN CÁNH CỬA & MÃ PIN (TỐI ƯU SIÊU MƯỢT TRÊN MOBILE) ================= */
  let currentPin = '';
  let isPinBusy = false;
  // Đọc mã PIN động từ database (settings) hoặc fallback 1234
  const correctPin = (window.SERVER_SETTINGS && window.SERVER_SETTINGS.pin_code) 
    ? String(window.SERVER_SETTINGS.pin_code).trim() 
    : '1234';

  let isDoorsOpen = false;

  const pinModal = document.getElementById('pin-modal');
  const pinModalBox = document.getElementById('pin-modal-box');
  const pinDots = document.querySelectorAll('.pin-dot');
  const pinInstruction = document.getElementById('pin-instruction');
  const openHeartBtn = document.getElementById('open-heart-btn');

  function openPinModal() {
    initAudio();
    if (pinModal) {
      pinModal.classList.remove('hidden');
      clearPin();
    }
  }

  function isDoorLockedByTimer() {
    const settings = window.SERVER_SETTINGS || {};
    const isEnabled = settings.event_counter_enabled !== undefined 
      ? (settings.event_counter_enabled === '1' || settings.event_counter_enabled === true || settings.event_counter_enabled === 'true')
      : true;
    if (!isEnabled) return false;

    const targetDateStr = settings.event_counter_target_date || '2026-10-20T00:00';
    const targetTime = new Date(targetDateStr).getTime();
    if (isNaN(targetTime)) return false;

    const mode = settings.event_counter_mode || 'auto';
    if (mode === 'countup') return false; // Kỷ niệm đã bắt đầu -> cho phép mở

    const now = Date.now();
    return targetTime > now;
  }

  function handleHeartButtonClick(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    initAudio();

    // Lưu ý: KHÔNG phát nhạc khi bấm trái tim bên ngoài cánh cửa.
    // Nhạc chỉ bắt đầu phát sau khi đã vào trong cánh cửa và bấm vào quà hoặc thư mời.

    if (isDoorLockedByTimer()) {
      soundMeow();
      const widget = document.getElementById('doorCountdownWidget');
      if (widget) {
        widget.classList.add('shake-error');
        setTimeout(() => widget.classList.remove('shake-error'), 600);
      }
      const notice = document.getElementById('doorLockNotice');
      if (notice) {
        notice.classList.remove('hidden');
        setTimeout(() => notice.classList.add('hidden'), 3500);
      }
      return;
    }

    openPinModal();
  }

  function closePinModal(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (pinModal) pinModal.classList.add('hidden');
    clearPin();
  }
  window.closePinModal = closePinModal;

  if (openHeartBtn) {
    openHeartBtn.addEventListener('click', handleHeartButtonClick);
  }

  if (pinModal) {
    pinModal.addEventListener('click', (e) => {
      if (e.target === pinModal) {
        closePinModal(e);
      }
    });
  }

  function hapticFeedback() {
    try {
      if (navigator.vibrate) navigator.vibrate(10);
    } catch (err) {}
  }

  // Cập nhật giao diện 4 ô chấm siêu mượt, không reflow DOM nặng
  function updatePinDisplay() {
    pinDots.forEach((dot, idx) => {
      const icon = dot.querySelector('.pin-paw-icon');
      if (idx < currentPin.length) {
        dot.classList.add('is-filled');
        if (icon) {
          icon.classList.remove('opacity-30', 'text-sm');
          icon.classList.add('opacity-100', 'text-base');
        }
      } else {
        dot.classList.remove('is-filled', 'is-correct', 'is-error');
        if (icon) {
          icon.classList.remove('opacity-100', 'text-base');
          icon.classList.add('opacity-30', 'text-sm');
        }
      }
    });
  }

  function pressKey(num) {
    if (isPinBusy || isDoorsOpen) return;
    if (currentPin.length < 4) {
      hapticFeedback();
      currentPin += String(num);
      updatePinDisplay();
      if (currentPin.length === 4) {
        validatePin();
      }
    }
  }

  function clearPin() {
    if (isPinBusy) return;
    currentPin = '';
    updatePinDisplay();
    if (pinInstruction) {
      const defaultSub = (window.SERVER_SETTINGS && window.SERVER_SETTINGS.pin_subtitle) 
        ? window.SERVER_SETTINGS.pin_subtitle 
        : 'Nhập mã bí mật 4 số để mở cửa';
      pinInstruction.textContent = defaultSub;
      pinInstruction.className = 'text-xs text-pink-200/80 mb-4 font-light';
    }
  }

  function deletePinKey() {
    if (isPinBusy) return;
    if (currentPin.length > 0) {
      hapticFeedback();
      currentPin = currentPin.slice(0, -1);
      updatePinDisplay();
    }
  }

  function validatePin() {
    isPinBusy = true;
    if (currentPin === correctPin) {
      // Đúng mật khẩu -> Mở cửa (chưa phát nhạc ở đây, chờ người xem vào bấm quà/thư mời)
      pinDots.forEach(dot => dot.classList.add('is-correct'));
      if (pinInstruction) {
        pinInstruction.textContent = 'Chính xác rồi! Đang mở cửa hoàng gia...';
        pinInstruction.className = 'text-xs text-green-300 font-bold mb-4';
      }
      setTimeout(() => {
        closePinModal();
        triggerOpenDoors();
        isPinBusy = false;
      }, 400);
    } else {
      // Sai mật khẩu
      pinDots.forEach(dot => dot.classList.add('is-error'));
      if (pinInstruction) {
        pinInstruction.textContent = 'Sai mã rồi, hãy thử lại nhé!';
        pinInstruction.className = 'text-xs text-rose-300 font-bold mb-4';
      }
      if (pinModalBox) {
        pinModalBox.classList.add('shake-error');
      }
      setTimeout(() => {
        if (pinModalBox) pinModalBox.classList.remove('shake-error');
        currentPin = '';
        isPinBusy = false;
        updatePinDisplay();
      }, 650);
    }
  }

  // Xử lý sự kiện bàn phím cảm ứng phản hồi tức thì và đảm bảo user gesture hợp lệ cho Web Audio / Audio play
  function bindFastTouchButton(btn, callback) {
    if (!btn) return;
    let lastHandledTime = 0;

    const handleAction = (e) => {
      const now = Date.now();
      if (now - lastHandledTime < 160) return;
      lastHandledTime = now;
      btn.classList.add('is-pressed');
      setTimeout(() => btn.classList.remove('is-pressed'), 120);
      initAudio();
      callback();
    };

    // Lắng nghe sự kiện click tiêu chuẩn để trình duyệt ghi nhận gesture người dùng
    btn.addEventListener('click', (e) => {
      handleAction(e);
    });

    // Hỗ trợ thiết bị cảm ứng phản hồi nhanh
    btn.addEventListener('touchend', (e) => {
      handleAction(e);
    }, { passive: true });
  }

  // Gắn phím số
  document.querySelectorAll('.pin-btn').forEach(btn => {
    const key = btn.dataset.key;
    bindFastTouchButton(btn, () => pressKey(key));
  });

  // Gắn phím Xóa & Lùi
  const clearBtn = document.getElementById('pin-clear');
  bindFastTouchButton(clearBtn, clearPin);

  const delBtn = document.getElementById('pin-del');
  bindFastTouchButton(delBtn, deletePinKey);

  // Hỗ trợ cả bàn phím cứng trên máy tính / iPad
  window.addEventListener('keydown', (e) => {
    if (pinModal && !pinModal.classList.contains('hidden')) {
      if (e.key >= '0' && e.key <= '9') {
        pressKey(e.key);
      } else if (e.key === 'Backspace') {
        deletePinKey();
      } else if (e.key === 'Escape') {
        closePinModal();
      }
    }
  });

  // Expose to window
  window.pressKey = pressKey;
  window.clearPin = clearPin;
  window.deletePinKey = deletePinKey;
  window.openPinModal = openPinModal;
  window.closePinModal = closePinModal;

  /* --- MỞ CỬA: SANG CODE 2 VÀ GIỮ TOÀN BỘ HIỆU ỨNG LIỀN MẠCH --- */
  function triggerOpenDoors() {
    if (isDoorsOpen) return;
    isDoorsOpen = true;

    // Không phát nhạc tự động khi mở cửa, chỉ phát khi bấm vào quà hoặc thư
    // startMusicIfNeeded();

    const heartWrapper = document.getElementById('heart-wrapper');
    if (heartWrapper) heartWrapper.style.display = 'none';

    const leftDoor = document.getElementById('left-door');
    const rightDoor = document.getElementById('right-door');
    const flash = document.getElementById('door-open-flash');

    if (leftDoor) leftDoor.classList.add('open-left');
    if (rightDoor) rightDoor.classList.add('open-right');
    if (flash) flash.classList.add('active');

    // Đàn bướm bay vút lên từ cánh cửa
    for (let i = 0; i < 6; i++) {
      setTimeout(() => {
        const b = document.createElement('div');
        b.className = 'butterfly-spawn';
        b.textContent = '🦋';
        b.style.left = (35 + Math.random() * 30) + 'vw';
        b.style.top = (65 + Math.random() * 15) + 'vh';
        b.style.setProperty('--dx', (Math.random() * 2 - 1) * 200 + 'px');
        b.style.setProperty('--dy', (-180 - Math.random() * 150) + 'px');
        b.style.setProperty('--rot', (Math.random() * 2 - 1) * 30 + 'deg');
        b.style.setProperty('--fly-time', (3.5 + Math.random() * 1.5) + 's');
        document.body.appendChild(b);
        setTimeout(() => b.remove(), 5500);
      }, i * 200);
    }

    blastHeartFirework(window.innerWidth / 2, window.innerHeight * 0.35);

    // KHI CỬA MỞ RA: Bó hoa vươn nhẹ nhàng nở rộ
    const centerScene = document.querySelector('.center-scene');
    if (centerScene) {
      setTimeout(() => {
        centerScene.classList.add('blooming');
      }, 350);
    }

    // KHI CỬA MỞ RA: Dàn hoa tươi dưới mặt đất mọc nhẹ nhàng lên
    setTimeout(() => {
      sproutGroundFlowers();
    }, 450);

    // KHI CỬA MỞ RA: Hiển thị nút gửi thư ở góc trái trên cùng
    const letterWrap = document.getElementById('letterControlWrap');
    if (letterWrap) {
      setTimeout(() => {
        letterWrap.classList.add('visible');
      }, 900);
    }

    setTimeout(() => {
      const dc = document.getElementById('door-container');
      if (dc) dc.style.display = 'none';
      blastHeartFirework(window.innerWidth * 0.3, window.innerHeight * 0.28);
      blastHeartFirework(window.innerWidth * 0.7, window.innerHeight * 0.28);
    }, 1600);
  }
  window.triggerOpenDoors = triggerOpenDoors;

  /* ================= 4. ÂM NHẠC MP3 & TIẾNG MÈO KÊU ================= */
  let audioCtx = null;

  function initAudio() {
    if (!audioCtx) {
      const AudioClass = window.AudioContext || window.webkitAudioContext;
      if (AudioClass) audioCtx = new AudioClass();
    }
    if (audioCtx && audioCtx.state === 'suspended') {
      audioCtx.resume().catch(() => {});
    }
  }

  function soundMeow() {
    try {
      initAudio();
      if (!audioCtx) return;
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      const now = audioCtx.currentTime;
      osc.type = 'sine';
      osc.frequency.setValueAtTime(580, now);
      osc.frequency.exponentialRampToValueAtTime(820, now + 0.18);
      osc.frequency.exponentialRampToValueAtTime(540, now + 0.45);
      gain.gain.setValueAtTime(0.001, now);
      gain.gain.linearRampToValueAtTime(0.18, now + 0.08);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.48);
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start(now);
      osc.stop(now + 0.5);
    } catch (e) {}
  }
  window.soundMeow = soundMeow;

  function startMusicIfNeeded() {
    initAudio();
    const bgAudio = document.getElementById('bgMusic');
    if (!bgAudio) return;

    // Nếu nhạc đang phát rồi thì KHÔNG làm gì cả (tránh bị phát lại từ đầu khi bấm các hộp quà hoặc thư khác)
    if (!bgAudio.paused) {
      return;
    }

    try {
      bgAudio.muted = false;
      bgAudio.volume = 0.85;

      const s = window.SERVER_SETTINGS || {};
      const onlineFallback = s.bg_music_online_url || 'https://assets.mixkit.co/music/preview/mixkit-romantic-moment-588.mp3';

      // Tự động kiểm tra nguồn nhạc
      if (!bgAudio.src || bgAudio.src === '' || bgAudio.src.endsWith('/') || bgAudio.src === window.location.href) {
        let targetSrc = '';
        if (s.bg_music_type === 'url' && s.bg_music_online_url) {
          targetSrc = s.bg_music_online_url;
        } else if (s.bg_music_url && s.bg_music_url.startsWith('http')) {
          targetSrc = s.bg_music_url;
        } else if (s.bg_music_file && s.bg_music_file.startsWith('http')) {
          targetSrc = s.bg_music_file;
        } else if (s.bg_music_url && !s.bg_music_url.startsWith('http') && s.bg_music_url !== '1.mp3' && s.bg_music_url !== '2.mp3') {
          targetSrc = (window.BASE_URL || '') + s.bg_music_url;
        } else {
          targetSrc = onlineFallback;
        }
        bgAudio.src = targetSrc;
      }

      // Xử lý lỗi tự động fallback sang online URL nếu file cục bộ bị 404
      if (!bgAudio.dataset.hasErrorHandler) {
        bgAudio.dataset.hasErrorHandler = 'true';
        bgAudio.addEventListener('error', function() {
          console.warn("Nhạc nền lỗi 404 hoặc không load được, tự động chuyển sang nhạc online lãng mạn...");
          if (bgAudio.src !== onlineFallback) {
            bgAudio.src = onlineFallback;
            bgAudio.load();
            if (isDoorsOpen) {
              bgAudio.play().catch(() => {});
            }
          }
        });
      }

      if (bgAudio.paused) {
        const p = bgAudio.play();
        if (p !== undefined) {
          p.catch((err) => {
            console.log("Audio play waiting for direct gesture confirmation:", err);
          });
        }
      }
    } catch (e) {
      console.log("startMusic error:", e);
    }
  }
  window.startMusicIfNeeded = startMusicIfNeeded;

  /* ================= 5. BƯỚM 3D & BÉ MÈO ================= */
  const butterflyEl = document.getElementById('butterflyEl');
  let bfPos = { x: 140, y: 140 };
  let isBfDodging = false;

  function flyButterflyToWaypoint() {
    if (!butterflyEl || isBfDodging) return;
    const vw = window.innerWidth || 380;
    const vh = window.innerHeight || 600;
    const targetX = Math.max(40, Math.min(vw - 50, 40 + Math.random() * (vw - 80)));
    const targetY = Math.max(70, Math.min(vh - 180, 70 + Math.random() * (vh - 240)));
    bfPos = { x: targetX, y: targetY };
    butterflyEl.style.transitionDuration = '2400ms';
    butterflyEl.style.left = targetX + 'px';
    butterflyEl.style.top = targetY + 'px';
  }

  function handleButterflyHover(e) {
    if (e) e.stopPropagation();
    isBfDodging = true;
    const vw = window.innerWidth || 380;
    const vh = window.innerHeight || 600;
    const dodgeX = Math.max(40, Math.min(vw - 50, bfPos.x + (Math.random() > 0.5 ? 90 : -90)));
    const dodgeY = Math.max(70, Math.min(vh - 160, bfPos.y + (Math.random() > 0.5 ? -70 : 50)));
    bfPos = { x: dodgeX, y: dodgeY };
    butterflyEl.style.transitionDuration = '500ms';
    butterflyEl.style.left = dodgeX + 'px';
    butterflyEl.style.top = dodgeY + 'px';
    triggerCatchButterfly();
    setTimeout(() => { isBfDodging = false; }, 600);
  }
  window.handleButterflyHover = handleButterflyHover;

  function handleButterflyClick(e) {
    if (e) e.stopPropagation();
    blastHeartFirework(bfPos.x, bfPos.y);
    triggerCatchButterfly();
  }
  window.handleButterflyClick = handleButterflyClick;

  setInterval(flyButterflyToWaypoint, 3500);

  /* Bé Mèo bắt bướm & trò chuyện */
  const catSpeech = document.getElementById('catSpeech');
  let isCatching = false;
  let speechTimeout = null;
  const defaultCatQuotes = [
    "Meo meo~ Chúc một ngày thật rực rỡ và hạnh phúc! 🐾💖",
    "Chúc người đẹp luôn rạng ngời như đóa hoa sớm mai! 🌸✨",
    "Bé mèo chúc bạn nhận được thật nhiều quà và yêu thương! 🌷❤️",
    "Nụ cười của bạn là điều kỳ diệu nhất hôm nay! 🌼💕",
    "Purrr~ Ngọt ngào, bình an và mãi tươi trẻ nhé! 🎁🎀"
  ];

  function getActiveCatQuotes() {
    if (window.SERVER_SETTINGS && typeof window.SERVER_SETTINGS.cat_quotes === 'string') {
      const list = window.SERVER_SETTINGS.cat_quotes
        .split('\n')
        .map(s => s.trim())
        .filter(s => s.length > 0);
      if (list.length > 0) return list;
    }
    return defaultCatQuotes;
  }

  function triggerCatchButterfly() {
    if (isCatching) return;
    isCatching = true;
    const cb = document.getElementById('catBox');
    const cs = document.getElementById('catShadow');
    if (cb) cb.classList.add('cat-catching');
    if (cs) cs.classList.add('shadow-catching');
    setTimeout(() => {
      isCatching = false;
      if (cb) cb.classList.remove('cat-catching');
      if (cs) cs.classList.remove('shadow-catching');
    }, 1150);
  }
  window.triggerCatchButterfly = triggerCatchButterfly;

  function catCheerAction(e) {
    if (e) e.stopPropagation();
    soundMeow();
    triggerCatchButterfly();
    if (!catSpeech) return;
    const quotes = getActiveCatQuotes();
    const quote = quotes[Math.floor(Math.random() * quotes.length)];
    catSpeech.textContent = quote;
    catSpeech.classList.add('active');
    clearTimeout(speechTimeout);
    speechTimeout = setTimeout(() => catSpeech.classList.remove('active'), 4000);
  }
  window.catCheerAction = catCheerAction;

  /* ================= 6. QUÀ & THƯ RƠI ================= */
  const fallingContainer = document.getElementById('fallingContainer');

  const svgGift = `
    <svg width="54" height="54" viewBox="0 0 100 100" fill="none">
      <ellipse cx="50" cy="88" rx="34" ry="9" fill="rgba(225,29,72,0.3)" />
      <path d="M20 46 L50 62 L50 84 L20 68 Z" fill="#e11d48" stroke="#9f1239" stroke-width="0.8" />
      <path d="M32 52.4 L38 55.6 L38 77.6 L32 74.4 Z" fill="#facc15" />
      <path d="M50 62 L80 46 L80 68 L50 84 Z" fill="#fb7185" stroke="#be123c" stroke-width="0.8" />
      <path d="M62 55.6 L68 52.4 L68 74.4 L62 77.6 Z" fill="#facc15" />
      <path d="M17 40 L50 56 L50 62 L17 46 Z" fill="#be123c" />
      <path d="M50 56 L83 40 L83 46 L50 62 Z" fill="#f43f5e" />
      <path d="M50 24 L83 40 L50 56 L17 40 Z" fill="#fff1f5" stroke="#fda4af" stroke-width="1.2" />
      <path d="M31 30.8 L36.5 28 L69 49.2 L63.5 52 Z" fill="#facc15" />
      <path d="M69 30.8 L63.5 28 L31 49.2 L36.5 52 Z" fill="#facc15" />
      <circle cx="50" cy="34" r="5" fill="#f59e0b" />
      <circle cx="48.5" cy="32.5" r="1.5" fill="#ffffff" />
    </svg>
  `;

  const svgEnv = `
    <svg width="58" height="50" viewBox="0 0 100 88" fill="none">
      <ellipse cx="50" cy="80" rx="42" ry="7" fill="rgba(244,63,94,0.3)" />
      <path d="M16 22 L84 22 L84 46 L16 46 Z" fill="#fff8fa" stroke="#fda4af" stroke-width="1.2" rx="3" />
      <rect x="8" y="24" width="84" height="54" rx="8" fill="#ffffff" stroke="#f472b6" stroke-width="1.5" />
      <path d="M8 78 L42 48 L8 24 Z" fill="#fdf2f8" stroke="#fbcfe8" stroke-width="1" />
      <path d="M92 78 L58 48 L92 24 Z" fill="#fdf2f8" stroke="#fbcfe8" stroke-width="1" />
      <path d="M8 78 L92 78 L50 48 Z" fill="#ffffff" stroke="#f9a8d4" stroke-width="1.4" />
      <path d="M8 24 L50 54 L92 24 C88 16 78 12 66 12 L34 12 C22 12 12 16 8 24 Z" fill="#fff1f5" stroke="#f472b6" stroke-width="1.4" />
      <g transform="translate(50, 52)">
        <circle cx="0" cy="0" r="10" fill="#e11d48" />
        <path d="M0 5 C-6 -1 -8 -6 -3 -9 C-1 -10 0 -7 0 -6 C0 -7 1 -10 3 -9 C8 -6 6 -1 0 5 Z" fill="#ffffff" opacity="0.95" />
      </g>
    </svg>
  `;

  const svgBouquetMini = `
    <svg width="52" height="52" viewBox="0 0 100 100" fill="none">
      <path d="M30 46 L50 86 L70 46 Z" fill="#fde68a" stroke="#b45309" stroke-width="1.5" />
      <circle cx="50" cy="34" r="14" fill="#fb7185" />
      <circle cx="38" cy="42" r="12" fill="#fda4af" />
      <circle cx="62" cy="42" r="12" fill="#fda4af" />
      <circle cx="50" cy="33" r="5" fill="#fef08a" />
      <circle cx="50" cy="62" r="5" fill="#f43f5e" />
    </svg>
  `;

  const isMobileDevice = (window.innerWidth || 380) < 640;

  const mobileFallConfig = [
    { type: 'gift', left: 7, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-1', delay: 0 },
    { type: 'env', left: 23, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-2', delay: 6 },
    { type: 'bq', left: 39, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-1', delay: 2 },
    { type: 'gift', left: 57, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-2', delay: 8 },
    { type: 'env', left: 73, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-1', delay: 4 },
    { type: 'bq', left: 89, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-2', delay: 10 }
  ];

  const desktopFallConfig = [
    { type: 'gift', left: 5, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-1', delay: 0 },
    { type: 'env', left: 15, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-2', delay: 6 },
    { type: 'bq', left: 25, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-1', delay: 2.4 },
    { type: 'gift', left: 35, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-2', delay: 8.4 },
    { type: 'env', left: 45, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-2', delay: 4.8 },
    { type: 'bq', left: 55, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-1', delay: 10.8 },
    { type: 'gift', left: 65, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-1', delay: 1.2 },
    { type: 'env', left: 75, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-2', delay: 7.2 },
    { type: 'bq', left: 85, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-1', delay: 3.6 },
    { type: 'gift', left: 94, dur: 12, swayDur: 3.8, swayAnim: 'fall-sway-2', delay: 9.6 }
  ];

  const currentFallConfig = isMobileDevice ? mobileFallConfig : desktopFallConfig;

  if (fallingContainer) {
    fallingContainer.innerHTML = '';
    currentFallConfig.forEach((cfg) => {
      const track = document.createElement('div');
      track.className = 'fall-track';
      track.style.left = cfg.left + '%';
      track.style.animation = `fall-smooth-y ${cfg.dur}s linear -${cfg.delay}s infinite`;

      const sway = document.createElement('div');
      sway.style.animation = `${cfg.swayAnim} ${cfg.swayDur}s ease-in-out infinite`;

      const btn = document.createElement('div');
      btn.className = 'fall-btn';
      btn.title = 'Bấm nhận quà và lời chúc 🎁';
      if (cfg.type === 'gift') btn.innerHTML = svgGift;
      else if (cfg.type === 'env') btn.innerHTML = svgEnv;
      else btn.innerHTML = svgBouquetMini;

      btn.onclick = (e) => {
        e.stopPropagation();
        startMusicIfNeeded();
        blastHeartFirework(e.clientX, e.clientY);
        openNextGiftModal();
      };

      sway.appendChild(btn);
      track.appendChild(sway);
      fallingContainer.appendChild(track);
    });
  }

  /* ================= 7. HOA MỌC DƯỚI MẶT ĐẤT ================= */
  function sproutGroundFlowers() {
    const groundContainer = document.getElementById('groundFlowers');
    if (!groundContainer) return;
    groundContainer.innerHTML = '';
    const groundFlowerCount = isMobileDevice ? 10 : 18;
    const segmentWidth = 94 / groundFlowerCount;

    const flowerSVGs = [
      `<svg width="28" height="56" viewBox="0 0 40 60" fill="none">
        <path d="M20 18 L20 60" stroke="#15803d" stroke-width="3.5" stroke-linecap="round" />
        <path d="M20 46 Q 10 42 6 46 Q 13 52 20 49" fill="#16a34a" stroke="#14532d" stroke-width="1" />
        <path d="M20 38 Q 30 34 34 37 Q 27 44 20 41" fill="#22c55e" stroke="#14532d" stroke-width="1" />
        <path d="M12 60 Q 16 52 20 60 Q 24 52 28 60" fill="#15803d" />
        <g transform="translate(20, 16)">
          <ellipse cx="-8" cy="-8" rx="7" ry="11" transform="rotate(-30 -8 -8)" fill="#fb7185" stroke="#be123c" stroke-width="1" />
          <ellipse cx="8" cy="-8" rx="7" ry="11" transform="rotate(30 8 -8)" fill="#fb7185" stroke="#be123c" stroke-width="1" />
          <ellipse cx="0" cy="-11" rx="7" ry="12" fill="#fda4af" stroke="#be123c" stroke-width="1" />
          <circle cx="0" cy="-3" r="3.5" fill="#fde047" stroke="#d97706" stroke-width="1" />
        </g>
      </svg>`,
      `<svg width="30" height="56" viewBox="0 0 40 60" fill="none">
        <path d="M20 18 L20 60" stroke="#166534" stroke-width="3.5" stroke-linecap="round" />
        <path d="M20 45 Q 8 40 6 44 Q 12 50 20 48" fill="#16a34a" />
        <path d="M20 38 Q 32 34 34 38 Q 28 44 20 41" fill="#22c55e" />
        <path d="M12 60 Q 16 52 20 60 Q 24 52 28 60" fill="#166534" />
        <g transform="translate(20, 15)">
          <ellipse cx="0" cy="-9" rx="5" ry="9" fill="#fef08a" stroke="#ca8a04" stroke-width="0.8" />
          <ellipse cx="9" cy="0" rx="9" ry="5" fill="#fef08a" stroke="#ca8a04" stroke-width="0.8" />
          <ellipse cx="0" cy="9" rx="5" ry="9" fill="#fef08a" stroke="#ca8a04" stroke-width="0.8" />
          <ellipse cx="-9" cy="0" rx="9" ry="5" fill="#fef08a" stroke="#ca8a04" stroke-width="0.8" />
          <circle cx="0" cy="0" r="4.5" fill="#f97316" />
        </g>
      </svg>`,
      `<svg width="28" height="56" viewBox="0 0 40 60" fill="none">
        <path d="M20 18 L20 60" stroke="#15803d" stroke-width="3.5" stroke-linecap="round" />
        <path d="M20 46 Q 10 42 6 46 Q 13 52 20 49" fill="#16a34a" />
        <path d="M20 39 Q 30 35 34 38 Q 27 45 20 42" fill="#22c55e" />
        <path d="M12 60 Q 16 52 20 60 Q 24 52 28 60" fill="#15803d" />
        <g transform="translate(20, 16)">
          <ellipse cx="-7" cy="-7" rx="6" ry="10" transform="rotate(-30 -7 -7)" fill="#c084fc" stroke="#7e22ce" stroke-width="1" />
          <ellipse cx="7" cy="-7" rx="6" ry="10" transform="rotate(30 7 -7)" fill="#c084fc" stroke="#7e22ce" stroke-width="1" />
          <ellipse cx="0" cy="-10" rx="6" ry="11" fill="#e9d5ff" stroke="#7e22ce" stroke-width="1" />
          <circle cx="0" cy="-3" r="3.2" fill="#fde047" />
        </g>
      </svg>`
    ];

    for (let i = 0; i < groundFlowerCount; i++) {
      const basePos = 3 + i * segmentWidth;
      const jitter = (Math.random() - 0.5) * (segmentWidth * 0.6);
      const pos = Math.max(2, Math.min(98, basePos + jitter)).toFixed(1);

      if (pos >= 42 && pos <= 58) continue;

      const sproutDelay = (0.15 + (i % 6) * 0.18 + Math.random() * 0.15).toFixed(2);
      const swayDelay = (Math.random() * 2.5).toFixed(1);
      const swayDur = (2.6 + Math.random() * 1.4).toFixed(1);
      const tilt = Math.round((Math.random() - 0.5) * 16);
      const randScale = (isMobileDevice ? 0.6 + Math.random() * 0.15 : 0.88 + Math.random() * 0.2).toFixed(2);

      const outer = document.createElement('div');
      outer.className = 'flower-sprout-entry';
      outer.style.position = 'absolute';
      outer.style.bottom = '0px';
      outer.style.left = pos + '%';
      outer.style.transform = 'translateX(-50%)';
      outer.style.animationDelay = sproutDelay + 's';
      outer.style.pointerEvents = 'auto';

      const inner = document.createElement('div');
      inner.className = 'flower-sprite';
      inner.style.transform = `scale(${randScale}) rotate(${tilt}deg)`;
      inner.style.animationDelay = swayDelay + 's';
      inner.style.animationDuration = swayDur + 's';
      inner.innerHTML = flowerSVGs[i % flowerSVGs.length];

      inner.onclick = (e) => {
        e.stopPropagation();
        blastHeartFirework(pos * window.innerWidth / 100, window.innerHeight - 30);
      };

      outer.appendChild(inner);
      groundContainer.appendChild(outer);
    }
  }
  window.sproutGroundFlowers = sproutGroundFlowers;

  /* ================= 8. MODAL LỜI CHÚC (ĐỌC TỪ DATABASE MYSQL) ================= */
  // Lấy dữ liệu lời chúc do PHP render qua window.SERVER_WISHES nếu có
  const defaultWishData = [
    {
      img: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=500&auto=format&fit=crop&q=80",
      recipient: "Gửi Mẹ yêu dấu",
      msg: "Chúc mẹ ngày 20-10 luôn dồi dào sức khỏe, ngập tràn niềm vui và luôn mỉm cười hạnh phúc bên gia đình!"
    },
    {
      img: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&auto=format&fit=crop&q=80",
      recipient: "Gửi Người thương",
      msg: "Chúc em một ngày ngọt ngào, rực rỡ và luôn nở nụ cười xinh đẹp nhất trên môi!"
    },
    {
      img: "https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=500&auto=format&fit=crop&q=80",
      recipient: "Gửi Vợ yêu thương",
      msg: "Cảm ơn em đã luôn là hậu phương ấm áp, người bạn đời tuyệt vời. Chúc vợ 20-10 ngập tràn hạnh phúc và luôn an yên!"
    },
    {
      img: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=500&auto=format&fit=crop&q=80",
      recipient: "Gửi Cô giáo kính yêu",
      msg: "Kính chúc Cô ngày lễ thật nhiều niềm vui, sức khỏe và luôn tràn đầy nhiệt huyết dìu dắt bao thế hệ học trò!"
    },
    {
      img: "https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=500&auto=format&fit=crop&q=80",
      recipient: "Gửi Chị / Em gái",
      msg: "Chúc chị/em ngày càng xinh đẹp, tự tin, công việc hanh thông và đón nhận vạn điều may mắn!"
    },
    {
      img: "https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500&auto=format&fit=crop&q=80",
      recipient: "Gửi Bạn thân",
      msg: "Chúc bạn luôn rạng ngời, tiền vô như nước, luôn yêu đời và sớm có người thương cưng chiều nhé!"
    },
    {
      img: "https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=500&auto=format&fit=crop&q=80",
      recipient: "Gửi Nữ đồng nghiệp",
      msg: "Chúc phái đẹp văn phòng luôn tươi trẻ, công việc thuận lợi, nhiều năng lượng và rạng rỡ mỗi ngày!"
    },
    {
      img: "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=500&auto=format&fit=crop&q=80",
      recipient: "Dành trọn yêu thương",
      msg: "Cảm ơn những hy sinh thầm lặng của phái đẹp. Chúc một nửa thế giới luôn được trân trọng, bình an và hạnh phúc!"
    }
  ];

  const wishData = (window.SERVER_WISHES && Array.isArray(window.SERVER_WISHES) && window.SERVER_WISHES.length > 0)
    ? window.SERVER_WISHES
    : defaultWishData;

  let unshownWishes = [];
  let openedCount = 0;

  function initWishPool() {
    unshownWishes = wishData.map((_, i) => i);
    for (let i = unshownWishes.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [unshownWishes[i], unshownWishes[j]] = [unshownWishes[j], unshownWishes[i]];
    }
  }
  initWishPool();

  let currentShownWishIdx = 0;

  function applyWishToModal(w) {
    const recTextEl = document.getElementById('giftModalRecipientText');
    if (recTextEl) recTextEl.textContent = w.recipient;
    else {
      const recParent = document.getElementById('giftModalRecipient');
      if (recParent) recParent.textContent = w.recipient;
    }

    const modalImg = document.getElementById('giftModalImg');
    if (modalImg) modalImg.src = w.img;

    const modalMsg = document.getElementById('giftModalMsg');
    if (modalMsg) modalMsg.textContent = w.msg;
  }

  function openNextGiftModal() {
    if (unshownWishes.length === 0) {
      initWishPool();
      openedCount = 0;
    }
    currentShownWishIdx = unshownWishes.pop();
    openedCount++;

    const w = wishData[currentShownWishIdx];
    applyWishToModal(w);

    const giftModal = document.getElementById('giftModal');
    if (giftModal) giftModal.classList.add('active');
    blastHeartFirework(window.innerWidth / 2, window.innerHeight * 0.4);
  }
  window.openNextGiftModal = openNextGiftModal;

  function closeGiftModal(e) {
    if (e) e.stopPropagation();
    const giftModal = document.getElementById('giftModal');
    if (giftModal) giftModal.classList.remove('active');
  }
  window.closeGiftModal = closeGiftModal;

  /* ================= 9. CANVAS PHÁO HOA TRÁI TIM & HƯƠNG HOA ================= */
  const confettiCanvas = document.getElementById('confettiCanvas');
  let cCtx = null;
  if (confettiCanvas) {
    cCtx = confettiCanvas.getContext('2d');
    function resizeConfettiCanvas() {
      confettiCanvas.width = window.innerWidth;
      confettiCanvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resizeConfettiCanvas);
    resizeConfettiCanvas();
  }

  let fwParticles = [];
  let aromaParticles = [];

  function triggerCardFirework(e) {
    if (e) e.stopPropagation();
    blastHeartFirework(window.innerWidth / 2, window.innerHeight * 0.28);
  }
  window.triggerCardFirework = triggerCardFirework;

  function triggerManualFirework(e) {
    if (e) e.stopPropagation();
    blastHeartFirework(window.innerWidth / 2, window.innerHeight * 0.32);
  }
  window.triggerManualFirework = triggerManualFirework;

  function blastHeartFirework(x, y) {
    const colors = ['#ff1493', '#ff4d6d', '#f43f5e', '#fda4af', '#facc15', '#ffffff', '#c084fc', '#e11d48'];
    const total = 42;
    for (let i = 0; i < total; i++) {
      const t = (i / total) * Math.PI * 2;
      const hx = 16 * Math.pow(Math.sin(t), 3);
      const hy = -(13 * Math.cos(t) - 5 * Math.cos(2 * t) - 2 * Math.cos(3 * t) - Math.cos(4 * t));
      const speed = 0.22 + Math.random() * 0.08;
      fwParticles.push({
        x: x || window.innerWidth / 2,
        y: y || window.innerHeight * 0.3,
        vx: hx * speed,
        vy: hy * speed,
        size: Math.random() * 2.8 + 2.2,
        color: colors[i % colors.length],
        alpha: 1,
        decay: 0.016 + Math.random() * 0.006
      });
    }
  }
  window.blastHeartFirework = blastHeartFirework;

  const flowerPoints = [
    { x: 300, y: 70 },
    { x: 300, y: 275 },
    { x: 245, y: 90 },
    { x: 200, y: 165 },
    { x: 140, y: 245 },
    { x: 355, y: 90 },
    { x: 400, y: 165 },
    { x: 460, y: 245 }
  ];
  const aromaColors = ['#fce7f3', '#fbcfe8', '#fdf2f8', '#fff1f2', '#fef08a'];

  function emitFlowerAroma() {
    const bSvg = document.getElementById('bouquetSvg');
    if (!bSvg || aromaParticles.length > 85) return;
    const rect = bSvg.getBoundingClientRect();
    const scaleX = rect.width / 600;
    const scaleY = rect.height / 540;

    for (let k = 0; k < 2; k++) {
      const pt = flowerPoints[Math.floor(Math.random() * flowerPoints.length)];
      aromaParticles.push({
        x: rect.left + pt.x * scaleX + (Math.random() - 0.5) * 16,
        y: rect.top + pt.y * scaleY + (Math.random() - 0.5) * 12,
        vx: (Math.random() - 0.5) * 0.45,
        vy: -(0.32 + Math.random() * 0.45),
        size: 2.0 + Math.random() * 2.8,
        color: aromaColors[Math.floor(Math.random() * aromaColors.length)],
        alpha: 0.42,
        decay: 0.0035 + Math.random() * 0.002
      });
    }
  }
  setInterval(emitFlowerAroma, 140);

  window.addEventListener('click', (e) => {
    if (e.target.closest('.modal-card, .modal-letter-box, .modal-overlay, .top-left-controls, .top-controls, #catBox, .fall-btn, .greeting-card, #pin-modal-box, input, textarea, button, form')) return;
    const symbols = ['🌸', '💖', '✨', '🌷'];
    const sym = symbols[Math.floor(Math.random() * symbols.length)];
    const el = document.createElement('div');
    el.textContent = sym;
    el.style.position = 'fixed';
    el.style.left = e.clientX + 'px';
    el.style.top = e.clientY + 'px';
    el.style.pointerEvents = 'none';
    el.style.fontSize = '20px';
    el.style.zIndex = '80';
    el.style.transform = 'translate(-50%, -50%)';
    el.style.transition = 'all 0.9s ease-out';
    document.body.appendChild(el);
    requestAnimationFrame(() => {
      el.style.transform = 'translate(-50%, -70px) scale(1.3)';
      el.style.opacity = '0';
    });
    setTimeout(() => el.remove(), 950);
  });

  function loopRender() {
    if (cCtx && confettiCanvas) {
      cCtx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);

      for (let i = aromaParticles.length - 1; i >= 0; i--) {
        const a = aromaParticles[i];
        a.x += a.vx;
        a.y += a.vy;
        a.alpha -= a.decay;
        if (a.alpha <= 0) { aromaParticles.splice(i, 1); continue; }
        cCtx.globalAlpha = a.alpha;
        cCtx.fillStyle = a.color;
        cCtx.beginPath();
        cCtx.arc(a.x, a.y, a.size, 0, Math.PI * 2);
        cCtx.fill();
      }

      for (let i = fwParticles.length - 1; i >= 0; i--) {
        const p = fwParticles[i];
        p.x += p.vx;
        p.y += p.vy;
        p.vy += 0.07;
        p.alpha -= p.decay;
        if (p.alpha <= 0) { fwParticles.splice(i, 1); continue; }
        cCtx.globalAlpha = p.alpha;
        cCtx.fillStyle = p.color;
        cCtx.beginPath();
        cCtx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        cCtx.fill();
      }
      cCtx.globalAlpha = 1;
    }
    requestAnimationFrame(loopRender);
  }
  loopRender();

  /* ================= 9. XỬ LÝ HỘP THƯ GỬI LỜI YÊU THƯƠNG (PHẢN HỒI SIÊU TỐC) ================= */
  window.openLetterModal = function(e) {
    if (e) {
      e.stopPropagation();
      if (e.preventDefault) e.preventDefault();
    }
    startMusicIfNeeded();
    const modal = document.getElementById('sendLetterModal');
    if (modal) {
      modal.classList.add('active');
      modal.style.display = 'flex';
      const nameInput = document.getElementById('senderNameInput');
      if (nameInput) {
        setTimeout(() => {
          try { nameInput.focus(); } catch (err) {}
        }, 120);
      }
    }
  };

  window.closeLetterModal = function(e) {
    if (e) {
      e.stopPropagation();
      if (e.preventDefault) e.preventDefault();
    }
    const modal = document.getElementById('sendLetterModal');
    if (modal) {
      modal.classList.remove('active');
      modal.style.display = '';
    }
  };

  // Gắn sự kiện trực tiếp lên nút gửi thư để đảm bảo hoạt động 100%
  const openLetterBtnEl = document.getElementById('openLetterBtn');
  if (openLetterBtnEl) {
    openLetterBtnEl.onclick = function(e) {
      window.openLetterModal(e);
    };
  }

  let isSubmittingLetter = false;

  window.handleSendLetterSubmit = function(e) {
    if (e) {
      e.preventDefault();
      if (e.stopPropagation) e.stopPropagation();
    }
    if (isSubmittingLetter) return;

    const nameInput = document.getElementById('senderNameInput');
    const contentInput = document.getElementById('letterContentInput');
    const submitBtn = document.getElementById('btnSubmitLetter');

    const sender = (nameInput ? nameInput.value.trim() : '') || 'Người gửi ẩn danh 🌸';
    const content = contentInput ? contentInput.value.trim() : '';

    if (!content) {
      alert('Vui lòng nhập nội dung lời nhắn!');
      return;
    }

    isSubmittingLetter = true;

    // 1. Phản hồi giao diện tức thì (Optimistic UX)
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> <span>Đã gửi thành công! 💖</span>';
    }

    // Gửi thông báo đến React Admin nếu đang trong iframe preview
    const letterId = Date.now();
    try {
      window.parent.postMessage({
        type: 'NEW_LETTER',
        letter: {
          id: letterId,
          sender_name: sender,
          content: content,
          created_at: new Date().toLocaleString('vi-VN'),
          is_read: 0
        }
      }, '*');
    } catch (err) {}

    // 2. Đóng modal ngay lập tức và bắn pháo hoa chúc mừng
    setTimeout(() => {
      window.closeLetterModal();
      if (nameInput) nameInput.value = '';
      if (contentInput) contentInput.value = '';
      if (submitBtn) {
        const btnText = (window.SERVER_SETTINGS && window.SERVER_SETTINGS.letter_submit_btn_text) || 'Gửi Thư Ngay 💖';
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> <span id="btnSubmitLetterText">${btnText}</span>`;
      }
      isSubmittingLetter = false;
    }, 400);

    // Bắn pháo hoa rực rỡ ăn mừng gửi thư
    blastHeartFirework(window.innerWidth / 2, window.innerHeight * 0.4);
    blastHeartFirework(window.innerWidth * 0.35, window.innerHeight * 0.3);
    blastHeartFirework(window.innerWidth * 0.65, window.innerHeight * 0.3);

    // Hiển thị bong bóng thoại của bé mèo ngay tức thì
    const catSpeech = document.getElementById('catSpeech');
    if (catSpeech) {
      const template = (window.SERVER_SETTINGS && window.SERVER_SETTINGS.letter_cat_speech) || '💌 Bé mèo đã chuyển thư của "{sender}" trao tận tay rồi nhé meo meo~ 🐾💖';
      catSpeech.textContent = template.replace('{sender}', sender);
      catSpeech.classList.add('active');
      setTimeout(() => catSpeech.classList.remove('active'), 5500);
    }

    // 3. Gửi nền AJAX ngầm với Timeout 3s (không làm chậm giao diện người dùng)
    const formData = new FormData();
    formData.append('action', 'send_letter');
    formData.append('sender_name', sender);
    formData.append('content', content);

    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    const timeoutId = controller ? setTimeout(() => controller.abort(), 3000) : null;

    fetch((window.BASE_URL || '') + 'api_send_letter.php', {
      method: 'POST',
      body: formData,
      signal: controller ? controller.signal : undefined
    })
    .catch(() => {
      // Fallback nếu api_send_letter chưa sẵn sàng thì gửi vào index.php
      return fetch((window.BASE_URL || '') + 'index.php', { 
        method: 'POST', 
        body: formData,
        signal: controller ? controller.signal : undefined 
      });
    })
    .catch(() => ({}))
    .finally(() => {
      if (timeoutId) clearTimeout(timeoutId);
    });
  };

  /* ================= 9. BỘ ĐẾM NGƯỢC THỜI GIAN MỞ THIỆP TẠI 2 CÁNH CỬA ================= */
  function initDoorCountdown() {
    const doorWidget = document.getElementById('doorCountdownWidget');
    if (!doorWidget) return;

    const settings = window.SERVER_SETTINGS || {};
    const isEnabled = settings.event_counter_enabled !== undefined 
      ? (settings.event_counter_enabled === '1' || settings.event_counter_enabled === true || settings.event_counter_enabled === 'true')
      : true;

    if (!isEnabled) {
      doorWidget.style.display = 'none';
      return;
    }

    const targetDateStr = settings.event_counter_target_date || '2026-10-20T00:00';
    const mode = settings.event_counter_mode || 'auto';

    const doorCntDays = document.getElementById('doorCntDays');
    const doorCntHours = document.getElementById('doorCntHours');
    const doorCntMinutes = document.getElementById('doorCntMinutes');
    const doorCntSeconds = document.getElementById('doorCntSeconds');
    const doorCounterUnitsWrap = document.getElementById('doorCounterUnitsWrap');
    const doorCounterBadgeText = document.getElementById('doorCounterBadgeText');
    const openHeartBtn = document.getElementById('open-heart-btn');

    let targetTime = new Date(targetDateStr).getTime();
    if (isNaN(targetTime)) {
      targetTime = new Date('2026-10-20T00:00:00').getTime();
    }

    let doorTimerInterval = null;
    let hasCelebratedArrival = false;

    function updateDoorTimer() {
      const now = Date.now();
      let diff = targetTime - now;
      let isCountUp = false;

      if (mode === 'countup') {
        isCountUp = true;
        diff = now - targetTime;
      } else if (mode === 'countdown') {
        isCountUp = false;
        diff = targetTime - now;
      } else {
        if (diff <= 0) {
          isCountUp = true;
          diff = now - targetTime;
        }
      }

      // KHI ĐÃ ĐẾN GIỜ MỞ THIỆP (diff <= 0 hoặc ở chế độ countup/auto đã qua giờ):
      // Người dùng yêu cầu: "đến giờ mở thiệp thì làm cho mất luôn khung đếm ngược luôn"
      if (isCountUp || diff <= 0) {
        if (doorTimerInterval) {
          clearInterval(doorTimerInterval);
          doorTimerInterval = null;
        }

        // Làm mất luôn khung đếm ngược hoàn toàn
        if (doorWidget) {
          if (!hasCelebratedArrival && doorWidget.classList.contains('is-live-counting')) {
            // Đang đếm ngược trực tiếp mà vừa chạm đúng 00:00:00 -> bắn pháo hoa ăn mừng & mờ dần rồi biến mất
            hasCelebratedArrival = true;
            try {
              blastHeartFirework(window.innerWidth / 2, window.innerHeight * 0.4);
              soundMeow();
            } catch (err) {}
            doorWidget.classList.add('door-counter-fadeout');
            setTimeout(() => {
              doorWidget.style.display = 'none';
              doorWidget.classList.add('hidden');
            }, 600);
          } else {
            // Mở trang khi đã quá giờ hoặc đã biến mất -> ẩn ngay lập tức
            doorWidget.style.display = 'none';
            doorWidget.classList.add('hidden');
          }
        }

        // Nút trái tim phát sáng mời gọi người xem chạm vào để nhập PIN mở cửa
        if (openHeartBtn) {
          openHeartBtn.classList.add('unlocked-pulse');
          openHeartBtn.setAttribute('title', 'Đã đến giờ mở thiệp! Chạm để nhập mật khẩu 💖');
        }
        return;
      }

      // Khi CHƯA ĐẾN GIỜ MỞ THIỆP (vẫn đang đếm ngược)
      if (doorWidget) {
        doorWidget.classList.add('is-live-counting');
        doorWidget.classList.remove('hidden', 'door-counter-fadeout');
        doorWidget.style.display = '';
      }
      if (doorCounterUnitsWrap) doorCounterUnitsWrap.classList.remove('hidden');

      if (doorCounterBadgeText && !settings.event_counter_badge) {
        doorCounterBadgeText.textContent = 'Đếm Ngược Mở Thiệp ⏳';
      }

      const positiveDiff = Math.max(0, diff);
      const totalSeconds = Math.floor(positiveDiff / 1000);
      const days = Math.floor(totalSeconds / 86400);
      const hours = Math.floor((totalSeconds % 86400) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = Math.floor(totalSeconds % 60);

      const pad = (n) => String(n).padStart(2, '0');

      if (doorCntDays) doorCntDays.textContent = days >= 100 ? String(days) : pad(days);
      if (doorCntHours) doorCntHours.textContent = pad(hours);
      if (doorCntMinutes) doorCntMinutes.textContent = pad(minutes);
      if (doorCntSeconds) doorCntSeconds.textContent = pad(seconds);
    }

    updateDoorTimer();
    doorTimerInterval = setInterval(updateDoorTimer, 1000);
  }

  // Khởi động bộ đếm mở cửa
  initDoorCountdown();

  // Nhấp vào widget đếm ngược ở cửa
  window.handleDoorCountdownClick = function(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    initAudio();
    if (isDoorLockedByTimer()) {
      soundMeow();
      const notice = document.getElementById('doorLockNotice');
      if (notice) {
        notice.classList.remove('hidden');
        setTimeout(() => notice.classList.add('hidden'), 3500);
      }
    } else {
      openPinModal();
    }
  };
}
