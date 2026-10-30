window.pageLoadFiles = ["Form"];

window.pageOnLoad = function (loading) {
    $.form.manage("/wechat/config", "#wechatForm");
    return false;
};
