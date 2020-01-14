/* FastClick.js */
!function(){"use strict";function a(b,d){function f(a,b){return function(){return a.apply(b,arguments)}}var e,g,h,i,j;if(d=d||{},this.trackingClick=!1,this.trackingClickStart=0,this.targetElement=null,this.touchStartX=0,this.touchStartY=0,this.lastTouchIdentifier=0,this.touchBoundary=d.touchBoundary||10,this.layer=b,this.tapDelay=d.tapDelay||200,this.tapTimeout=d.tapTimeout||700,!a.notNeeded(b)){for(g=["onMouse","onClick","onTouchStart","onTouchMove","onTouchEnd","onTouchCancel"],h=this,i=0,j=g.length;j>i;i++)h[g[i]]=f(h[g[i]],h);c&&(b.addEventListener("mouseover",this.onMouse,!0),b.addEventListener("mousedown",this.onMouse,!0),b.addEventListener("mouseup",this.onMouse,!0)),b.addEventListener("click",this.onClick,!0),b.addEventListener("touchstart",this.onTouchStart,!1),b.addEventListener("touchmove",this.onTouchMove,!1),b.addEventListener("touchend",this.onTouchEnd,!1),b.addEventListener("touchcancel",this.onTouchCancel,!1),Event.prototype.stopImmediatePropagation||(b.removeEventListener=function(a,c,d){var e=Node.prototype.removeEventListener;"click"===a?e.call(b,a,c.hijacked||c,d):e.call(b,a,c,d)},b.addEventListener=function(a,c,d){var e=Node.prototype.addEventListener;"click"===a?e.call(b,a,c.hijacked||(c.hijacked=function(a){a.propagationStopped||c(a)}),d):e.call(b,a,c,d)}),"function"==typeof b.onclick&&(e=b.onclick,b.addEventListener("click",function(a){e(a)},!1),b.onclick=null)}}var b=navigator.userAgent.indexOf("Windows Phone")>=0,c=navigator.userAgent.indexOf("Android")>0&&!b,d=/iP(ad|hone|od)/.test(navigator.userAgent)&&!b,e=d&&/OS 4_\d(_\d)?/.test(navigator.userAgent),f=d&&/OS [6-7]_\d/.test(navigator.userAgent),g=navigator.userAgent.indexOf("BB10")>0;a.prototype.needsClick=function(a){switch(a.nodeName.toLowerCase()){case"button":case"select":case"textarea":if(a.disabled)return!0;break;case"input":if(d&&"file"===a.type||a.disabled)return!0;break;case"label":case"iframe":case"video":return!0}return/\bneedsclick\b/.test(a.className)},a.prototype.needsFocus=function(a){switch(a.nodeName.toLowerCase()){case"textarea":return!0;case"select":return!c;case"input":switch(a.type){case"button":case"checkbox":case"file":case"image":case"radio":case"submit":return!1}return!a.disabled&&!a.readOnly;default:return/\bneedsfocus\b/.test(a.className)}},a.prototype.sendClick=function(a,b){var c,d;document.activeElement&&document.activeElement!==a&&document.activeElement.blur(),d=b.changedTouches[0],c=document.createEvent("MouseEvents"),c.initMouseEvent(this.determineEventType(a),!0,!0,window,1,d.screenX,d.screenY,d.clientX,d.clientY,!1,!1,!1,!1,0,null),c.forwardedTouchEvent=!0,a.dispatchEvent(c)},a.prototype.determineEventType=function(a){return c&&"select"===a.tagName.toLowerCase()?"mousedown":"click"},a.prototype.focus=function(a){var b;d&&a.setSelectionRange&&0!==a.type.indexOf("date")&&"time"!==a.type&&"month"!==a.type?(b=a.value.length,a.setSelectionRange(b,b)):a.focus()},a.prototype.updateScrollParent=function(a){var b,c;if(b=a.fastClickScrollParent,!b||!b.contains(a)){c=a;do{if(c.scrollHeight>c.offsetHeight){b=c,a.fastClickScrollParent=c;break}c=c.parentElement}while(c)}b&&(b.fastClickLastScrollTop=b.scrollTop)},a.prototype.getTargetElementFromEventTarget=function(a){return a.nodeType===Node.TEXT_NODE?a.parentNode:a},a.prototype.onTouchStart=function(a){var b,c,f;if(a.targetTouches.length>1)return!0;if(b=this.getTargetElementFromEventTarget(a.target),c=a.targetTouches[0],d){if(f=window.getSelection(),f.rangeCount&&!f.isCollapsed)return!0;if(!e){if(c.identifier&&c.identifier===this.lastTouchIdentifier)return a.preventDefault(),!1;this.lastTouchIdentifier=c.identifier,this.updateScrollParent(b)}}return this.trackingClick=!0,this.trackingClickStart=a.timeStamp,this.targetElement=b,this.touchStartX=c.pageX,this.touchStartY=c.pageY,a.timeStamp-this.lastClickTime<this.tapDelay&&a.preventDefault(),!0},a.prototype.touchHasMoved=function(a){var b=a.changedTouches[0],c=this.touchBoundary;return Math.abs(b.pageX-this.touchStartX)>c||Math.abs(b.pageY-this.touchStartY)>c?!0:!1},a.prototype.onTouchMove=function(a){return this.trackingClick?((this.targetElement!==this.getTargetElementFromEventTarget(a.target)||this.touchHasMoved(a))&&(this.trackingClick=!1,this.targetElement=null),!0):!0},a.prototype.findControl=function(a){return void 0!==a.control?a.control:a.htmlFor?document.getElementById(a.htmlFor):a.querySelector("button, input:not([type=hidden]), keygen, meter, output, progress, select, textarea")},a.prototype.onTouchEnd=function(a){var b,g,h,i,j,k=this.targetElement;if(k&&"date"==k.type)return!0;if(!this.trackingClick)return!0;if(a.timeStamp-this.lastClickTime<this.tapDelay)return this.cancelNextClick=!0,!0;if(a.timeStamp-this.trackingClickStart>this.tapTimeout)return!0;if(this.cancelNextClick=!1,this.lastClickTime=a.timeStamp,g=this.trackingClickStart,this.trackingClick=!1,this.trackingClickStart=0,f&&(j=a.changedTouches[0],k=document.elementFromPoint(j.pageX-window.pageXOffset,j.pageY-window.pageYOffset)||k,k.fastClickScrollParent=this.targetElement.fastClickScrollParent),h=k.tagName.toLowerCase(),"label"===h){if(b=this.findControl(k)){if(this.focus(k),c)return!1;k=b}}else if(this.needsFocus(k))return a.timeStamp-g>100||d&&window.top!==window&&"input"===h?(this.targetElement=null,!1):(this.focus(k),this.sendClick(k,a),d&&"select"===h||(this.targetElement=null,a.preventDefault()),!1);return d&&!e&&(i=k.fastClickScrollParent,i&&i.fastClickLastScrollTop!==i.scrollTop)?!0:(this.needsClick(k)||(a.preventDefault(),this.sendClick(k,a)),!1)},a.prototype.onTouchCancel=function(){this.trackingClick=!1,this.targetElement=null},a.prototype.onMouse=function(a){return this.targetElement?a.forwardedTouchEvent?!0:a.cancelable?!this.needsClick(this.targetElement)||this.cancelNextClick?(a.stopImmediatePropagation?a.stopImmediatePropagation():a.propagationStopped=!0,a.stopPropagation(),a.preventDefault(),!1):!0:!0:!0},a.prototype.onClick=function(a){var b;return this.trackingClick?(this.targetElement=null,this.trackingClick=!1,!0):"submit"===a.target.type&&0===a.detail?!0:(b=this.onMouse(a),b||(this.targetElement=null),b)},a.prototype.destroy=function(){var a=this.layer;c&&(a.removeEventListener("mouseover",this.onMouse,!0),a.removeEventListener("mousedown",this.onMouse,!0),a.removeEventListener("mouseup",this.onMouse,!0)),a.removeEventListener("click",this.onClick,!0),a.removeEventListener("touchstart",this.onTouchStart,!1),a.removeEventListener("touchmove",this.onTouchMove,!1),a.removeEventListener("touchend",this.onTouchEnd,!1),a.removeEventListener("touchcancel",this.onTouchCancel,!1)},a.notNeeded=function(a){var b,d,e,f;if("undefined"==typeof window.ontouchstart)return!0;if(d=+(/Chrome\/([0-9]+)/.exec(navigator.userAgent)||[,0])[1]){if(!c)return!0;if(b=document.querySelector("meta[name=viewport]")){if(-1!==b.content.indexOf("user-scalable=no"))return!0;if(d>31&&document.documentElement.scrollWidth<=window.outerWidth)return!0}}if(g&&(e=navigator.userAgent.match(/Version\/([0-9]*)\.([0-9]*)/),e[1]>=10&&e[2]>=3&&(b=document.querySelector("meta[name=viewport]")))){if(-1!==b.content.indexOf("user-scalable=no"))return!0;if(document.documentElement.scrollWidth<=window.outerWidth)return!0}return"none"===a.style.msTouchAction||"manipulation"===a.style.touchAction?!0:(f=+(/Firefox\/([0-9]+)/.exec(navigator.userAgent)||[,0])[1],f>=27&&(b=document.querySelector("meta[name=viewport]"),b&&(-1!==b.content.indexOf("user-scalable=no")||document.documentElement.scrollWidth<=window.outerWidth))?!0:"none"===a.style.touchAction||"manipulation"===a.style.touchAction?!0:!1)},a.attach=function(b,c){return new a(b,c)},"function"==typeof define&&"object"==typeof define.amd&&define.amd?define(function(){return a}):"undefined"!=typeof module&&module.exports?(module.exports=a.attach,module.exports.FastClick=a):window.FastClick=a}();


