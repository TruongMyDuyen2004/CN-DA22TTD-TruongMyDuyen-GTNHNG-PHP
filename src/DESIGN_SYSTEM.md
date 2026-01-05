# Design System - Ngon Gallery

## Màu sắc (Colors)

### Primary Colors
- **Primary**: `#c62828` - Đỏ chủ đạo, sang trọng
- **Primary Dark**: `#8e0000` - Đỏ đậm cho hover states
- **Primary Light**: `#ff5f52` - Đỏ nhạt cho backgrounds
- **Secondary**: `#ff6f00` - Cam ấm áp
- **Accent**: `#ffd54f` - Vàng nhấn nhá

### Neutral Colors
- **Text Dark**: `#1a1a1a` - Text chính
- **Text Medium**: `#4a4a4a` - Text phụ
- **Text Light**: `#757575` - Text mờ
- **Background Light**: `#fafafa` - Nền sáng
- **Background Gray**: `#f5f5f5` - Nền xám
- **White**: `#ffffff` - Trắng
- **Border**: `#e0e0e0` - Viền

### Gradients
- **Primary Gradient**: `linear-gradient(135deg, #c62828 0%, #ff6f00 100%)`
- **Overlay Gradient**: `linear-gradient(135deg, rgba(198,40,40,0.95) 0%, rgba(255,111,0,0.95) 100%)`

## Typography

### Font Families
- **Headings**: 'Playfair Display', serif - Sang trọng, cổ điển
- **Body**: 'Inter', 'Poppins', sans-serif - Hiện đại, dễ đọc

### Font Sizes
- **Hero Title**: 2.5rem - 3rem
- **Section Title**: 2rem - 2.5rem
- **Card Title**: 1.2rem - 1.5rem
- **Body**: 0.95rem - 1rem
- **Small**: 0.85rem - 0.9rem

### Font Weights
- **Light**: 300
- **Regular**: 400
- **Medium**: 500
- **Semibold**: 600
- **Bold**: 700

## Spacing

### Padding/Margin Scale
- **xs**: 0.5rem (8px)
- **sm**: 0.8rem (12px)
- **md**: 1.2rem (20px)
- **lg**: 2rem (32px)
- **xl**: 3rem (48px)
- **2xl**: 4rem (64px)

### Container
- **Max Width**: 1400px
- **Padding**: 2rem (desktop), 1rem (mobile)

## Shadows

### Shadow Levels
- **Small**: `0 2px 8px rgba(0,0,0,0.08)` - Subtle elevation
- **Medium**: `0 4px 16px rgba(0,0,0,0.1)` - Cards
- **Large**: `0 8px 32px rgba(0,0,0,0.12)` - Dropdowns
- **Hover**: `0 12px 40px rgba(0,0,0,0.15)` - Interactive elements

## Border Radius

### Radius Scale
- **Small**: 8px - Inputs, small buttons
- **Medium**: 12px - Cards, buttons
- **Large**: 16px - Large cards
- **XLarge**: 24px - Modals, auth boxes
- **Round**: 50% - Icons, avatars
- **Pill**: 25px - Pills, badges

## Components

### Buttons

#### Primary Button
```css
background: linear-gradient(135deg, #c62828 0%, #ff6f00 100%);
color: white;
padding: 0.8rem 1.8rem;
border-radius: 12px;
box-shadow: 0 4px 14px rgba(198,40,40,0.3);
```

#### Secondary Button
```css
background: #f5f5f5;
color: #1a1a1a;
border: 2px solid #e0e0e0;
```

#### Outline Button
```css
background: transparent;
color: #c62828;
border: 2px solid #c62828;
```

### Cards
```css
background: white;
border-radius: 16px;
padding: 2rem;
box-shadow: 0 4px 16px rgba(0,0,0,0.1);
border: 1px solid #e0e0e0;
```

### Inputs
```css
padding: 0.9rem 1.2rem;
border: 2px solid #e0e0e0;
border-radius: 12px;
background: #fafafa;
```

**Focus State:**
```css
border-color: #c62828;
background: white;
box-shadow: 0 0 0 4px rgba(198,40,40,0.1);
```

### Badges
```css
padding: 0.5rem 1.2rem;
background: linear-gradient(135deg, rgba(198,40,40,0.1), rgba(255,111,0,0.1));
color: #c62828;
border-radius: 20px;
border: 1px solid rgba(198,40,40,0.2);
```

## Icons

### Icon Library
Font Awesome 6.4.0

### Icon Sizes
- **Small**: 0.9rem
- **Medium**: 1rem
- **Large**: 1.2rem
- **XLarge**: 1.5rem

### Icon Usage
- Navigation: Subtle, 0.9rem
- Buttons: 1rem with 0.6rem gap
- Features: Large emoji or 4rem icons

## Animations

### Transitions
- **Fast**: 0.2s - Hover states
- **Normal**: 0.3s - Most interactions
- **Slow**: 0.4s - Modals, drawers

### Easing
- **Default**: ease
- **Smooth**: cubic-bezier(0.4, 0, 0.2, 1)

### Keyframes

#### Fade In
```css
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
```

#### Slide In Right
```css
@keyframes slideInRight {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
```

#### Pulse
```css
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
```

## Responsive Breakpoints

### Breakpoints
- **Mobile**: < 768px
- **Tablet**: 768px - 992px
- **Desktop**: 992px - 1200px
- **Large Desktop**: > 1200px

### Grid
- **Mobile**: 1 column
- **Tablet**: 2 columns
- **Desktop**: 3-4 columns

## Accessibility

### Focus States
- Visible outline or box-shadow
- Color contrast ratio: 4.5:1 minimum

### Interactive Elements
- Minimum touch target: 44x44px
- Clear hover states
- Keyboard navigation support

## Best Practices

### Do's ✅
- Use consistent spacing scale
- Apply shadows for depth
- Use gradients sparingly
- Maintain color contrast
- Add smooth transitions
- Use semantic HTML

### Don'ts ❌
- Mix too many colors
- Use small touch targets
- Forget hover states
- Ignore mobile responsiveness
- Overuse animations
- Skip accessibility

## Component Examples

### Menu Item Card
```html
<div class="menu-item">
    <div class="menu-item-image">🍜</div>
    <div class="menu-item-content">
        <div class="menu-item-header">
            <h4>Phở bò đặc biệt</h4>
            <span class="price">65,000đ</span>
        </div>
        <p class="menu-item-desc">Nước dùng hầm xương 12 tiếng</p>
        <span class="availability available">✓ Còn món</span>
        <button class="btn btn-primary">Thêm vào giỏ</button>
    </div>
</div>
```

### Alert Message
```html
<div class="alert alert-success">
    Đặt hàng thành công!
</div>
```

### User Dropdown
```html
<div class="user-dropdown">
    <button class="user-btn">
        <i class="fas fa-user-circle"></i>
        <span>Tên người dùng</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div class="user-menu">
        <a href="#"><i class="fas fa-user"></i> Thông tin cá nhân</a>
        <a href="#"><i class="fas fa-box"></i> Đơn hàng</a>
        <a href="#"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
    </div>
</div>
```

## File Structure

```
assets/
├── css/
│   ├── style.css          # Core styles
│   └── improvements.css   # Enhanced components
├── js/
│   ├── main.js           # Main functionality
│   └── cart.js           # Cart features
└── images/               # Images (if any)
```

## Version
Design System v1.0 - November 2025
