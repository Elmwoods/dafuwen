/**
 * Created by Administrator on 2018/11/14.
 */
var Base64 = {
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
    encode: function(e) {
        var t = "";
        var n, r, i, s, o, u, a;
        var f = 0;
        e = Base64._utf8_encode(e);
        while (f < e.length) {
            n = e.charCodeAt(f++);
            r = e.charCodeAt(f++);
            i = e.charCodeAt(f++);
            s = n >> 2;
            o = (n & 3) << 4 | r >> 4;
            u = (r & 15) << 2 | i >> 6;
            a = i & 63;
            if (isNaN(r)) {
                u = a = 64
            } else if (isNaN(i)) {
                a = 64
            }
            t = t + this._keyStr.charAt(s) + this._keyStr.charAt(o) + this._keyStr.charAt(u) + this._keyStr.charAt(a)
        }
        return t
    },
    decode: function(e) {
        var t = "";
        var n, r, i;
        var s, o, u, a;
        var f = 0;
        e=e.replace(/[^A-Za-z0-9+/=]/g,"");
        while (f < e.length) {
            s = this._keyStr.indexOf(e.charAt(f++));
            o = this._keyStr.indexOf(e.charAt(f++));
            u = this._keyStr.indexOf(e.charAt(f++));
            a = this._keyStr.indexOf(e.charAt(f++));
            n = s << 2 | o >> 4;
            r = (o & 15) << 4 | u >> 2;
            i = (u & 3) << 6 | a;
            t = t + String.fromCharCode(n);
            if (u != 64) {
                t = t + String.fromCharCode(r)
            }
            if (a != 64) {
                t = t + String.fromCharCode(i)
            }
        }
        t = Base64._utf8_decode(t);
        return t
    },
    _utf8_encode: function(e) {
        e = e.replace(/rn/g, "n");
        var t = "";
        for (var n = 0; n < e.length; n++) {
            var r = e.charCodeAt(n);
            if (r < 128) {
                t += String.fromCharCode(r)
            } else if (r > 127 && r < 2048) {
                t += String.fromCharCode(r >> 6 | 192);
                t += String.fromCharCode(r & 63 | 128)
            } else {
                t += String.fromCharCode(r >> 12 | 224);
                t += String.fromCharCode(r >> 6 & 63 | 128);
                t += String.fromCharCode(r & 63 | 128)
            }
        }
        return t
    },
    _utf8_decode: function(e) {
        var t = "";
        var n = 0;
        var r = c1 = c2 = 0;
        while (n < e.length) {
            r = e.charCodeAt(n);
            if (r < 128) {
                t += String.fromCharCode(r);
                n++
            } else if (r > 191 && r < 224) {
                c2 = e.charCodeAt(n + 1);
                t += String.fromCharCode((r & 31) << 6 | c2 & 63);
                n += 2
            } else {
                c2 = e.charCodeAt(n + 1);
                c3 = e.charCodeAt(n + 2);
                t += String.fromCharCode((r & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
                n += 3
            }
        }
        return t
    }
}



//普通等待ajax
function a_load(url,data,type,success,error){
    var e = layer.open({
        type:2
    });
    //console.log(Base64.decode(data));
    $.ajax({
        url:url,
        data:{data:data},
        type:type,
        dataType : 'json',
        success:function(data){
            layer.close(e);
            //data.data  = Base64.decode(data.data);
            success(data);
        }
    });
}

//已登录函数处理
function token_do(data,success){
    data.data  = Base64.decode(data.data);
    //令牌失效重新登录
    if(data.status == 304){
        layer.open({
            type:1,
            content:data.message
        });
        window.setTimeout(function(){
            window.location.href = data.data
        },2500);
    }else if(data.status == 302){
        window.location.href = data.data
    }else if(data.status == 200){
        success(data);
    }
}


function __ajax(url,data,mehod,success){
    //回调处理
    var r_success = function(data){
        token_do(data,success)
    }
    a_load(url,data,mehod,r_success);
}

function __eajax(url,data,mehod,success){
    //回调处理
    var r_success = function(data){
        token_do(data,success)
    }
    var _data = Base64.encode(data);
    a_load(url,_data,mehod,r_success);
}

function local_ajax(url,data,method,success){
    $.ajax({
        url:url,
        data:{data:data},
        type:type,
        method:method,
        dataType : 'json',
        success:function(data){
            success(data);
        }
    });
}

(function ($) {
    $.fn.extend({
        //已登录解密表单传输处理
        //success 程序函数代码处理
        //is_encode 是否加密传输
        "tokenform": function(success,is_encode){
            var url = $(this).attr('action');
            var type = $(this).attr('type');
            var data = $(this).serialize();
            type = typeof type == undefined ? 'get' : type;
            is_encode = typeof is_encode == undefined ? 0 : is_encode;

            if(is_encode == 1)
                data = Base64.encode(data);
            var r_success = function(data){
                token_do(data,success)
            }

            a_load(url,data,type,r_success);
        },
    });
})(jQuery);
