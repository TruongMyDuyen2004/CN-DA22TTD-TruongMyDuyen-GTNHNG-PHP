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
    
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM orders WHERE customer_id = ? AND status IN ('pending', 'processing')");
    $stmt->execute([$_SESSION['customer_id']]);
    $orderStats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE customer_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['customer_id']]);
    $orderStats['total_spent'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;
} catch (Exception $e) {
    // Bỏ qua lỗi nếu bảng orders chưa có
}

// Format ngày tham gia
$joinDate = isset($customer['created_at']) ? date('d/m/Y', strtotime($customer['created_at'])) : 'N/A';

// Cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Họ tên không được để trống';
    } else {
        // Xử lý birthday
        $birthdayValue = !empty($birthday) ? $birthday : null;
        $genderValue = !empty($gender) ? $gender : null;
        
        $stmt = $conn->prepare("UPDATE customers SET full_name = ?, phone = ?, address = ?, birthday = ?, gender = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $phone, $address, $birthdayValue, $genderValue, $_SESSION['customer_id']])) {
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_avatar']) && isset($_FILES['avatar'])) {
    if ($_FILES['avatar']['error'] == 0 && $_FILES['avatar']['size'] > 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $error = 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)';
        } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            $error = 'Kích thước file không được vượt quá 5MB';
        } else {
            // Đường dẫn tuyệt đối để tạo thư mục
            $base_dir = __DIR__ . '/../uploads/avatars/';
            if (!file_exists($base_dir)) {
                mkdir($base_dir, 0777, true);
            }
            
            $new_filename = 'avatar_' . $_SESSION['customer_id'] . '_' . time() . '.' . $ext;
            $full_path = $base_dir . $new_filename;
            // Đường dẫn lưu vào DB (tương đối từ thư mục gốc)
            $db_path = 'uploads/avatars/' . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $full_path)) {
                // Xóa avatar cũ nếu có (bỏ qua lỗi nếu không xóa được)
                if (!empty($customer['avatar'])) {
                    $old_file = __DIR__ . '/../' . $customer['avatar'];
                    if (file_exists($old_file) && is_writable($old_file)) {
                        @unlink($old_file);
                    }
                }
                
                $stmt = $conn->prepare("UPDATE customers SET avatar = ? WHERE id = ?");
                if ($stmt->execute([$db_path, $_SESSION['customer_id']])) {
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
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- Profile Header Card -->
        <div class="profile-hero">
            <div class="profile-hero-bg"></div>
            <div class="profile-hero-content">
                <div class="avatar-wrapper">
                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                        <div class="avatar-preview" id="avatarPreview">
                            <?php if (!empty($customer['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($customer['avatar']); ?>" alt="Avatar">
                            <?php else: ?>
                                <span class="avatar-letter"><?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" hidden>
                        <input type="hidden" name="upload_avatar" value="1">
                        <button type="button" class="avatar-edit-btn" id="editAvatarBtn" onclick="document.getElementById('avatarInput').click()">
                            <i class="fas fa-camera"></i>
                        </button>
                        <div class="avatar-actions" id="avatarActions" style="display: none;">
                            <button type="button" class="btn-cancel-avatar" onclick="cancelAvatarChange()">
                                <i class="fas fa-times"></i> Hủy
                            </button>
                            <button type="submit" class="btn-confirm-avatar">
                                <i class="fas fa-check"></i> Lưu ảnh
                            </button>
                        </div>
                    </form>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($customer['full_name']); ?></h1>
                    <p class="profile-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></p>
                    <p class="profile-join-date"><i class="fas fa-calendar-alt"></i> Tham gia: <?php echo $joinDate; ?></p>
                    <div class="profile-badges">
                        <span class="badge badge-member"><i class="fas fa-user-check"></i> Thành viên</span>
                        <span class="badge badge-verified"><i class="fas fa-shield-alt"></i> Đã xác thực</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-icon stat-orders">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $orderStats['total']; ?></span>
                    <span class="stat-label">Tổng đơn hàng</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $orderStats['completed']; ?></span>
                    <span class="stat-label">Hoàn thành</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $orderStats['pending']; ?></span>
                    <span class="stat-label">Đang xử lý</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-spent">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($orderStats['total_spent'], 0, ',', '.'); ?>đ</span>
                    <span class="stat-label">Đã chi tiêu</span>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="profile-content">
            <!-- Personal Info -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-user"></i>
                    <span>Thông tin cá nhân</span>
                </div>
                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Họ và tên</label>
                            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($customer['full_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($customer['email']); ?>" disabled class="disabled">
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
                            <select name="gender" class="form-select">
                                <option value="">-- Chọn giới tính --</option>
                                <option value="male" <?php echo ($customer['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Nam</option>
                                <option value="female" <?php echo ($customer['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Nữ</option>
                                <option value="other" <?php echo ($customer['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-save">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-lock"></i>
                    <span>Đổi mật khẩu</span>
                </div>
                <form method="POST" class="profile-form">
                    <div class="form-row three-cols">
                        <div class="form-group">
                            <label>Mật khẩu hiện tại</label>
                            <div class="input-password">
                                <input type="password" name="current_password" id="current_password" required placeholder="••••••••">
                                <button type="button" onclick="togglePass('current_password')"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu mới</label>
                            <div class="input-password">
                                <input type="password" name="new_password" id="new_password" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                                <button type="button" onclick="togglePass('new_password')"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Xác nhận mật khẩu</label>
                            <div class="input-password">
                                <input type="password" name="confirm_password" id="confirm_password" required minlength="6" placeholder="Nhập lại mật khẩu">
                                <button type="button" onclick="togglePass('confirm_password')"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn-password">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>
            

        </div>
    </div>
</section>

<style>
/* Profile Section - Green Theme */
.profile-section {
    padding: 2rem;
    background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 100%) !important;
    min-height: calc(100vh - 80px);
}

.profile-section .container {
    max-width: 900px;
    margin: 0 auto;
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

/* Profile Hero */
.profile-hero {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
    border-radius: 16px !important;
    padding: 2rem !important;
    margin-bottom: 1.5rem !important;
    position: relative !important;
    overflow: hidden !important;
    display: block !important;
    visibility: visible !important;
    border: 2px solid #16a34a;
}

.profile-hero-bg {
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
    border-radius: 0 16px 16px 0;
}

.profile-hero-content {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 2rem;
    position: relative;
    z-index: 1;
}

/* Profile Info - Center */
.profile-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

/* Avatar */
.avatar-wrapper {
    position: relative;
    flex-shrink: 0;
    padding-bottom: 10px;
}

.avatar-preview {
    width: 100px !important;
    height: 100px !important;
    border-radius: 50% !important;
    background: #fff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: 4px solid rgba(255, 255, 255, 0.5) !important;
    overflow: hidden !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-letter {
    font-size: 2.5rem;
    font-weight: 700;
    color: #22c55e;
}

.avatar-edit-btn, .avatar-save-btn {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.8rem;
}

.avatar-edit-btn {
    background: #fff;
    color: #22c55e;
}

.avatar-edit-btn:hover {
    background: #f0fdf4;
    transform: scale(1.1);
}

.avatar-save-btn {
    background: #22c55e;
    color: white;
}

/* Avatar Actions */
.avatar-actions {
    position: absolute;
    bottom: -40px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;
    white-space: nowrap;
    z-index: 10;
}

#avatarForm {
    position: relative;
}

.btn-cancel-avatar, .btn-confirm-avatar {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.3s ease;
}

.btn-cancel-avatar {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.btn-cancel-avatar:hover {
    background: #fecaca;
}

.btn-confirm-avatar {
    background: #fff;
    color: #16a34a;
}

.btn-confirm-avatar:hover {
    background: #f0fdf4;
}

/* Profile Info */
.profile-info h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0 0 0.4rem 0;
}

.profile-email {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.9rem;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.profile-email i {
    font-size: 0.8rem;
}

/* Join Date */
.profile-join-date {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.85rem;
    margin: 0 0 0.75rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.profile-join-date i {
    font-size: 0.75rem;
}

.profile-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-member {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.badge-verified {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

/* Profile Stats - Modern Design */
.profile-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.75rem;
    border: 2px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.stat-icon {
    width: 60px;
    height: 60px;
    min-width: 60px;
    min-height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-icon.stat-orders {
    background: #dcfce7;
    color: #16a34a;
}

.stat-icon.stat-completed {
    background: #dcfce7;
    color: #16a34a;
}

.stat-icon.stat-pending {
    background: #fef3c7;
    color: #d97706;
}

.stat-icon.stat-spent {
    background: #fce7f3;
    color: #db2777;
}

.stat-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Profile Content */
.profile-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Profile Card */
.profile-card {
    background: #fff !important;
    border-radius: 12px !important;
    padding: 1.5rem !important;
    border: 2px solid #22c55e !important;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.1) !important;
}

.card-header {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.6rem !important;
    margin-bottom: 1.25rem !important;
    padding-bottom: 0.75rem !important;
    border-bottom: 2px solid #dcfce7 !important;
    color: #111827 !important;
    font-size: 1rem !important;
    font-weight: 600 !important;
    text-align: center !important;
    width: 100% !important;
}

.card-header i {
    color: #22c55e !important;
    font-size: 0.95rem !important;
}

/* Form */
.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-row.three-cols {
    grid-template-columns: repeat(3, 1fr);
}

.profile-section .form-group label {
    display: block !important;
    color: #374151 !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.4rem !important;
}

.profile-section .form-group input,
.profile-section .profile-form input,
.profile-card input {
    width: 100% !important;
    padding: 0.7rem 0.9rem !important;
    background: #ffffff !important;
    border: 2px solid #e5e7eb !important;
    border-radius: 8px !important;
    color: #111827 !important;
    font-size: 0.9rem !important;
}

.profile-section .form-group input::placeholder,
.profile-card input::placeholder {
    color: #9ca3af !important;
}

.profile-section .form-group input:focus,
.profile-card input:focus {
    outline: none !important;
    border-color: #22c55e !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15) !important;
}

.profile-section .form-group input.disabled,
.profile-card input.disabled,
.profile-section .form-group input:disabled,
.profile-card input:disabled {
    background: #f3f4f6 !important;
    color: #6b7280 !important;
    cursor: not-allowed !important;
}

/* Select Dropdown */
.profile-section .form-select,
.profile-card .form-select,
.form-select {
    width: 100% !important;
    padding: 0.7rem 0.9rem !important;
    background: #ffffff !important;
    border: 2px solid #e5e7eb !important;
    border-radius: 8px !important;
    color: #111827 !important;
    font-size: 0.9rem !important;
    cursor: pointer !important;
    appearance: none !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    padding-right: 36px !important;
}

.profile-section .form-select:focus,
.profile-card .form-select:focus,
.form-select:focus {
    outline: none !important;
    border-color: #22c55e !important;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15) !important;
}

.profile-section .form-select:hover,
.profile-card .form-select:hover,
.form-select:hover {
    border-color: #22c55e !important;
}

/* Date Input */
.profile-section input[type="date"],
.profile-card input[type="date"] {
    cursor: pointer !important;
}

/* Password Input */
.input-password {
    position: relative;
}

.input-password input {
    padding-right: 40px !important;
}

.input-password button {
    position: absolute !important;
    right: 10px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    background: none !important;
    border: none !important;
    color: #9ca3af !important;
    cursor: pointer !important;
    padding: 0.2rem !important;
}

.input-password button:hover {
    color: #22c55e !important;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 0.5rem;
}

.btn-save, .btn-password {
    padding: 0.7rem 1.5rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.3s ease;
}

.btn-save {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    box-shadow: 0 3px 10px rgba(34, 197, 94, 0.25);
}

.btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
}

.btn-password {
    background: linear-gradient(135deg, #15803d 0%, #166534 100%);
    color: #ffffff;
    box-shadow: 0 3px 10px rgba(34, 197, 94, 0.25);
}

.btn-password:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
}

/* ========== CHAT MODERN STYLES ========== */

/* Chat Card Container */
.chat-card-modern {
    background: linear-gradient(180deg, #1a2744 0%, #0f1a2e 100%);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    flex-direction: column;
    max-height: 600px;
}

/* Chat Header */
.chat-header-modern {
    background: linear-gradient(135deg, #15803d 0%, #166534 100%);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.chat-header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.chat-logo {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 4px 15px rgba(212, 165, 116, 0.3);
}

.chat-logo i {
    font-size: 1.3rem;
    color: #1a1a1a;
}

.online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: #22c55e;
    border-radius: 50%;
    border: 2px solid #15803d;
    animation: pulse-online 2s infinite;
}

@keyframes pulse-online {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.chat-info h3 {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 0.2rem 0;
}

.chat-subtitle {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
}

.new-messages-badge {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 700;
    animation: badge-pulse 2s infinite;
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
}

@keyframes badge-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Chat Body */
.chat-body-modern {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: linear-gradient(180deg, rgba(15, 26, 46, 0.5) 0%, rgba(15, 26, 46, 0.8) 100%);
    min-height: 300px;
    max-height: 400px;
}

.chat-body-modern::-webkit-scrollbar {
    width: 6px;
}

.chat-body-modern::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}

.chat-body-modern::-webkit-scrollbar-thumb {
    background: rgba(212, 165, 116, 0.3);
    border-radius: 3px;
}

/* Date Separator */
.date-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 1rem 0;
    position: relative;
}

.date-separator::before,
.date-separator::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
}

.date-separator span {
    background: rgba(30, 41, 59, 0.8);
    color: rgba(255, 255, 255, 0.5);
    padding: 0.35rem 1rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
    margin: 0 1rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

/* Opacity classes for older messages */
.msg-older {
    opacity: 0.5;
}

.msg-older .message-bubble {
    transform: scale(0.97);
}

.msg-old {
    opacity: 0.75;
}

.msg-old .message-bubble {
    transform: scale(0.98);
}

/* Message Row */
.message-row {
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.message-row:hover {
    opacity: 1 !important;
}

.message-row:hover .message-bubble {
    transform: scale(1) !important;
}

.user-row {
    justify-content: flex-end;
}

.admin-row {
    justify-content: flex-start;
}

/* Message Bubble */
.message-bubble {
    max-width: 75%;
    padding: 0.9rem 1.1rem;
    border-radius: 18px;
    position: relative;
    transition: all 0.3s ease;
}

.user-msg {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border-bottom-right-radius: 6px;
    box-shadow: 0 3px 12px rgba(34, 197, 94, 0.3);
}

.admin-msg {
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.9);
    border-bottom-left-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-msg.new-reply-bubble {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.08) 100%);
    border-color: rgba(34, 197, 94, 0.4);
    animation: glow-new 2s infinite;
}

@keyframes glow-new {
    0%, 100% { box-shadow: 0 0 10px rgba(34, 197, 94, 0.2); }
    50% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.4); }
}

.new-reply-row {
    opacity: 1 !important;
}

/* New Indicator */
.new-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    padding: 0.2rem 0.6rem;
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pulse-dot {
    width: 6px;
    height: 6px;
    background: white;
    border-radius: 50%;
    animation: pulse-dot 1.5s infinite;
}

@keyframes pulse-dot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.5); opacity: 0.5; }
}

/* Message Text */
.msg-text {
    font-size: 0.9rem;
    line-height: 1.5;
    word-wrap: break-word;
}

/* Message Footer */
.msg-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.4rem;
    margin-top: 0.4rem;
}

.msg-time {
    font-size: 0.7rem;
    opacity: 0.6;
}

.msg-status-icon {
    font-size: 0.7rem;
    opacity: 0.7;
}

.msg-status-icon .replied {
    color: #4ade80;
    opacity: 1;
}

/* Admin Avatar */
.admin-avatar-small {
    width: 28px;
    height: 28px;
    min-width: 28px;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: #1a1a1a;
}

/* Chat Footer */
.chat-footer-modern {
    padding: 1rem 1.5rem;
    background: rgba(15, 26, 46, 0.8);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.btn-compose {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    width: 100%;
    padding: 0.9rem;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    color: #1a1a1a;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(212, 165, 116, 0.25);
}

.btn-compose:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 165, 116, 0.35);
}

.btn-compose i {
    font-size: 0.9rem;
}

/* Chat Empty State */
.chat-empty-modern {
    padding: 3rem 2rem;
    text-align: center;
}

.empty-illustration {
    margin-bottom: 1.5rem;
}

.empty-circle {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, rgba(212, 165, 116, 0.1) 0%, rgba(212, 165, 116, 0.05) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    border: 2px dashed rgba(212, 165, 116, 0.3);
}

.empty-circle i {
    font-size: 2rem;
    color: rgba(212, 165, 116, 0.5);
}

.empty-dots {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.empty-dots span {
    width: 8px;
    height: 8px;
    background: rgba(212, 165, 116, 0.3);
    border-radius: 50%;
    animation: bounce-dots 1.4s infinite ease-in-out;
}

.empty-dots span:nth-child(1) { animation-delay: 0s; }
.empty-dots span:nth-child(2) { animation-delay: 0.2s; }
.empty-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce-dots {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

.chat-empty-modern h4 {
    color: #fff;
    font-size: 1.2rem;
    margin: 0 0 0.5rem 0;
}

.chat-empty-modern p {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.9rem;
    margin: 0 0 1.5rem 0;
}

.btn-start-conversation {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.9rem 2rem;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    color: #1a1a1a;
    border-radius: 25px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(212, 165, 116, 0.25);
}

.btn-start-conversation:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 165, 116, 0.35);
}

/* Scroll End Marker */
.scroll-end-marker {
    height: 1px;
    margin-top: 0.5rem;
}

/* Fade in animation for messages */
.fade-in-msg {
    animation: fadeInMessage 0.4s ease forwards;
}

@keyframes fadeInMessage {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive for chat */
@media (max-width: 768px) {
    .chat-card-modern {
        max-height: 500px;
    }
    
    .chat-body-modern {
        max-height: 300px;
        padding: 1rem;
    }
    
    .message-bubble {
        max-width: 85%;
        padding: 0.75rem 1rem;
    }
    
    .chat-header-modern {
        padding: 0.9rem 1rem;
    }
    
    .chat-logo {
        width: 40px;
        height: 40px;
    }
    
    .chat-info h3 {
        font-size: 1rem;
    }
}

/* ========== END CHAT MODERN STYLES ========== */

/* Messages Section (Legacy) */
.unread-badge {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: auto;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.messages-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    max-height: 500px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.messages-list::-webkit-scrollbar {
    width: 6px;
}

.messages-list::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 3px;
}

.messages-list::-webkit-scrollbar-thumb {
    background: rgba(212, 165, 116, 0.3);
    border-radius: 3px;
}

.message-item {
    background: rgba(15, 23, 42, 0.4);
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
}

.message-item.has-new-reply {
    border-color: rgba(34, 197, 94, 0.4);
    background: rgba(34, 197, 94, 0.05);
}

.message-header-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    flex-wrap: wrap;
}

.message-id {
    font-weight: 700;
    color: #d4a574;
    font-size: 0.9rem;
}

.message-date {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.message-status {
    margin-left: auto;
    padding: 0.3rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.status-new {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
}

.status-read {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.status-replied {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
}

.my-message-bubble {
    background: rgba(34, 197, 94, 0.1);
    border-left: 3px solid #22c55e;
    padding: 1rem;
    border-radius: 0 10px 10px 0;
    margin-bottom: 1rem;
}

.my-message-bubble .bubble-label {
    font-size: 0.8rem;
    color: #60a5fa;
    margin-bottom: 0.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.my-message-bubble p {
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
    line-height: 1.6;
    font-size: 0.9rem;
}

.admin-reply-bubble {
    background: rgba(34, 197, 94, 0.1);
    border-left: 3px solid #22c55e;
    padding: 1rem;
    border-radius: 0 10px 10px 0;
    position: relative;
}

.admin-reply-bubble.new-reply {
    background: rgba(34, 197, 94, 0.15);
    animation: glow 2s infinite;
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 5px rgba(34, 197, 94, 0.3); }
    50% { box-shadow: 0 0 15px rgba(34, 197, 94, 0.5); }
}

.admin-reply-bubble .bubble-label {
    font-size: 0.8rem;
    color: #4ade80;
    margin-bottom: 0.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.new-tag {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: 10px;
    font-size: 0.65rem;
    margin-left: 0.5rem;
    animation: pulse 1.5s infinite;
}

.admin-reply-bubble p {
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
    line-height: 1.6;
    font-size: 0.9rem;
}

.reply-time {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.4);
    margin-top: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.btn-mark-read {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.75rem;
    padding: 0.4rem 0.9rem;
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-mark-read:hover {
    background: rgba(34, 197, 94, 0.3);
}

.waiting-reply-box {
    background: rgba(251, 191, 36, 0.1);
    border: 1px dashed rgba(251, 191, 36, 0.3);
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    color: #fbbf24;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.waiting-reply-box i {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.messages-footer {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    text-align: center;
}

.btn-new-message {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    color: #1a1a1a;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-new-message:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 165, 116, 0.3);
}

.no-messages {
    text-align: center;
    padding: 2rem;
    color: rgba(255, 255, 255, 0.5);
}

.no-messages i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-messages p {
    margin-bottom: 1.25rem;
}

.btn-contact {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #d4a574 0%, #c89456 100%);
    color: #1a1a1a;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-contact:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 165, 116, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .profile-section {
        padding: 1rem;
    }
    
    .profile-hero {
        padding: 1.5rem;
    }
    
    .profile-hero-content {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-info h1 {
        font-size: 1.5rem;
    }
    
    .profile-email,
    .profile-join-date {
        justify-content: center;
    }
    
    .profile-badges {
        justify-content: center;
    }
    
    .profile-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-number {
        font-size: 1.1rem;
    }
    
    .form-row, .form-row.three-cols {
        grid-template-columns: 1fr;
    }
    
    .profile-card {
        padding: 1.25rem;
    }
    
    .form-actions {
        justify-content: stretch;
    }
    
    .btn-save, .btn-password {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .profile-stats {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 1rem;
    }
}
</style>





<script>
// Auto scroll to newest messages
document.addEventListener('DOMContentLoaded', function() {
    const chatBody = document.getElementById('chatMessages');
    if (chatBody) {
        // Scroll to bottom to show newest messages
        chatBody.scrollTop = chatBody.scrollHeight;
        
        // Add fade-in animation for messages
        const messages = chatBody.querySelectorAll('.message-row');
        messages.forEach((msg, index) => {
            msg.style.animationDelay = (index * 0.05) + 's';
            msg.classList.add('fade-in-msg');
        });
    }
});

// Store original avatar HTML
let originalAvatarHTML = document.getElementById('avatarPreview').innerHTML;

// Preview avatar before upload
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Kích thước file không được vượt quá 5MB');
            return;
        }
        
        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('Chỉ chấp nhận file ảnh (JPG, PNG, GIF)');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(file);
        
        // Show action buttons
        document.getElementById('editAvatarBtn').style.display = 'none';
        document.getElementById('avatarActions').style.display = 'flex';
    }
});

// Cancel avatar change
function cancelAvatarChange() {
    document.getElementById('avatarPreview').innerHTML = originalAvatarHTML;
    document.getElementById('avatarForm').reset();
    document.getElementById('editAvatarBtn').style.display = 'flex';
    document.getElementById('avatarActions').style.display = 'none';
}

// Toggle password visibility
function togglePass(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.parentElement.querySelector('button i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
