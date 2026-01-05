<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

$success = '';
$error = '';

// Lấy thông tin khách hàng
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy thống kê đơn hàng
$orderStats = ['total' => 0, 'completed' => 0, 'pending' => 0, 'total_spent' => 0];
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $orderStats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM orders WHERE customer_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['customer_id']]);
    $orderStats['completed'] = $stmt->fetch(PDO::FETCH_ASSOC)['completed'] ?? 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM orders WHERE customer_id = ? AND status IN ('pending', 'processing', 'confirmed', 'preparing', 'delivering')");
    $stmt->execute([$_SESSION['customer_id']]);
    $orderStats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE customer_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['customer_id']]);
    $orderStats['total_spent'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;
} catch (Exception $e) {}

// Format ngày tham gia
$joinDate = isset($customer['created_at']) ? date('d/m/Y', strtotime($customer['created_at'])) : 'N/A';

// Kiểm tra Google login
$hasGoogleLogin = !empty($customer['google_id']);

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Họ tên không được để trống';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        $checkEmail = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $_SESSION['customer_id']]);
        if ($checkEmail->fetch()) {
            $error = 'Email này đã được sử dụng';
        } else {
            $stmt = $conn->prepare("UPDATE customers SET full_name = ?, email = ?, phone = ?, address = ?, birthday = ?, gender = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $phone, $address, $birthday ?: null, $gender ?: null, $_SESSION['customer_id']])) {
                $success = 'Cập nhật thông tin thành công';
                $_SESSION['customer_name'] = $full_name;
                $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
                $stmt->execute([$_SESSION['customer_id']]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
}

// Upload avatar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
    if ($_FILES['avatar']['error'] == 0 && $_FILES['avatar']['size'] > 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)';
        } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            $error = 'File không được vượt quá 5MB';
        } else {
            $base_dir = __DIR__ . '/../uploads/avatars/';
            if (!file_exists($base_dir)) mkdir($base_dir, 0777, true);
            
            $new_filename = 'avatar_' . $_SESSION['customer_id'] . '_' . time() . '.' . $ext;
            $db_path = 'uploads/avatars/' . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $base_dir . $new_filename)) {
                if (!empty($customer['avatar'])) @unlink(__DIR__ . '/../' . $customer['avatar']);
                $stmt = $conn->prepare("UPDATE customers SET avatar = ? WHERE id = ?");
                $stmt->execute([$db_path, $_SESSION['customer_id']]);
                $success = 'Cập nhật ảnh đại diện thành công';
                $customer['avatar'] = $db_path;
            }
        }
    }
}

// Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($current) || empty($new)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif (!password_verify($current, $customer['password'])) {
        $error = 'Mật khẩu hiện tại không đúng';
    } elseif ($new !== $confirm) {
        $error = 'Mật khẩu mới không khớp';
    } elseif (strlen($new) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        $stmt = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
        if ($stmt->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['customer_id']])) {
            $success = 'Đổi mật khẩu thành công';
        }
    }
}
?>

