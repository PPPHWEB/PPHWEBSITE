// ============================================================
// PRAISE PUBLISHING HOUSE — SHARED HEADER
// ============================================================
// Yahan koi bhi change karo — SAARE PAGES me automatically ho jayega!
// Koi bhi HTML file update karne ki zaroorat nahi.
// ============================================================

(function() {
    var pphCss = ["https://praisepublishinghouse.com/assets/css/plugin.css","https://praisepublishinghouse.com/assets/css/theme.css","https://praisepublishinghouse.com/assets/css/collection.css","https://praisepublishinghouse.com/assets/css/style.css"];
    pphCss.forEach(function(href){ if(!document.querySelector('link[data-pph-css="'+href+'"]')){ var l=document.createElement('link'); l.rel='stylesheet'; l.type='text/css'; l.href=href; l.setAttribute('data-pph-css',href); document.head.appendChild(l);} });
    var headerHTML = `<header id="header" class="main-header" style="display:flex;flex-direction:column;">
            <!-- header-top start -->
            <div class="header-top-area" style="display:flex;flex-direction:column;">
                <!-- notification-bar start -->
                <div class="notification-bar ptb-11 primary-bg" style="order:2;">
                    <!-- Mobile email show -->
                    <div class="d-xl-none text-center" style="padding:4px 10px;">
                        <a href="mailto:connect@praisepublishinghouse.com" style="color:#fff;font-size:12px;text-decoration:none;">✉ connect@praisepublishinghouse.com</a>
                    </div>
                    <div class="container-fluid d-none d-xl-block">
                        <div class="row">
                            <div class="col-xl-3">
                                <!-- <span class="d-inline-block extra-color"><img src="https://praisepublishinghouse.com/icon/5.png" style="width:17px"> </span> -->
                            </div>
                            <div class="col-xl-6 text-center"></div>
                            <div class="col-xl-3 text-end d-flex align-items-center justify-content-end" style="min-height:38px;">
                                <span class="d-inline-flex align-items-center extra-color" style="gap:6px;"><img src="https://praisepublishinghouse.com/icon/6.png" style="width:17px"> <a href="mailto:connect@praisepublishinghouse.com" class="d-inline-block extra-color">connect@praisepublishinghouse.com</a></span>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <!-- notification-bar end -->
                <!-- header-top-first start -->
                <div class="header-top-first ptb-10 position-relative body-bg" style="order:1;background-color:#ff6633 !important;">
                    <div class="container-fluid">
                        <div class="row align-items-center header-area">
                            <!-- header-logo start -->
                            <div class="col-6 col-xl-2 header-element header-logo">
                                <div class="header-theme-logo">
                                    <a href="https://praisepublishinghouse.com/index.html" class="d-inline-block theme-logo">
                                        <img src="https://praisepublishinghouse.com/gall/logo-1.png" class="width-88 width-xl-112 img-fluid" alt="logo" style="width:210px;max-width:210px !important;margin-right:-10px !important;">
                                    </a>
                                </div>
                            </div>
                            <!-- header-logo end -->
                            <!-- header-menu start -->
                            <div class="col-xl-8 col-xxl-8 d-none d-xl-block header-element header-menu">
                                <div class="mainmenu-content">
                                    <div class="main-wrap" style="margin-left:21%;">
                                        <ul class="menu-ul d-flex flex-wrap">
                                            <li class="menu-li">
                                                <a href="https://praisepublishinghouse.com/index.html" class="menu-link d-flex align-items-center ptb-5 plr-15">
                                                    <span class="menu-title text-uppercase heading-weight">Home</span> 
                                                </a> 
                                            </li> 
                                            <li class="menu-li">
                                                <a href="https://praisepublishinghouse.com/about.html" class="menu-link d-flex align-items-center ptb-5 plr-15">
                                                    <span class="menu-title text-uppercase heading-weight">About Us</span> 
                                                </a> 
                                            </li>   
                                            <li class="menu-li">
                                                <a href="https://praisepublishinghouse.com/Store.html" class="menu-link d-flex align-items-center ptb-5 plr-15">
                                                    <span class="menu-title text-uppercase heading-weight">Store</span>
                                                    <span class="icon-16 fw-normal"><i class="ri-arrow-down-s-line d-block lh-1"></i></span>
                                                </a>
                                                <div class="menu-dropdown menu-sub collapse position-absolute top-auto body-bg z-2 DropDownSlide box-shadow" style="min-width:200px;background:#ff6633;border-radius:8px;overflow:hidden;padding:5px 0;">
                                                    <ul class="menudrop-ul ptb-15" style="list-style:none;padding:10px 0;margin:0;">
                                                        <li style="padding:0;">
                                                            <a href="https://store.praisepublishinghouse.com/books/" style="display:block;padding:10px 16px;color:#fff;text-decoration:none;font-weight:600;font-size:14px;background:#ff6633;" onmouseover="this.style.background='#e55000'" onmouseout="this.style.background='#ff6633 '">📚 Books</a>
                                                        </li>
                                                        <li style="padding:0;">
                                                            <a href="https://praisepublishinghouse.com/free.html" style="display:block;padding:10px 16px;color:#fff;text-decoration:none;font-weight:600;font-size:14px;background:#ff6633;" onmouseover="this.style.background='#e55000'" onmouseout="this.style.background='#ff6633 '">🆓 Free Download</a>
                                                        </li>
                                                        <li style="padding:0;">
                                                            <a href="https://store.praisepublishinghouse.com/pdf-download/" style="display:block;padding:10px 16px;color:#fff;text-decoration:none;font-weight:600;font-size:14px;background:#ff6633;" onmouseover="this.style.background='#e55000'" onmouseout="this.style.background='#ff6633 '">📄 Download PDF</a>
                                                        </li>
                                                    </ul>
                                                </div>
</li> 
                                            <li class="menu-li">
                                                <a href="https://praisepublishinghouse.com/blogs.html" class="menu-link d-flex align-items-center ptb-5 plr-15">
                                                    <span class="menu-title text-uppercase heading-weight">Blogs</span> 
                                                </a> 
                                            </li> 
                                            <li class="menu-li">
                                                <a href="https://praisepublishinghouse.com/faq.html" class="menu-link d-flex align-items-center ptb-5 plr-15">
                                                    <span class="menu-title text-uppercase heading-weight">Faq's</span> 
                                                </a> 
                                            </li> 
                                            <li class="menu-li">
                                                <a href="https://praisepublishinghouse.com/contact.html" class="menu-link d-flex align-items-center ptb-5 plr-15">
                                                    <span class="menu-title text-uppercase heading-weight">Contact Us</span> 
                                                </a> 
                                            </li>
                                            <li class="menu-li">
                                                <a href="https://store.praisepublishinghouse.com/cart/" class="menu-link d-flex align-items-center ptb-5 plr-15">
                                                    <span class="menu-title text-uppercase heading-weight">Cart</span><span class="pph-cart-count" style="display:none;">0</span> 
                                                </a> 
                                            </li> 
                                            <li class="menu-li">
                                                <a href="https://store.praisepublishinghouse.com/checkout/" class="menu-link d-flex align-items-center ptb-5 plr-15">
                                                    <span class="menu-title text-uppercase heading-weight">Checkout</span> 
                                                </a> 
                                            </li>  
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <!-- header-menu end -->
                            <!-- header-icon start -->
                            <div class="col-6 col-xl-4 col-xxl-2 header-element header-icon">
                                <div class="header-icon-block d-flex justify-content-end">
                                    <!-- header-search start --> 
                                    <!-- header-search end -->
                                    <ul class="ul-mt15 flex-nowrap align-items-center header-icon-element" style="margin-right:-23.5px !important;">
                                        
                                     <li class="header-icon-wrap toggler-wrap d-xl-none">
                                            <div class="header-icon-wrapper">
                                                <a href="javascript:void(0)" class="d-block header-icon-toggler toggler-btn" aria-label="Menu toggler button">
                                                    <span class="d-block header-block-icon primary-link font-16 font-xl-20"><i class="ri-menu-line"></i></span>
                                                </a>
                                            </div>
                                        </li>
                                        
                                         
                                        <!-- <a  class="header-icon-wrapper" href=""><img src="https://praisepublishinghouse.com/icon/1.png" style="width:21px"></a>
                                        <a href=""><img src="https://praisepublishinghouse.com/icon/2.png" style="width:21px"></a>
                                        <a href=""><img src="https://praisepublishinghouse.com/icon/3.png" style="width:21px"></a>
                                        <a href=""><img src="https://praisepublishinghouse.com/icon/4.png" style="width:21px"></a> -->
                                    </ul>
                                </div>
                            </div>
                            <!-- header-icon end -->
                        </div>
                    </div>
                </div>
                <!-- header-top-first end -->
            </div>
            <!-- header-top end -->
        </header>`;
    
    var placeholder = document.getElementById('pph-header');
    if (placeholder) {
        placeholder.outerHTML = headerHTML;
    }

    // ============================================================
    // GOOGLE ANALYTICS 4 (GA4)
    // Measurement ID: G-0HGH9RDW3T
    // ============================================================
    (function() {
        var gaScript = document.createElement('script');
        gaScript.async = true;
        gaScript.src = 'https://www.googletagmanager.com/gtag/js?id=G-0HGH9RDW3T';
        document.head.appendChild(gaScript);

        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        window.gtag = gtag;
        gtag('js', new Date());
        gtag('config', 'G-0HGH9RDW3T');
    })();

    // ============================================================
    // META PIXEL (Facebook / Instagram)
    // Pixel ID: 2472492436533137
    // ============================================================
    (function(f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function() {
            n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
        };
        if (!f._fbq) f._fbq = n;
        n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
        t = b.createElement(e); t.async = true;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s);
    })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '2472492436533137');
    fbq('track', 'PageView');

    var fbNoScript = document.createElement('noscript');
    var fbImg = document.createElement('img');
    fbImg.height = 1;
    fbImg.width = 1;
    fbImg.style.display = 'none';
    fbImg.src = 'https://www.facebook.com/tr?id=2472492436533137&ev=PageView&noscript=1';
    fbNoScript.appendChild(fbImg);
    document.body.appendChild(fbNoScript);
    
    // Active menu highlight
    var currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.menu-link').forEach(function(link) {
        var href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
            link.blur();
        }
    });

    // Remove any leftover browser focus/active background box on menu links
    var activeMenuFix = document.createElement('style');
    activeMenuFix.innerHTML = '.menu-link, .menu-link:focus, .menu-link:active, .menu-link.active { background:transparent !important; background-color:transparent !important; outline:none !important; box-shadow:none !important; } .menu-link.active .menu-title { color:#fff !important; border-bottom:2px solid #fff; padding-bottom:2px; } .menu-link{position:relative;} .pph-cart-count{display:inline-block;min-width:18px;height:18px;line-height:18px;padding:0 5px;margin-left:7px;border-radius:9px;background:#fff;color:#ff6633;font-size:11px;font-weight:700;text-align:center;vertical-align:middle;}';
    document.head.appendChild(activeMenuFix);
})();

