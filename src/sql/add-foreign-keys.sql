-- =====================================================
-- THÊM FOREIGN KEYS CHO DATABASE NGON_GALLERY
-- Chạy file này trong phpMyAdmin để tạo đường nối
-- =====================================================

USE ngon_gallery;

-- Tắt kiểm tra FK tạm thời
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. BẢNG MENU_ITEMS -> CATEGORIES
-- =====================================================
ALTER TABLE menu_items
ADD CONSTRAINT fk_menu_items_category
FOREIGN KEY (category_id) REFERENCES categories(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- 2. BẢNG CART -> CUSTOMERS
-- =====================================================
ALTER TABLE cart
ADD CONSTRAINT fk_cart_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 3. BẢNG CART -> MENU_ITEMS
-- =====================================================
ALTER TABLE cart
ADD CONSTRAINT fk_cart_menu_item
FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 4. BẢNG ORDERS -> CUSTOMERS
-- =====================================================
ALTER TABLE orders
ADD CONSTRAINT fk_orders_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- 5. BẢNG ORDER_ITEMS -> ORDERS
-- =====================================================
ALTER TABLE order_items
ADD CONSTRAINT fk_order_items_order
FOREIGN KEY (order_id) REFERENCES orders(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 6. BẢNG ORDER_ITEMS -> MENU_ITEMS
-- =====================================================
ALTER TABLE order_items
ADD CONSTRAINT fk_order_items_menu_item
FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- 7. BẢNG REVIEWS -> CUSTOMERS
-- =====================================================
ALTER TABLE reviews
ADD CONSTRAINT fk_reviews_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 8. BẢNG REVIEWS -> MENU_ITEMS
-- =====================================================
ALTER TABLE reviews
ADD CONSTRAINT fk_reviews_menu_item
FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 9. BẢNG REVIEWS -> ORDERS
-- =====================================================
ALTER TABLE reviews
ADD CONSTRAINT fk_reviews_order
FOREIGN KEY (order_id) REFERENCES orders(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- 10. BẢNG REVIEW_LIKES -> REVIEWS
-- =====================================================
ALTER TABLE review_likes
ADD CONSTRAINT fk_review_likes_review
FOREIGN KEY (review_id) REFERENCES reviews(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 11. BẢNG REVIEW_LIKES -> CUSTOMERS
-- =====================================================
ALTER TABLE review_likes
ADD CONSTRAINT fk_review_likes_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 12. BẢNG REVIEW_COMMENTS -> REVIEWS
-- =====================================================
ALTER TABLE review_comments
ADD CONSTRAINT fk_review_comments_review
FOREIGN KEY (review_id) REFERENCES reviews(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 13. BẢNG REVIEW_COMMENTS -> CUSTOMERS
-- =====================================================
ALTER TABLE review_comments
ADD CONSTRAINT fk_review_comments_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 14. BẢNG RESERVATIONS -> CUSTOMERS
-- =====================================================
ALTER TABLE reservations
ADD CONSTRAINT fk_reservations_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 15. BẢNG SAVED_PROMOTIONS -> CUSTOMERS
-- =====================================================
ALTER TABLE saved_promotions
ADD CONSTRAINT fk_saved_promotions_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 16. BẢNG SAVED_PROMOTIONS -> PROMOTIONS
-- =====================================================
ALTER TABLE saved_promotions
ADD CONSTRAINT fk_saved_promotions_promotion
FOREIGN KEY (promotion_id) REFERENCES promotions(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 17. BẢNG PROMOTION_ITEMS -> PROMOTIONS
-- =====================================================
ALTER TABLE promotion_items
ADD CONSTRAINT fk_promotion_items_promotion
FOREIGN KEY (promotion_id) REFERENCES promotions(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 18. BẢNG PROMOTION_ITEMS -> MENU_ITEMS
-- =====================================================
ALTER TABLE promotion_items
ADD CONSTRAINT fk_promotion_items_menu_item
FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 19. BẢNG CONTACT_REPLIES -> CONTACTS
-- =====================================================
ALTER TABLE contact_replies
ADD CONSTRAINT fk_contact_replies_contact
FOREIGN KEY (contact_id) REFERENCES contacts(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- =====================================================
-- 20. BẢNG CONTACT_REPLIES -> ADMINS
-- =====================================================
ALTER TABLE contact_replies
ADD CONSTRAINT fk_contact_replies_admin
FOREIGN KEY (admin_id) REFERENCES admins(id)
ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- 21. BẢNG PASSWORD_RESETS -> CUSTOMERS
-- =====================================================
ALTER TABLE password_resets
ADD CONSTRAINT fk_password_resets_customer
FOREIGN KEY (customer_id) REFERENCES customers(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- Bật lại kiểm tra FK
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- HOÀN THÀNH! Refresh Designer để xem đường nối
-- =====================================================
