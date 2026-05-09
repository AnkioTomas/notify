window.pageLoadFiles = [

];
window.pageOnLoad = function (loading) {

    function getLink() {
        $.request.get("/token/get",{},function (data) {
            $("#linkInput").val(data.data);
        });
    }

    getLink();


    $("#copyBtn").on("click",function () {
        $.copy($("#linkInput").val());
        $.toaster.success("已复制");
    })


    $("#resetBtn").on("click",function () {
        $.request.get("/token/reset",{},function (data) {
            getLink();
            $.toaster.success("已重置");
        });

    })




    window.pageOnUnLoad = function () {

    };
    return false
};