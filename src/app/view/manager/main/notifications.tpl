<title id="title">通知列表 - {$title}</title>
<style id="style">

    #notificationsRoot .card-view-item:hover {
        transform: none !important;
        box-shadow: none !important;
        border-color: transparent !important;
    }

    @media (prefers-color-scheme: dark) {
        #notificationsRoot .card-view-item,
        #notificationsRoot .card-view-item:hover {
            background: transparent !important;
            border-color: transparent !important;
        }
    }




</style>

<div id="container" class="container">
    <div  class="row col-space16">

        <div class="col-xs12">

            <div id="notificationsRoot"></div>
        </div>
    </div>
</div>

<script id="script" src="/static/js/notifications/list.js?v={$__v}"></script>
