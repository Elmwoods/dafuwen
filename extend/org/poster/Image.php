<?php

//
class Image
{

/**
 * 生成缩略图
 * @param string     源图绝对完整地址{带文件名及后缀名}
 * @param string     目标图绝对完整地址{带文件名及后缀名}
 * @param int        缩略图宽{0:此时目标高度不能为0，目标宽度为源图宽*(目标高度/源图高)}
 * @param int        缩略图高{0:此时目标宽度不能为0，目标高度为源图高*(目标宽度/源图宽)}
 * @param int        是否裁切{宽,高必须非0}
 * @param int/float  缩放{0:不缩放, 0<this<1:缩放到相应比例(此时宽高限制和裁切均失效)}
 * @return boolean
 */
function img2thumb($src_img, $dst_img, $width = 75, $height = 75, $cut = 0, $proportion = 0)
{
    if(!is_file($src_img))
    {
        return false;
    }
    $ot = $this->fileext($dst_img);
    $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
    $srcinfo = getimagesize($src_img);
    $src_w = $srcinfo[0];
    $src_h = $srcinfo[1];
    $type  = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
    $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);
 
    $dst_h = $height;
    $dst_w = $width;
    $x = $y = 0;
 
    /**
     * 缩略图不超过源图尺寸（前提是宽或高只有一个）
     */
    if(($width> $src_w && $height> $src_h) || ($height> $src_h && $width == 0) || ($width> $src_w && $height == 0))
    {
        $proportion = 1;
    }
    if($width> $src_w)
    {
        $dst_w = $width = $src_w;
    }
    if($height> $src_h)
    {
        $dst_h = $height = $src_h;
    }
 
    if(!$width && !$height && !$proportion)
    {
        return false;
    }
    if(!$proportion)
    {
        if($cut == 0)
        {
            if($dst_w && $dst_h)
            {
                if($dst_w/$src_w> $dst_h/$src_h)
                {
                    $dst_w = $src_w * ($dst_h / $src_h);
                    $x = 0 - ($dst_w - $width) / 2;
                }
                else
                {
                    $dst_h = $src_h * ($dst_w / $src_w);
                    $y = 0 - ($dst_h - $height) / 2;
                }
            }
            else if($dst_w xor $dst_h)
            {
                if($dst_w && !$dst_h)  //有宽无高
                {
                    $propor = $dst_w / $src_w;
                    $height = $dst_h  = $src_h * $propor;
                }
                else if(!$dst_w && $dst_h)  //有高无宽
                {
                    $propor = $dst_h / $src_h;
                    $width  = $dst_w = $src_w * $propor;
                }
            }
        }
        else
        {
            if(!$dst_h)  //裁剪时无高
            {
                $height = $dst_h = $dst_w;
            }
            if(!$dst_w)  //裁剪时无宽
            {
                $width = $dst_w = $dst_h;
            }
            $propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
            $dst_w = (int)round($src_w * $propor);
            $dst_h = (int)round($src_h * $propor);
            $x = ($width - $dst_w) / 2;
            $y = ($height - $dst_h) / 2;
        }
    }
    else
    {
        $proportion = min($proportion, 1);
        $height = $dst_h = $src_h * $proportion;
        $width  = $dst_w = $src_w * $proportion;
    }
 
    $src = $createfun($src_img);
    $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
 
    /*$dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
    //这一句一定要有
    imagesavealpha($dst, true);
    //拾取一个完全透明的颜色,最后一个参数127为全透明
    $bg = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefill($dst, 0, 0, $bg);*/

    if(function_exists('imagecopyresampled'))
    {
        imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
    }
    else
    {
        imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
    }
	if($ot == 'png'){
		$level = 6;
	}else{
		$level = 70;
	}
    $otfunc($dst, $dst_img,$level);
    imagedestroy($dst);
    imagedestroy($src);
    return true;
}

function fileext($file)
{
    return pathinfo($file, PATHINFO_EXTENSION);
}

