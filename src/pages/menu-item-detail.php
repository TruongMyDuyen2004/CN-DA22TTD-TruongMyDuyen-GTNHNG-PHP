<?php
$db = new Database();
$conn = $db->connect();

$item_id = $_GET['id'] ?? 0;

// Lấy thông tin món ăn
$stmt = $conn->prepare("
    SELECT m.*, c.name as category_name 
    FROM menu_items m 
    JOIN categories c ON m.category_id = c.id 
    WHERE m.id = ?
");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo '<div class="container"><p>Không tìm thấy món ăn</p></div>';
    return;
}
?>

<link rel="stylesheet" href="assets/css/reviews.css">

<section class="menu-item-detail-section" style="padding: 4rem 0; background: #f8fafc;">
    <div class="container">
        <!-- Item Info -->
        <div style="background: white; border-radius: 24px; padding: 3rem; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
                <div>
                    <?php if($item['image']): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                    <?php else: ?>
                        <div style="width: 100%; aspect-ratio: 1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem;">
                            <i class="fas fa-utensils"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <span style="display: inline-block; padding: 0.5rem 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px; font-size: 0.85rem; font-weight: 700; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($item['category_name']); ?>
                    </span>
                    
                    <h1 style="font-size: 2.5rem; font-weight: 800; color: #1e293b; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($item['name']); ?>
                    </h1>
                    
                    <?php 
                    $menu_item_id = $item['id'];
                    include 'includes/menu-item-reviews.php'; 
                    ?>
                    
                    <p style="color: #64748b; font-size: 1.1rem; line-height: 1.8; margin: 1.5rem 0;">
                        <?php echo htmlspecialchars($item['description']); ?>
                    </p>
                    
                    <div style="display: flex; align-items: center; gap: 2rem; margin: 2rem 0;">
                        <div style="font-size: 2.5rem; font-weight: 800; color: #667eea;">
                            <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                        </div>
                        
                        <?php if($item['is_available']): ?>
                            <span style="padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); color: #155724; border-radius: 12px; font-weight: 700;">
                                <i class="fas fa-check-circle"></i> Còn món
                            </span>
                        <?php else: ?>
                            <span style="padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; border-radius: 12px; font-weight: 700;">
                                <i class="fas fa-times-circle"></i> Hết món
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($item['is_available'] && isset($_SESSION['customer_id'])): ?>
                    <div style="display: flex; gap: 1rem;">
                        <button onclick="addToCart(<?php echo $item['id']; ?>)" class="btn btn-primary" style="flex: 1; padding: 1.2rem; font-size: 1.1rem; border-radius: 12px;">
                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                        </button>
                        <button onclick="showReviewModal()" class="btn btn-secondary" style="padding: 1.2rem 1.5rem; border-radius: 12px;">
                            <i class="fas fa-star"></i> Đánh giá
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        

        
        <!-- Back Button -->
        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php?page=menu" class="btn btn-secondary" style="padding: 1rem 2rem; border-radius: 12px;">
                <i class="fas fa-arrow-left"></i> Quay lại thực đơn
            </a>
        </div>
    </div>
</section>

<!-- Review Modal -->
<div id="reviewModal" class="review-modal">
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h3>Đánh giá món ăn</h3>
            <p><?php echo htmlspecialchars($item['name']); ?></p>
        </div>
        
        <form id="reviewForm" onsubmit="submitReview(event)">
            <input type="hidden" name="menu_item_id" value="<?php echo $item['id']; ?>">
            
            <div class="form-group">
                <label>Đánh giá của bạn *</label>
                <div class="rating-input" id="ratingInput">
                    <i class="far fa-star" data-rating="1"></i>
                    <i class="far fa-star" data-rating="2"></i>
                    <i class="far fa-star" data-rating="3"></i>
                    <i class="far fa-star" data-rating="4"></i>
                    <i class="far fa-star" data-rating="5"></i>
                </div>
                <input type="hidden" name="rating" id="ratingValue" required>
            </div>
            
            <div class="form-group">
                <label>Nhận xét của bạn *</label>
                <textarea name="comment" required placeholder="Chia sẻ trải nghiệm của bạn về món ăn này..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Gửi đánh giá
                </button>
                <button type="button" onclick="closeReviewModal()" class="btn-cancel">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Custom Notification Styles */
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

