/* ===================================
   REVIEWS MODAL FOR MENU PAGE
   =================================== */

// Mở modal hiển thị đánh giá
function openReviewsModal(menuItemId, menuItemName) {
    // Tạo modal nếu chưa có
    let modal = document.getElementById('reviewsModal');
    if (!modal) {
        modal = createReviewsModal();
        document.body.appendChild(modal);
    }
    
    // Cập nhật tiêu đề
    document.getElementById('reviewsModalTitle').textContent = menuItemName;
    
    // Hiển thị modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Load reviews
    loadModalReviews(menuItemId);
}

// Đóng modal
function closeReviewsModal() {
    const modal = document.getElementById('reviewsModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Tạo modal HTML
function createReviewsModal() {
    const modal = document.createElement('div');
    modal.id = 'reviewsModal';
    modal.className = 'reviews-modal';
    modal.innerHTML = `
        <div class="reviews-modal-overlay" onclick="closeReviewsModal()"></div>
        <div class="reviews-modal-container">
            <div class="reviews-modal-header">
                <h3 id="reviewsModalTitle">Đánh giá món ăn</h3>
                <button class="reviews-modal-close" onclick="closeReviewsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="reviews-modal-body" id="reviewsModalBody">
                <div class="reviews-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Đang tải đánh giá...</p>
                </div>
            </div>
        </div>
    `;
    return modal;
}

// Load reviews vào modal
async function loadModalReviews(menuItemId) {
    const container = document.getElementById('reviewsModalBody');
    container.innerHTML = `
        <div class="reviews-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Đang tải đánh giá...</p>
        </div>
    `;
    
    try {
        const response = await fetch(`api/get-reviews.php?menu_item_id=${menuItemId}&page=1&sort=newest`);
        const data = await response.json();
        
        if (data.success) {
            renderModalReviews(data);
        } else {
            container.innerHTML = `
                <div class="reviews-empty">
                    <i class="fas fa-comment-slash"></i>
                    <p>Không thể tải đánh giá</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
        container.innerHTML = `
            <div class="reviews-empty">
                <i class="fas fa-exclamation-circle"></i>
                <p>Có lỗi xảy ra khi tải đánh giá</p>
            </div>
        `;
    }
}

// Render reviews trong modal
function renderModalReviews(data) {
    const container = document.getElementById('reviewsModalBody');
    const { stats, reviews } = data;
    
    let html = '';
    
    // Rating Summary
    if (stats.total_reviews > 0) {
        const avgRating = parseFloat(stats.avg_rating).toFixed(1);
        const star5Percent = (stats.star_5 / stats.total_reviews * 100).toFixed(0);
        const star4Percent = (stats.star_4 / stats.total_reviews * 100).toFixed(0);
        const star3Percent = (stats.star_3 / stats.total_reviews * 100).toFixed(0);
        const star2Percent = (stats.star_2 / stats.total_reviews * 100).toFixed(0);
        const star1Percent = (stats.star_1 / stats.total_reviews * 100).toFixed(0);
        
        html += `
            <div class="modal-rating-summary">
                <div class="modal-rating-overview">
                    <div class="modal-rating-score">${avgRating}</div>
                    <div class="modal-rating-stars">
                        ${renderStars(avgRating)}
                    </div>
                    <div class="modal-rating-count">${stats.total_reviews} đánh giá</div>
                </div>
                <div class="modal-rating-breakdown">
                    ${renderRatingBar(5, stats.star_5, star5Percent)}
                    ${renderRatingBar(4, stats.star_4, star4Percent)}
                    ${renderRatingBar(3, stats.star_3, star3Percent)}
                    ${renderRatingBar(2, stats.star_2, star2Percent)}
                    ${renderRatingBar(1, stats.star_1, star1Percent)}
                </div>
            </div>
        `;
    }
    
    // Reviews List
    if (reviews.length > 0) {
        html += '<div class="modal-reviews-list">';
        reviews.forEach(review => {
            html += renderReviewItem(review);
        });
        html += '</div>';
    } else {
        html += `
            <div class="reviews-empty">
                <i class="fas fa-comment-slash"></i>
                <h4>Chưa có đánh giá</h4>
                <p>Hãy là người đầu tiên đánh giá món ăn này!</p>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

// Render stars
function renderStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="fas fa-star"></i>';
        } else if (i - 0.5 <= rating) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        } else {
            stars += '<i class="far fa-star"></i>';
        }
    }
    return stars;
}

// Render rating bar
function renderRatingBar(stars, count, percent) {
    return `
        <div class="modal-rating-bar">
            <div class="modal-rating-bar-label">
                <i class="fas fa-star"></i>
                <span>${stars}</span>
            </div>
            <div class="modal-rating-bar-track">
                <div class="modal-rating-bar-fill" style="width: ${percent}%"></div>
            </div>
            <div class="modal-rating-bar-count">${count}</div>
        </div>
    `;
}

// Render review item
function renderReviewItem(review) {
    const initials = review.full_name ? review.full_name.charAt(0).toUpperCase() : 'U';
    const date = new Date(review.created_at).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    const likedClass = review.is_liked_by_user ? 'liked' : '';
    const likeIcon = review.is_liked_by_user ? 'fas' : 'far';
    
    // Avatar HTML - hiển thị ảnh nếu có, không thì hiển thị chữ cái đầu
    let avatarHtml = '';
    if (review.avatar) {
        avatarHtml = `<img src="${escapeHtml(review.avatar)}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">`;
    } else {
        avatarHtml = initials;
    }
    
    return `
        <div class="modal-review-item">
            <div class="modal-review-header">
                <div class="modal-review-author">
                    <div class="modal-review-avatar">${avatarHtml}</div>
                    <div class="modal-review-author-info">
                        <h4>${escapeHtml(review.full_name || 'Khách hàng')}</h4>
                        <div class="modal-review-date">${date}</div>
                    </div>
                </div>
                <div class="modal-review-rating">
                    ${renderStars(review.rating)}
                </div>
            </div>
            <div class="modal-review-content">
                <p>${escapeHtml(review.comment)}</p>
            </div>
            <div class="modal-review-footer">
                <button class="modal-review-like-btn ${likedClass}" onclick="toggleModalLike(${review.id}, this)">
                    <i class="${likeIcon} fa-heart"></i>
                    <span class="like-count">${review.likes_count || 0}</span>
                </button>
            </div>
        </div>
    `;
}

// Toggle like trong modal
async function toggleModalLike(reviewId, button) {
    // Kiểm tra đăng nhập
    if (!document.querySelector('.user-dropdown')) {
        alert('Vui lòng đăng nhập để thích đánh giá');
        return;
    }
    
    button.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('review_id', reviewId);
        
        const response = await fetch('api/review-like.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const likeCount = button.querySelector('.like-count');
            const icon = button.querySelector('i');
            
            likeCount.textContent = data.likes_count;
            
            if (data.action === 'liked') {
                button.classList.add('liked');
                icon.className = 'fas fa-heart';
            } else {
                button.classList.remove('liked');
                icon.className = 'far fa-heart';
            }
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    } catch (error) {
        console.error('Error toggling like:', error);
        alert('Có lỗi xảy ra khi thích đánh giá');
    } finally {
        button.disabled = false;
    }
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Đóng modal khi nhấn ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeReviewsModal();
    }
});