/**
 * 图片加水印（适用于png/jpg/gif格式）
 *
 * @author flynetcn
 *
 * @param $srcImg 原图片
 * @param $waterImg 水印图片
 * @param $savepath 保存路径
 * @param $savename 保存名字
 * @param $positon 水印位置
 * 1:顶部居左, 2:顶部居右, 3:居中, 4:底部局左, 5:底部居右
 * @param $alpha 透明度 -- 0:完全透明, 100:完全不透明
 *
 * @return 成功 -- 加水印后的新图片地址
 *          失败 -- -1:原文件不存在, -2:水印图片不存在, -3:原文件图像对象建立失败
 *          -4:水印文件图像对象建立失败 -5:加水印后的新图片保存失败
 */
function img_water_mark($srcImg, $waterImg, $savepath=null, $savename=null, $positon=3, $alpha=100)
{
    $temp = pathinfo($srcImg);
    $name = $temp['basename'];
    $path = $temp['dirname'];
    $exte = $temp['extension'];
    $savename = $savename ? $savename : $name;
    $savepath = $savepath ? $savepath : $path;
    $savefile = $savepath .'/'. $savename;
    $srcinfo = @getimagesize($srcImg);
    if (!$srcinfo) {
        return -1; //原文件不存在
    }
    $waterinfo = @getimagesize($waterImg);
    if (!$waterinfo) {
        return -2; //水印图片不存在
    }
    $srcImgObj = $this->image_create_from_ext($srcImg);
    if (!$srcImgObj) {
        return -3; //原文件图像对象建立失败
    }
    $waterImgObj = $this->image_create_from_ext($waterImg);
    if (!$waterImgObj) {
        return -4; //水印文件图像对象建立失败
    }
    switch ($positon) {
    //1顶部居左
    case 1: $x=$y=0; break;
    //2顶部居右
    case 2: $x = $srcinfo[0]-$waterinfo[0]; $y = 0; break;
    //3居中
    case 3: $x = ($srcinfo[0]-$waterinfo[0])/2; $y = ($srcinfo[1]-$waterinfo[1])/2; break;
    //4底部居左
    case 4: $x = 0; $y = $srcinfo[1]-$waterinfo[1]; break;
    //5底部居右
    case 5: $x = $srcinfo[0]-$waterinfo[0]; $y = $srcinfo[1]-$waterinfo[1]; break;
	//logo
//	case 6: $x = 281; $y = 265; break;
//	//二维码
//	case 7: $x = 240; $y = 814; break;
	case 6: $x = 320; $y = 302; break;
	//二维码
	case 7: $x = 257; $y = 919; break;

    default: $x=$y=0;
    }
    $this->imagecopymerge_alpha($srcImgObj, $waterImgObj, $x, $y, 0, 0, $waterinfo[0], $waterinfo[1], $alpha);
    switch ($srcinfo[2]) {
    case 1: imagegif($srcImgObj, $savefile); break;
    case 2: imagejpeg($srcImgObj, $savefile); break;
    case 3: imagepng($srcImgObj, $savefile); break;
    default: return -5; //保存失败
    }
    imagedestroy($srcImgObj);
    imagedestroy($waterImgObj);
    return $savefile;
}

