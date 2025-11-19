// Cart functionality
class Cart {
    constructor() {
        this.updateCartCount();
        this.initEventListeners();
    }
    
    // Cập nhật số lượng món trong giỏ trên header
    updateCartCount() {
        fetch('api/cart.php?action=get_count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                        cartBadge.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Thêm món vào giỏ
    addToCart(itemId, itemName, quantity = 1) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('menu_item_id', itemId);
        formData.append('quantity', quantity);
        
        fetch('api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Kiểm tra content-type trước khi parse JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Response is not JSON:', text);
                    throw new Error('Server trả về dữ liệu không hợp lệ');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showNotification(data.message, 'success');
                this.updateCartCount();
            } else {
                this.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('Có lỗi xảy ra: ' + error.message, 'error');
        });
    }
    
    // Cập nhật số lượng
    updateQuantity(cartId, quantity) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('cart_id', cartId);
        formData.append('quantity', quantity);
        
        fetch('api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Response is not JSON:', text);
                    throw new Error('Server trả về dữ liệu không hợp lệ');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.updateCartCount();
                // Cập nhật tổng tiền nếu đang ở trang giỏ hàng
                if (window.location.search.includes('page=cart')) {
                    location.reload();
                }
            } else {
                this.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const t = window.translations || {};
            this.showNotification(t.error_occurred || 'An error occurred', 'error');
        });
    }
    
    // Xóa món khỏi giỏ
    removeFromCart(cartId) {
        const t = window.translations || {};
        if (!confirm(t.confirm_remove || 'Are you sure you want to remove this item?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('cart_id', cartId);
        
        fetch('api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message, 'success');
                this.updateCartCount();
                // Reload trang giỏ hàng
                if (window.location.search.includes('page=cart')) {
                    location.reload();
                }
            } else {
                this.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const t = window.translations || {};
            this.showNotification(t.error_occurred || 'An error occurred', 'error');
        });
    }
    
    // Hiển thị mini cart
    showMiniCart() {
        fetch('api/cart.php?action=get_items')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderMiniCart(data.items, data.subtotal);
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Render mini cart
    renderMiniCart(items, subtotal) {
        // Get translations from global variable
        const t = window.translations || {};
        
        let html = '<div class="mini-cart-overlay" onclick="closeMiniCart()"></div>';
        html += '<div class="mini-cart">';
        html += '<div class="mini-cart-header">';
        html += `<h3>${t.cart_title || 'Your Cart'}</h3>`;
        html += '<button onclick="closeMiniCart()" class="close-btn">×</button>';
        html += '</div>';
        
        if (items.length === 0) {
            html += `<div class="mini-cart-empty">${t.empty_cart || 'Cart is empty'}</div>`;
        } else {
            html += '<div class="mini-cart-items">';
            items.forEach(item => {
                html += `
                    <div class="mini-cart-item">
                        <div class="mini-item-info">
                            <h4>${item.name}</h4>
                            <p>${item.quantity} x ${this.formatPrice(item.price)}đ</p>
                        </div>
                        <div class="mini-item-price">${this.formatPrice(item.price * item.quantity)}đ</div>
                    </div>
                `;
            });
            html += '</div>';
            
            html += '<div class="mini-cart-footer">';
            html += `<div class="mini-cart-total">${t.total || 'Total'}: <span>${this.formatPrice(subtotal)}đ</span></div>`;
            html += `<a href="?page=cart" class="btn btn-primary btn-block">${t.view_cart || 'View Cart'}</a>`;
            html += `<a href="?page=checkout" class="btn btn-secondary btn-block">${t.checkout || 'Checkout'}</a>`;
            html += '</div>';
        }
        
        html += '</div>';
        
        // Thêm vào body
        const container = document.createElement('div');
        container.id = 'mini-cart-container';
        container.innerHTML = html;
        document.body.appendChild(container);
    }
    
    // Format giá
    formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price);
    }
    
    // Hiển thị thông báo
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Hiển thị
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Ẩn sau 3 giây
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Khởi tạo event listeners
    initEventListeners() {
        // Quick add buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('quick-add-btn')) {
                const itemId = e.target.dataset.itemId;
                const itemName = e.target.dataset.itemName;
                this.addToCart(itemId, itemName);
            }
        });
    }
}

// Khởi tạo cart khi trang load
let cart;
document.addEventListener('DOMContentLoaded', () => {
    cart = new Cart();
});

// Helper functions
function addToCart(itemId, itemName) {
    if (cart) {
        cart.addToCart(itemId, itemName);
    }
}

function showMiniCart() {
    if (cart) {
        cart.showMiniCart();
    }
}

function closeMiniCart() {
    const container = document.getElementById('mini-cart-container');
    if (container) {
        container.remove();
    }
}

function updateCartQuantity(cartId, quantity) {
    if (cart) {
        cart.updateQuantity(cartId, quantity);
    }
}

function removeFromCart(cartId) {
    if (cart) {
        cart.removeFromCart(cartId);
    }
}
