(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-my-aboutus"],{"07ec":function(n,t,e){var i=e("24fb");t=i(!1),t.push([n.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/*行情的颜色*/\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.content[data-v-7d8b3f58]{background-color:#252433;min-height:100vh}.content .headTop[data-v-7d8b3f58]{width:94%;padding:0 3%;font-size:%?32?%;display:flex;align-items:center;padding-bottom:%?20?%;color:#fff}.content .headTop .icon[data-v-7d8b3f58]{display:block;width:10%}.content .headTop p[data-v-7d8b3f58]{width:80%;text-align:center;display:block;white-space:nowrap;\r\n  /* 强制性的在一行显示所有的文本，直到文本结束或者遭遇br标签对象才换行*/overflow:hidden;text-overflow:ellipsis\r\n  /* 溢出的文字隐藏起来*/}.content .newstxt[data-v-7d8b3f58]{padding:%?20?% 0;font-size:%?28?%;color:#fff;line-height:%?50?%}.content .newstxt uni-image[data-v-7d8b3f58]{width:100%;height:auto}',""]),n.exports=t},"0cd7":function(n,t,e){"use strict";e("7a82"),Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;e("570b");var i={data:function(){return{statusBarHeight:"",content:""}},computed:{i18n:function(){return this.$t("login")}},onLoad:function(){this.getAbout();var n=this;uni.getSystemInfo({success:function(t){"ios"==t.platform?n.statusBarHeight=t.statusBarHeight+45:n.statusBarHeight=t.statusBarHeight+50}})},methods:{getAbout:function(){var n=this;this.$http.service({type:1}).then((function(t){var e=t.data;200==e.code?n.content=e.data.content:401==e.code?(uni.removeStorageSync("token"),uni.removeStorageSync("userInfo"),uni.reLaunch({url:"/pages/logon/index"})):uni.showToast({title:e.msg,icon:"none"})})).catch((function(n){uni.showToast({title:"报错了",icon:"none"})}))},back:function(){uni.switchTab({url:"./index"})}}};t.default=i},"120c":function(n,t,e){"use strict";e.r(t);var i=e("a54d"),o=e("6794");for(var a in o)["default"].indexOf(a)<0&&function(n){e.d(t,n,(function(){return o[n]}))}(a);e("d5bd");var r=e("f0c5"),s=Object(r["a"])(o["default"],i["b"],i["c"],!1,null,"7d8b3f58",null,!1,i["a"],void 0);t["default"]=s.exports},6794:function(n,t,e){"use strict";e.r(t);var i=e("0cd7"),o=e.n(i);for(var a in i)["default"].indexOf(a)<0&&function(n){e.d(t,n,(function(){return i[n]}))}(a);t["default"]=o.a},a54d:function(n,t,e){"use strict";e.d(t,"b",(function(){return i})),e.d(t,"c",(function(){return o})),e.d(t,"a",(function(){}));var i=function(){var n=this,t=n.$createElement,e=n._self._c||t;return e("v-uni-view",{staticClass:"content"},[e("v-uni-view",{staticClass:"headTop",style:{paddingTop:n.statusBarHeight+"rpx"},on:{click:function(t){arguments[0]=t=n.$handleEvent(t),n.back()}}},[e("v-uni-view",{staticClass:"iconfont icon"},[n._v("")]),e("p",[n._v(n._s(n.i18n.my[4]))])],1),e("v-uni-view",{staticClass:"newstxt warp"},[n.content?e("v-uni-view",{domProps:{innerHTML:n._s(n.content)}}):e("v-uni-view",[n._v(n._s(n.i18n.noMones))])],1)],1)},o=[]},bcb5:function(n,t,e){var i=e("07ec");i.__esModule&&(i=i.default),"string"===typeof i&&(i=[[n.i,i,""]]),i.locals&&(n.exports=i.locals);var o=e("4f06").default;o("6348ba58",i,!0,{sourceMap:!1,shadowMode:!1})},d5bd:function(n,t,e){"use strict";var i=e("bcb5"),o=e.n(i);o.a}}]);