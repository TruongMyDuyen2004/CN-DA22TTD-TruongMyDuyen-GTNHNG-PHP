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

// Cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Họ tên không được để trống';
    } else {
        $stmt = $conn->prepare("UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $phone, $address, $_SESSION['customer_id']])) {
            $success = 'Cập nhật thông tin thành công';
            $_SESSION['customer_name'] = $full_name;
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// Upload avatar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)';
        } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            $error = 'Kích thước file không được vượt quá 5MB';
        } else {
            $upload_dir = 'uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'avatar_' . $_SESSION['customer_id'] . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                // Xóa avatar cũ nếu có
                if (!empty($customer['avatar']) && file_exists($customer['avatar'])) {
                    unlink($customer['avatar']);
                }
                
                $stmt = $conn->prepare("UPDATE customers SET avatar = ? WHERE id = ?");
                if ($stmt->execute([$upload_path, $_SESSION['customer_id']])) {
                    $success = 'Cập nhật ảnh đại diện thành công';
                    // Refresh data
                    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
                    $stmt->execute([$_SESSION['customer_id']]);
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } else {
                $error = 'Lỗi khi upload file';
            }
        }
    } else {
        $error = 'Vui lòng chọn file ảnh';
    }
}

// Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif (!password_verify($current_password, $customer['password'])) {
        $error = 'Mật khẩu hiện tại không đúng';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu mới không khớp';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed, $_SESSION['customer_id']])) {
            $success = 'Đổi mật khẩu thành công';
        }
    }
}
?>

<section class="profile-section">
    <div class="container">
        <h2>Thông tin cá nhân</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="profile-grid">
            <!-- Avatar Card -->
            <div class="profile-card avatar-card">
                <div class="card-header">
                    <i class="fas fa-camera"></i>
                    <h3>Ảnh đại diện</h3>
                </div>
                <div class="avatar-section">
                    <div class="avatar-preview">
                        <?php if (!empty($customer['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($customer['avatar']); ?>" alt="Avatar" id="avatarPreview">
                        <?php else: ?>
                            <div class="avatar-placeholder" id="avatarPreview">
                                <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                        <div class="avatar-upload">
                            <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('avatarInput').click()">
                                <i class="fas fa-upload"></i> Chọn ảnh
                            </button>
                            <button type="submit" name="upload_avatar" class="btn btn-primary" id="uploadBtn" style="display: none;">
                                <i class="fas fa-save"></i> Lưu ảnh
                            </button>
                        </div>
                        <small class="avatar-hint">JPG, PNG hoặc GIF. Tối đa 5MB</small>
                    </form>
                </div>
            </div>
            
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-user-edit"></i>
                    <h3>Thông tin cá nhân</h3>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Họ và tên *</label>
                        <input type="text" name="full_name" required value="<?php echo htmlspecialchars($customer['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($customer['email']); ?>" disabled>
                        <small>Email không thể thay đổi</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <textarea name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Cập nhật</button>
                </form>
            </div>
            
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-lock"></i>
                    <h3>Đổi mật khẩu</h3>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Mật khẩu hiện tại *</label>
                        <input type="password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mật khẩu mới *</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Xác nhận mật khẩu mới *</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">Đổi mật khẩu</button>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
/* Profile Section */
.profile-section {
    padding: 3rem 0 5rem;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

.profile-section h2 {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 2.5rem;
}

/* Profile Grid */
.profile-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    max-width: 900px;
    margin: 0 auto;
}

.profile-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.profile-card:hover {
    box-shadow: 0 6px 30px rgba(0, 0, 0, 0.12);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.card-header i {
    color: #dc2626;
    font-size: 1.25rem;
}

.card-header h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.4rem;
    font-size: 0.875rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem 0.875rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #dc2626;
    background: white;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.form-group input:disabled {
    background: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}

.form-group small {
    display: block;
    margin-top: 0.35rem;
    color: #64748b;
    font-size: 0.8rem;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.btn {
    width: 100%;
    padding: 0.875rem;
    font-size: 0.95rem;
    font-weight: 600;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 0.75rem;
}

.btn-primary {
    background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #b91c1c 0%, #c2410c 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
}

/* Alerts */
.alert {
    padding: 0.875rem 1.25rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    font-weight: 500;
    font-size: 0.9rem;
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 2px solid #6ee7b7;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    border: 2px solid #fca5a5;
}

/* Avatar Card */
.avatar-card {
    grid-column: 1 / -1;
}

.avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}

.avatar-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid #f1f5f9;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.avatar-preview:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 30px rgba(0, 0, 0, 0.15);
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 3rem;
    font-weight: 700;
}

.avatar-upload {
    display: flex;
    gap: 1rem;
}

.avatar-hint {
    color: #64748b;
    font-size: 0.85rem;
    text-align: center;
}

.btn-secondary {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
    color: white;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #475569 0%, #334155 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .profile-section {
        padding: 2rem 0;
    }
    
    .profile-section h2 {
        font-size: 2rem;
        margin-bottom: 1.5rem;
    }
    
    .profile-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .profile-card {
        padding: 1.5rem;
    }
    
    .avatar-preview {
        width: 120px;
        height: 120px;
    }
    
    .avatar-upload {
        flex-direction: column;
        width: 100%;
    }
}
</style>



<style>
/* Notification styles */
.custom-notification {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    min-width: 320px;
    max-width: 500px;
    padding: 2rem 2.5rem;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    z-index: 10000;
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.custom-notification.show {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
}

.custom-notification.success {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.notification-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.notification-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.notification-backdrop.show {
    opacity: 1;
    pointer-events: auto;
}
</style>

<script>
// Preview avatar before upload
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
        };
        reader.readAsDataURL(file);
        document.getElementById('uploadBtn').style.display = 'inline-block';
    }
});
</script>