/*计算页面 font-size*/
//方式二
if (window.frames.length != parent.frames.length) {//在iframe打开,获取父类窗口font-size改变大小
	document.getElementsByTagName("html")[0].style.fontSize = window.parent.document.getElementsByTagName("html")[0].style.fontSize;
	window.onresize = function(){
		document.getElementsByTagName("html")[0].style.fontSize = window.parent.document.getElementsByTagName("html")[0].style.fontSize;
	}
}else{
	
	function adapt(designWidth, rem2px,type) {
		var type = type || 0;
		if(type==1){
			 
			!function(a,b){var c=a.documentElement,d="orientationchange"in window?"orientationchange":"resize",e=function(){var a=c.clientWidth>640?640:c.clientWidth<320?320:c.clientWidth;a&&(c.style.fontSize=20*(a/320)+"px")};a.addEventListener&&(b.addEventListener(d,e,!1),a.addEventListener("DOMContentLoaded",e,!1),e())}(document,window);
			console.log($(document.body))
			$('html').append('<div id="popstyle2"><style>\
				.pp-mask .content_box {padding: .6rem .7rem .8rem;}\
			.pp-mask .content_box .title {overflow: hidden;height: 1.5rem;line-height: 1.5rem;text-align: center;font-size: .7rem;}\
			.title img {width: .7rem;margin-top: -0.1rem;vertical-align: middle;margin-right: .1rem;}\
			.pp-mask .msg {text-align: center;color: #0b0b0b !important;font-size: .7rem;}\
			.btn_box {text-align: center;padding: 0 !important;margin: 0;width: 100%;border-top: 2px solid rgba(0,0,0,0.1);height: 2rem;line-height: 2rem;}\
			</div></style>');
		}else{
			var d = window.document.createElement('div');
			d.style.width = '1rem';
			d.style.display = "none";
			var head = window.document.getElementsByTagName('head')[0];
			head.appendChild(d);
			var defaultFontSize = parseFloat(window.getComputedStyle(d, null).getPropertyValue('width'));
			d.remove();
			document.documentElement.style.fontSize = window.innerWidth / designWidth * rem2px / defaultFontSize * 100 + '%';
			var st = document.createElement('style');
			var portrait = "@media screen and (min-width: " + window.innerWidth + "px) {html{font-size:" + ((window.innerWidth / (designWidth / rem2px) / defaultFontSize) * 100) + "%;}}";
			var landscape = "@media screen and (min-width: " + window.innerHeight + "px) {html{font-size:" + ((window.innerHeight / (designWidth / rem2px) / defaultFontSize) * 100) + "%;}}"
			st.innerHTML = portrait + landscape;
			head.appendChild(st);
			return defaultFontSize
		}

	};
	var defaultFontSize = adapt(750, 100);
}
// tab切换
var tab=function(a,b,c){function d(d){a.eq(d).addClass("z-on").siblings().removeClass("z-on"),b.eq(d).addClass("z-on").siblings().removeClass("z-on"),c&&c(d,a.eq(d),b.eq(d))}var e,f;a.click(function(a){if(a.preventDefault(),!$(this).hasClass("z-on")){var b=$(this).index();d(b)}}),e=0,location.hash&&(f=a.filter('[href="'+location.hash+'"]'),f.length&&(e=f.index())),d(e)};


