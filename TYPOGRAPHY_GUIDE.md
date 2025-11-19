# Typography Guide - Ngon Gallery

## Font Stack

### System Font Stack (Modern & Fast)
```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 
             'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 
             'Helvetica Neue', sans-serif;
```

### Ưu điểm của System Fonts

✅ **Tốc độ**: Không cần tải font từ server
✅ **Native**: Sử dụng font mặc định của hệ điều hành
✅ **Quen thuộc**: Người dùng đã quen với font này
✅ **Accessibility**: Tối ưu cho khả năng đọc
✅ **Performance**: Giảm thời gian load trang

### Font trên các hệ điều hành

- **macOS/iOS**: San Francisco (SF Pro)
- **Windows**: Segoe UI
- **Android**: Roboto
- **Ubuntu**: Ubuntu
- **Linux**: Oxygen, Cantarell

## Font Weights

### Hierarchy
- **800 (Extra Bold)**: Headings chính, số liệu quan trọng
- **700 (Bold)**: Subheadings, buttons, labels
- **600 (Semi Bold)**: Navigation, badges, form labels
- **500 (Medium)**: Body text phụ, descriptions
- **400 (Regular)**: Body text thông thường

### Usage

```css
/* Headings - Extra Bold */
h1, h2, h3 {
    font-weight: 800;
    letter-spacing: -0.03em;
}

/* Buttons - Bold */
.btn {
    font-weight: 700;
    letter-spacing: 0.01em;
}

/* Navigation - Semi Bold */
.nav-link {
    font-weight: 600;
}

/* Body - Medium/Regular */
body {
    font-weight: 400;
}
```

## Font Sizes

### Admin Panel
```css
h1: 2.5rem   (40px)
h2: 2rem     (32px)
h3: 1.5rem   (24px)
h4: 1.25rem  (20px)
h5: 1.1rem   (17.6px)
h6: 1rem     (16px)

Body: 15px
Small: 0.85rem (13.6px)
Tiny: 0.75rem (12px)
```

### Customer Site
```css
h1: 3rem     (48px)
h2: 2.5rem   (40px)
h3: 2rem     (32px)
h4: 1.5rem   (24px)
h5: 1.25rem  (20px)
h6: 1.1rem   (17.6px)

Body: 16px
Small: 0.95rem (15.2px)
Tiny: 0.8rem (12.8px)
```

## Letter Spacing

### Tight (Headings)
```css
letter-spacing: -0.03em;
```
Dùng cho headings lớn để tạo cảm giác compact và hiện đại.

### Normal (Body)
```css
letter-spacing: -0.01em;
```
Dùng cho body text, giúp dễ đọc.

### Wide (Uppercase)
```css
letter-spacing: 0.05em - 0.08em;
```
Dùng cho text uppercase (badges, labels) để tăng khả năng đọc.

## Line Height

### Headings
```css
line-height: 1.2 - 1.3;
```
Tight line height cho headings tạo cảm giác mạnh mẽ.

### Body Text
```css
line-height: 1.6 - 1.7;
```
Comfortable line height cho body text dễ đọc.

## Text Styles

### Headings
```css
h1, h2, h3, h4, h5, h6 {
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.03em;
    color: var(--gray-900);
}
```

### Body Text
```css
body {
    font-size: 16px;
    line-height: 1.6;
    letter-spacing: -0.01em;
    color: var(--gray-700);
}
```

### Buttons
```css
.btn {
    font-weight: 700;
    font-size: 0.95rem - 1rem;
    letter-spacing: 0.01em;
}
```

### Badges
```css
.badge {
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
```

### Navigation
```css
.nav-link {
    font-weight: 600;
    font-size: 0.95rem;
    letter-spacing: 0.01em;
}
```

### Labels
```css
label {
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.01em;
}
```

### Table Headers
```css
th {
    font-weight: 700;
    font-size: 0.8rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
```

## Text Hierarchy

### Level 1: Hero/Main Title
- Font size: 3rem (48px)
- Font weight: 800
- Letter spacing: -0.03em
- Use: Hero sections, main page titles

### Level 2: Section Title
- Font size: 2.5rem (40px)
- Font weight: 800
- Letter spacing: -0.03em
- Use: Section headings

### Level 3: Card Title
- Font size: 1.5rem (24px)
- Font weight: 700
- Letter spacing: -0.02em
- Use: Card headings, subsections

### Level 4: Item Title
- Font size: 1.25rem (20px)
- Font weight: 700
- Letter spacing: -0.02em
- Use: List items, menu items

