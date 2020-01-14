var lunhui = {

	//成功弹出层
	success: function(message,url){
		layer.msg(message, {icon: 6,time:2000}, function(index){
            layer.close(index);
            window.location.href=url;
        });
	},

	// 错误弹出层
	error: function(message) {
        layer.msg(message, {icon: 5,time:2000}, function(index){
            layer.close(index);
        });       
    },

	// 确认弹出层
    confirm : function(id,url) {
        layer.confirm('确认删除此条记录吗?', {icon: 3, title:'提示'}, function(index){
	        $.getJSON(url, {'id' : id}, function(res){
	            if(res.code == 1){
	                layer.msg(res.msg,{icon:1,time:1500,shade: 0.1});
	                Ajaxpage()
	            }else{
	                layer.msg(res.msg,{icon:0,time:1500,shade: 0.1});
	            }
	        });
	        layer.close(index);
	    })
    },

    //状态
    status : function(id,url){
	    $.post(url,{id:id},function(data){	         
	        if(data.code==1){
	            var a='<span class="label label-danger">禁用</span>'
	            $('#zt'+id).html(a);
	            layer.msg(data.msg,{icon:2,time:1500,shade: 0.1,});
	            return false;
	        }else{
	            var b='<span class="label label-info">开启</span>'
	            $('#zt'+id).html(b);
	            layer.msg(data.msg,{icon:1,time:1500,shade: 0.1,});
	            return false;
	        }         	        
	    });
	    return false;
	}


}
							


