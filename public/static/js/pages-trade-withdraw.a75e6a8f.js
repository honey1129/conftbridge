(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-trade-withdraw"],{"06de":function(t,n,i){"use strict";var e;i.d(n,"b",(function(){return a})),i.d(n,"c",(function(){return o})),i.d(n,"a",(function(){return e}));var a=function(){var t=this,n=t.$createElement,i=t._self._c||n;return i("v-uni-view",{staticClass:"content"},[i("v-uni-view",{staticClass:"headTop",style:{paddingTop:t.statusBarHeight+"rpx"},on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.back()}}},[i("v-uni-view",{staticClass:"iconfont icon"},[t._v("")]),i("p",[t._v(t._s(t.i18n.column_2))])],1),i("v-uni-view",{staticClass:"newstxt"},[i("v-uni-view",{staticClass:"trantop"},[i("v-uni-view",{staticClass:"topfl"},[i("v-uni-picker",{attrs:{value:t.index,range:t.coinLst,"range-key":"code"},on:{change:function(n){arguments[0]=n=t.$handleEvent(n),t.bindPickerChange.apply(void 0,arguments)}}},[i("v-uni-view",{staticClass:"rowes"},[i("v-uni-view",{staticClass:"topfls"},[i("v-uni-image",{attrs:{src:t.coinLst[t.index].icon,mode:""}}),t._v(t._s(t.coinLst[t.index].code))],1),i("v-uni-view",{staticClass:"topfr"},[t._v(t._s(t.i18n.transer_1)),i("v-uni-view",{staticClass:"iconfont"},[t._v("")])],1)],1)],1)],1)],1),i("v-uni-view",{staticClass:"trantop"},[i("v-uni-view",{staticClass:"topfl"},[i("v-uni-picker",{attrs:{value:t.indexs,range:t.chainLst,"range-key":"chain"},on:{change:function(n){arguments[0]=n=t.$handleEvent(n),t.bindPickerChanges.apply(void 0,arguments)}}},[i("v-uni-view",{staticClass:"rowes"},[i("v-uni-view",{staticClass:"topfls"},[t._v(t._s(t.chainLst[t.indexs].chain))]),i("v-uni-view",{staticClass:"topfr"},[t._v(t._s(t.i18n.deposit_2)),i("v-uni-view",{staticClass:"iconfont"},[t._v("")])],1)],1)],1)],1)],1),i("v-uni-view",{staticClass:"tranrow"},[i("v-uni-view",{staticClass:"name"},[t._v(t._s(t.i18n.withdraw_1))]),i("v-uni-view",{staticClass:"inputcon"},[i("v-uni-input",{attrs:{type:"text",placeholder:t.i18n.withdraw_2},model:{value:t.ruleForm.address,callback:function(n){t.$set(t.ruleForm,"address",n)},expression:"ruleForm.address"}}),i("v-uni-view",{staticClass:"all"},[i("v-uni-view",{staticClass:"iconfont fl",on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.scan.apply(void 0,arguments)}}},[t._v("")]),i("v-uni-view",{staticClass:"iconfont fr",on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.gotos.apply(void 0,arguments)}}},[t._v("")])],1)],1),i("v-uni-view",{staticClass:"name"},[t._v(t._s(t.i18n.withdraw_5))]),i("v-uni-view",{staticClass:"inputcon"},[i("v-uni-input",{attrs:{type:"number",placeholder:t.i18n.withdraw_3},model:{value:t.ruleForm.money,callback:function(n){t.$set(t.ruleForm,"money",n)},expression:"ruleForm.money"}})],1),i("v-uni-view",{staticClass:"tips"},[t._v(t._s(t.i18n.withdraw_4)+":"+t._s(t.$public.toIntercept(t.limit.handling_fee,4))+"/"+t._s(t.i18n.withdraw_6))]),i("v-uni-view",{staticClass:"name"},[t._v(t._s(t.i18n.withdraw_7))]),i("v-uni-view",{staticClass:"inputcon"},[i("v-uni-input",{attrs:{type:"password",placeholder:t.i18n.withdraw_9},model:{value:t.ruleForm.payment_password,callback:function(n){t.$set(t.ruleForm,"payment_password",n)},expression:"ruleForm.payment_password"}})],1)],1),i("v-uni-view",{staticClass:"button popup-info",class:t.disabled?"":"active",attrs:{disabled:t.disabled},on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t.submitForm.apply(void 0,arguments)}}},[i("v-uni-text",{staticClass:"button-text info-text"},[t._v(t._s(t.i18n.btn_2))])],1)],1)],1)},o=[]},"3e7a":function(t,n,i){var e=i("5d74");e.__esModule&&(e=e.default),"string"===typeof e&&(e=[[t.i,e,""]]),e.locals&&(t.exports=e.locals);var a=i("4f06").default;a("1c1af660",e,!0,{sourceMap:!1,shadowMode:!1})},"5d74":function(t,n,i){var e=i("24fb");n=e(!1),n.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/*行情的颜色*/\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.content[data-v-a9fd0b5e]{background-color:#f6f6f6;min-height:100vh}.content .red[data-v-a9fd0b5e]{color:red!important;font-size:%?32?%!important}.content .green[data-v-a9fd0b5e]{color:green!important;font-size:%?32?%!important;font-weight:700}.content .headTop[data-v-a9fd0b5e]{width:94%;padding:0 3%;font-size:%?32?%;display:flex;align-items:center;padding-bottom:%?20?%}.content .headTop .icon[data-v-a9fd0b5e]{display:block;width:10%}.content .headTop p[data-v-a9fd0b5e]{width:80%;text-align:center;display:block;white-space:nowrap;\r\n  /* 强制性的在一行显示所有的文本，直到文本结束或者遭遇br标签对象才换行*/overflow:hidden;text-overflow:ellipsis\r\n  /* 溢出的文字隐藏起来*/}.content .newstxt[data-v-a9fd0b5e]{margin-bottom:%?100?%;padding-bottom:%?50?%;position:relative}.content .newstxt .trantop[data-v-a9fd0b5e]{width:89%;margin:%?30?% 3%;padding:%?20?%;border-radius:%?10?%;background-color:#fff}.content .newstxt .trantop .rowes[data-v-a9fd0b5e]{justify-content:space-between;display:flex;align-items:center}.content .newstxt .trantop .rowes uni-image[data-v-a9fd0b5e]{width:%?60?%;height:%?60?%;border-radius:50%;display:block;margin-right:%?10?%}.content .newstxt .trantop .rowes .topfls[data-v-a9fd0b5e]{display:flex;align-items:center}.content .newstxt .trantop .topfr[data-v-a9fd0b5e]{display:flex;align-items:center;color:#999;margin-left:%?10?%}.content .newstxt .tranrow[data-v-a9fd0b5e]{width:89%;margin:%?30?% 3%;padding:%?20?%;border-radius:%?10?%;background-color:#fff}.content .newstxt .tranrow .name[data-v-a9fd0b5e]{font-size:%?32?%;color:#000;font-weight:700;margin-top:%?40?%}.content .newstxt .tranrow .inputcon[data-v-a9fd0b5e]{display:flex;align-items:center;justify-content:space-between;margin-top:%?40?%;padding-bottom:%?16?%;margin-bottom:%?20?%;border-bottom:%?1?% solid #f5f5f5;font-size:%?28?%}.content .newstxt .tranrow .inputcon uni-input[data-v-a9fd0b5e]{font-size:%?28?%;width:80%}.content .newstxt .tranrow .all[data-v-a9fd0b5e]{display:flex;color:#0173e5}.content .newstxt .tranrow .all .fl[data-v-a9fd0b5e]{margin-right:%?26?%;padding-right:%?26?%;border-right:%?1?% solid #ccc}.content .newstxt .tranrow .tips[data-v-a9fd0b5e]{color:#999;font-size:%?24?%;margin:%?10?% 0;text-align:right}.content .button[data-v-a9fd0b5e]{width:94%;margin-left:3%;background-color:#b9bcc3;border-radius:%?10?%;font-size:%?32?%;color:#fff;font-weight:500;text-align:center;padding:%?20?% 0}.content .active[data-v-a9fd0b5e]{background:#0173e5}.content .codeicon[data-v-a9fd0b5e]{color:#0173e5}',""]),t.exports=n},"621b":function(t,n,i){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var e={data:function(){return{statusBarHeight:"",index:0,indexs:0,num:"0",feel:"1",selectCoin:"",coinLst:[],selectChain:"",chainLst:[],limit:[],ruleForm:{money:"",pid:"",address:"",payment_password:"",code:"",type:""},codeTime:"",msg:"发送验证码",disabled:!0}},watch:{ruleForm:{handler:function(t,n){this.OnBtnChange()},deep:!0}},computed:{i18n:function(){return this.$t("login")}},onLoad:function(){this.getCoin();var t=this;uni.getSystemInfo({success:function(n){"ios"==n.platform?t.statusBarHeight=n.statusBarHeight+45:t.statusBarHeight=n.statusBarHeight+50}})},methods:{OnBtnChange:function(){this.ruleForm.payment_password&&this.ruleForm.address&&this.ruleForm.money?this.disabled=!1:this.disabled=!0},getCoin:function(){var t=this;this.$http.getCoinLst().then((function(n){var i=n.data;200==i.code?(t.coinLst=i.data,console.log(t.coinLst[0].icon),t.getChain(t.coinLst[0].pid),t.getFee(t.coinLst[0].pid),t.ruleForm.pid=t.coinLst[0].pid):uni.showToast({title:i.msg,icon:"none"})})).catch((function(n){uni.showToast({title:t.$t("login").wrong_1,icon:"none"})}))},getChain:function(t){var n=this,i={pid:t};this.$http.getChainLst(i).then((function(i){var e=i.data;console.log(e.data),200==e.code?(n.chainLst=e.data,n.walletRecharge(n.chainLst[0].type,t),n.ruleForm.type=n.chainLst[0].type):uni.showToast({title:e.msg,icon:"none"})})).catch((function(t){uni.showToast({title:n.$t("login").wrong_1,icon:"none"})}))},getFee:function(t){var n=this,i={pid:t};this.$http.checkBalance(i).then((function(t){var i=t.data;200==i.code?n.limit=i.data:uni.showToast({title:i.msg,icon:"none"})})).catch((function(t){uni.showToast({title:n.$t("login").wrong_1,icon:"none"})}))},walletRecharge:function(t,n){var i=this,e={type:t,pid:n};this.$http.bpay(e).then((function(t){var n=t.data;console.log(n.data),200==n.code?(i.url=n.data.address,i.imgUrl=n.data.qrcode,i.isShow=!0):uni.showToast({title:n.msg,icon:"none"})})).catch((function(t){uni.showToast({title:i.$t("login").wrong_1,icon:"none"})}))},back:function(){uni.navigateBack({delta:1})},bindPickerChange:function(t){this.index=t.detail.value,this.getChain(this.coinLst[t.detail.value].pid),this.getFee(this.coinLst[t.detail.value].pid),this.ruleForm.pid=this.coinLst[t.detail.value].pid},bindPickerChanges:function(t){this.indexs=t.detail.value},copy:function(t){console.log(t),uni.setClipboardData({data:t,success:function(){uni.showToast({icon:"success"})}})},scan:function(){uni.scanCode({onlyFromCamera:!0,success:function(t){uni.showToast({icon:"success"})},fail:function(t){console.log("扫码失败",t)}})},gotos:function(){uni.navigateTo({url:"add"})},submitForm:function(){var t=this,n={pid:this.ruleForm.pid,money:this.ruleForm.money,address:this.ruleForm.address,type:this.ruleForm.type,payment_password:this.ruleForm.payment_password};return console.log(n),""==n.money?(uni.showToast({icon:"none",title:this.$t("login").withdraw_3}),!1):""==n.address?(uni.showToast({icon:"none",title:this.$t("login").withdraw_2}),!1):""==n.payment_password?(uni.showToast({icon:"none",title:this.$t("login").withdraw_9}),!1):void this.$http.applyWithdraw(n).then((function(t){var n=t.data;n.code,uni.showToast({title:n.msg,icon:"none"})})).catch((function(n){console.log(n),uni.showToast({title:t.$t("login").wrong_1})}))},codeicon:function(){var t=this;if(""!=this.ruleForm.google_code){var n={email:this.ruleForm.google_code};this.$http.sendEmail(n).then((function(n){var i=n.data;200==i.code?t.countSeconds():uni.showToast({title:i.msg,icon:"none"})})).catch((function(n){console.log(n),uni.showToast({title:t.$t("login").wrong_1})}))}else uni.showToast({icon:"none",title:this.$t("login").withdraw_10})},countSeconds:function(){var t=this;if(this.codeTime>0)uni.showToast({title:this.$t("login").tips_13,icon:"none"});else{this.codeTime=60;var n=setInterval((function(){t.codeTime--,t.codeTime<1&&(clearInterval(n),t.codeTime=0)}),1e3)}}}};n.default=e},"78ed":function(t,n,i){"use strict";i.r(n);var e=i("621b"),a=i.n(e);for(var o in e)"default"!==o&&function(t){i.d(n,t,(function(){return e[t]}))}(o);n["default"]=a.a},"8a0b":function(t,n,i){"use strict";i.r(n);var e=i("06de"),a=i("78ed");for(var o in a)"default"!==o&&function(t){i.d(n,t,(function(){return a[t]}))}(o);i("9b48");var s,r=i("f0c5"),c=Object(r["a"])(a["default"],e["b"],e["c"],!1,null,"a9fd0b5e",null,!1,e["a"],s);n["default"]=c.exports},"9b48":function(t,n,i){"use strict";var e=i("3e7a"),a=i.n(e);a.a}}]);