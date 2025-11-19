/* ===================================
   REVIEWS & RATINGS JAVASCRIPT
   =================================== */

class ReviewSystem {
    constructor(menuItemId) {
        this.menuItemId = menuItemId;
        this.currentPage = 1;
        this.currentSort = 'newest';
        this.isLoading = false;
        this.init();
    }

    init() {
        this.loadReviews();
        this.attachEventListeners();
    }

    attachEventListeners() {
        // Like button clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.review-like-btn')) {
                const btn = e.target.closest('.review-like-btn');
                const reviewId = btn.dataset.reviewId;
                this.toggleLike(reviewId, btn);
            }
            
            // Comment button clicks
            if (e.target.closest('.review-comment-btn')) {
                const btn = e.target.closest('.review-comment-btn');
                const reviewId = btn.dataset.reviewId;
                this.toggleComments(reviewId);
            }
            
            // Load more button
            if (e.target.closest('.load-more-btn')) {
                this.loadMoreReviews();
            }
        });
        
        // Sort change
        document.addEventListener('change', (e) => {
            if (e.target.id === 'reviewSort') {
                this.changeSort(e.target.value);
            }
        });
    }

    async loadReviews(append = false) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        const container = document.getElementById('reviewsContainer');
        if (!container) return;

        if (!append) {
            container.innerHTML = '<div class="reviews-loading"><i class="fas fa-spinner"></i></div>';
        }

        try {
            const response = await fetch(`api/get-reviews.php?menu_item_id=${this.menuItemId}&page=${this.currentPage}&sort=${this.currentSort}`);
            const data = await response.json();

            if (data.success) {
                this.renderReviews(data, append);
            } else {
                container.innerHTML = '<div class="reviews-empty"><p>Không thể tải đánh giá</p></div>';
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
            container.innerHTML = '<div class="reviews-empty"><p>Có lỗi xảy ra khi tải đánh giá</p></div>';
        } finally {
            this.isLoading = false;
        }
    }
    
    async loadMoreReviews() {
        this.currentPage++;
        await this.loadReviews(true);
    }
    
    async changeSort(sort) {
        this.currentSort = sort;
        this.currentPage = 1;
        await this.loadReviews(false);
    }

    renderReviews(data, append = false) {
        const container = document.getElementById('reviewsContainer');
        const { stats, reviews, has_more } = data;

        let html = '';

        if (!append) {
            // Rating Summary
            if (stats.total_reviews > 0) {
            const avgRating = parseFloat(stats.avg_rating).toFixed(1);
            const star5Percent = (stats.star_5 / stats.total_reviews * 100).toFixed(0);
            const star4Percent = (stats.star_4 / stats.total_reviews * 100).toFixed(0);
            const star3Percent = (stats.star_3 / stats.total_reviews * 100).toFixed(0);
            const star2Percent = (stats.star_2 / stats.total_reviews * 100).toFixed(0);
            const star1Percent = (stats.star_1 / stats.total_reviews * 100).toFixed(0);

            html += `
                <div class="rating-summary">
                    <div class="rating-overview">
                        <div class="rating-score">${avgRating}</div>
                        <div class="rating-stars">
                            ${this.renderStars(avgRating)}
                        </div>
                        <div class="rating-count">${stats.total_reviews} đánh giá</div>
                    </div>
                    <div class="rating-breakdown">
                        ${this.renderRatingBar(5, stats.star_5, star5Percent)}
                        ${this.renderRatingBar(4, stats.star_4, star4Percent)}
                        ${this.renderRatingBar(3, stats.star_3, star3Percent)}
                        ${this.renderRatingBar(2, stats.star_2, star2Percent)}
                        ${this.renderRatingBar(1, stats.star_1, star1Percent)}
                    </div>
                </div>
            `;
            }
            
            // Sort dropdown
            if (stats.total_reviews > 0) {
                html += `
                    <div class="reviews-sort">
                        <span class="reviews-sort-label">
                            <i class="fas fa-sort"></i>
                            Sắp xếp theo:
                        </span>
                        <select id="reviewSort">
                            <option value="newest" ${this.currentSort === 'newest' ? 'selected' : ''}>Mới nhất</option>
                            <option value="oldest" ${this.currentSort === 'oldest' ? 'selected' : ''}>Cũ nhất</option>
                            <option value="highest" ${this.currentSort === 'highest' ? 'selected' : ''}>Đánh giá cao nhất</option>
                            <option value="lowest" ${this.currentSort === 'lowest' ? 'selected' : ''}>Đánh giá thấp nhất</option>
                        </select>
                    </div>
                `;
            }
            
            // Reviews List
            if (reviews.length > 0) {
                html += '<div class="reviews-list" id="reviewsList">';
                reviews.forEach(review => {
                    html += this.renderReviewItem(review);
                });
                html += '</div>';
            } else {
                html += `
                    <div class="reviews-empty">
                        <i class="fas fa-comment-slash"></i>
                        <h3>Chưa có đánh giá</h3>
                        <p>Hãy là người đầu tiên đánh giá món ăn này!</p>
                    </div>
                `;
            }
            
            // Load more button
            if (has_more) {
                html += `
                    <button class="load-more-btn">
                        <i class="fas fa-chevron-down"></i>
                        Xem thêm đánh giá
                    </button>
                `;
            }

            container.innerHTML = html;
        } else {
            // Append mode - chỉ thêm reviews mới
            const reviewsList = document.getElementById('reviewsList');
            if (reviewsList) {
                reviews.forEach(review => {
                    const reviewElement = document.createElement('div');
                    reviewElement.innerHTML = this.renderReviewItem(review);
                    reviewsList.appendChild(reviewElement.firstElementChild);
                });
            }
            
            // Remove old load more button
            const oldLoadMore = container.querySelector('.load-more-btn');
            if (oldLoadMore) {
                oldLoadMore.remove();
            }
            
            // Add new load more button if needed
            if (has_more) {
                const loadMoreBtn = document.createElement('button');
                loadMoreBtn.className = 'load-more-btn';
                loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Xem thêm đánh giá';
                container.appendChild(loadMoreBtn);
            }
        }
    }

    renderStars(rating) {
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

    renderRatingBar(stars, count, percent) {
        return `
            <div class="rating-bar">
                <div class="rating-bar-label">
                    <i class="fas fa-star"></i>
                    <span>${stars}</span>
                </div>
                <div class="rating-bar-track">
                    <div class="rating-bar-fill" style="width: ${percent}%"></div>
                </div>
                <div class="rating-bar-count">${count}</div>
            </div>
        `;
    }

    renderReviewItem(review) {
        const initials = review.full_name ? review.full_name.charAt(0).toUpperCase() : 'U';
        const date = new Date(review.created_at).toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const likedClass = review.is_liked_by_user ? 'liked' : '';
        const likeIcon = review.is_liked_by_user ? 'fas' : 'far';
        const likeText = 'Thích'; // hoặc 'Like' tùy ngôn ngữ
        
        // Avatar rendering - show image if exists, otherwise show initials
        let avatarHtml = '';
        if (review.avatar) {
            avatarHtml = `<img src="${this.escapeHtml(review.avatar)}" alt="${this.escapeHtml(review.full_name)}" class="review-avatar-img">`;
        } else {
            avatarHtml = `<span class="review-avatar-text">${initials}</span>`;
        }

        return `
            <div class="review-item" data-review-id="${review.id}">
                <div class="review-header">
                    <div class="review-author">
                        <div class="review-avatar">${avatarHtml}</div>
                        <div class="review-author-info">
                            <h4>${this.escapeHtml(review.full_name || 'Khách hàng')}</h4>
                            <div class="review-date">${date}</div>
                        </div>
                    </div>
                    <div class="review-rating">
                        ${this.renderStars(review.rating)}
                    </div>
                </div>
                <div class="review-content">
                    <p class="review-comment">${this.escapeHtml(review.comment)}</p>
                </div>
                <div class="review-footer">
                    <button class="review-like-btn ${likedClass}" data-review-id="${review.id}">
                        <i class="${likeIcon} fa-thumbs-up"></i>
                        <span class="like-count">${review.likes_count || 0}</span>
                        <span class="like-text">${likeText}</span>
                    </button>
                    <button class="review-comment-btn" data-review-id="${review.id}">
                        <i class="far fa-comment"></i>
                        <span class="comment-count">${review.comments_count || 0}</span>
                    </button>
                </div>
                
                <!-- Comments Section -->
                <div class="review-comments-section" id="comments-${review.id}" style="display: none;">
                    <div class="comments-list" id="comments-list-${review.id}">
                        <div class="comments-loading">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải bình luận...
                        </div>
                    </div>
                    ${this.isLoggedIn() ? `
                    <div class="comment-form">
                        <textarea class="comment-input" id="comment-input-${review.id}" placeholder="Viết bình luận..." maxlength="500"></textarea>
                        <button class="comment-submit-btn" onclick="reviewSystem.submitComment(${review.id})">
                            <i class="fas fa-paper-plane"></i> Gửi
                        </button>
                    </div>
                    ` : `
                    <div class="comment-login-prompt">
                        <i class="fas fa-info-circle"></i>
                        <a href="auth/login.php">Đăng nhập</a> để bình luận
                    </div>
                    `}
                </div>
            </div>
        `;
    }

    async toggleLike(reviewId, button) {
        // Kiểm tra đăng nhập
        if (!this.isLoggedIn()) {
            alert('Vui lòng đăng nhập để thích đánh giá');
            return;
        }

        // Disable button
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
                // Update UI
                const likeCount = button.querySelector('.like-count');
                const icon = button.querySelector('i');

                likeCount.textContent = data.likes_count;

                if (data.action === 'liked') {
                    button.classList.add('liked');
                    icon.className = 'fas fa-thumbs-up';
                } else {
                    button.classList.remove('liked');
                    icon.className = 'far fa-thumbs-up';
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

    isLoggedIn() {
        // Check if user is logged in (you can customize this)
        return document.querySelector('.user-dropdown') !== null;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async toggleComments(reviewId) {
        const commentsSection = document.getElementById(`comments-${reviewId}`);
        
        if (commentsSection.style.display === 'none') {
            commentsSection.style.display = 'block';
            await this.loadComments(reviewId);
        } else {
            commentsSection.style.display = 'none';
        }
    }

    async loadComments(reviewId) {
        const commentsList = document.getElementById(`comments-list-${reviewId}`);
        
        try {
            const response = await fetch(`api/review-comment.php?review_id=${reviewId}`);
            const data = await response.json();
            
            if (data.success && data.comments) {
                if (data.comments.length === 0) {
                    commentsList.innerHTML = `
                        <div class="comments-empty">
                            <i class="far fa-comment"></i>
                            <p>Chưa có bình luận nào</p>
                        </div>
                    `;
                } else {
                    commentsList.innerHTML = data.comments.map(comment => this.renderComment(comment)).join('');
                }
            } else {
                commentsList.innerHTML = '<div class="comments-error">Không thể tải bình luận</div>';
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            commentsList.innerHTML = '<div class="comments-error">Có lỗi xảy ra</div>';
        }
    }

    renderComment(comment) {
        const initials = comment.full_name ? comment.full_name.charAt(0).toUpperCase() : 'U';
        const date = new Date(comment.created_at).toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        return `
            <div class="comment-item" data-comment-id="${comment.id}">
                <div class="comment-avatar">${initials}</div>
                <div class="comment-content">
                    <div class="comment-header">
                        <strong>${this.escapeHtml(comment.full_name)}</strong>
                        <span class="comment-date">${date}</span>
                    </div>
                    <p class="comment-text">${this.escapeHtml(comment.comment)}</p>
                </div>
            </div>
        `;
    }

    async submitComment(reviewId) {
        if (!this.isLoggedIn()) {
            alert('Vui lòng đăng nhập để bình luận');
            return;
        }

        const input = document.getElementById(`comment-input-${reviewId}`);
        const comment = input.value.trim();

        if (!comment) {
            alert('Vui lòng nhập nội dung bình luận');
            return;
        }

        if (comment.length < 2) {
            alert('Bình luận quá ngắn');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('review_id', reviewId);
            formData.append('comment', comment);

            const response = await fetch('api/review-comment.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                input.value = '';
                await this.loadComments(reviewId);
                
                // Update comment count
                const commentBtn = document.querySelector(`.review-comment-btn[data-review-id="${reviewId}"]`);
                if (commentBtn) {
                    const countSpan = commentBtn.querySelector('.comment-count');
                    const currentCount = parseInt(countSpan.textContent) || 0;
                    countSpan.textContent = currentCount + 1;
                }
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        } catch (error) {
            console.error('Error submitting comment:', error);
            alert('Có lỗi xảy ra khi gửi bình luận');
        }
    }
}

// Make reviewSystem global for inline onclick handlers
let reviewSystem;

// Initialize review system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const reviewsContainer = document.getElementById('reviewsContainer');
    if (reviewsContainer) {
        const menuItemId = reviewsContainer.dataset.menuItemId;
        if (menuItemId) {
            reviewSystem = new ReviewSystem(menuItemId);
        }
    }
});
