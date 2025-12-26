<?php
session_start();

echo "<h2>Debug Combo Session</h2>";

echo "<h3>applied_combos:</h3>";
echo "<pre>";
print_r($_SESSION['applied_combos'] ?? 'Không có');
echo "</pre>";

echo "<h3>applied_promo:</h3>";
echo "<pre>";
print_r($_SESSION['applied_promo'] ?? 'Không có');
echo "</pre>";

echo "<hr>";
echo "<a href='clear-combo-session.php'>Xóa tất cả combo</a> | ";
echo "<a href='index.php?page=cart'>Xem giỏ hàng</a> | ";
echo "<a href='index.php?page=promotions'>Trang khuyến mãi</a>";
