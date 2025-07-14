window.pageLoadFiles = [

];
window.pageOnLoad = function (loading) {

    function getLink() {
        $.request.get("/sub/get",{},function (data) {
            $("#linkInput").val(location.origin+"/subscribe/"+data.data);
        });
    }

    getLink();
    function copyToClipboard(text) {
        if (navigator.clipboard?.writeText) {
            try {
                navigator.clipboard.writeText(text);
            } catch (_) {
            }
        }
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = 0;
        document.body.appendChild(ta);
        ta.select();
        try {
            return document.execCommand('copy');
        } finally {
            document.body.removeChild(ta);
        }
    }

    $("#copyBtn").on("click",function () {
        copyToClipboard($("#linkInput").val());
        $.toaster.success("已复制");
    })


    $("#resetBtn").on("click",function () {
        $.request.get("/sub/reset",{},function (data) {
            getLink();
            $.toaster.success("已重置");
        });

    })




    window.pageOnUnLoad = function () {

    };
    return false
};