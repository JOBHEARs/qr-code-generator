<?php 
session_start();
include 'db.php';

// ค่าตั้งต้น
$text = '';
$color = '#000000';
$size = 200;
$lang = 'th';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['inputData'])) {
        $text = htmlspecialchars($_POST['inputData']);
        $color = $_POST['qrColor'];
        $size = $_POST['qrSize'];
    }
    if (isset($_POST['lang'])) {
        $lang = $_POST['lang'];
    }
    
    // บันทึกประวัติ QR Code
    if (isset($_POST['saveHistory']) && isset($_SESSION["user_id"])) {
        $userId = $_SESSION["user_id"];
        $qrText = htmlspecialchars($_POST['inputData']);
        $qrColor = $_POST['qrColor'];
        $qrSize = $_POST['qrSize'];
        $fileName = htmlspecialchars($_POST['fileName']);
        
        $stmt = $conn->prepare("INSERT INTO qr_history (user_id, qr_text, qr_color, qr_size, file_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $userId, $qrText, $qrColor, $qrSize, $fileName);
        $stmt->execute();
        $stmt->close();
    }
    
    // ลบประวัติ
    if (isset($_POST['deleteHistory']) && isset($_SESSION["user_id"])) {
        $historyId = $_POST['historyId'];
        $userId = $_SESSION["user_id"];
        $stmt = $conn->prepare("DELETE FROM qr_history WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $historyId, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

// ดึงประวัติ QR Code
$history = [];
if (isset($_SESSION["user_id"])) {
    $userId = $_SESSION["user_id"];
    $stmt = $conn->prepare("SELECT id, qr_text, qr_color, qr_size, file_name, created_at FROM qr_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}

// ตรวจสอบการล็อกอิน
$isLoggedIn = isset($_SESSION["user_id"]);
$username = $isLoggedIn ? $_SESSION["username"] : null;
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator Pro</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body onload="loadTheme()">

<!-- Header -->
<div class="header">
    <img src="logo.png" alt="Logo" class="logo">
    <div class="header-right">
        <form method="post" action="" style="margin: 0;">
            <select name="lang" onchange="this.form.submit()">
                <option value="th" <?php echo $lang === 'th' ? 'selected' : ''; ?>>ไทย</option>
                <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
            </select>
        </form>

        <button id="toggleTheme" onclick="toggleTheme()">
            🌓 <?php echo $lang === 'th' ? 'ธีม' : 'Theme'; ?>
        </button>

        <?php if ($isLoggedIn): ?>
            <span>👤 <?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php"><button><?php echo $lang === 'th' ? 'ออกจากระบบ' : 'Logout'; ?></button></a>
        <?php else: ?>
            <a href="login.php"><button><?php echo $lang === 'th' ? 'เข้าสู่ระบบ' : 'Login'; ?></button></a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <!-- Settings Panel -->
    <div class="panel">
        <div class="panel-title">
            ⚙️ <?php echo $lang === 'th' ? 'ตั้งค่า QR Code' : 'QR Code Settings'; ?>
        </div>

        <form id="qrForm">
            <div class="form-group">
                <label class="form-label">
                    <?php echo $lang === 'th' ? 'ข้อมูล' : 'Data'; ?>
                </label>
                <input type="text" class="form-input" id="inputData" 
                       placeholder="<?php echo $lang === 'th' ? 'URL, ข้อความ, หรือข้อมูลอื่นๆ' : 'URL, text, or other data'; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <?php echo $lang === 'th' ? 'ชื่อไฟล์' : 'File Name'; ?>
                </label>
                <input type="text" class="form-input" id="fileName" 
                       placeholder="<?php echo $lang === 'th' ? 'ตั้งชื่อไฟล์ของคุณ' : 'Set your file name'; ?>" 
                       value="my_qrcode">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <?php echo $lang === 'th' ? 'เลือกสี' : 'Choose Color'; ?>
                </label>
                <div class="color-picker-section">
                    <div class="color-preset-grid">
                        <div class="color-preset active" style="background: #000000;" data-color="#000000"></div>
                        <div class="color-preset" style="background: #1a73e8;" data-color="#1a73e8"></div>
                        <div class="color-preset" style="background: #34a853;" data-color="#34a853"></div>
                        <div class="color-preset" style="background: #ea4335;" data-color="#ea4335"></div>
                        <div class="color-preset" style="background: #fbbc04;" data-color="#fbbc04"></div>
                        <div class="color-preset" style="background: #9c27b0;" data-color="#9c27b0"></div>
                        <div class="color-preset" style="background: #ff6d00;" data-color="#ff6d00"></div>
                        <div class="color-preset" style="background: #00bcd4;" data-color="#00bcd4"></div>
                        <div class="color-preset" style="background: #e91e63;" data-color="#e91e63"></div>
                        <div class="color-preset" style="background: #3f51b5;" data-color="#3f51b5"></div>
                        <div class="color-preset" style="background: #009688;" data-color="#009688"></div>
                        <div class="color-preset" style="background: #8bc34a;" data-color="#8bc34a"></div>
                        <div class="color-preset" style="background: #ff5722;" data-color="#ff5722"></div>
                        <div class="color-preset" style="background: #607d8b;" data-color="#607d8b"></div>
                        <div class="color-preset" style="background: #795548;" data-color="#795548"></div>
                        <div class="color-preset" style="background: #424242;" data-color="#424242"></div>
                    </div>
                    <div class="custom-color-input">
                        <input type="color" id="qrColor" value="#000000">
                        <input type="text" class="form-input" id="colorHex" value="#000000" placeholder="#000000">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <?php echo $lang === 'th' ? 'ขนาด' : 'Size'; ?>
                </label>
                <div class="size-options">
                    <div class="size-option" data-size="150">
                        <span class="size-label"><?php echo $lang === 'th' ? 'เล็ก' : 'Small'; ?></span>
                        <span class="size-value">150×150</span>
                    </div>
                    <div class="size-option active" data-size="200">
                        <span class="size-label"><?php echo $lang === 'th' ? 'กลาง' : 'Medium'; ?></span>
                        <span class="size-value">200×200</span>
                    </div>
                    <div class="size-option" data-size="300">
                        <span class="size-label"><?php echo $lang === 'th' ? 'ใหญ่' : 'Large'; ?></span>
                        <span class="size-value">300×300</span>
                    </div>
                </div>
                <input type="hidden" id="qrSize" value="200">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <?php echo $lang === 'th' ? 'รูปแบบไฟล์' : 'File Format'; ?>
                </label>
                <select class="form-input" id="fileType">
                    <option value="png">PNG</option>
                    <option value="jpg">JPG</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>
        </form>
    </div>

    <!-- QR Preview Panel -->
    <div class="panel qr-preview-panel">
        <div class="panel-title">
            👁️ <?php echo $lang === 'th' ? 'ตัวอย่าง QR Code' : 'QR Code Preview'; ?>
        </div>

        <div class="qr-display">
            <div id="qrcode">
                <div class="qr-placeholder">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zM3 21h8v-8H3v8zm2-6h4v4H5v-4zM13 3v8h8V3h-8zm6 6h-4V5h4v4zM13 13h2v2h-2zM15 15h2v2h-2zM13 17h2v2h-2zM15 19h2v2h-2zM17 17h2v2h-2zM17 13h2v2h-2zM19 15h2v2h-2z"/>
                    </svg>
                    <p><?php echo $lang === 'th' ? 'ใส่ข้อมูลเพื่อสร้าง QR Code' : 'Enter data to generate QR Code'; ?></p>
                </div>
            </div>
        </div>

        <div id="qrInfoBox" style="display: none;">
            <div class="qr-info">
                <div class="qr-info-row">
                    <span><?php echo $lang === 'th' ? 'ข้อมูล:' : 'Data:'; ?></span>
                    <strong id="infoText">-</strong>
                </div>
                <div class="qr-info-row">
                    <span><?php echo $lang === 'th' ? 'สี:' : 'Color:'; ?></span>
                    <strong id="infoColor">-</strong>
                </div>
                <div class="qr-info-row">
                    <span><?php echo $lang === 'th' ? 'ขนาด:' : 'Size:'; ?></span>
                    <strong id="infoSize">-</strong>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" id="downloadQR" style="display:none;" onclick="downloadQR()">
            ⬇️ <?php echo $lang === 'th' ? 'ดาวน์โหลด' : 'Download'; ?>
        </button>

        <?php if ($isLoggedIn): ?>
        <button class="btn btn-secondary" id="saveHistory" style="display:none;" onclick="saveToHistory()">
            💾 <?php echo $lang === 'th' ? 'บันทึกลงประวัติ' : 'Save to History'; ?>
        </button>
        <?php endif; ?>
    </div>

    <!-- History Panel -->
    <div class="panel history-panel">
        <div class="panel-title">
            📚 <?php echo $lang === 'th' ? 'ประวัติ QR Code' : 'QR Code History'; ?>
        </div>

        <?php if ($isLoggedIn): ?>
            <?php if (count($history) > 0): ?>
                <div id="historyList">
                    <?php foreach ($history as $item): ?>
                        <div class="history-item">
                            <div class="history-qr-mini" id="mini-qr-<?php echo $item['id']; ?>"></div>
                            <div class="history-content" onclick='loadFromHistory(<?php echo json_encode($item); ?>)'>
                                <div class="history-name">📄 <?php echo htmlspecialchars($item['file_name']); ?></div>
                                <div class="history-text"><?php echo htmlspecialchars($item['qr_text']); ?></div>
                                <div class="history-date">🕒 <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></div>
                            </div>
                            <div class="history-actions">
                                <button class="history-delete" onclick="deleteHistory(<?php echo $item['id']; ?>, event)">🗑️</button>
                            </div>
                        </div>
                        <script>
                            setTimeout(() => {
                                new QRCode(document.getElementById("mini-qr-<?php echo $item['id']; ?>"), {
                                    text: "<?php echo addslashes($item['qr_text']); ?>",
                                    width: 44,
                                    height: 44,
                                    colorDark: "<?php echo $item['qr_color']; ?>",
                                    colorLight: "#ffffff"
                                });
                            }, 100);
                        </script>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="history-empty">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                    <p><?php echo $lang === 'th' ? 'ยังไม่มีประวัติ<br>สร้าง QR Code แรกของคุณ!' : 'No history yet<br>Create your first QR Code!'; ?></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="history-empty">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
                <p><?php echo $lang === 'th' ? 'เข้าสู่ระบบเพื่อดูประวัติ' : 'Login to view history'; ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
    const lang = '<?php echo $lang; ?>';
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    let currentQRText = '';

    // Color Preset Selection
    document.querySelectorAll('.color-preset').forEach(preset => {
        preset.addEventListener('click', function() {
            document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            
            const color = this.getAttribute('data-color');
            document.getElementById('qrColor').value = color;
            document.getElementById('colorHex').value = color;
            generateQR();
        });
    });

    // Color Picker Change
    document.getElementById('qrColor').addEventListener('input', function() {
        const color = this.value;
        document.getElementById('colorHex').value = color;
        document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
        generateQR();
    });

    // Hex Input Change
    document.getElementById('colorHex').addEventListener('input', function() {
        let color = this.value;
        if (color.charAt(0) !== '#') {
            color = '#' + color;
        }
        if (/^#[0-9A-F]{6}$/i.test(color)) {
            document.getElementById('qrColor').value = color;
            document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
            generateQR();
        }
    });

    // Size Selection
    document.querySelectorAll('.size-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.size-option').forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            
            const size = this.getAttribute('data-size');
            document.getElementById('qrSize').value = size;
            generateQR();
        });
    });

    function generateQR() {
        let text = document.getElementById("inputData").value.trim();
        let color = document.getElementById("qrColor").value;
        let size = parseInt(document.getElementById("qrSize").value);
        
        if (text === "") {
            document.getElementById("qrcode").innerHTML = `
                <div class="qr-placeholder">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zM3 21h8v-8H3v8zm2-6h4v4H5v-4zM13 3v8h8V3h-8zm6 6h-4V5h4v4zM13 13h2v2h-2zM15 15h2v2h-2zM13 17h2v2h-2zM15 19h2v2h-2zM17 17h2v2h-2zM17 13h2v2h-2zM19 15h2v2h-2z"/>
                    </svg>
                    <p>${lang === 'th' ? 'ใส่ข้อมูลเพื่อสร้าง QR Code' : 'Enter data to generate QR Code'}</p>
                </div>
            `;
            document.getElementById("downloadQR").style.display = "none";
            document.getElementById("qrInfoBox").style.display = "none";
            if (isLoggedIn) {
                document.getElementById("saveHistory").style.display = "none";
            }
            return;
        }

        currentQRText = text;
        document.getElementById("qrcode").innerHTML = "";
        new QRCode(document.getElementById("qrcode"), {
            text: text,
            width: size,
            height: size,
            colorDark: color,
            colorLight: "#ffffff"
        });

        // Update info box
        document.getElementById("infoText").textContent = text.length > 30 ? text.substring(0, 30) + '...' : text;
        document.getElementById("infoColor").textContent = color.toUpperCase();
        document.getElementById("infoSize").textContent = size + '×' + size + 'px';
        document.getElementById("qrInfoBox").style.display = "block";

        document.getElementById("downloadQR").style.display = "block";
        if (isLoggedIn) {
            document.getElementById("saveHistory").style.display = "block";
        }
    }

    function downloadQR() {
        let canvas = document.querySelector("#qrcode canvas");
        let fileName = document.getElementById("fileName").value.trim() || "qrcode";

        if (!canvas) {
            showToast(lang === 'th' ? '⚠️ กรุณาสร้าง QR Code ก่อนดาวน์โหลด!' : '⚠️ Please generate QR Code first!', 'error');
            return;
        }

        let fileType = document.getElementById("fileType").value;

        if (fileType === "png" || fileType === "jpg") {
            let mimeType = fileType === "png" ? "image/png" : "image/jpeg";
            let imageData = canvas.toDataURL(mimeType);
            
            let link = document.createElement("a");
            link.href = imageData;
            link.download = `${fileName}.${fileType}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showToast(lang === 'th' ? '✅ ดาวน์โหลดสำเร็จ!' : '✅ Downloaded successfully!', 'success');
        } 
        else if (fileType === "pdf") {
            const { jsPDF } = window.jspdf;
            let pdf = new jsPDF();
            let imgData = canvas.toDataURL("image/png");
            let pageWidth = pdf.internal.pageSize.getWidth();
            let qrSize = 60;
            let xPos = (pageWidth - qrSize) / 2;

            pdf.addImage(imgData, "PNG", xPos, 30, qrSize, qrSize);
            pdf.save(`${fileName}.pdf`);
            
            showToast(lang === 'th' ? '✅ ดาวน์โหลดสำเร็จ!' : '✅ Downloaded successfully!', 'success');
        }
    }

    function saveToHistory() {
        let text = document.getElementById("inputData").value.trim();
        let color = document.getElementById("qrColor").value;
        let size = document.getElementById("qrSize").value;
        let fileName = document.getElementById("fileName").value.trim() || "qrcode";

        if (text === "") {
            showToast(lang === 'th' ? '⚠️ กรุณาใส่ข้อมูลก่อนบันทึก!' : '⚠️ Please enter data first!', 'error');
            return;
        }

        let formData = new FormData();
        formData.append('saveHistory', '1');
        formData.append('inputData', text);
        formData.append('qrColor', color);
        formData.append('qrSize', size);
        formData.append('fileName', fileName);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            showToast(lang === 'th' ? '✅ บันทึกสำเร็จ!' : '✅ Saved successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        })
        .catch(error => {
            showToast(lang === 'th' ? '❌ เกิดข้อผิดพลาด!' : '❌ An error occurred!', 'error');
        });
    }

    function loadFromHistory(item) {
        document.getElementById("inputData").value = item.qr_text;
        document.getElementById("qrColor").value = item.qr_color;
        document.getElementById("colorHex").value = item.qr_color;
        document.getElementById("qrSize").value = item.qr_size;
        document.getElementById("fileName").value = item.file_name;
        
        // Update size selection
        document.querySelectorAll('.size-option').forEach(o => o.classList.remove('active'));
        document.querySelector(`.size-option[data-size="${item.qr_size}"]`).classList.add('active');
        
        // Clear color preset selection
        document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
        
        generateQR();
        showToast(lang === 'th' ? '📥 โหลดจากประวัติแล้ว' : '📥 Loaded from history', 'success');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deleteHistory(id, event) {
        event.stopPropagation();
        
        if (!confirm(lang === 'th' ? 'คุณต้องการลบประวัตินี้?' : 'Delete this history?')) {
            return;
        }

        let formData = new FormData();
        formData.append('deleteHistory', '1');
        formData.append('historyId', id);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            showToast(lang === 'th' ? '🗑️ ลบสำเร็จ!' : '🗑️ Deleted successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            showToast(lang === 'th' ? '❌ เกิดข้อผิดพลาด!' : '❌ An error occurred!', 'error');
        });
    }

    function showToast(message, type) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + type;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function toggleTheme() {
        document.body.classList.toggle("dark-theme");
        let isDark = document.body.classList.contains("dark-theme");
        localStorage.setItem("theme", isDark ? "dark" : "light");
    }

    function loadTheme() {
        let savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") {
            document.body.classList.add("dark-theme");
        }
    }

    // Event Listeners
    document.getElementById("inputData").addEventListener("input", generateQR);

    <?php if ($text) { ?>
        generateQR();
    <?php } ?>
</script>

</body>
</html>