//dom加载完成后执行的js
;$(function(){

	//全选的实现
	$(".check-all").click(function(){
		$(".ids").prop("checked", this.checked);
	});
	$(".ids").click(function(){
		var option = $(".ids");
		option.each(function(i){
			if(!this.checked){
				$(".check-all").prop("checked", false);
				return false;
			}else{
				$(".check-all").prop("checked", true);
			}
		});
	});
	
	$('.data-table .confirm').click(function(){
		if(window.confirm("确认要执行删除操作吗？")){
			$.get($(this).attr('href'));
			$(this).parents('tr').fadeOut();
			return false;
		}else{
			return false;
		}	
	})

    //ajax get请求
    $('.ajax-get').click(function(){
        var target;
        var that = this;
        if ( $(this).hasClass('confirm') ) {
            if(!confirm('确认要执行该操作吗?')){
                return false;
            }
        }
        if ( (target = $(this).attr('href')) || (target = $(this).attr('url')) ) {
            $.get(target).success(function(data){
            	if(typeof data=='string')data = JSON.parse(data); //2018-07-06 11:47:33
                if (data.code==1 || data.status==1) {
                    if (data.url) {
                        updateAlert(data.msg + ' 页面即将自动跳转~','alert-success');
                    }else{
                        updateAlert(data.msg,'alert-success');
                    }
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else if( $(that).hasClass('no-refresh')){
                            $('#top-alert').find('button').click();
                        }else{
                            location.reload();
                        }
                    },1500);
                }else{
					if(data.status==0){
						if(data.result!=undefined){
							$.each(data.result, function(i, val) {  
							    data.msg = val;
							}); 
						}	
						updateAlert(data.msg,'alert-error');
					}else{
						updateAlert(data.msg);
					}
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else{
                            $('#top-alert').find('button').click();
                        }
                    },1500);
                }
            });

        }
        return false;
    });

    //ajax post submit请求
    $('.ajax-post').click(function(){
        var target,query,form;
        var target_form = $(this).attr('target-form');
        var that = this;
        var nead_confirm=false;
        if( ($(this).attr('type')=='submit') || (target = $(this).attr('href')) || (target = $(this).attr('url')) ){
            form = $('.'+target_form);

            if ($(this).attr('hide-data') === 'true'){//无数据时也可以使用的功能
            	form = $('.hide-data');
            	query = form.serialize();
            }else if (form.get(0)==undefined){
            	return false;
            }else if ( form.get(0).nodeName=='FORM' ){
                if ( $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                if($(this).attr('url') !== undefined){
                	target = $(this).attr('url');
                }else{
                	target = form.get(0).action;
                }
                query = form.serialize();
            }else if( form.get(0).nodeName=='INPUT' || form.get(0).nodeName=='SELECT' || form.get(0).nodeName=='TEXTAREA') {
                form.each(function(k,v){
                    if(v.type=='checkbox' && v.checked==true){
                        nead_confirm = true;
                    }
                })
                if ( nead_confirm && $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                query = form.serialize();
            }else{
                if ( $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                query = form.find('input,select,textarea').serialize();
            }
            $(that).addClass('disabled').attr('autocomplete','off').prop('disabled',true);
            $.post(target,query).success(function(data){
				$(that).removeClass('disabled').prop('disabled',false);
				if(typeof data=='string')data = JSON.parse(data); //2018-07-06 11:47:33
                if (data.code==1 || data.status==1) {
					if($(that).hasClass('dialog_submit')){
						//对话框中的提交动作
						
						if (data.url) {
							window.parent.location.href=data.url;
						}else{
							window.parent.location.reload();
						}
						window.parent.$.Dialog.close();
					}else{
						if (data.url) {
							updateAlert(data.msg + ' 页面即将自动跳转~','alert-success');
						}else{
							updateAlert(data.msg ,'alert-success');
						}
						setTimeout(function(){
							if (data.url) {
								location.href=data.url;
							}else if( $(that).hasClass('no-refresh')){
								$('#top-alert').find('button').click();
							}else{
								location.reload();
							}
						},1500);
					}
                }else{console.log(data);
					if($(that).hasClass('dialog_submit')){
						alert(data.msg);
					}else{
						if(data.status==0){
							if(data.result!=undefined){
								var j=0;
								$.each(data.result, function(i, val) {  
								    if(j==0)data.msg = val;
								    j++;
								}); 
							}	
							updateAlert(data.msg,'alert-error');
						}else{
							updateAlert(data.msg);
						}
						//
						setTimeout(function(){
							if (data.url) {
								location.href=data.url;
							}else{
								$('#top-alert').find('button').click();
							}
						},1500);
					}
                }
            });
        }
        return false;
    });

	/**顶部警告栏*/
	var content = $('#main');
	var top_alert = $('#top-alert');
	top_alert.find('.close').on('click', function () {
		top_alert.removeClass('block').slideUp(200);
		// content.animate({paddingTop:'-=55'},200);
	});

    window.updateAlert = function (text,c) {
		text = text||'default';
		c = c||false;
		if ( text!='default' ) {
            top_alert.find('.alert-content').text(text);
			if (top_alert.hasClass('block')) {
			} else {
				top_alert.addClass('block').slideDown(200);
				// content.animate({paddingTop:'+=55'},200);
			}
		} else {
			if (top_alert.hasClass('block')) {
				top_alert.removeClass('block').slideUp(200);
				// content.animate({paddingTop:'-=55'},200);
			}
		}
		if ( c!=false ) {
            top_alert.removeClass('alert-error alert-warn alert-info alert-success').addClass(c);
		}
		setTimeout(function(){
			if($('#top-alert').is(":visible")){
				$('#top-alert').find('.close').click();
			}
		},2000)
	};

    //按钮组
    (function(){
        //按钮组(鼠标悬浮显示)
        $(".btn-group").mouseenter(function(){
            var userMenu = $(this).children(".dropdown-menu");
            var icon = $(this).find(".btn i");
            icon.addClass("btn-arrowup").removeClass("btn-arrowdown");
            userMenu.show();
            clearTimeout(userMenu.data("timeout"));
        }).mouseleave(function(){
            var userMenu = $(this).children(".dropdown-menu");
            var icon = $(this).find(".btn i");
            icon.removeClass("btn-arrowup").addClass("btn-arrowdown");
            userMenu.data("timeout") && clearTimeout(userMenu.data("timeout"));
            userMenu.data("timeout", setTimeout(function(){userMenu.hide()}, 100));
        });

        //按钮组(鼠标点击显示)
        // $(".btn-group-click .btn").click(function(){
        //     var userMenu = $(this).next(".dropdown ");
        //     var icon = $(this).find("i");
        //     icon.toggleClass("btn-arrowup");
        //     userMenu.toggleClass("block");
        // });
        $(".btn-group-click .btn").click(function(e){
            if ($(this).next(".dropdown-menu").is(":hidden")) {
                $(this).next(".dropdown-menu").show();
                $(this).find("i").addClass("btn-arrowup");
                e.stopPropagation();
            }else{
                $(this).find("i").removeClass("btn-arrowup");
            }
        })
  //       $(".dropdown").click(function(e) {
  //           e.stopPropagation();
  //       });
		// /*
  //       $(document).click(function() {
  //           $(".dropdown").hide();
  //           $(".btn-group-click .btn").find("i").removeClass("btn-arrowup");
  //       });
		// */
		
		// $('.dropdown').hover(function(){
		// 		$(this).find('.dropdown-menu').show();
		// 		$(this).find('.dropdown-toggle').addClass('hover');
		// 	}
		// 	,function(){
		// 		$(this).find('.dropdown-menu').hide();
		// 		$(this).find('.dropdown-toggle').removeClass('hover');
		// 	});
    })();
	//侧栏
	if(!$('#main-container .sidebar').html()){
		$('#main-container .main_body').width(1200);
	}

    // 独立域表单获取焦点样式
    $(".text").focus(function(){
        $(this).addClass("focus");
    }).blur(function(){
        $(this).removeClass('focus');
    });
    $("textarea").focus(function(){
        $(this).closest(".textarea").addClass("focus");
    }).blur(function(){
        $(this).closest(".textarea").removeClass("focus");
    });
	
	//侧栏
	if(!$('#main-container .sidebar').html()){
		$('#main-container .main_body').width(1200);
	}

	
	$('.upload-pre-item22 em').click(function() {
		if(confirm('确认删除？')){
			$(this).parent().remove();
		}
	});
	
	//初始化复制按钮
	$('.list_copy_link').each(function(index, element) {
        var id = $(element).attr('id');
		$.WeiOms.initCopyBtn(id);
    });
	
});

/* 上传图片预览弹出层 */
$(function(){
    $(window).resize(function(){
        var winW = $(window).width();
        var winH = $(window).height();
		/*
        $(".upload-img-box").click(function(){
        	//如果没有图片则不显示
        	if($(this).find('img').attr('src') === undefined){
        		return false;
        	}
            // 创建弹出框以及获取弹出图片
            var imgPopup = "<div id=\"uploadPop\" class=\"upload-img-popup\"></div>"
            var imgItem = $(this).find(".upload-pre-item2").html();

            //如果弹出层存在，则不能再弹出
            var popupLen = $(".upload-img-popup").length;
            if( popupLen < 1 ) {
                $(imgPopup).appendTo("body");
                $(".upload-img-popup").html(
                    imgItem + "<a class=\"close-pop\" href=\"javascript:;\" title=\"关闭\"></a>"
                );
            }

            // 弹出层定位
            var uploadImg = $("#uploadPop").find("img");
            var popW = uploadImg.width();
            var popH = uploadImg.height();
            var left = (winW -popW)/2;
            var top = (winH - popH)/2 + 50;
            $(".upload-img-popup").css({
                "max-width" : winW * 0.9,
                "left": left,
                "top": top
            });
        });

        // 关闭弹出层
        $("body").on("click", "#uploadPop .close-pop", function(){
            $(this).parent().remove();
        });
		*/
    }).resize();

    // 缩放图片
    function resizeImg(node,isSmall){
        if(!isSmall){
            $(node).height($(node).height()*1.2);
        } else {
            $(node).height($(node).height()*0.8);
        }
    }
	
	//选择文字
	$(".selectRange").parent().on('mouseup',function (e) {
		var text = "";
        if (document.selection) {
            text = document.selection.createRange().text;
        }else if (window.getSelection()) {
            text = window.getSelection();
        }
        if (text!= "") {
			var url = $(this).find('.selectRange').data('url');	
			showAddToBlack(e.pageX + 10,e.pageY + 10,text,url);
        }
    });
	$(document).click(function(e){
		if(e && !(e.target == $('.selectRange')[0]) && $(addToBlackBtn).is(":visible") && !(e.target == $('#addToBlackBtn a')[0])){
			$('#addToBlackBtn').hide();
		}
	})
	//列表显示大图
	var listPicZoom = $('<div class="list_pic_zoom" style="position:absolute; border:1px solid #ddd; background:#fff;padding:10px;display:none; z-index:10000;"><img width="200" height="200" src=""/></div>');
	$('body').append(listPicZoom);
	$('.data-table .list_img').hover(function(){
		var left = $(this).offset().left+50;
		var top = $(this).offset().top-225;
		$('img',listPicZoom).attr('src',$(this).attr('src'));
		listPicZoom.css({left:left,top:top}).show();
	},
	function(){
		listPicZoom.hide();
		$('img',listPicZoom).attr('src','');
	})
})
var addToBlackBtn,uploadObj;
function showAddToBlack(x,y,t,url){
	if(!addToBlackBtn){
		addToBlackBtn = $("<div id='addToBlackBtn'><a href='javascript:;' style='background:#44b549;color:#fff;padding:2px 4px;' class='btn'>添加至黑名单</a></div>");
		$("body").append(addToBlackBtn);
		$(addToBlackBtn).click(function(){
			$(this).hide();
			var word = $(addToBlackBtn).data('text');	
			var url = $(addToBlackBtn).data('url');	
			//console.log(url);
			var data = {word:word+""};
			if(confirm('确认要将“'+word+'”加入黑名单吗?')){
				$.post(url,data,function(d){
					if(d.result=='success'){
						 updateAlert(d.msg ,'alert-success');
					}else{
						 updateAlert(d.msg);
					}
					
				});
			}
		})
	}
	$(addToBlackBtn).data('text',t);
	$(addToBlackBtn).data('url',url)
	setTimeout(function(){
		$("#addToBlackBtn").css({
			"top": y + "px",
			"left": x + "px",
			"position": "absolute"
		}).show();
	},200)
	
}

//标签页切换(无下一步)
function showTab() {
    $(".tab-nav li").click(function(){
        var self = $(this), target = self.data("tab");
        self.addClass("current").siblings(".current").removeClass("current");
        window.location.hash = "#" + target.substr(3);
        $(".tab-pane.in").removeClass("in");
        $("." + target).addClass("in");
    }).filter("[data-tab=tab" + window.location.hash.substr(1) + "]").click();
}
$(function(){
	var $window = $(window), $subnav = $("#subnav"), url;
	$window.resize(function(){
		$("#main").css("min-height", $window.height() - 130);
	}).resize();

	/* 表单获取焦点变色 */
	$("form").on("focus", "input", function(){
		$(this).addClass('focus');
	}).on("blur","input",function(){
				$(this).removeClass('focus');
			});
	$("form").on("focus", "textarea", function(){
		$(this).closest('label').addClass('focus');
	}).on("blur","textarea",function(){
		$(this).closest('label').removeClass('focus');
	});
	$('a').each(function(index, element) {
       if($(this).text()=="预览")$(this).attr('target','_blank');
    });
	
	//$('.sidebar').css('min-height',$('body').height());
	//控制聚焦
	$('.sidenav_sub li').each(function(){
		if($(this).hasClass('active')){
			var cate_id = $(this).data('id');
			$('.top_nav li').each(function(){
				if($(this).data('id')==cate_id){
					$(this).addClass('active');
				}
			})
		}
	})
});
//标签页切换(有下一步)
function nextTab() {
     $(".tab-nav li").click(function(){
        var self = $(this), target = self.data("tab");
        self.addClass("current").siblings(".current").removeClass("current");
        window.location.hash = "#" + target.substr(3);
        $(".tab-pane.in").removeClass("in");
        $("." + target).addClass("in");
        showBtn();
    }).filter("[data-tab=tab" + window.location.hash.substr(1) + "]").click();

    $("#submit-next").click(function(){
        $(".tab-nav li.current").next().click();
        showBtn();
    });
}

// 下一步按钮切换
function showBtn() {
    var lastTabItem = $(".tab-nav li:last");
    if( lastTabItem.hasClass("current") ) {
        $("#submit").removeClass("hidden");
        $("#submit-next").addClass("hidden");
    } else {
        $("#submit").addClass("hidden");
        $("#submit-next").removeClass("hidden");
    }
}
function change_event(obj){	
	var hiderel = $(obj).attr('toggle-data');
	if(hiderel=='' || hiderel==undefined)	return false;
	
	var arr = new Array();
    arr = hiderel.split(",");
    $.each(arr, function (index, tx) {	
		var arr2 = new Array();
		arr2 = tx.split("@");
		console.log(arr2)
		if(arr2[1]=='hide'){
		    $('.toggle-'+arr2[0]).hide();
		}else{
			$('.toggle-'+arr2[0]).show();
		}
	});
	
}

function playSound(id,obj){
    var audio = document.getElementById(id);
    if(audio.paused){
        audio.play();
        $(obj).find('img').attr('src',IMG_PATH+'/icon_sound_play.gif');
        audio.addEventListener('ended', function () {  
//          alert('over');
            $(obj).find('img').attr('src',IMG_PATH+'/icon_sound.png');
        }, false);
        return;
    }
    audio.pause();
    $(obj).find('img').attr('src',IMG_PATH+'/icon_sound.png');
    
}

function parseSecondToMinAndSecond(value){
	var mins = Math.floor( value / 60 );
	var seconds = ( value - mins*60 );
	return (mins < 10 ? "0"+mins : mins) + ":" + ( seconds == 0 ? "00" : seconds );
}
function parseSecondToMinAndSecond2(value){
	var mins = Math.floor( value / 60 );
	var seconds = ( value - mins*60 );
	return (mins < 10 ? "0"+mins : mins) + ":00";
}
//上传图片组件
function initUploadImg(opts){
	$(".upload-img-view").dragsort({
	    itemSelector: ".upload-pre-item22", dragSelector: ".upload-pre-item22", dragBetween: false, placeHolderTemplate: "<div class='upload-pre-item22'></div>",dragSelectorExclude:'em',dragEnd: function() {$(".upload-pre-item22").attr('style','')}
	});
	$uploadDom = $('.uploadrow2');
	if(opts && opts.uploadDom){
		$uploadDom = opts.uploadDom;
	}
	$uploadDom.each(function(index, obj) {
		$(obj).click(function(){
			uploadImgDialog(obj,opts);
		});
		
	});
	
}
function uploadImgDialog(obj,opts){
	var maxCount = parseInt($(obj).data('max'));
	var field = $(obj).attr('rel');
	var btnWidth = parseInt($(obj).attr('data-btnWidth'));
	var btnHeiht = parseInt($(obj).attr('data-btnHeiht'));

	uploadObj = obj;
	$uploadHtml = '<div><div class="upload_dialog" style="height:520px;overflow-y:hidden;overflow-x:hidden;"><div><iframe id="goodsIframe" name="goodsIframe" style="height:520px;width:100%; border:none" border="0" src="'+UPLOAD_DIALOG_URL+'?max='+maxCount+'&field='+field+'&btnWidth='+btnWidth+'&btnHeiht='+btnHeiht+'"></iframe></div></div></div>';
	$.Dialog.open("上传图片",{width:800,height:560},$uploadHtml);
}



//上传附件组件
function initUploadFile(callback,is_cb){
	$(".upload_file").each(function(index, obj) {
		setTimeout(function(){
			var name = $(obj).find('input[type="hidden"]').attr('name');
			$("#upload_file_"+name).uploadify({
				"height"          : 30,
				"swf"             : JS_PATH+"/plugins/uploadify/uploadify.swf",
				"fileObjName"     : "download",
				"buttonText"      : "上传附件",
				"uploader"        : UPLOAD_FILE,
				"width"           : 120,
				'removeTimeout'	  : 1,
				"onUploadSuccess" : function(file, data, response) {
					if(callback && is_cb===0){
					}else{
						onUploadFileSuccess(file, data, name);
					}
					if(callback) callback(data,name);
				}
			});
		},300);
	});
}								
function onUploadFileSuccess(file, data, name){
	var data = $.parseJSON(data);
	if(data.code){
		$("input[name="+name+"]").val(data.id);
		$("input[name="+name+"]").parent().find('.upload-img-box').html(
			"<div class=\"upload-pre-file\"><span class=\"upload_icon_all\"></span>" + data.name + "</div>"
		);
	} else {
		//console.log(data);
		updateAlert(data.msg);
		setTimeout(function(){
			$('#top-alert').find('button').click();
			//$(that).removeClass('disabled').prop('disabled',false);
		},1500);
	}
}

//颜色拾取
function simpleColorPicker(_this,callback){
	var currentColor = $(this).find('input').val();
	var left = $(_this).offset().left;
	var top = $(_this).offset().top;
	var height = $(_this).height();
	var colors = ["#55BD47","#10AD61","#35A4DE","#3D78DA","#9058CB","#DE9C33","#EBAC16","#F9861F","#E75735","#D54036"];
	var colorEles = "";
	for(var i=0;i<colors.length;i++){
		colorEles += "<span data-color='"+colors[i]+"' style='background-color:"+colors[i]+"'></span>";
	}
	var $html = $("<div class='simpleColorBox'>"+colorEles+"</div>");
	$html.css({'top':top+height,'left':left});
	$('body').append($html); 
	$('span',$html).click(function(){
		var color = $(this).data('color');
		$(_this).css({'background':color});
		$(_this).find('input').val(color);
		$html.remove();
		if(callback)callback(color);
	})
}



(function(){
	/* 选择图文素材 */
	function openSelectAppMsg(dataUrl,callback,title){
		var count=1;
		if(count==1){
			dataUrl = dataUrl+'?isAjax=ajax&isRadio=1&random='+Math.random();
		}else{
			dataUrl = dataUrl+'?isAjax=1&random='+Math.random();
		}
		var $contentHtml = $('<div class="appmsg_dialog" style="padding:10px; max-height:560px;overflow-y:auto;overflow-x:hidden;">'+
			'<iframe id="usersIframe" name="usersIframe" style="height:530px;width:100%; border:none" border="0" src="'+dataUrl+'"><ul class="mt_10"><center><br/><br/><br/><img src="'+IMG_PATH+'/loading.gif"/></center></ul></iframe></div>');
		$.Dialog.open(title?title:"选择图文素材",{width:1000,height:640},$contentHtml);
		$.ajax({
			url:dataUrl,
			data:{'type':'ajax'},
			dataType:'html',
			success:function(data){
				$data = $(data);
				$('ul',$contentHtml).html($data);
				$data.find('.material_list').masonry({
					// options
					itemSelector : '.appmsg_li'
					//columnWidth : 308
				  });
				$('iframe').load(function () {
				$("iframe").contents().find("li").on('click',function(){
					callback(this);
				});
			});
			}
		})
	}
		/* 选择文本素材 */
	function openSelectAppText(dataUrl,callback,title){
		var count=1;
		if(count==1){
			dataUrl = dataUrl+'?isAjax=ajax&isRadio=1&random='+Math.random();
		}else{
			dataUrl = dataUrl+'?isAjax=1&random='+Math.random();
		}
		var $contentHtml = $('<div><div class="goods_dialog" style="padding:10px; overflow-y:hidden;overflow-x:hidden;"><div class="mt_10"><iframe id="usersIframe" name="usersIframe" style="height:530px;width:100%; border:none" border="0" src="'+dataUrl+'"></iframe></div></div><div class=" btn_bar"><a href="javascript:;" class="btn btn-primary confirm_btn">确定</a>&nbsp;&nbsp;<a href="javascript:;" class="btn btn-danger border-btn cancel_btn">取消</a></div></div>');
		$.Dialog.open(title?title:"选择文本素材",{width:1000,height:640},$contentHtml);
		
		$('.cancel_btn',$contentHtml).click(function(){
			$.Dialog.close();
		})
		$('.confirm_btn',$contentHtml).click(function(){
			var trs = $(window.frames["usersIframe"].document,$contentHtml).find("table tr");
			var usresList = new Array();
			//var obj = new Object();
			trs.each(function(index, element) {
				if($(element).find(".ids").prop("checked")){
					usresList.id = $(element).find("input").val();
					usresList.content = $(element).find('td[type="content"]').text();
					//usresList.push(obj);
				}
            });
			if(usresList.length>count){
				alert("只能选择"+count+"个选项");
				return;
			}
			callback(usresList);
			$.Dialog.close();
		})
	}
	function initCopyBtn(id){
		var copyClient = new ZeroClipboard( document.getElementById(id) );
		copyClient.on( "ready", function( readyEvent ) {
		  copyClient.on( "aftercopy", function( event ) {
			updateAlert('复制成功!,请粘贴使用','alert-success');
		  } );
		} );
	}

    /* 选择门店 */
	function openSelectShops(dataUrl,callback){
		var $contentHtml = $('<div><div class="goods_dialog" style="padding:10px; height:530px;overflow-y:auto;overflow-x:hidden;"><div class="mt_10"><iframe id="shopsIframe" name="shopsIframe" style="height:530px;width:100%; border:none" border="0" src="'+dataUrl+'?isAjax=ajax"></iframe></div></div><div class="btn_bar"><a href="javascript:;" class="btn confirm_btn">确定</a>&nbsp;&nbsp;<a href="javascript:;" class="border-btn cancel_btn">取消</a></div></div>');
		$.Dialog.open("选择门店",{width:640,height:640},$contentHtml);
		
		$('.cancel_btn',$contentHtml).click(function(){
			$.Dialog.close();
		})
		$('.confirm_btn',$contentHtml).click(function(){
			var trs = $(window.frames["shopsIframe"].document,$contentHtml).find("table tr");
			var shopsList = new Array();
			trs.each(function(index, element) {
				if($(element).find(".ids").prop("checked")){
					var obj = new Object();
					obj.id = $(element).find("input").val();
					obj.name = $(element).find('td[type="name"]').text();
					obj.address = $(element).find('td[type="address"]').text();
					shopsList.push(obj);
				}
            });
			callback(shopsList);
			$.Dialog.close();
		})
	}	
	/*
	 * 
	 */
	function openSelectLists(dataUrl,count,title,callback){
		if(count==1){
			dataUrl = dataUrl+'?isAjax=ajax&isRadio=1&random='+Math.random();
		}else{
			dataUrl = dataUrl+'?isAjax=1&random='+Math.random();
		}
		var $contentHtml = $('<div><div class="goods_dialog" style="padding:10px; height:530px;overflow-y:hidden;overflow-x:hidden;"><div class="mt_10"><iframe id="usersIframe" name="usersIframe" style="height:530px;width:100%; border:none" border="0" src="'+dataUrl+'"></iframe></div></div><div class="btn_bar"><a href="javascript:;" class="btn btn-primary confirm_btn">确定</a>&nbsp;&nbsp;<a href="javascript:;" class="btn btn-danger border-btn cancel_btn">取消</a></div></div>');
		$.Dialog.open(title,{width:1000,height:610},$contentHtml);
		
		$('.cancel_btn',$contentHtml).click(function(){
			$.Dialog.close();
		})
		$('.confirm_btn',$contentHtml).click(function(){
			var trs = $(window.frames["usersIframe"].document,$contentHtml).find("table tr");
			var usresList = new Array();
			trs.each(function(index, element) {
				if($(element).find(".ids").prop("checked")){
					var obj = new Object();
					obj.id = $(element).find("input").val();
					obj.nickname = $(element).find('td[type="nickname"]').text();
					obj.img = $(element).find('img')?$(element).find('img').attr('src'):"";
					obj.sex_name = $(element).find('td[type="sex_name"]').text();
					obj.group = $(element).find('td[type="group"]').text();
					usresList.push(obj);
				}
            });
			if(usresList.length>count){
				alert("只能选择"+count+"个选项");
				return;
			}
			callback(usresList);
			$.Dialog.close();
		})
	}
	
	/* 选择用户 */
	/*
	* dataUrl 请求数据的链接
	* callback(data) data:选择的用户数组
	* count 0 表示无限制
	*/
	function openSelectUsers(dataUrl,count,callback){
		if(count==1){
			dataUrl = dataUrl+'?isAjax=ajax&isRadio=1&random='+Math.random();
		}else{
			dataUrl = dataUrl+'?isAjax=1&random='+Math.random();
		}
		var $contentHtml = $('<div><div class="goods_dialog" style="padding:10px; height:530px;overflow-y:hidden;overflow-x:hidden;"><div class="mt_10"><iframe id="usersIframe" name="usersIframe" style="height:530px;width:100%; border:none" border="0" src="'+dataUrl+'"></iframe></div></div><div class="btn_bar"><a href="javascript:;" class="btn btn-primary confirm_btn">确定</a>&nbsp;&nbsp;<a href="javascript:;" class="btn btn-danger border-btn cancel_btn">取消</a></div></div>');
		$.Dialog.open("选择用户",{width:1000,height:610},$contentHtml);
		
		$('.cancel_btn',$contentHtml).click(function(){
			$.Dialog.close();
		})
		$('.confirm_btn',$contentHtml).click(function(){
			var trs = $(window.frames["usersIframe"].document,$contentHtml).find("table tr");
			var usresList = new Array();
			trs.each(function(index, element) {
				if($(element).find(".ids").prop("checked")){
					var obj = new Object();
					obj.id = $(element).find("input").val();
					obj.nickname = $(element).find('td[type="nickname"]').text();
					obj.img = $(element).find('img')?$(element).find('img').attr('src'):"";
					obj.sex_name = $(element).find('td[type="sex_name"]').text();
					obj.group = $(element).find('td[type="group"]').text();
					obj.openid = $(element).find('input[name="openid"]').val();
					usresList.push(obj);
				}
            });
			if(count>0 && usresList.length>count){
				alert("只能选择"+count+"个用户");
				return;
			}
			callback(usresList);
			$.Dialog.close();
		})
	}
	//选择单用户
	function selectSingleUser(dataUrl,name){
		$.WeiOms.openSelectUsers(dataUrl,1,function(data){
			if(data && data.length>0){
				for(var i=0;i<data.length;i++){
					var $html = $('<div class="item" onClick="$.WeiOms.selectSingleUser(\''+dataUrl+'\',\''+name+'\')">'+
								'<img src="'+data[i].img+'"/>'+
								'<span class="name">'+data[i].nickname+'</span>'+
								'<input type="hidden" name="'+name+'" value="'+data[i].id+'"/>'+
							'</div>');
					$('#userList').html($html);
				}
				
			}
		})
	}
	//选择单用户
	function selectMutiUser(dataUrl,count,name){
		$.WeiOms.openSelectUsers(dataUrl,count,function(data){
			if(data && data.length>0){
				for(var i=0;i<data.length;i++){
					var $html = $('<div class="item" onClick="$.WeiOms.selectSingleUser('+dataUrl+','+name+')">'+
								'<img src="'+data[i].img+'"/>'+
								'<input type="hidden" name="'+name+'[]" value="'+data[i].id+'"/>'+
								'<em class="del" onClick="$(this).parent().remove();">X</em>'+
								'<span class="name">'+data[i].nickname+'</span>'+
							'</div>');
					$html.insertBefore($('#userList .common_add_btn'));
				}
				
			}
		})
	}
	//banner
	//通用banner
	function banner(id,isAuto,delayTime,wh){
		if($(id).find('ul').html()==undefined)return;
		if(!wh)wh = 2;
		var screenWidth = $(id).width();
		var count = $(id).find('li') .size();
		$(id).find('ul').width(screenWidth*count);
		$(id).find('li').height(screenWidth/wh);
		$(id).height(screenWidth/wh);
		$(id).find('li').width(screenWidth).height(screenWidth/wh);
		$(id).find('li img').width(screenWidth).height(screenWidth/wh);
		$(id).find('li .title').css({'width':'98%','padding-left':'2%'})
		// With options
		$(id).find('li .title').each(function(index, element) {
            $(this).text($(this).text().length>15?$(this).text().substring(0,15)+" ...":$(this).text());
        });
		var flipsnap = Flipsnap(id+' ul');
		flipsnap.element.addEventListener('fstouchend', function(ev) {
			$(id).find('.identify em').eq(ev.newPoint).addClass('cur').siblings().removeClass('cur');
		}, false);
		$(id).find('.identify em').eq(0).addClass('cur')
		if(isAuto){
			var point = 1;
			setInterval(function(){
				//console.log(point);
				flipsnap.moveToPoint(point);
				$(id).find('.identify em').eq(point).addClass('cur').siblings().removeClass('cur');
				if(point+1==$(id).find('li').size()){
					point=0;
				}else{
					point++;
					}
				
				},delayTime)
		}
	}
	//多图banner
	function mutipicBanner(id,isAuto,delayTime,num){
		if($(id).find('ul').html()==undefined)return;  
		var screenWidth = $(id).width();
		var count = $(id).find('li') .size();
		var aNew=Math.ceil(count/num-1)  ;
		$(id).find('ul').width(screenWidth*count/num);
		$(id).find('li').width(screenWidth/num*0.9375)
		$(id).find('li').css('marginLeft',screenWidth/num*0.03125+'px') //li的margin
		$(id).find('li').css('marginRight',screenWidth/num*0.03125+'px')
		$(id).find('li').css('marginTop',screenWidth/num*0.03125+'px')
		$(id).find('li .title').css({'width':'98%','padding-left':'2%'})
		// With options
		$(id).find('li .title').each(function(index, element) {
            $(this).text($(this).text().length>15?$(this).text().substring(0,15)+" ...":$(this).text());
        });  
    	var points='';
		for (var i = 0; i <= aNew; i++) {			
			
			points += '<em></em>';
		};	
		$(id).find('.pointer').html(points);
		var flipsnap = Flipsnap(id+' ul',{
			distance:screenWidth ,
			maxPoint: Math.ceil(count/num-1) 
		});
		flipsnap.element.addEventListener('fstouchend', function(ev) {
			$(id).find('.mutipic_banner_identify em').eq(ev.newPoint).addClass('cur').siblings().removeClass('cur');
		}, false);
		$(id).find('.mutipic_banner_identify em').eq(0).addClass('cur')
		if(isAuto){
			var point = 1;
			setInterval(function(){
				//console.log(point);
				flipsnap.moveToPoint(point);
				$(id).find('.mutipic_banner_identify em').eq(point).addClass('cur').siblings().removeClass('cur');
				if(point+1==$(id).find('li').size()){
					point=0;
				}else{
					point++;
					}
				
				},delayTime)
		}
		//console.log($(id).html())
		
	}
	function moneyFormat(value){
		var float = parseFloat(value);
		float = Math.ceil(float*100);
		float = float/100;
		if(Number(float) === float && float % 1 === 0){
			float = float+".00";
		}
		return float;
	}
	/* 框架形式打开提交对话框 */
	function openSubmitDialog(title,url,w,h){
        if(url.indexOf("?") >= 0){
            url += "&isAjax=ajax";
        }else{
            url += "?isAjax=ajax";
        }
		var $contentHtml = $('<div><div class="goods_dialog" style="padding:0 10px;height:'+h+'px;overflow:hidden"><div class="mt_10"><iframe id="goodsIframe" name="goodsIframe" style="height:'+h+'px;width:100%; border:none" border="0" src="'+url+'?isAjax=ajax"></iframe></div></div></div>');
		$.Dialog.open(title,{width:w,height:h+40},$contentHtml);
	}
	//danmu
	/* 弹幕 */
	function initDanmu(flyBox,height){
		flyBox.danmu({
			left: 0,  
			top:'auto',  //区域的起始位置x坐标
			bottom: 'auto' ,  //区域的起始位置y坐标
			height: height, //区域的高度 
			width:  flyBox.width(), //区域的宽度 
			zindex :10, //div的css样式zindex
			speed:60000, //弹幕速度，飞过区域的毫秒数 
			//danmuss:danmuss, //danmuss对象，运行时的弹幕内容 
		});
		flyBox.danmu('danmu_start');
		queryComment(flyBox,0);
		setInterval(function(){
			queryComment(flyBox,1);
		},1000*10)
	}

	var WeiOms = {
		openSelectAppMsg:openSelectAppMsg,
		openSelectAppText:openSelectAppText,
		initCopyBtn:initCopyBtn,
		uploadImgDialog:uploadImgDialog,
		openSelectShops:openSelectShops,
		openSelectUsers:openSelectUsers,
		initBanner:banner,
		initMutipicBanner:mutipicBanner,
		moneyFormat:moneyFormat,
		selectSingleUser:selectSingleUser,
		selectMutiUser:selectMutiUser,
		openSubmitDialog:openSubmitDialog,
		openSelectLists:openSelectLists,
		initDanmu:initDanmu
		
	}
	$.extend({WeiOms:WeiOms});
})();
/* base */
// Array.prototype.remove = function() {
//     var what, a = arguments, L = a.length, ax;
//     while (L && this.length) {
//         what = a[--L];
//         while ((ax = this.indexOf(what)) !== -1) {
//             this.splice(ax, 1);
//         }
//     }
//     return this;
// };





//
$(document).ready(function(){

var urlstr = location.href;　　　　//获取浏览器的url
var urlstatus=false;　　　　　　　　//标记
　//导航高亮
$(".nav-tabs a").each(function () {
	if ((urlstr + '/').indexOf($(this).attr('href')) > -1&&$(this).attr('href')!='') {
		console.log($(this))
		$(this).parent().addClass('active'); urlstatus = true;
	} else {
		$(this).removeClass('active');
	}
});
//当前样式保持
if (!urlstatus) {$(".nav-tabs a").eq(0).addClass('active'); }



//全选的实现
$('.check-all').on('ifChecked', function (event) {
    $('input[name="ids[]"]').iCheck('check');
});
$('.check-all').on('ifUnchecked', function (event) {
    $('input[name="ids[]"]').iCheck('uncheck');
});



});