// template.js
!function(){function a(a){return a.replace(t,"").replace(u,",").replace(v,"").replace(w,"").replace(x,"").split(/^$|,+/)}function b(a){return"'"+a.replace(/('|\\)/g,"\\$1").replace(/\r/g,"\\r").replace(/\n/g,"\\n")+"'"}function c(c,d){function e(a){return m+=a.split(/\n/).length-1,k&&(a=a.replace(/[\n\r\t\s]+/g," ").replace(/<!--.*?-->/g,"")),a&&(a=s[1]+b(a)+s[2]+"\n"),a}function f(b){var c=m;if(j?b=j(b,d):g&&(b=b.replace(/\n/g,function(){return m++,"$line="+m+";"})),0===b.indexOf("=")){var e=l&&!/^=[=#]/.test(b);if(b=b.replace(/^=[=#]?|[\s;]*$/g,""),e){var f=b.replace(/\s*\([^\)]+\)/,"");n[f]||/^(include|print)$/.test(f)||(b="$escape("+b+")")}else b="$string("+b+")";b=s[1]+b+s[2]}return g&&(b="$line="+c+";"+b),r(a(b),function(a){if(a&&!p[a]){var b;b="print"===a?u:"include"===a?v:n[a]?"$utils."+a:o[a]?"$helpers."+a:"$data."+a,w+=a+"="+b+",",p[a]=!0}}),b+"\n"}var g=d.debug,h=d.openTag,i=d.closeTag,j=d.parser,k=d.compress,l=d.escape,m=1,p={$data:1,$filename:1,$utils:1,$helpers:1,$out:1,$line:1},q="".trim,s=q?["$out='';","$out+=",";","$out"]:["$out=[];","$out.push(",");","$out.join('')"],t=q?"$out+=text;return $out;":"$out.push(text);",u="function(){var text=''.concat.apply('',arguments);"+t+"}",v="function(filename,data){data=data||$data;var text=$utils.$include(filename,data,$filename);"+t+"}",w="'use strict';var $utils=this,$helpers=$utils.$helpers,"+(g?"$line=0,":""),x=s[0],y="return new String("+s[3]+");";r(c.split(h),function(a){a=a.split(i);var b=a[0],c=a[1];1===a.length?x+=e(b):(x+=f(b),c&&(x+=e(c)))});var z=w+x+y;g&&(z="try{"+z+"}catch(e){throw {filename:$filename,name:'Render Error',message:e.message,line:$line,source:"+b(c)+".split(/\\n/)[$line-1].replace(/^[\\s\\t]+/,'')};}");try{var A=new Function("$data","$filename",z);return A.prototype=n,A}catch(B){throw B.temp="function anonymous($data,$filename) {"+z+"}",B}}var d=function(a,b){return"string"==typeof b?q(b,{filename:a}):g(a,b)};d.version="3.0.0",d.config=function(a,b){e[a]=b};var e=d.defaults={openTag:"<%",closeTag:"%>",escape:!0,cache:!0,compress:!1,parser:null},f=d.cache={};d.render=function(a,b){return q(a,b)};var g=d.renderFile=function(a,b){var c=d.get(a)||p({filename:a,name:"Render Error",message:"Template not found"});return b?c(b):c};d.get=function(a){var b;if(f[a])b=f[a];else if("object"==typeof document){var c=document.getElementById(a);if(c){var d=(c.value||c.innerHTML).replace(/^\s*|\s*$/g,"");b=q(d,{filename:a})}}return b};var h=function(a,b){return"string"!=typeof a&&(b=typeof a,"number"===b?a+="":a="function"===b?h(a.call(a)):""),a},i={"<":"&#60;",">":"&#62;",'"':"&#34;","'":"&#39;","&":"&#38;"},j=function(a){return i[a]},k=function(a){return h(a).replace(/&(?![\w#]+;)|[<>"']/g,j)},l=Array.isArray||function(a){return"[object Array]"==={}.toString.call(a)},m=function(a,b){var c,d;if(l(a))for(c=0,d=a.length;d>c;c++)b.call(a,a[c],c,a);else for(c in a)b.call(a,a[c],c)},n=d.utils={$helpers:{},$include:g,$string:h,$escape:k,$each:m};d.helper=function(a,b){o[a]=b};var o=d.helpers=n.$helpers;d.onerror=function(a){var b="Template Error\n\n";for(var c in a)b+="<"+c+">\n"+a[c]+"\n\n";"object"==typeof console&&console.error(b)};var p=function(a){return d.onerror(a),function(){return"{Template Error}"}},q=d.compile=function(a,b){function d(c){try{return new i(c,h)+""}catch(d){return b.debug?p(d)():(b.debug=!0,q(a,b)(c))}}b=b||{};for(var g in e)void 0===b[g]&&(b[g]=e[g]);var h=b.filename;try{var i=c(a,b)}catch(j){return j.filename=h||"anonymous",j.name="Syntax Error",p(j)}return d.prototype=i.prototype,d.toString=function(){return i.toString()},h&&b.cache&&(f[h]=d),d},r=n.$each,s="break,case,catch,continue,debugger,default,delete,do,else,false,finally,for,function,if,in,instanceof,new,null,return,switch,this,throw,true,try,typeof,var,void,while,with,abstract,boolean,byte,char,class,const,double,enum,export,extends,final,float,goto,implements,import,int,interface,long,native,package,private,protected,public,short,static,super,synchronized,throws,transient,volatile,arguments,let,yield,undefined",t=/\/\*[\w\W]*?\*\/|\/\/[^\n]*\n|\/\/[^\n]*$|"(?:[^"\\]|\\[\w\W])*"|'(?:[^'\\]|\\[\w\W])*'|[\s\t\n]*\.[\s\t\n]*[$\w\.]+/g,u=/[^\w$]+/g,v=new RegExp(["\\b"+s.replace(/,/g,"\\b|\\b")+"\\b"].join("|"),"g"),w=/^\d[^,]*|,\d[^,]*/g,x=/^,+|,+$/g;e.openTag="{{",e.closeTag="}}";var y=function(a,b){var c=b.split(":"),d=c.shift(),e=c.join(":")||"";return e&&(e=", "+e),"$helpers."+d+"("+a+e+")"};e.parser=function(a,b){a=a.replace(/^\s/,"");var c=a.split(" "),e=c.shift(),f=c.join(" ");switch(e){case"if":a="if("+f+"){";break;case"else":c="if"===c.shift()?" if("+c.join(" ")+")":"",a="}else"+c+"{";break;case"/if":a="}";break;case"each":var g=c[0]||"$data",h=c[1]||"as",i=c[2]||"$value",j=c[3]||"$index",k=i+","+j;"as"!==h&&(g="[]"),a="$each("+g+",function("+k+"){";break;case"/each":a="});";break;case"echo":a="print("+f+");";break;case"print":case"include":a=e+"("+c.join(",")+");";break;default:if(-1!==f.indexOf("|")){var l=b.escape;0===a.indexOf("#")&&(a=a.substr(1),l=!1);for(var m=0,n=a.split("|"),o=n.length,p=l?"$escape":"$string",q=p+"("+n[m++]+")";o>m;m++)q=y(q,n[m]);a="=#"+q}else a=d.helpers[e]?"=#"+e+"("+c.join(",")+");":"="+a}return a},"function"==typeof define?define(function(){return d}):"undefined"!=typeof exports?module.exports=d:this.template=d}();
template.config('openTag', '[{');
template.config('closeTag', '}]');
template.config('cache', 'false');
template.config("escape", false);//类型为boolean，默认为true。是否编码输出html字符
template.helper('parseInt', parseInt);
var winFun = {};
var tempcode = {};
var QF=function(a,b,c,d,e,f,g,h,i,j,k,l,m,n){return d=b.getElementsByTagName("body")[0],e="`",l=[],m="!",j=function(){var b,c;if(e!=location.hash){for(n.lash=f=location.hash.substring(m.length+1),g=f.split("/"),b=l.length;b--;)if(h=f.match(l[b][0])){h[0]=l[b][1],g=h;break}if(!n[g[0]])return location.hash="#"+m+i,n.lash=i,void 0;for(b=0;b<d.classList.length;b++){c=d.classList[b],/body-.*/.test(c)&&d.classList.remove(c);break}d.classList.add("body-"+g[0]),n.pop&&n.pop.apply(a,g),e=location.hash,n[g.shift()].apply(a,g)}},n={lash:"",init:function(b){return void 0!==b.key&&(m=b.key),i=b.index||"V",b.pop&&"function"==typeof b.pop&&(n.pop=b.pop),j(),"onhashchange"in a?a.onhashchange=j:setInterval(function(){e!=location.hash&&(j(),e=location.hash)},100),this},reg:function(a,b){var c,d;if(a){if(void 0==b&&(b=function(){}),a instanceof RegExp)"function"==typeof b&&(c="A"+(1*("8"+Math.random()).substring(3)).toString(16),n[c]=b,b=c),l.push([a,b]);else if(a instanceof Array)for(d in a)g=[].concat(a[d]).concat(b),this.reg.apply(this,g);else"string"==typeof a&&("function"==typeof b?n[a]=b:"string"==typeof b&&n[b]&&(n[a]=n[b]));return this}},go:function(a){return location.hash="#"+m+a,this}}},QG=function(){return QF(this,document)};

function flow ($flow){
	var t = this;
	t.$box = $flow;
	t.$load = t.$box.find('.flow-load');
	t.$ct = t.$box.find('.flow-ct');
	t.url = $flow.data('url'); //请求url
	t.boxOffsetTop = t.$box.offset().top;

	// 初始化参数
	t.init();

	if(!t.url){
		return;
	}
	//loading ...
	t.$ct.after('<div class="flow-load" ><div class="zero m-empty"><div class="m-loading" style="display:block;"><div class="box"><div class="u-loading"><i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i></div><div class="txt">数据加载中...</div></div></div><div class="p" style="display:none;">没有数据</div></div></div>');
	t.ajax();

	// 滚动异步加载
	$(window).on('scroll', function(){
		if (!t.do) {
			return ;
		}
		if(t.$box.css('display') == 'none'){
			return;
		}
		// 获取屏幕底部到顶部的高度
		var ih = $(window).scrollTop() + $(window).height();

		if(t.$ct.height() + t.$ct.offset().top -1 <  ih){//滚动继续请求下一页
			t.ajax();
		}
	})
}
flow.prototype = {
	init: function () {
		var t = this;
		t.page = t.$box.data('page') || 0; //初始页码，默认1
		t.temp = t.$box.data('temp') || 'flow'; // 模版名称
		t.dlist = t.$box.data('dlist') ? t.$box.data('dlist').split('.') : ['data'];
		t.method = t.$box.data('method') == 'post' ? 'post' : 'get'
		t.params = t.$box.data('params') ? t.$box.data('params')+'&' : '';
		t.xhr = null; //ajax
		t.do = true; //启动开关
		t.len = 0; // item长度
		t.cb = {}; // 回调
		t.cb.changedata = t.$box.data('cb-changedata') || null; //更改val
		t.cb.insert = t.$box.data('cb-insert') || null; //更改val
		t.page_ini = t.page; //初始页码
		t.page_key = t.$box.data('pagekey') || 'page'; //初始页码
		t.page_next = t.$box.data('pagenext') ? winFun[t.$box.data('pagenext')] : function () {
			return t.page + 1;
		}
	},
	// 拼接url
	setParams: function(){
		var t = this;

		var url = t.url;
		var fd = null;
		if(t.method == 'post'){
			fd = new FormData();
			fd.append('page', t.page);
			if(t.$box.data('params')){
				$.each(t.$box.data('params').split('&'), function (i, v) {
					var arr = v.split('=');
					if(arr.length == 2){
						fd.append(arr[0], arr[1]);
					}
				})
			}
		} else {
			// 拼接url
			/\?/.test(url) ? url += '&' : url += '?'
			url += 'page=' + t.page;
			t.$box.data('params') && (url += '&'+t.$box.data('params'));
		}
		return {url: url, fd: fd};
	},


	keyData: function (d) {
		var t = this;
		var data = d;
		for(var i=0; i < t.dlist.length; i++){
			try{
				data = data[t.dlist[i]];
			} catch(e){
				data = null;
			}
		}
		return data;
	},

	// ajax
	ajax: function(){
		var t = this;
		if(!t.do){
			return
		}
		if(t.$box.css('display') == 'none'){
			return;
		}
		t.$load.show();
		t.do = false;
		
		if(t.xhr){t.xhr.abort();}

		t.xhr = cmAjax({
			url: t.url,
			type: t.method,
			dataType: 'json',
			data: t.params + t.page_key + '='+t.page,
			success: function (d) {
				// 获取列表数据
				var data = t.keyData(d);

				// loading状态
				if(!data || data.length<=0){
					t.$load.find('.yes').hide();
					// 如果第一页，就显示空值时的提示
					if(t.page_ini == t.page && t.$load.find('.zero').length){
						t.$load.find('.zero').show();
					} else {
						t.$load.find('.no').show();
					}
					return;
				} else {
					t.$load.find('.yes').show();
					t.$load.find('.zero').hide();
					t.$load.find('.no').hide();
					$('.m-loading').hide();
				}

				$.each(data,function(i,v){
					t.len++;
					t.pin(v)
				})
				t.do = true;

				t.page = t.page_next();

				// 如果一屏幕显示完不能触发滚动时间时，自动给他触发一次
				if($(window).height() >= t.$ct.height()){
					$(window).trigger('scroll');
				}
			}
		})
	
	},

	pin: function(v){
		var t = this;
		// 回调方法-更改数据
		t.cb.changedata && (v = winFun[t.cb.changedata](v));
//console.log(v);
		var item = template.compile(tempcode[t.temp])(v);

		var $item = $(item).data('index',t.len);

		$item.appendTo(t.$ct);

		// 回调-插入后回调
		t.cb.insert && (v = winFun[t.cb.insert]($item,v));
	},
}

var transitionEnd=function(){var c,a=document.createElement("ceshi"),b={WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",transition:"transitionend"};for(c in b)if(void 0!==a.style[c])return b[c]}();


var popup = {
	removeTouch: function(event){
		var target = event.target;
		event.preventDefault();
	},
	ini: function () {
		
		// 蒙板css
		$("head").append('<div id="popstyle"><style>\
			.pp-mask, .pp-overlay{ \
				background-color: #fff;\
				 top: 0; left: 0; bottom: 0;\
				width: 100%; border-radius: 4px;  color: #333;\
			} \
			.pp-mask .icon_box{  width: 100%;text-align: center;position: relative;} \
			.pp-mask .icon,.pp-mask .icon_box span{width: 2.5rem;height: 2.5rem;display: inline-block;position: absolute;top: -1.25rem;background-color: rgb(255,224,0);margin-left: -1.25rem;border-radius: 50%;z-index: 9999;} \
			.pp-mask span.icon0{width: 3rem;height: 3rem;display: inline-block;position: absolute;top: -1.5rem;background-color: rgb(255,224,0);margin-left: -1.5rem;border-radius: 50%;z-index: 9999;} \
			.pp-mask span.icon0>i{width: 2.8rem;height: 2.8rem;position: absolute;top: .1rem;margin-left: -1.4rem;border-radius: 50%;} \
			.pp-mask .content_box{padding: .4rem .5rem .6rem;}\
			.pp-mask .cont0{margin-top:1.5rem;}\
			.btn_box{text-align: center;padding: 0 !important;margin: 0;width: 100%;border-top: 2px solid rgba(0,0,0,0.1); height: 1rem;line-height: 1rem;}\
			.btn_box a{font-size: .4re; width: 49.5%;border:0;height: 100%;margin: 0!important;padding: 0 ;  background: transparent;  line-height: inherit;}\
			.btn_box a.btn0_css{float:left;color: rgb(102,102,102);}\
			.btn_box a.only_btn{float:none;}\
			.pp-mask .btn_box .btn1{border-left:2px solid rgba(0,0,0,0.1);}\
			.pp-mask .content_box p{color: #333;} .pp-mask .content_box .title{overflow: hidden;height:1rem; line-height: 1rem; text-align: center;font-size: .4rem;} .pp-mask .msg{text-align:center;color:#0b0b0b !important;   font-size: .3rem;}\
			.pp-mask .msg img{width:100%;}\
			.title img{width: .4rem;margin-top: -0.1rem;vertical-align: middle;margin-right: .1rem;}\
			.pp-mask .icon_box .icon{}\
			.pp-mask .content_box  .icon0>i{background-size: cover !important;} \
			.pp-mask .content_box  .icon1{background:url("/static/wap/images/bd-norecord@2x.png")  .5rem no-repeat;background-size: 8%;} \
			.pp-mask .content_box  .icon2{background:url("/static/wap/images/choiceon@2x.png");background-size: cover;} \
			.myclass-layui-layer{     box-shadow: none; background: transparent;/*min-height:7.5rem;*/border-radius: 10px;}.myclass-layui-layer .layui-layer-content{margin-top: -0.6rem;}\
			.img-tips{background: url("/static/wap/images/img-share.png") no-repeat center;background-size: 80%;height: 3rem;}\
			.pp-mask .hidden{display:none;}\
			</div></style>'
		);
	},
	show: function (opt) {
		
		///////////////////
		var _this = this;
		var opt = opt || {};
		var btn_html = '';
		opt.type = opt.type || 1;
		opt.title = opt.title || '提示信息';
		opt.closeBtn = opt.closeBtn || 0;
		opt.shadeClose = opt.shadeClose===false? false: true;//是否允许点击遮罩关闭窗口
		opt.skin = opt.skin || 'myclass-layui-layer';//自定义样式class
		opt.area = opt.area || ['80%', ''];//窗口宽高度
		opt.icon = opt.icon==0 ? opt.icon : opt.icon || 1;
		opt.msg = opt.msg || '';
		opt.btn0_text = opt.btn0_text || '';
		opt.btn1_text = opt.btn1_text || '';
		opt.df_btn = opt.df_btn || 1;
		opt.title_status = opt.title_status || '';
		
		opt.data = typeof(opt.data) != undefined ? JSON.stringify(opt.data) : opt.data || {};//传递业务数据(json对象转字符串 )

		if(opt.btn0_text && opt.btn1_text){//两个按钮
			btn_html = '<a class="btn0 btn0_css">'+opt.btn0_text+'</a><a class="btn1">'+opt.btn1_text+'</a>';
		}else if(opt.df_btn==2 && !opt.btn0_text){//两个默认
			btn_html = '<a class="btn0">取消</a><a class="btn1">确定</a>';
		}else if(opt.df_btn==1 && !opt.btn0_text){//单个默认
			btn_html = '<a class="btn0 only_btn">确定</a>';
		}else{
			btn_html = '<a class="btn0 only_btn">'+opt.btn0_text+'</a>';
		}
        var icon_html = '';
		if(opt.icon=='1'){
			var icon_url = '/static/wap/images/bd-norecord@2x.png';
            icon_html = '<img src="'+icon_url+'"/>';
		}else if(opt.icon=='2'){
			var icon_url = '/static/wap/images/choiceon@2x.png';
            icon_html = '<img src="'+icon_url+'"/>';
		}

		layer.open({
			type: opt.type,
			title: false,
			closeBtn: opt.closeBtn,
			shadeClose: opt.shadeClose,
			skin: opt.skin,
			area: opt.area,
			shade: 0.8, //遮罩透明度
			content:'<div class="pp-mask">\
						<div class="content_box cont'+opt.icon+'">\
							<p class="title '+opt.title_status+'">'+ icon_html + opt.title+'</p>\
							<div class="msg">'+opt.msg+'</div>\
						</div>\
						<div class="layui-layer-btn layui-layer-btn- btn_box">\
							'+btn_html+'\
						</div>\
					</div>',
			btn1:function(index){//按钮1回调
				layer.close(index);
				opt.cb_btn0 && eval(opt.cb_btn0+'('+opt.data+',0)');
			},
			btn2:function(){//按钮2回调
				opt.cb_btn1 && eval(opt.cb_btn1+'('+opt.data+',1)');
			}
		});
		//图片url
		if(opt.icon==0 && opt.icon_url){
			$('.icon0>i').css({'background':"url('"+opt.icon_url+"')"});
		}
	},
	hide: function () {
		//$('#popstyle').remove();
	}

	
}

var share = function(){
	//layer.msg('点击微信右上角按钮选择分享');
	$('#show_pop').remove();
	$('<div id="show_pop" style="position: fixed;top: 0;z-index: 9999;height:100%;width: 100%;background: rgba(0,0,0,.8);"><div class="img-tips"></div></div>').appendTo($('body'));

	setTimeout(function(){
		colse_iframe_pop();
	}, 2000);
}

/*分享提示*/
$(document).on('click','.share',function(){
	share();
	//layer.msg('点击微信右上角按钮选择分享');
});

//点击关闭分享层
$(document).on('click tap','#show_pop',function(e){
	$(this).css({'display':'none'});

});

//关闭iframe窗口
var colse_iframe_pop = function(){
	var index = parent.layer.getFrameIndex(window.name); //获取窗口索引

		parent.layer.close(index);
}

//给iframe增加锚点，当没有锚点时关闭所有iframe
window.onhashchange=function () {
    var viewName=location.hash.replace('#',"");
    if(viewName==''){
        layer.closeAll();
    }
};


// 公共判断ajax
var cmAjax = function(o) {
	var index = layer.msg('请稍后...',{time: 10*1000});
	$('.mui-pull-caption').text('');//
	var _success = function () {}
	o.dataType = 'json';
	if(o.success != undefined){
		_success = o.success;
		delete o.success;
	}
	
	o.success = function(d) {
		layer.close(index);
		if(typeof(click_flag) != 'undefined'){//alert(typeof(click_flag));//2016/11/18
			click_flag = false;//点击标记
		}
		if(d && typeof(d.code) != undefined){
			if(!d || !d.data || !d.data.length){//没有数据
				$('.mui-pull-bottom-pocket').addClass('mui-visibility');
				$('.mui-pull-caption').text('没有更多数据了');
			}
			if(d.code == 1 || d.status == 1){
				_success(d);
			} else {
				if(d.code ==0 || d.status == 0 || d.error || d.error_code){//
					_success(d);
				} else {
					layer.msg('请求失败，请稍后再试');
				}
				if(d.error_code){
					switch(d.error_code){
						case -99002:
							layer.msg(d.error);
							location.reload();//刷新当前页面
//							 if(confirm("是否刷新页面")){
//								location.reload();//刷新当前页面
//							 }
							break;
						default:
							break;
					}
				} 
			} 
		} else {
			layer.msg('请求失败，请稍后再试');
		}
	}
	var _ajax = $.ajax(o);
	return _ajax;
}

//格式化日期日间戳13位
var long2time = function(long,type){
	var date = new Date(long);
	if(type==1){
		return  date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
	}else if(type==2){
		return  date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate() + " " + date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds();
	}else{
		return  date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate() + " " + date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds();
	}
}


var getelembyclass=function (selector){
	var classes=[];
	var elems=document.getElementsByTagName("*");
	var reg=new RegExp("(^|\\s)"+selector.substring(0)+"($|\\s)");
	for(var i=0;i<elems.length;i++){
		if(reg.test(elems[i].className)){
			classes.push(elems[i]);
		}
	}
	return classes;
}

//图片处理 2016/7/6
var imgage_handle=function (){

	var img = getelembyclass("main2-image");
	var dtDiv = getelembyclass("main2-cont-div1img");
	for(var i=0;i<img.length;i++){
		var dtDivs = getComputedStyle(dtDiv[i], "");
		var dtDivW = dtDivs.width;
		var imgWidth = img[i].width;
		var imgHeight = img[i].height;
		var imgScale = 0;
		var newWidth = 0;
		var newHeight = 0;
		var difference = 0;
		if (imgWidth >= imgHeight) {
			img[i].style.height = 100 + "%";
			imgScale = parseInt(dtDivW) / imgHeight;
			newWidth = imgScale * imgWidth;
			difference = (newWidth - parseInt(dtDivW)) / 2;
			img[i].style.left = -difference + "px";
		}
		else if (imgWidth < imgHeight) {
			img[i].style.width = 100 + "%";
			imgScale = parseInt(dtDivW) / imgWidth;
			newHeight = imgScale * imgHeight;
			difference = (newHeight - parseInt(dtDivW)) / 2;
			img[i].style.top = -difference + "px";
		}
	}
}

var isChinese=function (str){
	var mode = /[u00-uff]/; //匹配英文字母的正则表达式
	return !mode.test( str );
};



$(function () {
	FastClick.attach(document.body);

	popup.ini();

	//初始模板变量
	$('.u-temp').each(function(){
		tempcode[$(this).data('type')] = $(this).html();
		$(this).remove();
	});

	$('a,button,input').css({'-webkit-tap-highlight-color':'rgba(0, 0, 0, 0)'});



	/*弹窗js 2016/10/18*/
	$('.main14-top1-div2,show_popup').click(function(){
		$('#msg_box').show();
	});

	//阻止点击元素关闭
	$(".main6-erweima>div").click(function(event){
		event.stopImmediatePropagation();
	});

	$(document).on('click tap','#msg_box',function(e){
		e.stopPropagation();//取消冒泡
		//$('#main9').show();
		$(this).css({'display':'none'});

	});

	//
	$("head").append('<meta name="format-detection" content="telephone=no">');

})


//2016/8/5

/** 
 * 对日期进行格式化， 
 * @param date 要格式化的日期 
 * @param format 进行格式化的模式字符串
 *     支持的模式字母有： 
 *     y:年, 
 *     M:年中的月份(1-12), 
 *     d:月份中的天(1-31), 
 *     h:小时(0-23), 
 *     m:分(0-59), 
 *     s:秒(0-59), 
 *     S:毫秒(0-999),
 *     q:季度(1-4)
 * @return String
 * @author yanis.wang
 * @see	http://yaniswang.com/frontend/2013/02/16/dateformat-performance/
 */
template.helper('dateFormat', function (date, format,isf) {
	if(date.length==10)date = date*1000;
    if (typeof date === "string") {
        var mts = date.match(/(\/Date\((\d+)\)\/)/);
        if (mts && mts.length >= 3) {
            date = parseInt(mts[2]);
        }
    }
    date = new Date(parseInt(date));

    if (!date || date.toUTCString() == "Invalid Date") {
        return "";
    }

	var week = new Array("周日", "周一", "周二", "周三", "周四", "周五", "周六");  
    var map = {
		"Y": date.getFullYear(), //年份
        "M": date.getMonth() + 1, //月份 
        "d": date.getDate(), //日 
        "h": date.getHours(), //小时 
        "m": date.getMinutes(), //分 
        "s": date.getSeconds(), //秒 
        "q": Math.floor((date.getMonth() + 3) / 3), //季度 
        "S": date.getMilliseconds(), //毫秒 
		"w": week[date.getDay()],//同几
		"Z": c_time(date)
    };
    if(isf==1){
		 //获取js 时间戳
		var time=new Date().getTime();
		//去掉 js 时间戳后三位，与php 时间戳保持一致
		time=parseInt((time-date)/1000);

		//存储转换值 
		var s;
		if(time<60*10){//十分钟内
			return '刚刚';
		}else if((time<60*60)&&(time>=60*10)){
			//超过十分钟少于1小时
			s = Math.floor(time/60);
			return  s+"分钟前";
		}else if((time<60*60*24)&&(time>=60*60)){ 
			//超过1小时少于24小时
			s = Math.floor(time/60/60);
			return  s+"小时前";
		}else if((time<60*60*24*3)&&(time>=60*60*24)){ 
			//超过1天少于3天内
			s = Math.floor(time/60/60/24);
			return s+"天前";
		}else{ 
			//超过3天
			//var date= new Date(parseInt(date) * 1000);
			//return date.getFullYear()+"/"+(date.getMonth()+1)+"/"+date.getDate();
		}
	}
    format = format.replace(/([YyMdwhmsqSZ])+/g, function(all, t){
        var v = map[t];
        if(v !== undefined){
            if(all.length > 1){
                v = '0' + v;
                v = v.substr(v.length-2);
            }
            return v;
        }
        else if(t === 'y'){
            return (date.getFullYear() + '').substr(4 - all.length);
        }
        return all;
    });

    return format;
});


function c_time(time){
	var Digital=new Date(time)
	var hours=Digital.getHours()
	var minutes=Digital.getMinutes()
	var seconds=Digital.getSeconds()
	var dn="AM" 
	if (hours>12){
		dn="PM"
		hours=hours-12
	}
	return dn;
}


//距离换算
template.helper('distance_hs', function (m) {  
    if(m<=100){
		return m+'m';
	}else if(m>100 && m<=1000){
		return '<1km';
	}else if(m>1000 && m<=3000){
		return '<3km';
	}else{
		return '>3km';
	}
}); 

//2017/1/14
function show_time(time){
	var Digital=new Date(time)
	var hours=Digital.getHours()
	var minutes=Digital.getMinutes()
	var seconds=Digital.getSeconds()
	var dn="AM" 
	if (hours>12){
		dn="PM"
		hours=hours-12
	}
	if (hours==0)hours=12
	if (minutes<=9)minutes="0"+minutes
	if (seconds<=9)seconds="0"+seconds

	return minutes+':'+seconds+''+dn;
}

template.helper('gd_time', function (end_time,status) { 

	var end_time = new Date(parseInt(end_time));
	var nowTime = new Date();
	var nMS=end_time.getTime() - nowTime.getTime();// + difference;//求时间差
	var myD=Math.floor(nMS/(1000 * 60 * 60 * 24));
	var myH=Math.floor(nMS/(1000*60*60)) % 24;
	var myM=Math.floor(nMS/(1000*60)) % 60;
	if(myD<=9)myD = '0'+myD;
	if(myH<=9)myH = '0'+myH;
	if(myM<=9)myM = '0'+myM;
	if(myD>= 0){
		var str = myD+"天"+myH+"时"+myM+"分";
	}
	return str;

}); 

//截取字符串
template.helper('changetext', function (s,n) {
	return s.slice(0, n).replace(/([^x00-xff])/g, "$1a").slice(0, n).replace(/([^x00-xff])a/g, "$1");
});

//七牛图片格式化
template.helper('qiniu_image_format', function (url,str_format,is_replace) {
	if(!url)return '';
	if(url.indexOf("http") >=0 || url.indexOf("https") >=0 ){
		if(url.indexOf("?") >=0 && str_format && is_replace==0){
			url = url+'&'+str_format;
		}else if(url.indexOf("?") >=0 && str_format && is_replace==1){
			var arr = new Array();
            arr = url.split('?');
            url = arr[0]+'?'+str_format;
		}else{
			url = url+'?'+str_format;
		}
		return url;
	}
});

//格式化字符串输出文本
template.helper('format_txt', function (feed_text_str) {  
	var feed_text_arr = [];
	feed_text_arr = feed_text_str.split('#');
	if(feed_text_arr.length>=3){
		return feed_text_arr[0]+'<i style="color:rgb(255, 224, 0);font-style:normal;">#'+feed_text_arr[1]+'#</i>'+feed_text_arr[2];
	}else{
		return feed_text_str;
	}
});

// 上传到七牛
function uploadQiniu (file,finput_id,qiniu_call_back_func,is_write) {
	//loading..
	$('body').after('<div class="flow-load" ><div class="zero m-empty"><div class="m-loading" style="display:block;"><div class="box"><div class="u-loading"><i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i> <i></i></div><div class="txt">数据加载中...</div></div></div><div class="p" style="display:none;">没有数据</div></div></div>');
	$('.m-loading').show();
	$('.m-loading').find('.txt').html('上传中...')
//压缩
	lrz(file, {
            width:400,
			quality: 0.7,
            before: function() {
                console.log('压缩开始');
            },
            fail: function(err) {
                console.error(err);
            },
            always: function() {
                console.log('压缩结束');
            },
            done: function (results) {
				$.ajax({
					url: SITE_URL+'/active/upload_token',
					dataType: 'json',
					data: {
					data_type: 'json',
					//_token: '{{csrf_token()}}',
					},
					success: function (d) {
						var Qiniu_UploadUrl = "http://upload.qiniu.com/putb64/-1";
						var xhr = new XMLHttpRequest();
						xhr.open('POST', Qiniu_UploadUrl, true);
						
						xhr.upload.onprogress = function (ev) {  
							var percent = 0;  
							if(ev.lengthComputable) { 
								percent = 100 * ev.loaded/ev.total;  
								console.log(percent);
							}  
						} 

						xhr.onreadystatechange = function(response) {
							if (xhr.readyState == 4 && xhr.status == 200 && xhr.responseText != "") {
								$('.m-loading').hide();
								var imgSrc = d.domain+'/'+ JSON.parse(xhr.responseText).key;
								if(finput_id && is_write!=1)$('#'+finput_id).val(imgSrc);
								//qiniu_call_back_func 动态传方法（变量）
								typeof qiniu_call_back_func == 'function' && qiniu_call_back_func(imgSrc,finput_id);//上传成功回调	

							} else if (xhr.status != 200 && xhr.responseText) {
								alert('err')
							}
						};

						var base64_content = results.base64;
						base64_content = base64_content.replace(/^data:image\/(jpeg|png|gif);base64,/, '');
						xhr.setRequestHeader("Content-Type", "application/octet-stream");
						xhr.setRequestHeader("Authorization",'UpToken '+d.token);
						xhr.send(base64_content);
					}
				})

			}
		});
}


// 手机短信验证
function mobile_verify(stype,mobile,cb) {
	var _url = SITE_URL+'/user/send_verify_code';//
	var cb = cb||"\'\'";
	if(!mobile){
		layer.msg('手机号不能为空');
		return false;
	}
    // 发送验证码
    var i = 60;
    var time_;
    if (i != 60) {
        return;
    }
    i = 60;
    $(".phone_code").attr("onclick", "");
    time_ = window.setInterval(function() {
        i--;
        $(".phone_code").text(i + "s重发");
        if (i == 0) {
            $(".phone_code").text("重试");
            $(".phone_code").attr("onclick", "mobile_verify("+stype+","+mobile+","+cb+")");
            window.clearInterval(time_);
            return;
        }
    }, 1000);

	if(mobile){
		cmAjax({
			type:'POST',
			url:_url,
			data:{ajax:1,mobile:mobile,type:stype},
			dataType:'JSON',
			beforeSend:function(){
				layer.msg('发送中...');
			},
			success: function (d) {
				//console.log(result);
				if(d.code == 1){//成功操作
					layer.msg('验证码已发送,请留意！');
				}else{
					$(".phone_code").attr("onclick", "mobile_verify("+stype+","+mobile+")");
					$(".phone_code").text('发送验证码');
					window.clearInterval(time_);
					console.log(typeof(cb));
					if(cb && typeof(cb) != 'string'){
						typeof cb=='function' && cb(d);
						return false;
					}else{
						layer.msg(d.error);//
					}
				}
			}
		});
	}else{
		layer.msg('手机号码不能为空');
	}

    return;

}

//绑定手机验证
var check_bind_mobile = function(d,get_mobile){
	if(d){//强制绑定，二次操作
		var is_repeat_bind = d.is_repeat_bind;
		var mobile = get_mobile = d.mobile;
		var verify_code = d.verify_code;
		var type = d.type;
	}else{
		var is_repeat_bind = get_mobile!='' ? 1: 0;//;
		var mobile = $('#mobile').val();
		var verify_code = $('#verify_code').val();
		var type = $('#type').val();
	}
	if(!mobile){
		layer.msg('手机号不能为空');
		return false;
	}else if(!verify_code){
		layer.msg('验证码不能为空');
		return false;
	}
	cmAjax({
		type: "GET",
		url:SITE_URL+'/user/bind_mobile?ajax=1',
		data:{mobile:mobile,verify_code:verify_code,type:type,is_repeat_bind:is_repeat_bind},
		dataType:'json',
		success: function(d) {
			if(d.status==1){
				if(get_mobile!=''){
					if(is_repeat_bind==1){//强制绑定，回调参数
						d.btype=2;
						d.is_repeat_bind = 2;//
						d.mobile = mobile;
						d.verify_code=verify_code;
						d.type=1;//手机绑定类型
					}else{//首次
						d.btype=1;
					}
					window.parent.callback_parent_function(d);//回调父类
				}else{
					window.parent.show_box(d);//验证新手机操作
				}
			}else{
				layer.msg(d.error);
			}
		},
		error: function(d) {
			layer.msg('网络异常');
		}
	});
}


//倒计时
//服务器时间serverTime
var countdown = function(serverTime){
	var dateTime = new Date(); 
     
    setInterval(function(){ 
      $(".endtime").each(function(){ 
        var obj = $(this); 
        var endTime = new Date(parseInt(obj.attr('value'))); 
        var nowTime = new Date(); 
        var nMS=endTime.getTime() - nowTime.getTime(); 
        var myD=Math.floor(nMS/(1000 * 60 * 60 * 24)); //天 
        var myH=Math.floor(nMS/(1000*60*60)) % 24; //小时 
        var myM=Math.floor(nMS/(1000*60)) % 60; //分钟 
        var myS=Math.floor(nMS/1000) % 60; //秒 
        var myMS=Math.floor(nMS/100) % 10; //拆分秒 
		if(myD<=9)myD = '0'+myD;
		if(myH<=9)myH = '0'+myH;
		if(myM<=9)myM = '0'+myM;
		if(myS<=9)myS = '0'+myS;
        if(myD>= 0){ 
            var str = '<span class="date">'+myD+'</span> 天 <span class="date">'+myH+'</span> : <span class="date">'+myM+'</span> : <span class="date">'+myS+'</span>'; 
        }else{ 
            //var str = '<span class="date">已结束</span>';    
			return ;
        } 
        obj.html(str); 
      }); 
    }, 1000); //每个0.1秒执行一次 
}

//消息向上滚动 
var top_msg_box = function(){
	var settime;
	$(".top_msg_box").hover(function () {
		clearInterval(settime);
	}, function () {
		settime = setInterval(function () {
			var $first = $(".top_msg_box ul:first");     //选取div下的第一个ul 而不是li；
			var height = $first.find("li:first").height();      //获取第一个li的高度，为ul向上移动做准备；
			$first.animate({ "marginTop": -height + "px" }, 0, function () {//600 缓冲时间
				$first.css({ marginTop: 0 }).find("li:first").appendTo($first); //设置上边距为零，为了下一次移动做准备
			});
		}, 5000);
	}).trigger("mouseleave");       //trigger()方法的作用是触发被选元素的制定事件类型
}

//判断滚动条方法
var check_scroll = function( fn ) {
	var beforeScrollTop = document.body.scrollTop,
		fn = fn || function() {};
	window.addEventListener("scroll", function() {
		var afterScrollTop = document.body.scrollTop,
			delta = afterScrollTop - beforeScrollTop;
		if( delta === 0 ) return false;
		if ($(document).scrollTop()<=0){
			fn("top_end");//滚动条已经到达顶部为0
		}else if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
			fn("down_end");//滚动条已经到达底部为
		}else{
			fn( delta > 0 ? "down" : "up" );
		}
		beforeScrollTop = afterScrollTop;
	}, false);
}

//回到微信公众号界面
function closepage(){
	WeixinJSBridge.call('closeWindow');
}

//视频截图
function vload(obj) {
    $(obj).removeAttr("poster");
    var vimg = $("<img/>")[0];
    captureImage(obj, vimg);
    $(obj).after(vimg);
    $(obj).remove();
}
function captureImage(video, output) { //截图 
    var scale = 0.8; //缩放
    try {
        var videocanvas = $("<canvas/>")[0];
        videocanvas.width = video.videoWidth * scale;
        videocanvas.height = video.videoHeight * scale;
        videocanvas.getContext('2d').drawImage(video, 0, 0, videocanvas.width, videocanvas.height);
        output.src = videocanvas.toDataURL("image/png");
        delete videocanvas;
    } catch(e) {
        output.src = "/static/images/logo.jpg";
    }
}
//end /