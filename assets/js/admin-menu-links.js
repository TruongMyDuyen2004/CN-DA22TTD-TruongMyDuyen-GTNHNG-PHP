/**
 * Thêm nút "Sửa" cho admin ở mỗi món ăn
 */

document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra xem có phải admin không (dựa vào việc có nút "Quản lý thực đơn")
    const hasAdminLink = document.querySelector('.btn-admin-menu');
    
    if (hasAdminLink) {
        // Tìm tất cả các menu-item-actions
        const menuActions = document.querySelectorAll('.menu-item-actions');
        
        menuActions.forEach(actions => {
            // Lấy ID món ăn từ nút "Xem"
            const viewBtn = actions.querySelector('button[onclick*="menu-item-detail"]');
            if (viewBtn) {
                const onclick = viewBtn.getAttribute('onclick');
                const match = onclick.match(/id=(\d+)/);
                
                if (match) {
                    const itemId = match[1];
                    
                    // Kiểm tra xem đã có nút admin chưa
                    if (!actions.querySelector('.btn-admin')) {
                        // Tạo nút admin
                        const adminBtn = document.createElement('a');
                        adminBtn.href = `admin/menu.php?edit=${itemId}`;
                        adminBtn.target = '_blank';
                        adminBtn.className = 'btn btn-small btn-admin';
                        adminBtn.title = 'Chỉnh sửa món ăn';
                        adminBtn.innerHTML = '<i class="fas fa-edit"></i> Sửa';
                        
                        // Thêm vào sau nút "Đánh giá" hoặc "Xem"
                        const reviewBtn = actions.querySelector('.btn-warning');
                        if (reviewBtn) {
                            reviewBtn.after(adminBtn);
                        } else {
                            viewBtn.after(adminBtn);
                        }
                    }
                }
            }
        });
    }
});
