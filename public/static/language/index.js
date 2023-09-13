import fontCN from './CN.js'; //简体语言包
import fontUS from './EN.js'; //英文语言包
import Vue from 'vue'
import VueI18n from 'vue-i18n'; //引入实时切换
Vue.use(VueI18n);

const i18n = new VueI18n({//设置默认语言为简体
	locale: uni.getStorageSync('language_key')? uni.getStorageSync('language_key'):'zh-Hans',
	messages: {
		'zh-Hans': fontCN,//简体	
		'en':fontUS,//英文
	}
})
export default i18n;