/* Mobile menu: Cart & Checkout links (Contact Us ke baad) */
(function(){
  function pphAddMobileCart(){
    var mm = document.querySelector('#mobile-menu .menu-ul');
    if(!mm || mm.querySelector('.pph-mobile-cart')) return;
    [['Cart','https://store.praisepublishinghouse.com/cart/'],['Checkout','https://store.praisepublishinghouse.com/checkout/']].forEach(function(it){
      var li=document.createElement('li');
      li.className='menu-li bst pph-mobile-cart';
      li.innerHTML='<div class="menu-btn d-flex flex-row-reverse"><button type="button" class="width-48 icon-16 ptb-10 bsl" aria-label="Menu item"> </button><span class="width-calc-48 ptb-10 plr-15"><a href="'+it[1]+'" class="d-inline-block body-color">'+it[0]+'</a></span></div>';
      mm.appendChild(li);
    });
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',pphAddMobileCart);}else{pphAddMobileCart();}
})();


// ============================================================
// PPH MOBILE MENU (orange, right-slide) — MAIN + STORE, mobile only
// Replaces old white left menu on main. Same look on both sites.
// ============================================================
(function(){
  function isMobile(){ return window.innerWidth < 1200; }

  // ---- inject styles once ----
  if(!document.getElementById('pph-mnav-style')){
    var st=document.createElement('style'); st.id='pph-mnav-style';
    st.textContent=
      '@media(max-width:1199px){'
      + 'body{overflow-x:hidden !important;}'
      + '#mobile-menu{display:none !important;}'
      + '.header-logo .theme-logo img{width:200px !important;max-width:200px !important;margin-right:0 !important;margin-top:0 !important;}'
      + '.header-icon-toggler,.toggler-btn{padding:6px 10px !important;display:inline-flex !important;align-items:center;}'
      + '.toggler-wrap,.header-icon-wrapper,.header-icon-toggler,.toggler-btn{position:relative !important;z-index:100000 !important;pointer-events:auto !important;}'
      + '}'
      + '#pph-mnav-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);opacity:0;visibility:hidden;transition:.25s;z-index:100040;}'
      + '#pph-mnav-overlay.open{opacity:1;visibility:visible;}'
      + '#pph-mnav{position:fixed;top:0;right:-320px;width:290px;max-width:85%;height:100%;background:#ff6633;z-index:100050;transition:right .28s ease;box-shadow:-4px 0 18px rgba(0,0,0,.25);overflow-y:auto;padding:16px 0;}'
      + '#pph-mnav.open{right:0;}'
      + '#pph-mnav .pph-mnav-close{position:absolute;top:8px;right:14px;color:#fff;font-size:28px;line-height:1;background:none;border:0;cursor:pointer;}'
      + '#pph-mnav .pph-mnav-head{padding:14px 22px 6px;color:#ffe3d5;font-size:12px;letter-spacing:1px;text-transform:uppercase;font-weight:700;}'
      + '#pph-mnav a{display:block;padding:12px 22px;color:#fff;text-decoration:none;font-weight:600;font-size:15px;border-bottom:1px solid rgba(255,255,255,.15);}'
      + '#pph-mnav a.sub{padding-left:38px;font-size:14px;font-weight:500;background:rgba(0,0,0,.08);}'
      + '#pph-mnav a:active{background:#e55000;}';
    (document.head||document.documentElement).appendChild(st);
  }

  function build(){
    // remove off-screen negative margins (mobile only)
    if(isMobile()){
      var ul=document.querySelector('.header-icon-element'); if(ul) ul.style.setProperty('margin-right','0','important');
      var lg=document.querySelector('.header-logo .theme-logo img'); if(lg) lg.style.setProperty('margin-right','0','important');
    }
    // build off-canvas menu once
    if(!document.getElementById('pph-mnav')){
      var ov=document.createElement('div'); ov.id='pph-mnav-overlay';
      var nav=document.createElement('div'); nav.id='pph-mnav';
      nav.innerHTML='<button class="pph-mnav-close" aria-label="Close">&times;</button>'
        +'<a href="https://praisepublishinghouse.com/index.html">HOME</a>'
        +'<a href="https://praisepublishinghouse.com/about.html">ABOUT US</a>'
        +'<div class="pph-mnav-head">Store</div>'
        +'<a href="https://store.praisepublishinghouse.com/books/" class="sub">\uD83D\uDCDA Books</a>'
        +'<a href="https://praisepublishinghouse.com/free.html" class="sub">\uD83C\uDD93 Free Download</a>'
        +'<a href="https://store.praisepublishinghouse.com/pdf-download/" class="sub">\uD83D\uDCC4 Download PDF</a>'
        +'<a href="https://praisepublishinghouse.com/blogs.html">BLOGS</a>'
        +'<a href="https://praisepublishinghouse.com/faq.html">FAQ\u0027S</a>'
        +'<a href="https://praisepublishinghouse.com/contact.html">CONTACT US</a>'
        +'<a href="https://store.praisepublishinghouse.com/cart/">CART</a>'
        +'<a href="https://store.praisepublishinghouse.com/checkout/">CHECKOUT</a>';
      document.body.appendChild(ov); document.body.appendChild(nav);
      function close(){nav.classList.remove('open');ov.classList.remove('open');}
      window.pphMnavOpen=function(){nav.classList.add('open');ov.classList.add('open');};
      ov.addEventListener('click',close);
      nav.querySelector('.pph-mnav-close').addEventListener('click',close);
    }
    // bind hamburger in CAPTURE phase => blocks old theme.js white menu on main
    var tog=document.querySelector('.header-icon-toggler, .toggler-btn');
    if(tog && !tog.dataset.pphBound){
      tog.dataset.pphBound='1';
      tog.addEventListener('click',function(e){
        e.preventDefault(); e.stopPropagation();
        if(window.pphMnavOpen) window.pphMnavOpen();
      }, true);
    }
  }

  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',build);}else{build();}
  var n=0,iv=setInterval(function(){build(); if(++n>25) clearInterval(iv);},200);
  window.addEventListener('resize',build);
})();


// ============================================================
// MOBILE HEADER LOCK — dono sites (main + store) ka header mobile view
// me bilkul same size/position/look. Subdomain (WordPress/Astra) apna
// font/spacing laga raha tha jisse maroon bar ka mail id aur baaki
// components alag size me dikhte the. Yahan har component ko main site
// ke exact values par !important se lock kar rahe hain. Sirf <1200px
// (mobile view). Desktop par koi asar nahi. Saare pages par auto apply.
// ============================================================
(function(){
  if(document.getElementById('pph-hdr-lock')) return;
  var st=document.createElement('style'); st.id='pph-hdr-lock';
  st.textContent='@media (max-width:1199.98px){'
    + '#header,#header .header-top-area,#header .notification-bar,#header .header-top-first,#header .container-fluid,#header .row,#header [class*="col-"]{box-sizing:border-box !important;}'
    + '#header .notification-bar{padding-top:11px !important;padding-bottom:11px !important;}'
    + '#header .notification-bar .d-xl-none{padding:4px 10px !important;text-align:center !important;}'
    + '#header .notification-bar .d-xl-none a,#header .notification-bar a[href^="mailto"]{font-family:var(--body-font-family) !important;font-size:12px !important;line-height:1.4 !important;letter-spacing:normal !important;font-weight:400 !important;color:#fff !important;text-decoration:none !important;}'
    + '#header .header-top-first{padding-top:10px !important;padding-bottom:10px !important;}'
    + '#header .header-top-first .container-fluid{padding-left:15px !important;padding-right:15px !important;margin-left:0 !important;margin-right:0 !important;max-width:100% !important;width:100% !important;}'
    + '#header .header-area{margin-left:0 !important;margin-right:0 !important;flex-wrap:nowrap !important;}'
    + '#header .header-area>[class*="col-"]{padding-left:0 !important;padding-right:0 !important;}'
    + '#header .header-top-first{position:relative !important;}'
    + '#header .header-top-first .container-fluid>.header-area{position:static !important;}'
    + '#header .header-area .header-logo{width:auto !important;max-width:calc(100% - 80px) !important;flex:0 0 auto !important;padding-left:0 !important;}'
    + '#header .header-area .header-icon{position:absolute !important;right:15px !important;top:50% !important;transform:translateY(-50%) !important;width:auto !important;max-width:none !important;flex:0 0 auto !important;margin:0 !important;padding:0 !important;z-index:100000 !important;}'
    + '#header .header-icon-block{justify-content:flex-end !important;margin:0 !important;padding:0 !important;}'
    + '#header .header-icon-block .header-icon-element{margin:0 !important;padding:0 !important;}'
    + '#header .header-logo .theme-logo img{margin-right:0 !important;margin-left:0 !important;}'
    + '#header .header-icon-toggler .header-block-icon,#header .header-icon-toggler .header-block-icon i.ri-menu-line{font-size:16px !important;line-height:1 !important;vertical-align:middle !important;display:inline-block !important;color:#fff !important;}'
    + '#header .header-icon-toggler .header-block-icon i.ri-menu-line:before{font-size:16px !important;line-height:1 !important;}'
    + '}';
  (document.head||document.documentElement).appendChild(st);
})();