//生成文字水印
function appendTextMark($dst_path,$save_path,$file_dir,$nickname){
	@header("Content-type:text/html;charset=utf-8"); 
	//获取图片信息
	$info = getimagesize($dst_path);
	$main = imagecreatefromjpeg ( $dst_path );        
    $width = imagesx ( $main );  
    $height = imagesy ( $main );

	//获取图片扩展名
	$type = image_type_to_extension($info[2],false);
	//动态的把图片导入内存中
	$fun = "imagecreatefrom{$type}";
	$image = $fun($dst_path);
	//指定字体颜色
	$col = imagecolorallocatealpha($image,0,0,0,50);
	//指定字体内容
	$content = '约战人：'.$nickname;
	//$content =  iconv("GBK", "UTF-8", $content);//解决乱码问题
	//$content =  mb_convert_encoding($content,'UTF-8','GB2312');
	//给图片添加文字
	$font = $file_dir.'msyh.ttf';
	$fontSize = 23;//18号字体  

    $fontWidth = imagefontwidth ( $fontSize );  
    $fontHeight = imagefontheight ( $fontSize );  
    $abslength =  $this->abslength($content);
	//var_dump($abslength);exit;
	 $temp = imagettfbbox($fontSize,0,$font,$content);
	 $font_w = $temp[2] - $temp[6];//字体占的宽度   
     $font_h = $temp[3] - $temp[7];//字体占的高度
	 //var_dump($w,$h);exit;
    //$textWidth = $fontWidth * $abslength;//mb_strlen(iconv('gbk', 'utf-8', $content), 'utf-8'); 
    $x = ceil ( ($width - $font_w) / 2 );//计算文字的水平位置  
//var_dump($x);exit;
	imagettftext($image,$fontSize,0,$x,470,$col,$font,$content);

	//指定输入类型
	//header('Content-type:'.$info['mime']);
	//动态的输出图片到浏览器中
	$func = "image{$type}";
	$func($image,$save_path);
	//销毁图片
	imagedestroy($image);
}

/**
* 可以统计中文字符串长度的函数
* @param $str 要计算长度的字符串
* @param $type 计算长度类型，0(默认)表示一个中文算一个字符，1表示一个中文算两个字符
*
*/
function abslength($str)
{
    if(empty($str)){
        return 0;
    }

    if(function_exists('mb_strlen')){
		if (!preg_match("/[/x7f-/xff]/", $str)) {
			return mb_strlen($str,'utf-8')*3;
		}
        return mb_strlen($str,'utf-8')*2;
    }
    else {
        preg_match_all("/./u", $str, $ar);
        return count($ar[0]);
    }
}
 
function image_create_from_ext($imgfile)
{
    $info = getimagesize($imgfile);
    $im = null;
    switch ($info[2]) {
    case 1: $im=imagecreatefromgif($imgfile); break;
    case 2: $im=imagecreatefromjpeg($imgfile); break;
    case 3: $im=imagecreatefrompng($imgfile); break;
    }
    return $im;
}

