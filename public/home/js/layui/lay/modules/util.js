/** layui-v2.3.0 MIT License By https://www.layui.com */ ;
layui.define("jquery", function (e) {
    "use strict";
    var t = layui.$,
        i = {
            fixbar: function (e) {
                var i, a, o = "layui-fixbar",
                    r = "layui-fixbar-top",
                    l = t(document),
                    n = t("body");
                e = t.extend({
                    showHeight: 200
                }, e), e.bar1 = e.bar1 === !0 ? "&#xe606;" : e.bar1, e.bar2 = e.bar2 === !0 ? "&#xe607;" : e.bar2, e.bgcolor = e.bgcolor ? "background-color:" + e.bgcolor : "";
                var c = [e.bar1, e.bar2, "&#xe604;"],
                    g = t(['<ul class="' + o + '">', e.bar1 ? '<li class="layui-icon" lay-type="bar1" style="' + e.bgcolor + '">' + c[0] + "</li>" : "", e.bar2 ? '<li class="layui-icon" lay-type="bar2" style="' + e.bgcolor + '">' + c[1] + "</li>" : "", '<li class="layui-icon ' + r + '" lay-type="top" style="' + e.bgcolor + '">' + c[2] + "</li>", "</ul>"].join("")),
                    u = g.find("." + r),
                    s = function () {
                        var t = l.scrollTop();
                        t >= e.showHeight ? i || (u.show(), i = 1) : i && (u.hide(), i = 0)
                    };
                t("." + o)[0] || ("object" == typeof e.css && g.css(e.css), n.append(g), s(), g.find("li").on("click", function () {
                    var i = t(this),
                        a = i.attr("lay-type");
                    "top" === a && t("html,body").animate({
                        scrollTop: 0
                    }, 200), e.click && e.click.call(this, a)
                }), l.on("scroll", function () {
                    clearTimeout(a), a = setTimeout(function () {
                        s()
                    }, 100)
                }))
            },
            countdown: function (e, t, i) {
                var a = this,
                    o = "function" == typeof t,
                    r = new Date(e).getTime(),
                    l = new Date(!t || o ? (new Date).getTime() : t).getTime(),
                    n = r - l,
                    c = [Math.floor(n / 864e5), Math.floor(n / 36e5) % 24, Math.floor(n / 6e4) % 60, Math.floor(n / 1e3) % 60];
                o && (i = t);
                var g = setTimeout(function () {
                    a.countdown(e, l + 1e3, i)
                }, 1e3);
                return i && i(n > 0 ? c : [0, 0, 0, 0], t, g), n <= 0 && clearTimeout(g), g
            },
            timeAgo: function (e, t) {
                var i = this,
                    a = [
                        [],
                        []
                    ],
                    o = (new Date).getTime() - new Date(e).getTime();
                return o > 6912e5 ? (o = new Date(e), a[0][0] = i.digit(o.getFullYear(), 4), a[0][1] = i.digit(o.getMonth() + 1), a[0][2] = i.digit(o.getDate()), t || (a[1][0] = i.digit(o.getHours()), a[1][1] = i.digit(o.getMinutes()), a[1][2] = i.digit(o.getSeconds())), a[0].join("-") + " " + a[1].join(":")) : o >= 864e5 ? (o / 1e3 / 60 / 60 / 24 | 0) + "天前" : o >= 36e5 ? (o / 1e3 / 60 / 60 | 0) + "小时前" : o >= 12e4 ? (o / 1e3 / 60 | 0) + "分钟前" : o < 0 ? "未来" : "刚刚"
            },
            digit: function (e, t) {
                var i = "";
                e = String(e), t = t || 2;
                for (var a = e.length; a < t; a++) i += "0";
                return e < Math.pow(10, t) ? i + (0 | e) : e
            },
            toDateString: function (e, t) {
                var i = this,
                    a = new Date(e || new Date),
                    o = [i.digit(a.getFullYear(), 4), i.digit(a.getMonth() + 1), i.digit(a.getDate())],
                    r = [i.digit(a.getHours()), i.digit(a.getMinutes()), i.digit(a.getSeconds())];
                return t = t || "yyyy-MM-dd HH:mm:ss", t.replace(/yyyy/g, o[0]).replace(/MM/g, o[1]).replace(/dd/g, o[2]).replace(/HH/g, r[0]).replace(/mm/g, r[1]).replace(/ss/g, r[2])
            },
            escape: function (e) {
                return String(e || "").replace(/&(?!#?[a-zA-Z0-9]+;)/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/'/g, "&#39;").replace(/"/g, "&quot;")
            }
        };
    e("util", i)
});