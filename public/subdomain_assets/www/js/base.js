

// スクロールに合わせて出現
document.querySelectorAll(".anime").forEach((el) => {
        gsap.fromTo(
            el,
            { y: 50, opacity: 0 },
            {
                y: 0,
                opacity: 1,
                duration: 1.5,
                scrollTrigger: {
                    trigger: el,
                    start: "top 80%",
                    ease: "expo",
                    //markers: true // 検証用のマーカーを表示
                },
            }
        );
});

//pagetopボタンの制御
$(function(){
    var totop = $('.pagetop'); 

    $(".pagetop").hide();
    $(window).on("scroll", function() {
        if ($(window).scrollTop() > 300 ) {
            $(".pagetop").fadeIn("fast");
        } else {
            $(".pagetop").fadeOut("fast");
        }
        scrollHeight = $(document).height();
        scrollPosition = $(window).height() + $(window).scrollTop();
        footHeight = $('.sec_footer').innerHeight();
        if ( scrollHeight - scrollPosition  <= footHeight ) {
            $(".pagetop").css({
                "position":"absolute",
                "bottom": footHeight + 20
            });
        } else {
            $(".pagetop").css({
                "position":"fixed",
                "right": "40px",
                "bottom": "20px"
            });
        }
    });
    $('.pagetop').click(function () {
        $('body,html').animate({
        scrollTop: 0
        }, 400);
        return false;
        });
});

//スムーズスクロール設定
$(function(){
    
    // 同じページ内のアンカーかチェックする関数
    function isSamePageAnchor(href) {
        try {
            // hrefが#で始まる場合は同じページ内
            if (href.indexOf('#') === 0) {
                return true;
            } else {
                // URLオブジェクトを使って比較
                var linkUrl = new URL(href, window.location.href);
                var currentUrl = window.location;
                
                // originとpathnameが同じなら同じページ
                return linkUrl.origin === currentUrl.origin && 
                       linkUrl.pathname === currentUrl.pathname;
            }
        } catch(e) {
            // URL解析に失敗した場合（相対パスなど）、パス部分で比較
            var hrefWithoutHash = href.split('#')[0];
            var currentPath = window.location.pathname;
            return hrefWithoutHash === '' || 
                   hrefWithoutHash === currentPath ||
                   hrefWithoutHash === window.location.pathname;
        }
    }
    
    // スムーズスクロールを実行する関数
    function smoothScrollToAnchor(anchor, updateHash) {
        var scrollAdjust = -105;
        var scrollSpeed = 400;

        var target = $(anchor);
        if (target.length) {
            var position = target.offset().top + scrollAdjust;
            $('body,html').animate({scrollTop:position}, scrollSpeed, 'swing');
            $('.uk-offcanvas').removeClass('uk-open');
            $('html').removeClass('uk-offcanvas-page');
            $('body').removeClass('uk-offcanvas-container');
            
            // URLのハッシュを更新（オプション）
            if (updateHash && history.pushState) {
                history.pushState(null, null, anchor);
            }
        }
    }
    
    // リンククリック時のスムーズスクロール
    $('a[href*="#sec"]').click(function(e){
        var href = $(this).attr("href");
        
        // hrefからアンカー部分（#sec_xxx）を抽出
        var hash = href.match(/#sec[^#]*$/);
        if (!hash) {
            return true; // アンカーがない場合は通常のリンク動作
        }
        
        var anchor = hash[0];
        
        // 同じページ内のアンカーかチェック
        if (!isSamePageAnchor(href)) {
            return true; // 他のページへのリンクの場合は通常の遷移
        }
        
        // 同じページ内の場合はスムーズスクロール
        smoothScrollToAnchor(anchor, true);
        e.preventDefault();
        return false;
    });
    
    // ページ読み込み時にURLにハッシュがある場合、スムーズスクロール
    if (window.location.hash && window.location.hash.match(/^#sec/)) {
        setTimeout(function() {
            smoothScrollToAnchor(window.location.hash, false);
        }, 100); // ページ読み込み完了を待つ
    }
});