<section class="profile-section">
    <div class="profile-wrapper">
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <form method="POST" enctype="multipart/form-data" id="avatarForm">
                    <div class="avatar-wrapper" id="avatarPreview">
                        <?php if (!empty($customer['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($customer['avatar']); ?>" alt="Avatar">
                        <?php else: ?>
                            <span class="avatar-letter"><?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="avatar" id="avatarInput" accept="image/*" hidden>
                    <input type="hidden" name="upload_avatar" value="1">
                    <button type="button" class="avatar-edit" onclick="document.getElementById('avatarInput').click()">
                        <i class="fas fa-camera"></i>
                    </button>
                </form>
                <form method="POST" enctype="multipart/form-data" id="avatarSubmit" style="display:none">
                    <input type="file" name="avatar" id="avatarHidden" accept="image/*" hidden>
                    <input type="hidden" name="upload_avatar" value="1">
                </form>
                <div class="avatar-actions" id="avatarActions" style="display:none">
                    <button type="button" class="btn-cancel" onclick="cancelAvatar()"><i class="fas fa-times"></i></button>
                    <button type="button" class="btn-save" onclick="saveAvatar()"><i class="fas fa-check"></i></button>
                </div>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($customer['full_name']); ?></h1>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></p>
                <p><i class="fas fa-calendar"></i> Tham gia: <?php echo $joinDate; ?></p>
            </div>
            <div class="profile-badges">
                <span class="badge"><i class="fas fa-user-check"></i> Thành viên</span>
                <?php if ($hasGoogleLogin): ?>
                    <span class="badge badge-google"><i class="fab fa-google"></i> Google</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats -->
        <div class="profile-stats">
            <div class="stat-box">
                <i class="fas fa-shopping-bag"></i>
                <div><span class="stat-num"><?php echo $orderStats['total']; ?></span><span class="stat-label">Tổng đơn</span></div>
            </div>
            <div class="stat-box">
                <i class="fas fa-check-circle"></i>
                <div><span class="stat-num"><?php echo $orderStats['completed']; ?></span><span class="stat-label">Hoàn thành</span></div>
            </div>
            <div class="stat-box">
                <i class="fas fa-clock"></i>
                <div><span class="stat-num"><?php echo $orderStats['pending']; ?></span><span class="stat-label">Đang xử lý</span></div>
            </div>
            <div class="stat-box">
                <i class="fas fa-wallet"></i>
                <div><span class="stat-num"><?php echo number_format($orderStats['total_spent'], 0, ',', '.'); ?>đ</span><span class="stat-label">Đã chi tiêu</span></div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="profile-content">
            <!-- Personal Info -->
            <div class="profile-card">
                <h3><i class="fas fa-user"></i> Thông tin cá nhân</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Họ và tên</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($customer['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" placeholder="Chưa cập nhật">
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" placeholder="Chưa cập nhật">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày sinh</label>
                            <input type="date" name="birthday" value="<?php echo htmlspecialchars($customer['birthday'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Giới tính</label>
                            <select name="gender">
                                <option value="">Chọn giới tính</option>
                                <option value="male" <?php echo ($customer['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Nam</option>
                                <option value="female" <?php echo ($customer['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Nữ</option>
                                <option value="other" <?php echo ($customer['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <h3><i class="fas fa-lock"></i> Đổi mật khẩu</h3>
                <form method="POST">
                    <div class="form-row three">
                        <div class="form-group">
                            <label>Mật khẩu hiện tại</label>
                            <div class="input-pass">
                                <input type="password" name="current_password" id="pass1" required placeholder="••••••••">
                                <button type="button" onclick="togglePass('pass1')"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu mới</label>
                            <div class="input-pass">
                                <input type="password" name="new_password" id="pass2" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                                <button type="button" onclick="togglePass('pass2')"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Xác nhận mật khẩu</label>
                            <div class="input-pass">
                                <input type="password" name="confirm_password" id="pass3" required minlength="6" placeholder="Nhập lại">
                                <button type="button" onclick="togglePass('pass3')"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn-secondary"><i class="fas fa-key"></i> Đổi mật khẩu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.profile-section {
    min-height: 100vh;
    background: #f1f5f9;
    padding: 2rem 1rem;
}
.profile-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

/* Alert */
.alert {
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    font-weight: 500;
}
.alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Header */
.profile-header {
    background: linear-gradient(135deg, #059669, #047857);
    border-radius: 20px;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.profile-avatar { position: relative; }
.avatar-wrapper {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 3px solid rgba(255,255,255,0.3);
}
.avatar-wrapper img { width: 100%; height: 100%; object-fit: cover; }
.avatar-letter { font-size: 2.5rem; font-weight: 700; color: #059669; }
.avatar-edit {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #059669;
    color: #059669;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
.avatar-edit:hover { background: #059669; color: #fff; }
.avatar-actions {
    position: absolute;
    bottom: -35px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;
}
.btn-cancel, .btn-save {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
.btn-cancel { background: #fff; color: #dc2626; }
.btn-save { background: #059669; color: #fff; }

.profile-info { flex: 1; }
.profile-info h1 { color: #fff; font-size: 1.5rem; margin: 0 0 0.5rem; }
.profile-info p { color: rgba(255,255,255,0.85); font-size: 0.9rem; margin: 0.25rem 0; display: flex; align-items: center; gap: 0.5rem; }
.profile-badges { display: flex; gap: 0.5rem; }
.badge {
    background: rgba(255,255,255,0.2);
    color: #fff;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.badge-google { background: rgba(234,67,53,0.9); }

/* Stats */
.profile-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.stat-box {
    background: #fff;
    border-radius: 14px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid #e5e7eb;
}
.stat-box i {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}
.stat-box:nth-child(1) i { background: #dbeafe; color: #2563eb; }
.stat-box:nth-child(2) i { background: #dcfce7; color: #16a34a; }
.stat-box:nth-child(3) i { background: #fef3c7; color: #d97706; }
.stat-box:nth-child(4) i { background: #fce7f3; color: #db2777; }
.stat-box div { display: flex; flex-direction: column; }
.stat-num { font-size: 1.25rem; font-weight: 700; color: #111827; }
.stat-label { font-size: 0.75rem; color: #6b7280; text-transform: uppercase; }

/* Content */
.profile-content { display: flex; flex-direction: column; gap: 1.5rem; }
.profile-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
}
.profile-card h3 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.1rem;
    color: #111827;
    margin: 0 0 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f3f4f6;
}
.profile-card h3 i {
    width: 34px;
    height: 34px;
    background: #ecfdf5;
    color: #059669;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

/* Form */
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.form-row.three { grid-template-columns: repeat(3, 1fr); }
.form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
.form-group input,
.form-group select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #111827;
    background: #fff;
    transition: all 0.2s;
}
.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5,150,105,0.1);
}
.form-group input::placeholder { color: #9ca3af; }
.form-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}

/* Force light theme for select */
.profile-section select,
.profile-section .form-group select,
.profile-section select option {
    background-color: #ffffff !important;
    background: #ffffff !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827 !important;
}
.profile-section select:focus {
    background-color: #ffffff !important;
    color: #111827 !important;
}

/* Password input */
.input-pass { position: relative; }
.input-pass input { padding-right: 44px; }
.input-pass button {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.4rem;
}
.input-pass button:hover { color: #059669; }

/* Buttons */
.form-actions { margin-top: 1.5rem; display: flex; justify-content: flex-end; }
.btn-primary, .btn-secondary {
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}
.btn-primary {
    background: linear-gradient(135deg, #059669, #047857);
    color: #fff;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,150,105,0.3); }
.btn-secondary {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
}
.btn-secondary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }

/* Responsive */
@media (max-width: 768px) {
    .profile-header { flex-direction: column; text-align: center; padding: 1.5rem; }
    .profile-info { text-align: center; }
    .profile-info p { justify-content: center; }
    .profile-badges { justify-content: center; }
    .profile-stats { grid-template-columns: repeat(2, 1fr); }
    .form-row, .form-row.three { grid-template-columns: 1fr; }
    .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
}
@media (max-width: 480px) {
    .profile-section { padding: 1rem 0.75rem; }
    .stat-box { padding: 1rem; gap: 0.75rem; }
    .stat-box i { width: 38px; height: 38px; font-size: 1rem; }
    .stat-num { font-size: 1.1rem; }
    .profile-card { padding: 1.25rem; }
}
</style>

<script>
let originalAvatar = document.getElementById('avatarPreview').innerHTML;
let selectedFile = null;

document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) { alert('File không được vượt quá 5MB'); return; }
        if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) { alert('Chỉ chấp nhận JPG, PNG, GIF'); return; }
        selectedFile = file;
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatarPreview').innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        reader.readAsDataURL(file);
        document.querySelector('.avatar-edit').style.display = 'none';
        document.getElementById('avatarActions').style.display = 'flex';
    }
});

function saveAvatar() {
    if (selectedFile) {
        const dt = new DataTransfer();
        dt.items.add(selectedFile);
        document.getElementById('avatarHidden').files = dt.files;
        document.getElementById('avatarSubmit').submit();
    }
}

function cancelAvatar() {
    document.getElementById('avatarPreview').innerHTML = originalAvatar;
    document.getElementById('avatarForm').reset();
    document.querySelector('.avatar-edit').style.display = 'flex';
    document.getElementById('avatarActions').style.display = 'none';
    selectedFile = null;
}

function togglePass(id) {
    const input = document.getElementById(id);
    const icon = input.parentElement.querySelector('i');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}
</script>
