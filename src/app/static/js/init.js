$.loader(['Pjax'],()=>{
    const breakpointCondition = mdui.breakpoint();

    if(breakpointCondition.down("lg")){
        $.waitClass(".navigation-drawer",document.body,()=>{
            const drawer = document.querySelector(".navigation-drawer");
            drawer.open = false;
        })
    }
    let pjaxUtils = new PjaxUtils(true, function () {
    }, "/404");

    function setActive(url) {
        let menuPanel = $("mdui-list");
        menuPanel.find("mdui-list-item").each(function () {
            let link = $(this).data("link");
            let match = $(this).data("match");

            console.log(link, url, link === url)
            if (link === url || (match && new RegExp(match).test(url))) {
                console.log(this)
                $(this).attr("active", "true");

                let parent = $(this).parent().parent();
                console.log(parent);
                if (parent[0].tagName === "MDUI-COLLAPSE-ITEM") {
                    let value = parent.attr("value");
                    let collapse = parent.parent()[0];
                    collapse.value = [value];
                    $(collapse).find("mdui-icon").addClass("rotate-ccw");
                }
            } else {
                $(this).removeAttr("active");
            }
        });
    }



    pjaxUtils.loadUri(window.location.pathname + window.location.search);
    $.waitClass(".layout-main", document.body, () => {
        setActive(window.location.pathname);
    });

    $(document).on("click", "mdui-list-item", (event) => {
        let targetElem = event.target;
        if (targetElem.tagName !== "MDUI-LIST-ITEM") {
            targetElem = targetElem.parentElement;
        }

        let url = $(targetElem).attr("data-link");
        let pjax = $(targetElem).attr("data-pjax");
        let target = $(targetElem).attr("data-target");
        if (url) {
            if (pjax === "true") {
                pjaxUtils.loadUri(url);
                setActive(url);
            } else {
                if (target === "self"){
                    window.location.href = url;
                }else {
                    window.open(url);
                }
            }
        }
    });

// 监听事件

    $(document).on("click", "#navigation-drawer-switch", (event) => {
        const drawer = document.querySelector(".navigation-drawer");
        drawer.open = !drawer.open;
    });



});
document.addEventListener("DOMContentLoaded", function() {
    let menuPanel = $("mdui-list");
    menuPanel
        .find("mdui-collapse")
        .on("open", (event) => {
            $(event.target).find("mdui-icon").addClass("rotate-ccw");
        })
        .on("close", (event) => {
            $(event.target).find("mdui-icon").removeClass("rotate-ccw");
        });



});