### Level 5: Body Large
- Font size: 1.15rem (18.4px)
- Font weight: 500
- Letter spacing: -0.01em
- Use: Introductions, descriptions

### Level 6: Body Regular
- Font size: 1rem (16px)
- Font weight: 400
- Letter spacing: -0.01em
- Use: Regular text

### Level 7: Body Small
- Font size: 0.95rem (15.2px)
- Font weight: 500
- Letter spacing: 0em
- Use: Secondary text

### Level 8: Caption
- Font size: 0.85rem (13.6px)
- Font weight: 500
- Letter spacing: 0.01em
- Use: Captions, metadata

### Level 9: Tiny
- Font size: 0.75rem (12px)
- Font weight: 600
- Letter spacing: 0.05em
- Use: Badges, tags, labels

## Text Colors

### Primary Text
```css
color: var(--gray-900); /* #0f172a */
```
Headings, important text

### Secondary Text
```css
color: var(--gray-700); /* #334155 */
```
Body text, descriptions

### Tertiary Text
```css
color: var(--gray-600); /* #475569 */
```
Less important text

### Muted Text
```css
color: var(--gray-500); /* #64748b */
```
Placeholders, disabled text

### Accent Text
```css
color: var(--primary); /* #dc2626 */
```
Links, highlights, prices

## Text Transforms

### Uppercase
```css
text-transform: uppercase;
```
Use for:
- Badges
- Labels
- Table headers
- Section badges
- Navigation (optional)

### Capitalize
```css
text-transform: capitalize;
```
Use sparingly, prefer natural casing

### None (Default)
```css
text-transform: none;
```
Use for most text

## Best Practices

### Do's ✅

1. **Use system fonts** for better performance
2. **Maintain hierarchy** with size and weight
3. **Use negative letter spacing** for large headings
4. **Use positive letter spacing** for uppercase text
5. **Keep line height comfortable** (1.6-1.7 for body)
6. **Use bold weights** (700-800) for emphasis
7. **Limit font weights** to 3-4 variations
8. **Test readability** on different devices

### Don'ts ❌

1. Don't use too many font sizes
2. Don't use light weights (100-300) for small text
3. Don't use all caps for long text
4. Don't use tight line height for body text
5. Don't mix too many font weights
6. Don't use decorative fonts for body text
7. Don't ignore contrast ratios
8. Don't use font sizes below 12px

## Accessibility

### Minimum Sizes
- Body text: 16px minimum
- Small text: 14px minimum
- Tiny text: 12px minimum (use sparingly)

### Contrast Ratios
- Normal text: 4.5:1 minimum (WCAG AA)
- Large text (18px+): 3:1 minimum
- Bold text (14px+ bold): 3:1 minimum

### Readability
- Line length: 50-75 characters optimal
- Line height: 1.5-1.7 for body text
- Paragraph spacing: 1.5em minimum

## Examples

### Hero Section
```css
.hero-title {
    font-size: 3rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
    color: var(--gray-900);
}

.hero-subtitle {
    font-size: 1.25rem;
    font-weight: 500;
    letter-spacing: -0.01em;
    line-height: 1.6;
    color: var(--gray-600);
}
```

### Card
```css
.card-title {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--gray-900);
}

.card-description {
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.6;
    color: var(--gray-600);
}
```

### Button
```css
.btn {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}
```

### Badge
```css
.badge {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
```

## Responsive Typography

### Mobile (< 768px)
```css
h1 { font-size: 2rem; }
h2 { font-size: 1.75rem; }
h3 { font-size: 1.5rem; }
body { font-size: 15px; }
```

### Tablet (768px - 1024px)
```css
h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
h3 { font-size: 1.75rem; }
body { font-size: 16px; }
```

### Desktop (> 1024px)
```css
h1 { font-size: 3rem; }
h2 { font-size: 2.5rem; }
h3 { font-size: 2rem; }
body { font-size: 16px; }
```

## Performance Tips

1. **Use system fonts** - No external font loading
2. **Limit font weights** - Fewer variations = faster
3. **Use font-display: swap** - If using web fonts
4. **Subset fonts** - Only include needed characters
5. **Preload critical fonts** - For above-the-fold content

## Version History

### v2.0 (Current)
- System font stack
- Bold weights (700-800)
- Negative letter spacing for headings
- Uppercase for labels/badges
- Improved hierarchy

### v1.0 (Previous)
- Google Fonts (Inter, Playfair Display)
- Mixed weights
- Standard letter spacing
