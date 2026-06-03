<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{!! config('master.app.profile.description') !!}">
    <meta name="author" content="{!! config('master.app.profile.author') !!}">
    <title>@stack('title',config('master.app.profile.name')) | {!! config('master.app.profile.short_name') !!}</title>
    <link rel="icon" href="{{ url($template.config('master.app.web.favicon'))}}">
    <link rel="stylesheet" href="{{ url($template.'/css/vendors_css.css') }}">
    <link rel="stylesheet" href="{{ url($template.'/css/style.css') }}">
    <link rel="stylesheet" href="{{ url($template.'/css/skin_color.css') }}">
    <link rel="stylesheet" href="{{ url('css/backend-layout.css') }}">
    @stack('css')
</head>
<body class="hold-transition light-skin sidebar-mini theme-primary fixed lkh-content-scroll">
<div class="wrapper">
    <div id="loader"></div>
    @include('backend.main.menu.header')
    @include('backend.main.menu.sidebar')
    @yield('content')
    @include('backend.main.menu.footer')
    @include('backend.main.menu.control-sidebar')
</div>
<script src="{{ url($template.'/js/vendors.min.js') }}"></script>
<script src="{{ url($template.'/assets/icons/feather-icons/feather.min.js') }}"></script>
<script src="{{ url($template.'/assets/vendor_components/jquery-blockUi/jquery.blockUi.js') }}"></script>
<script src="{{ url($template.'/assets/vendor_components/select2/dist/js/select2.js') }}"></script>
<script src="{{ url($template.'/assets/vendor_components/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ url($template.'/js/template.js') }}"></script>
<script src="{{ url($template.'/js/jquery.loadmodal.js?time='.time()) }}"></script>
<script src="{{ url('/js/'.$backend.'/js/jquery.js?time='.time()) }}"></script>
<script src="{{ url('/js/helper.js') }}"></script>
<script>
(function () {
    var body = document.body;
    if (!body.classList.contains('lkh-content-scroll')) {
        return;
    }

    function isMobileLayout() {
        return window.innerWidth <= 767;
    }

    function syncMobileSidebarState() {
        if (!isMobileLayout()) {
            return;
        }
        body.classList.remove('sidebar-collapse');
    }

    function sidebarWidth() {
        if (isMobileLayout()) {
            return 0;
        }
        if (body.classList.contains('sidebar-collapse')) {
            return body.classList.contains('sidebar-mini') ? 60 : 0;
        }

        return 270;
    }

    function applyLayoutMetrics() {
        var header = document.querySelector('.main-header');
        var footer = document.querySelector('.main-footer');
        var root = document.documentElement;

        if (header) {
            root.style.setProperty('--lkh-header-h', header.offsetHeight + 'px');
        }
        if (footer) {
            root.style.setProperty('--lkh-footer-h', footer.offsetHeight + 'px');
        }
        root.style.setProperty('--lkh-sidebar-w', sidebarWidth() + 'px');
    }

    window.addEventListener('load', function () {
        syncMobileSidebarState();
        applyLayoutMetrics();
        setTimeout(applyLayoutMetrics, 350);
    });
    window.addEventListener('resize', function () {
        syncMobileSidebarState();
        applyLayoutMetrics();
    });

    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('collapsed.pushMenu expanded.pushMenu', function () {
            syncMobileSidebarState();
            applyLayoutMetrics();
        });
        jQuery(document).on('click', '.content-wrapper', function () {
            if (isMobileLayout() && body.classList.contains('sidebar-open')) {
                body.classList.remove('sidebar-open', 'sidebar-collapse');
            }
        });
        jQuery(window).on('load', function () {
            syncMobileSidebarState();
            setTimeout(applyLayoutMetrics, 50);
        });
    }
})();
</script>
@stack('js')
</body>
</html>
