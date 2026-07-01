/**
 * 習い事クーポン管理システム 共通JavaScript
 */

// アプリケーション名前空間
window.VoucherApp = window.VoucherApp || {};

/**
 * 共通ユーティリティ関数
 */
VoucherApp.Utils = {
    /**
     * スムーススクロール
     */
    smoothScroll: function(target, duration = 800) {
        const targetElement = document.querySelector(target);
        if (!targetElement) return;
        
        const targetPosition = targetElement.offsetTop;
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        let startTime = null;

        function animation(currentTime) {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const run = VoucherApp.Utils.ease(timeElapsed, startPosition, distance, duration);
            window.scrollTo(0, run);
            if (timeElapsed < duration) requestAnimationFrame(animation);
        }

        requestAnimationFrame(animation);
    },

    /**
     * イージング関数
     */
    ease: function(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t + b;
        t--;
        return -c / 2 * (t * (t - 2) - 1) + b;
    },

    /**
     * 要素の表示/非表示切り替え
     */
    toggle: function(element) {
        if (element.style.display === 'none' || element.style.display === '') {
            element.style.display = 'block';
        } else {
            element.style.display = 'none';
        }
    },

    /**
     * フェードイン効果
     */
    fadeIn: function(element, duration = 500) {
        element.style.opacity = '0';
        element.style.display = 'block';
        
        let last = +new Date();
        const tick = function() {
            element.style.opacity = +element.style.opacity + (new Date() - last) / duration;
            last = +new Date();
            
            if (+element.style.opacity < 1) {
                requestAnimationFrame(tick);
            }
        };
        
        tick();
    },

    /**
     * 要素がビューポート内にあるかチェック
     */
    isInViewport: function(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },

    /**
     * デバウンス関数
     */
    debounce: function(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
};

/**
 * アニメーション管理
 */
VoucherApp.Animation = {
    /**
     * スクロール時のアニメーション初期化
     */
    initScrollAnimations: function() {
        const animatedElements = document.querySelectorAll('.fade-in, .slide-in-left');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0) translateX(0)';
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        animatedElements.forEach(element => {
            // 初期状態を設定
            element.style.opacity = '0';
            element.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            
            if (element.classList.contains('slide-in-left')) {
                element.style.transform = 'translateX(-30px)';
            } else {
                element.style.transform = 'translateY(20px)';
            }
            
            observer.observe(element);
        });
    },

    /**
     * ホバーエフェクトの初期化
     */
    initHoverEffects: function() {
        const hoverElements = document.querySelectorAll('.hover-lift');
        
        hoverElements.forEach(element => {
            element.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.15)';
            });
            
            element.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'initial';
            });
        });
    }
};

/**
 * フォーム管理
 */
VoucherApp.Form = {
    /**
     * バリデーション
     */
    validate: function(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                VoucherApp.Form.showError(input, 'この項目は必須です');
                isValid = false;
            } else {
                VoucherApp.Form.clearError(input);
            }
        });
        
        return isValid;
    },

    /**
     * エラー表示
     */
    showError: function(input, message) {
        VoucherApp.Form.clearError(input);
        
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message text-red-600 text-sm mt-1';
        errorElement.textContent = message;
        
        input.parentNode.appendChild(errorElement);
        input.classList.add('border-red-500');
    },

    /**
     * エラークリア
     */
    clearError: function(input) {
        const errorElement = input.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
        input.classList.remove('border-red-500');
    }
};

/**
 * 通知管理
 */
VoucherApp.Notification = {
    /**
     * 成功メッセージ表示
     */
    success: function(message, duration = 3000) {
        VoucherApp.Notification.show(message, 'success', duration);
    },

    /**
     * エラーメッセージ表示
     */
    error: function(message, duration = 5000) {
        VoucherApp.Notification.show(message, 'error', duration);
    },

    /**
     * 情報メッセージ表示
     */
    info: function(message, duration = 3000) {
        VoucherApp.Notification.show(message, 'info', duration);
    },

    /**
     * 通知表示
     */
    show: function(message, type, duration) {
        const notification = document.createElement('div');
        notification.className = `notification fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 ${type}`;
        notification.textContent = message;
        
        // スタイル設定
        const styles = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            info: 'bg-blue-500 text-white'
        };
        
        notification.className += ' ' + styles[type];
        
        document.body.appendChild(notification);
        
        // フェードイン
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            notification.style.transition = 'all 0.3s ease-out';
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // 自動削除
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    }
};

/**
 * DOMContentLoaded時の初期化
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('習い事クーポン管理システム JavaScript初期化完了');
    
    // アニメーション初期化
    VoucherApp.Animation.initScrollAnimations();
    VoucherApp.Animation.initHoverEffects();
    
    // スムーススクロールリンクの初期化
    const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
    smoothScrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            VoucherApp.Utils.smoothScroll(target);
        });
    });
    
    // フォームバリデーションの初期化
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!VoucherApp.Form.validate(this)) {
                e.preventDefault();
            }
        });
    });
});

/**
 * ウィンドウサイズ変更時の処理
 */
window.addEventListener('resize', VoucherApp.Utils.debounce(function() {
    // レスポンシブ対応の処理をここに追加
}, 250));