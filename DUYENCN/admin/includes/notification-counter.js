// Auto-update notification counters
(function() {
    // Store previous counts to detect new notifications
    let previousCounts = {
        orders: 0,
        reservations: 0,
        contacts: 0
    };
    
    // Function to play notification sound
    function playNotificationSound() {
        // Create a simple beep sound using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (e) {
            console.log('Audio notification not supported');
        }
    }
    
    // Function to show desktop notification
    function showDesktopNotification(title, message) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '../assets/images/logo.png',
                badge: '../assets/images/logo.png'
            });
        }
    }
    
    // Request notification permission on load
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Function to update notification badges
    function updateNotifications() {
        fetch('../../api/admin-notifications.php')
            .then(response => response.json())
            .then(data => {
                // Check for new notifications
                if (data.pending_reservations > previousCounts.reservations) {
                    playNotificationSound();
                    showDesktopNotification(
                        'Đặt bàn mới!',
                        `Bạn có ${data.pending_reservations - previousCounts.reservations} đặt bàn mới cần xác nhận`
                    );
                }
                
                if (data.pending_orders > previousCounts.orders) {
                    playNotificationSound();
                    showDesktopNotification(
                        'Đơn hàng mới!',
                        `Bạn có ${data.pending_orders - previousCounts.orders} đơn hàng mới cần xử lý`
                    );
                }
                
                if (data.unread_contacts > previousCounts.contacts) {
                    showDesktopNotification(
                        'Liên hệ mới!',
                        `Bạn có ${data.unread_contacts - previousCounts.contacts} tin nhắn mới`
                    );
                }
                
                // Update previous counts
                previousCounts.orders = data.pending_orders;
                previousCounts.reservations = data.pending_reservations;
                previousCounts.contacts = data.unread_contacts;
                
                // Update orders badge
                updateBadge('orders.php', data.pending_orders);
                
                // Update reservations badge
                updateBadge('reservations.php', data.pending_reservations);
                
                // Update contacts badge
                updateBadge('contacts.php', data.unread_contacts);
                
                // Update page title with total notifications
                const total = data.pending_orders + data.pending_reservations + data.unread_contacts;
                if (total > 0) {
                    document.title = `(${total}) ${document.title.replace(/^\(\d+\)\s*/, '')}`;
                } else {
                    document.title = document.title.replace(/^\(\d+\)\s*/, '');
                }
            })
            .catch(error => console.error('Error updating notifications:', error));
    }
    
    // Function to update individual badge
    function updateBadge(page, count) {
        const navItem = document.querySelector(`a[href="${page}"]`);
        if (!navItem) return;
        
        let badge = navItem.querySelector('.badge-notification');
        
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge-notification pulse';
                navItem.appendChild(badge);
            }
            badge.textContent = count;
            
            // Add pulse animation for new notifications
            badge.classList.add('pulse');
            setTimeout(() => badge.classList.remove('pulse'), 2000);
        } else {
            if (badge) {
                badge.remove();
            }
        }
    }
    
    // Update immediately on load
    updateNotifications();
    
    // Update every 30 seconds
    setInterval(updateNotifications, 30000);
    
    // Update when page becomes visible
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateNotifications();
        }
    });
})();