//生成圆角图片
function create_radius_pic($pic_list,$save_pic_path,$bg_w,$bg_h){
	$pic_list    = array_slice($pic_list, 0, 9); // 只操作前9个图片  

	//$bg_w    = 105; // 背景图片宽度  
	//$bg_h    = 105; // 背景图片高度  

	$background = imagecreatetruecolor($bg_w,$bg_h); // 背景图片  
	$color   = imagecolorallocate($background, 202, 201, 201); // 为真彩色画布创建白色背景，再设置为透明  
	imagefill($background, 0, 0, $color);  
	imageColorTransparent($background, $color);   

	$pic_count  = count($pic_list);  
	$lineArr    = array();  // 需要换行的位置  
	$space_x    = 3;  
	$space_y    = 3;  
	$line_x  = 0;  
	switch($pic_count) {  
	case 1: // 正中间  
		$start_x    = intval(0);  // 开始位置X  
		$start_y    = intval(0);  // 开始位置Y  
		$pic_w   = intval($bg_w); // 宽度  
		$pic_h   = intval($bg_h); // 高度  
		break;  
	} 
	// 产生一个弧角图片
	function get_lt_rounder_corner($radius) {
		// $radius：弧角图片的大小
		$img		= imagecreatetruecolor($radius, $radius);
		$bgcolor	= imagecolorallocate($img, 202, 201, 201);
		$fgcolor	= imagecolorallocate($img, 0, 0, 0);
		imagefill($img, 0, 0, $bgcolor);
		// $radius,$radius：以图像的右下角开始画弧
		// $radius*2, $radius*2：已宽度、高度画弧
		// 180, 270：指定了角度的起始和结束点
		// fgcolor：指定颜色
		imagefilledarc($img, $radius, $radius, $radius*2, $radius*2, 180, 270, $fgcolor, IMG_ARC_PIE);
		// 设置颜色为透明
		imagecolortransparent($img, $fgcolor);
		return $img;
	}
	foreach( $pic_list as $k=>$pic_path ) {  
		$kk = $k + 1;  
		if ( in_array($kk, $lineArr) ) {  
			$start_x    = $line_x;  
			$start_y    = $start_y + $pic_h + $space_y;  
		}  
		$pathInfo    = pathinfo($pic_path);  
		switch( strtolower($pathInfo['extension']) ) {  
			case 'jpg':  
			case 'jpeg':  
				$imagecreatefromjpeg    = 'imagecreatefromjpeg';  
			break;  
			case 'png':  
				$imagecreatefromjpeg    = 'imagecreatefrompng';  
			break;  
			case 'gif':  
			default:  
				$imagecreatefromjpeg    = 'imagecreatefromstring';  
				$pic_path    = file_get_contents($pic_path);  
			break;  
		}  
		$resource   = $imagecreatefromjpeg($pic_path);
		
		$image_width	= imagesx($resource);
		$image_height	= imagesy($resource);

		// 图片圆角处理
		$radius		= 50;
		// lt(左上角)
		$lt_corner	= get_lt_rounder_corner($radius);
		imagecopymerge($resource, $lt_corner, 0, 0, 0, 0, $radius, $radius, 100);
		// lb(左下角)
		$lb_corner	= imagerotate($lt_corner, 90, 0);
		imagecopymerge($resource, $lb_corner, 0, $image_height - $radius, 0, 0, $radius, $radius, 100);
		// rb(右上角)
		$rb_corner	= imagerotate($lt_corner, 180, 0);
		imagecopymerge($resource, $rb_corner, $image_width - $radius, $image_height - $radius, 0, 0, $radius, $radius, 100);
		// rt(右下角)
		$rt_corner	= imagerotate($lt_corner, 270, 0);
		imagecopymerge($resource, $rt_corner, $image_width - $radius, 0, 0, 0, $radius, $radius, 100);
		
		// $start_x,$start_y copy图片在背景中的位置  
		// 0,0 被copy图片的位置  
		// $pic_w,$pic_h copy后的高度和宽度  
		imagecopyresized($background,$resource,$start_x,$start_y,0,0,$pic_w,$pic_h,imagesx($resource),imagesy($resource)); // 最后两个参数为原始图片宽度和高度，倒数两个参数为copy时的图片宽度和高度  
		$start_x    = $start_x + $pic_w + $space_x;  
	}  

	//header("Content-type: image/png");  
	imagepng($background,$save_pic_path);  
	
	imagedestroy($background);

    

}

/**
 * 处理圆角图片
 * @param  string  $imgpath 源图片路径
 * @param  integer $radius  圆角半径长度默认为15,处理成圆型
 * @return [type]           [description]
 */
public function radius_img($imgpath = '',$save_pic_path, $radius = 15) {
    $ext     = pathinfo($imgpath);
    $src_img = null;
    switch ($ext['extension']) {
    case 'jpg':
        $src_img = imagecreatefromjpeg($imgpath);
        break;
    case 'png':
        $src_img = imagecreatefrompng($imgpath);
        break;
    }
    $wh = getimagesize($imgpath);
    $w  = $wh[0];
    $h  = $wh[1];
    // $radius = $radius == 0 ? (min($w, $h) / 2) : $radius;
    $img = imagecreatetruecolor($w, $h);
    //这一句一定要有
    imagesavealpha($img, true);
    //拾取一个完全透明的颜色,最后一个参数127为全透明
    $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
    imagefill($img, 0, 0, $bg);
    $r = $radius; //圆 角半径
    for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
            $rgbColor = imagecolorat($src_img, $x, $y);
            if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                //不在四角的范围内,直接画
                imagesetpixel($img, $x, $y, $rgbColor);
            } else {
                //在四角的范围内选择画
                //上左
                $y_x = $r; //圆心X坐标
                $y_y = $r; //圆心Y坐标
                if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
                //上右
                $y_x = $w - $r; //圆心X坐标
                $y_y = $r; //圆心Y坐标
                if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
                //下左
                $y_x = $r; //圆心X坐标
                $y_y = $h - $r; //圆心Y坐标
                if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
                //下右
                $y_x = $w - $r; //圆心X坐标
                $y_y = $h - $r; //圆心Y坐标
                if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }
    }

    imagepng($img,$save_pic_path,6);
    imagedestroy($img);
}


