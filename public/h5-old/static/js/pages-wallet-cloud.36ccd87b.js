(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-wallet-cloud"],{"0178":function(t,n,o){"use strict";o("7a82"),Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var e={data:function(){return{statusBarHeight:"",password:"",passwords:""}},computed:{i18n:function(){return this.$t("login")}},onLoad:function(){var t=this;uni.getSystemInfo({success:function(n){"ios"==n.platform?t.statusBarHeight=n.statusBarHeight+45:t.statusBarHeight=n.statusBarHeight+50}})},methods:{sumid:function(){var t=this;return""==this.password||""==this.passwords?(uni.showToast({icon:"none",title:this.$t("login").wallet[11]}),!1):this.password!=this.passwords?(uni.showToast({icon:"none",title:this.$t("login").tips_9}),!1):void this.$http.generateAddress().then((function(t){var n=t.data;200==n.code?(uni.showToast({title:n.msg,icon:"none"}),uni.switchTab({url:"/pages/index/index"})):uni.showToast({title:n.msg,icon:"none"})})).catch((function(n){console.log(n),uni.showToast({title:t.$t("login").wrong_1})}))},back:function(){uni.navigateBack({delta:1})}}};n.default=e},"246d":function(t,n,o){"use strict";var e=o("e319"),a=o.n(e);a.a},"2a90":function(t,n,o){var e=o("24fb");n=e(!1),n.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/*行情的颜色*/\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.content[data-v-9e5cbfc6]{height:100vh;overflow:hidden}.content .headTop[data-v-9e5cbfc6]{width:94%;padding:0 3%;font-size:%?32?%;display:flex;align-items:center;padding-bottom:%?50?%}.content .headTop .icon[data-v-9e5cbfc6]{color:#000;display:block;width:10%}.content .newstxt[data-v-9e5cbfc6]{width:94%;margin:0 3%}.content .newstxt h2[data-v-9e5cbfc6]{margin-bottom:%?20?%}.content .newstxt p[data-v-9e5cbfc6]{font-size:%?26?%}.content uni-button[data-v-9e5cbfc6]{width:94%;background:#d382f1;border-radius:%?10?%;font-size:%?32?%;color:#fff;font-weight:500;position:fixed;bottom:15%}.content .formBox[data-v-9e5cbfc6]{margin-top:%?72?%}.content .formBox .row[data-v-9e5cbfc6]{padding:%?20?% 0;margin-bottom:%?40?%}.content .formBox .row uni-input[data-v-9e5cbfc6]{width:94%;padding:%?30?% 3%;margin-top:%?30?%;background-color:#f5f5f5;border-radius:%?15?%}.content .formBox .flexbox[data-v-9e5cbfc6]{display:flex;font-size:%?24?%;justify-content:space-between;align-items:center}.content .formBox .flexbox .RememberCheck[data-v-9e5cbfc6]{color:#777a81}.content .formBox .flexbox .flexfr[data-v-9e5cbfc6]{color:#d382f1}.content .formBox .flexbox .iconfont[data-v-9e5cbfc6]{color:#777a81}',""]),t.exports=n},"2cbd":function(t,n,o){"use strict";o.d(n,"b",(function(){return e})),o.d(n,"c",(function(){return a})),o.d(n,"a",(function(){}));var e=function(){var t=this,n=t.$createElement,o=t._self._c||n;return o("v-uni-view",{staticClass:"content"},[o("v-uni-view",{staticClass:"headTop",style:{paddingTop:t.statusBarHeight+"rpx"},on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.back()}}},[o("v-uni-view",{staticClass:"iconfont icon"},[t._v("")])],1),o("v-uni-view",{staticClass:"newstxt"},[o("h2",[t._v(t._s(t.i18n.wallet[8]))]),o("p",[t._v(t._s(t.i18n.wallet[9]))]),o("v-uni-view",{staticClass:"formBox"},[o("v-uni-form",{attrs:{action:""}},[o("v-uni-view",{staticClass:"row"},[o("span",[t._v(t._s(t.i18n.wallet[10]))]),o("v-uni-input",{attrs:{type:"password",placeholder:t.i18n.wallet[11],"placeholder-style":"color:#777a81"},model:{value:t.password,callback:function(n){t.password=n},expression:"password"}})],1),o("v-uni-view",{staticClass:"row"},[o("span",[t._v(t._s(t.i18n.wallet[12]))]),o("v-uni-input",{attrs:{type:"password",placeholder:t.i18n.wallet[11],"placeholder-style":"color:#777a81"},model:{value:t.passwords,callback:function(n){t.passwords=n},expression:"passwords"}})],1)],1),o("v-uni-button",{attrs:{type:"default"},on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.sumid()}}},[t._v(t._s(t.i18n.wallet[13]))])],1)],1)],1)},a=[]},"720c":function(t,n,o){"use strict";o.r(n);var e=o("0178"),a=o.n(e);for(var i in e)["default"].indexOf(i)<0&&function(t){o.d(n,t,(function(){return e[t]}))}(i);n["default"]=a.a},"8e80":function(t,n,o){"use strict";o.r(n);var e=o("2cbd"),a=o("720c");for(var i in a)["default"].indexOf(i)<0&&function(t){o.d(n,t,(function(){return a[t]}))}(i);o("246d");var s=o("f0c5"),c=Object(s["a"])(a["default"],e["b"],e["c"],!1,null,"9e5cbfc6",null,!1,e["a"],void 0);n["default"]=c.exports},e319:function(t,n,o){var e=o("2a90");e.__esModule&&(e=e.default),"string"===typeof e&&(e=[[t.i,e,""]]),e.locals&&(t.exports=e.locals);var a=o("4f06").default;a("c048bf5e",e,!0,{sourceMap:!1,shadowMode:!1})}}]);