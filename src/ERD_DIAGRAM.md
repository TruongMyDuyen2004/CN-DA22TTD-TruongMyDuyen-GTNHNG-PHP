# ERD - Ngon Gallery Restaurant

## 1. Mô hình Người dùng (Customer)

```mermaid
erDiagram
    customers {
        int id PK
        string full_name
        string email
        string phone
    }
    
    categories {
        int id PK
        string name
    }
    
    menu_items {
        int id PK
        int category_id FK
        string name
        decimal price
        string image
    }
    
    cart {
        int id PK
        int customer_id FK
        int menu_item_id FK
        int quantity
    }
    
    orders {
        int id PK
        int customer_id FK
        string order_number
        decimal total_amount
        string status
    }
    
    order_items {
        int id PK
        int order_id FK
        int menu_item_id FK
        int quantity
        decimal price
    }
    
    reviews {
        int id PK
        int customer_id FK
        int menu_item_id FK
        int rating
        text comment
    }
    
    review_likes {
        int id PK
        int review_id FK
        int customer_id FK
    }
    
    favorites {
        int id PK
        int customer_id FK
        int menu_item_id FK
    }
    
    reservations {
        int id PK
        int customer_id FK
        date reservation_date
        time reservation_time
        int number_of_guests
        string status
    }
    
    member_cards {
        int id PK
        int customer_id FK
        string card_number
        decimal balance
    }
    
    card_transactions {
        int id PK
        int card_id FK
        string type
        decimal amount
    }
    
    vouchers {
        int id PK
        string code
        string discount_type
        decimal discount_value
    }
    
    voucher_usage {
        int id PK
        int voucher_id FK
        int customer_id FK
        int order_id FK
    }
    
    customer_points {
        int id PK
        int customer_id FK
        int available_points
        string tier
    }
    
    contacts {
        int id PK
        int customer_id FK
        string subject
        text message
    }

    %% Relationships
    categories ||--o{ menu_items : "chứa"
    
    customers ||--o{ cart : "có"
    customers ||--o{ orders : "đặt"
    customers ||--o{ reviews : "viết"
    customers ||--o{ review_likes : "thích"
    customers ||--o{ favorites : "yêu thích"
    customers ||--o{ reservations : "đặt bàn"
    customers ||--|| member_cards : "sở hữu"
    customers ||--|| customer_points : "tích"
    customers ||--o{ voucher_usage : "dùng"
    customers ||--o{ contacts : "gửi"
    
    menu_items ||--o{ cart : "trong"
    menu_items ||--o{ order_items : "thuộc"
    menu_items ||--o{ reviews : "được đánh giá"
    menu_items ||--o{ favorites : "được thích"
    
    orders ||--o{ order_items : "gồm"
    orders ||--o{ voucher_usage : "áp dụng"
    
    reviews ||--o{ review_likes : "nhận"
    
    member_cards ||--o{ card_transactions : "ghi"
    
    vouchers ||--o{ voucher_usage : "được dùng"
```

---

## 2. Mô hình Quản trị viên (Admin)

```mermaid
erDiagram
    admins {
        int id PK
        string username
        string email
        string password
    }
    
    categories {
        int id PK
        string name
        int display_order
    }
    
    menu_items {
        int id PK
        int category_id FK
        string name
        decimal price
        boolean is_available
    }
    
    orders {
        int id PK
        int customer_id FK
        string order_number
        decimal total_amount
        string status
        string payment_status
    }
    
    reservations {
        int id PK
        int confirmed_by FK
        string customer_name
        date reservation_date
        string status
    }
    
    dining_tables {
        int id PK
        string table_number
        int capacity
        string location
    }
    
    reservation_tables {
        int id PK
        int reservation_id FK
        int table_id FK
    }
    
    vouchers {
        int id PK
        string code
        string name
        decimal discount_value
        boolean is_active
    }
    
    member_cards {
        int id PK
        int customer_id FK
        string card_number
        decimal balance
        string status
    }
    
    topup_requests {
        int id PK
        int card_id FK
        string transaction_code
        decimal amount
        string status
    }
    
    contacts {
        int id PK
        string name
        string email
        text message
        string status
    }
    
    contact_replies {
        int id PK
        int contact_id FK
        int admin_id FK
        text reply_message
    }
    
    point_settings {
        int id PK
        string setting_key
        string setting_value
    }

    %% Relationships
    admins ||--o{ reservations : "xác nhận"
    admins ||--o{ contact_replies : "trả lời"
    
    categories ||--o{ menu_items : "chứa"
    
    reservations ||--o{ reservation_tables : "gán"
    dining_tables ||--o{ reservation_tables : "được đặt"
    
    member_cards ||--o{ topup_requests : "yêu cầu nạp"
    
    contacts ||--o{ contact_replies : "được trả lời"
```

---

## Tổng quan hệ thống

| Phía | Chức năng chính |
|------|-----------------|
| **Customer** | Xem menu, đặt hàng, giỏ hàng, đánh giá, yêu thích, đặt bàn, thẻ thành viên, voucher, tích điểm, liên hệ |
| **Admin** | Quản lý menu, đơn hàng, đặt bàn, bàn ăn, voucher, thẻ thành viên, nạp tiền, trả lời liên hệ, cài đặt điểm |