//生成海报
public function create_poster($beijing,$nickname,$logourl,$erweimaurl,$file_save_path,$poster_data=[]){
	
    $beijing = imagecreatefromjpeg($beijing);  
    $logourl = imagecreatefrompng($logourl); 
    $erweimaurl = imagecreatefrompng($erweimaurl);  
    $image_3 = imageCreatetruecolor(imagesx($beijing),imagesy($beijing));  
    $color = imagecolorallocate($image_3, 255, 255, 255);  
    imagefill($image_3, 0, 0, $color);  
    imageColorTransparent($image_3, $color);  
    imagecopyresampled($image_3,$beijing,0,0,0,0,imagesx($beijing),imagesy($beijing),imagesx($beijing),imagesy($beijing));     
    //字体颜色  
    $white = imagecolorallocate($image_3, 111, 255, 255);  
    $rqys = imagecolorallocate($image_3, 255, 255, 255);  //字体颜色
    //$black = imagecolorallocate($image_3,120,84,26);  
    $font = EXTEND_PATH."/org/poster/msyh.ttf";  //写的文字用到的字体。字体最好用系统有得，否则会包charmap的错，这是黑体   
    //imagettftext设置生成图片的文本      
    imagettftext($image_3,28,0,$poster_data['display_name']['left'],$poster_data['display_name']['top']+30,$rqys,$font,$nickname);  
    //imagecopymerge($image_3,$logourl, 10,10,0,0,150,150,100);//左，上，右，下，宽度，高度，透明度  
    $this->imagecopymerge_alpha($image_3,$logourl, $poster_data['avatar']['left'],$poster_data['avatar']['top'],0,0,$poster_data['avatar']['width'],$poster_data['avatar']['height'],100);//左，上，右，下，宽度，高度，透明度 
    //imagecopymerge($image_3,$erweimaurl, 120,100,0,0,imagesx($erweimaurl),imagesy($erweimaurl), 100);  
    $this->imagecopymerge_alpha($image_3,$erweimaurl, $poster_data['qrcode']['left'],$poster_data['qrcode']['top'],0,0,imagesx($erweimaurl),imagesy($erweimaurl), 100);  
    //生成图片  
    //imagepng($image_3);//在浏览器上显示  
    imagejpeg($image_3,$file_save_path,60);//保存到本地  
    imagedestroy($image_3);  
	
	return true;
}

 function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
    $opacity=$pct;
    // getting the watermark width
    $w = imagesx($src_im);
    // getting the watermark height
    $h = imagesy($src_im);
    
    // creating a cut resource
    $cut = imagecreatetruecolor($src_w, $src_h);

    //这一句一定要有
    imagesavealpha($cut, true);
    //拾取一个完全透明的颜色,最后一个参数127为全透明
    $bg = imagecolorallocatealpha($cut, 255, 255, 255, 127);
    imagefill($cut, 0, 0, $bg);

    // copying that section of the background to the cut
    imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
    // inverting the opacity
    //$opacity = 100 - $opacity;
    
    // placing the watermark now
    imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
    imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
}

//下载图片
 function http_get_data($url,$save_filename) {  
	$ch = curl_init ();  
	curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );  
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );  
	curl_setopt ( $ch, CURLOPT_URL, $url );  
	ob_start ();  
	curl_exec ( $ch );  
	$return_content = ob_get_contents ();  
	ob_end_clean ();  
	  
	$return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );  
	
	$fp= @fopen($save_filename,"a"); //将文件绑定到流    
	fwrite($fp,$return_content); //写入文件  
	fclose($fp);
	if($return_code!==200){
		return false;
	}else{
		return true;
	}
} 


}