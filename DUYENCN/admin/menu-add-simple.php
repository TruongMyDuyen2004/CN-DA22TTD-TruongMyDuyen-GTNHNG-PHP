<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Lấy danh mục
$stmt = $conn->query("SELECT * FROM categories ORDER BY display_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm món ăn - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #f97316;
            color: white;
        }
        .btn-primary:hover {
            background: #ea580c;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .result {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            display: none;
        }
        .result.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        .result.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        .preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1><i class="fas fa-plus-circle"></i> Thêm món ăn mới</h1>
                <a href="menu-manage.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
            
            <div class="form-card">
                <form id="addForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>📸 Hình ảnh món ăn</label>
                        <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(this)">
                        <div class="preview" id="preview">
                            <span style="color: #9ca3af;">Chưa chọn ảnh</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>🍜 Tên món (Tiếng Việt) *</label>
                        <input type="text" name="name" required placeholder="VD: Phở bò, Bánh xèo...">
                    </div>
                    
                    <div class="form-group">
                        <label>🍜 Tên món (Tiếng Anh)</label>
                        <input type="text" name="name_en" placeholder="VD: Beef Pho, Vietnamese Pancake...">
                    </div>
                    
                    <div class="form-group">
                        <label>💰 Giá (VNĐ) *</label>
                        <input type="number" name="price" required min="0" step="1000" placeholder="VD: 45000">
                    </div>
                    
                    <div class="form-group">
                        <label>📂 Danh mục *</label>
                        <select name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📝 Mô tả (Tiếng Việt)</label>
                        <textarea name="description" rows="3" placeholder="Mô tả món ăn..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>📝 Mô tả (Tiếng Anh)</label>
                        <textarea name="description_en" rows="3" placeholder="Dish description..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="is_available" value="1" checked style="width: auto;">
                            <span>✅ Món ăn đang có sẵn</span>
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Thêm món
                        </button>
                        <a href="menu-manage.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </div>
                </form>
                
                <div id="result" class="result"></div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.getElementById('addForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            console.log('Form submitted');
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            
            resultDiv.style.display = 'block';
            resultDiv.className = 'result';
            resultDiv.innerHTML = '⏳ Đang xử lý...';
            
            try {
                console.log('Sending request...');
                const response = await fetch('api/add-menu-item.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Response is not JSON:', text);
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '❌ Lỗi: Server trả về dữ liệu không hợp lệ<br><br>' + text.substring(0, 200);
                    return;
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = '✅ ' + data.message + '<br>ID món mới: ' + data.id + 
                                         '<br><br>Đang chuyển hướng...';
                    
                    setTimeout(() => {
                        window.location.href = 'menu-manage.php';
                    }, 2000);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '❌ Lỗi: ' + data.message;
                    
                    if (data.debug) {
                        resultDiv.innerHTML += '<br><br><strong>Debug:</strong><br>' + JSON.stringify(data.debug, null, 2);
                    }
                }
                
            } catch (error) {
                console.error('Error:', error);
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ Có lỗi xảy ra: ' + error.message;
            }
        });
    </script>
</body>
</html>
