(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-trade-launch"],{2047:function(t,n,e){"use strict";var a=e("ede9"),i=e.n(a);i.a},"6c04":function(t,n,e){"use strict";e("7a82"),Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0,e("e9c4");var a={data:function(){return{statusBarHeight:"",navIndex:0,activeName:"1",deList:[],deLists:[]}},computed:{i18n:function(){return this.$t("login")}},onLoad:function(){this.checkIndex(0);var t=this;uni.getSystemInfo({success:function(n){"ios"==n.platform?t.statusBarHeight=n.statusBarHeight+45:t.statusBarHeight=n.statusBarHeight+50}})},methods:{back:function(){uni.navigateBack({delta:1})},godetail:function(t){uni.setStorageSync("launch",JSON.stringify(t)),uni.navigateTo({url:"./launchdele?id="+t.id+"&type="+this.activeName})},checkIndex:function(t){var n=this;this.navIndex=t,this.$http.launchList().then((function(t){var e=t.data;200==e.code?n.deList=e.data.data:uni.showToast({title:e.msg,icon:"none"})})).catch((function(t){uni.showToast({title:n.$t("login").wrong_1,icon:"none"})}))},checkIndexs:function(t){var n=this;this.navIndex=t,this.$http.myLaunch().then((function(t){var e=t.data;200==e.code?n.deLists=e.data.data:uni.showToast({title:e.msg,icon:"none"})})).catch((function(t){uni.showToast({title:n.$t("login").wrong_1,icon:"none"})}))}}};n.default=a},"75a8":function(t,n,e){"use strict";e.r(n);var a=e("6c04"),i=e.n(a);for(var s in a)["default"].indexOf(s)<0&&function(t){e.d(n,t,(function(){return a[t]}))}(s);n["default"]=i.a},b8f7:function(t,n,e){"use strict";e.d(n,"b",(function(){return a})),e.d(n,"c",(function(){return i})),e.d(n,"a",(function(){}));var a=function(){var t=this,n=t.$createElement,e=t._self._c||n;return e("v-uni-view",{staticClass:"content"},[e("v-uni-view",{staticClass:"headTop",style:{paddingTop:t.statusBarHeight+"rpx"}},[e("v-uni-view",{staticClass:"iconfont icon",on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.back()}}},[t._v("")]),e("v-uni-view",{staticClass:"head-nav"},[e("v-uni-view",{class:0==t.navIndex?"activite":"",on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.checkIndex(0)}}},[t._v(t._s(t.i18n.launch_12))]),e("v-uni-view",{class:1==t.navIndex?"activite":"",on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.checkIndexs(1)}}},[t._v(t._s(t.i18n.launch_13))])],1)],1),0==t.navIndex?e("v-uni-view",{staticClass:"newstxt"},[t.deList.length>0?e("v-uni-view",{staticClass:"rowul"},t._l(t.deList,(function(n,a){return e("v-uni-view",{key:a,staticClass:"rowli",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.godetail(n)}}},[e("v-uni-image",{attrs:{src:n.image}}),e("v-uni-view",{staticClass:"name"},[t._v(t._s(n.pname))]),e("v-uni-view",{staticClass:"li"},[e("em",[t._v(t._s(t.i18n.launch_4))]),e("p",[t._v(t._s(n.fxunit))])]),e("v-uni-view",{staticClass:"li"},[e("em",[t._v(t._s(t.i18n.launch_11))]),e("p",[t._v(t._s(n.fxtime))])]),e("span",{directives:[{name:"show",rawName:"v-show",value:1==n.status,expression:"item.status==1"}],staticClass:"red tips"},[t._v(t._s(t.i18n.launch_14))]),e("span",{directives:[{name:"show",rawName:"v-show",value:2==n.status,expression:"item.status==2"}],staticClass:"red tips"},[t._v(t._s(t.i18n.launch_15))]),e("span",{directives:[{name:"show",rawName:"v-show",value:3==n.status,expression:"item.status==3"}],staticClass:"red tips"},[t._v(t._s(t.i18n.launch_2))])],1)})),1):e("v-uni-view",{staticClass:"noMore"},[t._v(t._s(t.i18n.noMones))])],1):t._e(),2==t.navIndex?e("v-uni-view",{staticClass:"newstxt"},[t.deLists.length>0?e("v-uni-view",{staticClass:"rowul"},t._l(t.deLists,(function(n,a){return e("v-uni-navigator",{key:a,staticClass:"rowli",attrs:{url:"/pages/trade/launchdele?id="+n.id}},[e("v-uni-image",{attrs:{src:n.image}}),e("v-uni-view",{staticClass:"name"},[t._v(t._s(n.pname))]),e("v-uni-view",{staticClass:"li"},[e("em",[t._v(t._s(t.i18n.launch_4))]),e("p",[t._v(t._s(n.fxunit))])]),e("v-uni-view",{staticClass:"li"},[e("em",[t._v(t._s(t.i18n.launch_11))]),e("p",[t._v(t._s(n.fxtime))])]),e("span",{directives:[{name:"show",rawName:"v-show",value:1==n.status,expression:"item.status==1"}],staticClass:"red tips"},[t._v(t._s(t.i18n.launch_14))]),e("span",{directives:[{name:"show",rawName:"v-show",value:2==n.status,expression:"item.status==2"}],staticClass:"red tips"},[t._v(t._s(t.i18n.launch_15))]),e("span",{directives:[{name:"show",rawName:"v-show",value:3==n.status,expression:"item.status==3"}],staticClass:"red tips"},[t._v(t._s(t.i18n.launch_2))])],1)})),1):e("v-uni-view",{staticClass:"noMore"},[t._v(t._s(t.i18n.noMones))])],1):t._e()],1)},i=[]},e23b:function(t,n,e){var a=e("24fb");n=a(!1),n.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/*行情的颜色*/\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.content[data-v-feadfb76]{background-color:#f6f6f6;min-height:100vh}.content .headTop[data-v-feadfb76]{width:94%;padding:0 3%;font-size:%?32?%;display:flex;align-items:center;background-color:#fff;padding-bottom:%?20?%}.content .headTop .icon[data-v-feadfb76]{display:block;width:10%}.content .headTop .head-nav[data-v-feadfb76]{display:flex;justify-content:start;align-items:center;color:#999;font-size:%?36?%;width:80%}.content .headTop .activite[data-v-feadfb76]{color:#000;border-color:#12b298!important}.content .headTop .head-nav > uni-view[data-v-feadfb76]{width:45%;text-align:center;border:%?1?% solid #ccc;padding:%?20?% 0;font-size:%?32?%}.content .newstxt[data-v-feadfb76]{width:94%;margin:0 3%}.content .newstxt .noMore[data-v-feadfb76]{text-align:center;padding:%?50?%;color:#a9b8c1}.content .newstxt .rowul[data-v-feadfb76]{overflow:hidden}.content .newstxt .rowul .rowli[data-v-feadfb76]{background-color:#fff;border-radius:%?10?%;margin-top:%?20?%;position:relative;padding-bottom:%?20?%}.content .newstxt .rowul .rowli uni-image[data-v-feadfb76]{width:100%;height:%?200?%;border-radius:%?10?% %?10?% 0 0;margin-bottom:%?20?%}.content .newstxt .rowul .rowli .name[data-v-feadfb76]{font-size:%?32?%;color:#333;margin-left:%?20?%;font-weight:700;padding-bottom:%?10?%}.content .newstxt .rowul .rowli .li[data-v-feadfb76]{padding:%?6?% %?20?%;display:flex;align-items:center;justify-content:space-between;font-size:%?26?%;color:#000}.content .newstxt .rowul .rowli .li em[data-v-feadfb76]{color:#ccc;font-weight:700}.content .newstxt .rowul .rowli .tips[data-v-feadfb76]{width:%?150?%;height:%?50?%;line-height:%?50?%;background-color:red;text-align:center;color:#fff;font-size:%?24?%;position:absolute;right:10%;top:0;border-radius:0 0 %?10?% %?10?%}',""]),t.exports=n},ede9:function(t,n,e){var a=e("e23b");a.__esModule&&(a=a.default),"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var i=e("4f06").default;i("367e88c0",a,!0,{sourceMap:!1,shadowMode:!1})},fe7f:function(t,n,e){"use strict";e.r(n);var a=e("b8f7"),i=e("75a8");for(var s in i)["default"].indexOf(s)<0&&function(t){e.d(n,t,(function(){return i[t]}))}(s);e("2047");var o=e("f0c5"),c=Object(o["a"])(i["default"],a["b"],a["c"],!1,null,"feadfb76",null,!1,a["a"],void 0);n["default"]=c.exports}}]);