.custom-notification.warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.custom-notification.error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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

@keyframes bounceIn {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

/* Backdrop overlay */
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

@media (max-width: 768px) {
    .custom-notification {
        min-width: 280px;
        max-width: 90%;
        padding: 1.5rem 2rem;
    }
    
    .notification-content i {
        font-size: 2.5rem;
    }
    
    .notification-content {
        font-size: 1rem;
    }
}
</style>

<script src="assets/js/reviews.js"></script>
<script>
// Rating input
const ratingStars = document.querySelectorAll('#ratingInput i');
let selectedRating = 0;

ratingStars.forEach(star => {
    star.addEventListener('click', () => {
        selectedRating = parseInt(star.dataset.rating);
        document.getElementById('ratingValue').value = selectedRating;
        updateRatingStars();
    });
    
    star.addEventListener('mouseenter', () => {
        const rating = parseInt(star.dataset.rating);
        highlightStars(rating);
    });
});

document.getElementById('ratingInput').addEventListener('mouseleave', () => {
    updateRatingStars();
});

function highlightStars(rating) {
    ratingStars.forEach((star, index) => {
        if (index < rating) {
            star.className = 'fas fa-star active';
        } else {
            star.className = 'far fa-star';
        }
    });
}

function updateRatingStars() {
    highlightStars(selectedRating);
}

// Modal functions
function showReviewModal() {
    <?php if(!isset($_SESSION['customer_id'])): ?>
        alert('Vui lòng đăng nhập để đánh giá');
        window.location.href = 'auth/login.php';
        return;
    <?php endif; ?>
    
    document.getElementById('reviewModal').classList.add('active');
}

// Auto open review modal if action=review in URL
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'review') {
        showReviewModal();
    }
});

function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('active');
    document.getElementById('reviewForm').reset();
    selectedRating = 0;
    updateRatingStars();
}

// Submit review
async function submitReview(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    if (!formData.get('rating')) {
        showNotification('Vui lòng chọn số sao đánh giá', 'warning');
        return;
    }
    
    try {
        const response = await fetch('api/submit-review.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✓ Cảm ơn bạn đã đánh giá! Đánh giá của bạn đã được ghi nhận.', 'success');
            closeReviewModal();
            // Reset form
            document.getElementById('reviewForm').reset();
            selectedRating = 0;
            updateRatingStars();
        } else {
            showNotification(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        console.error('Error submitting review:', error);
        showNotification('Có lỗi xảy ra khi gửi đánh giá', 'error');
    }
}

// Show notification with animation
function showNotification(message, type = 'success') {
    // Remove existing notification and backdrop
    const existing = document.querySelector('.custom-notification');
    const existingBackdrop = document.querySelector('.notification-backdrop');
    if (existing) existing.remove();
    if (existingBackdrop) existingBackdrop.remove();
    
    // Create backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'notification-backdrop';
    document.body.appendChild(backdrop);
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        backdrop.classList.add('show');
        notification.classList.add('show');
    }, 10);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        backdrop.classList.remove('show');
        notification.classList.remove('show');
        setTimeout(() => {
            backdrop.remove();
            notification.remove();
        }, 400);
    }, 3000);
    
    // Click backdrop to close
    backdrop.addEventListener('click', () => {
        backdrop.classList.remove('show');
        notification.classList.remove('show');
        setTimeout(() => {
            backdrop.remove();
            notification.remove();
        }, 400);
    });
}

// Add to cart function
async function addToCart(itemId) {
    try {
        const formData = new FormData();
        formData.append('menu_item_id', itemId);
        formData.append('quantity', 1);
        
        const response = await fetch('api/cart.php?action=add', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Đã thêm vào giỏ hàng!');
            // Update cart count if exists
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge && data.cart_count) {
                cartBadge.textContent = data.cart_count;
            }
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        alert('Có lỗi xảy ra khi thêm vào giỏ hàng');
    }
}
</script>
