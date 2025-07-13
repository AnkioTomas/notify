<script src="/static/framework/bootloader.js?v={$__v}"></script>
<script src="/static/framework/utils/Loading.js?v={$__v}"></script>
<script src="/static/framework/utils/Logger.js?v={$__v}"></script>
<script src="/static/framework/utils/Loader.js?v={$__v}"></script>
<script src="/static/framework/utils/Event.js?v={$__v}"></script>
<script src="/static/framework/utils/Toaster.js?v={$__v}"></script>
<script src="/static/framework/utils/Request.js?v={$__v}"></script>
<script src="/static/components/theme/ThemeSwitcher.js?v={$__v}"></script>
<script src="/static/components/language/Language.js?v={$__v}"></script>
<script>
    window._v = "{$__v}"
    let level = debug ? 'debug' : 'error';
    $.logger.setLevel(level);
    $.logger.info('App is running in ' + level + ' mode');
    $.preloader([
        'Loading',
        'Logger',
        'Event',
        'Toaster',
        'Request',
        'ThemeSwitcher',
        'Language'
    ]);
    window.loading && window.loading.close();
    $.request.setBaseUrl(baseUri).setOnCode(401,()=>{
        $.toaster.error('登录已过期，请重新登录');
        setTimeout(()=>{
            window.location.href = '/login';
        },1000);
    }).setOnCode(301,(response)=>{
        window.location.href = response.data;
    });
</script>
