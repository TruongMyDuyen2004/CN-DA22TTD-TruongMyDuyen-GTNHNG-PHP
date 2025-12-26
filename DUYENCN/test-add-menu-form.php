<?php
session_start();
require_once 'config/database.php';

// Tạo session admin giả để test
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1; // Giả lập đăng nhập admin
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
    <title>Test Form Thêm Món</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-group input {
            width: auto;
        }
        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
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
        .info {
            background: #dbeafe;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #3b82f6;
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
    <div class="container">
        <h1>🍽️ Test Form Thêm Món Ăn</h1>
        
        <div class="info">
            <strong>ℹ️ Thông tin:</strong><br>
            - Session Admin ID: <?php echo $_SESSION['admin_id']; ?><br>
            - Số danh mục: <?php echo count($categories); ?><br>
            - Database: Đã kết nối
        </div>

        <?php if (count($categories) == 0): ?>
        <div class="result error" style="display: block;">
            <strong>⚠️ Cảnh báo:</strong> Chưa có danh mục nào!<br>
            Bạn cần tạo danh mục trước khi thêm món.
        </div>
        <?php endif; ?>

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
                <label class="checkbox-group">
                    <input type="checkbox" name="is_available" value="1" checked>
                    <span>✅ Món ăn đang có sẵn</span>
                </label>
            </div>

            <button type="submit">➕ Thêm món ăn</button>
        </form>

        <div id="result" class="result"></div>
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
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');
            
            // Hiển thị loading
            resultDiv.className = 'result';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Đang xử lý...';
            
            try {
                const response = await fetch('admin/api/add-menu-item.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                console.log('Response:', data);
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = '✅ ' + data.message + '<br>ID món mới: ' + data.id;
                    
                    // Reset form
                    this.reset();
                    document.getElementById('preview').innerHTML = '<span style="color: #9ca3af;">Chưa chọn ảnh</span>';
                    
                    // Reload sau 2 giây
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = '❌ Lỗi: ' + data.message;
                    
                    if (data.debug) {
                        resultDiv.innerHTML += '<br><br><strong>Debug info:</strong><br>' + JSON.stringify(data.debug, null, 2);
                    }
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ Có lỗi xảy ra: ' + error.message;
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>
