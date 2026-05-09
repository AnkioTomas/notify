window.pageLoadFiles = ["Form"];

window.pageOnLoad = function (loading) {

    $.form.manage("/wechat/config", "#wechatForm");

    window.pageOnUnLoad = function () {};
    return false;
};
