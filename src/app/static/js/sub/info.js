window.pageLoadFiles = [

];
window.pageOnLoad = function (loading) {

    function getLink() {
        $.request.get("/sub/get",{},function (data) {
            $("#linkInput").val(location.origin+"/subscribe/"+data.data);
        });
    }

    getLink();


    $("#copyBtn").on("click",function () {
        $.copy($("#linkInput").val());
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