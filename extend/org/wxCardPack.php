<?php
//微信卡券
//
class wxCardPack {

    public function __construct() {

    }

    /*     * *****************************************************
     *      微信卡券：获取颜色
     * ***************************************************** */

    public function wxCardColor($wxAccessToken) {
		$wxColorsList = \think\Cache::get('Cache_wxColorsList');
		if (!empty($wxColorsList) && is_array($wxColorsList)) {
		    return $wxColorsList;
		}
		$url = "https://api.weixin.qq.com/card/getcolors?access_token=" . $wxAccessToken;
		$result = $this->https_request($url);
		if (isset($result['colors']) && !empty($result['colors'])) {
		    \think\Cache::set('Cache_wxColorsList', $result['colors']);
		    return $result['colors'];
		}
		return array(
		    Array('name' => 'Color010', 'value' => '#63b359'), Array('name' => 'Color020', 'value' => '#2c9f67'),
		    Array('name' => 'Color030', 'value' => '#509fc9'), Array('name' => 'Color040', 'value' => '#5885cf'),
		    Array('name' => 'Color050', 'value' => '#9062c0'), Array('name' => 'Color060', 'value' => '#d09a45'),
		    Array('name' => 'Color070', 'value' => '#e4b138'), Array('name' => 'Color080', 'value' => '#ee903c'),
		    Array('name' => 'Color081', 'value' => '#f08500'), Array('name' => 'Color082', 'value' => '#a9d92d'),
		    Array('name' => 'Color090', 'value' => '#dd6549'), Array('name' => 'Color100', 'value' => '#cc463d'),
		    Array('name' => 'Color101', 'value' => '#cf3e36'), Array('name' => 'Color102', 'value' => '#5E6671'),
		);
    }

    /*     * *****************************************************
     *      微信卡券：上传LOGO - 需要改写动态功能
     * ***************************************************** */
    public function wxCardUpdateImg($wxAccessToken, $imgpath) {
		//$data['buffer']     =  '@D:\\workspace\\htdocs\\yky_test\\logo.jpg';
		$data['buffer'] = new \CURLFile(realpath($imgpath));
		$url = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=" . $wxAccessToken;
		//$result = $this->https_request($url,$data,$header); 
		$result = $this->wxHttpsRequest($url, $data);
		return $result;
    }

    /*     * *****************************************************
     *      微信卡券：创建卡券
     * ***************************************************** */

    public function wxCardCreated($wxAccessToken, $jsonData) {
		$url = "https://api.weixin.qq.com/card/create?access_token=" . $wxAccessToken;
		$result = $this->wxHttpsRequest($url, $jsonData);
		return $result;
    }

    /*     * *****************************************************
     *      微信门店：获取门店列表
     * ***************************************************** */

    public function wxGetPoiList($wxAccessToken) {
		$url = "https://api.weixin.qq.com/cgi-bin/poi/getpoilist?access_token=" . $wxAccessToken;
		$result = $this->wxHttpsRequest($url, '{"begin":0,"limit":50}');
		return $result;
    }

    /*     * *****************************************************
     *      微信卡券：删除卡券
     * ***************************************************** */
    public function wxCardDelete($wxAccessToken, $jsonData) {
		$url = "https://api.weixin.qq.com/card/delete?access_token=" . $wxAccessToken;
		$result = $this->wxHttpsRequest($url, $jsonData);
		return $result;
    }

    /*     * **************************************************
     *  微信卡券生成二维码ticket
     * ************************************************** */
    public function wxCardQrCodeTicket($wxAccessToken, $jsonData) {
		$url = "https://api.weixin.qq.com/card/qrcode/create?access_token=" . $wxAccessToken;
		$result = $this->wxHttpsRequest($url, $jsonData);
		return $result;
    }

    /*     * *****************************************************
     *      微信卡券：查询用户CODE
     * ***************************************************** */
    public function wxCardQueryCode($wxAccessToken, $jsonData) {
		$url = "https://api.weixin.qq.com/card/code/get?access_token=" . $wxAccessToken;
		$result = $this->wxHttpsRequest($url, $jsonData);
		return $result;
    }

    /*     * *****************************************************
     *      微信卡券：消耗卡券
     * ***************************************************** */
    public function wxCardConsume($wxAccessToken, $jsonData) {
		$url = "https://api.weixin.qq.com/card/code/consume?access_token=" . $wxAccessToken;
		$result = $this->wxHttpsRequest($url, $jsonData);
		return $result;
    }
	 
	/*******************************************************
	*      微信卡券：选择卡券 - 解析CODE
	*******************************************************/       
	public function wxCardDecryptCode($wxAccessToken,$jsonData){
		$url = "https://api.weixin.qq.com/card/code/decrypt?access_token=" . $wxAccessToken;
		$result = $this->wxHttpsRequest($url,$jsonData);
		return $result;      
	}
	 
	/*******************************************************
	 *      微信卡券：更改库存
	 *******************************************************/
	public function wxCardModifyStock($wxAccessToken,$jsonData){     
	    $url    = "https://api.weixin.qq.com/card/modifystock?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url,$jsonData);
	    return $result;      
	}
 
	/*******************************************************
	 *      微信卡券：群发投放卡券
	 *******************************************************/
	public function GetHtml($wxAccessToken,$jsonData){     
	    $url    = "https://api.weixin.qq.com/card/mpnews/gethtml?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url,$jsonData);
	    return $result;  
	}
	
	public function GetCardInfo($wxAccessToken,$jsonData){     
	    $url    = "https://api.weixin.qq.com/card/get?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url,$jsonData);
	    return $result;  
	}
	
	public function ActivateCard($wxAccessToken,$jsonData){
	    $url    = "https://api.weixin.qq.com/card/membercard/activate?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url,$jsonData);
	    return $result;  
	}
	
	
	//设置白名单
	public function TestWhiteList($wxAccessToken, $jsonData = '{"openid":["oZrMms0Em_h17445dSccyK-ZdQmI","oZrMms12QuDmt_mCJOam85yNF0DI"]}')
	{
	    $url = "https://api.weixin.qq.com/card/testwhitelist/set?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url,$jsonData);
	    return $result;  
	}
	
	
	//设置会员卡的支付功能
	public function PayCell($wxAccessToken, $jsonData)
	{
	    $url = "https://api.weixin.qq.com/card/paycell/set?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url,$jsonData);
	    return $result;
	}
	
	//设置激活会员卡时填写的资料
	public function ActivateUserForm($wxAccessToken, $jsonData)
	{
		$url = "https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url,$jsonData);
	    return $result;
	}
	
	//创建货架
	public function LandingPage($wxAccessToken, $jsonData)
	{
		$url = "https://api.weixin.qq.com/card/landingpage/create?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url, $jsonData);
	    return $result;
	}
	
	//拉取会员信息
	public function MemberCardUserInfo($wxAccessToken, $jsonData = '{"card_id": "pZrMmsxQC8ShgGOiI0TgacjKp0bk","code": "424060459312"}')
	{
		$url = "https://api.weixin.qq.com/card/membercard/userinfo/get?access_token=" . $wxAccessToken;
	    $result = $this->wxHttpsRequest($url, $jsonData);
	    return $result;
	}
	
	
	
    //https请求（支持GET和POST）
    protected function https_request($url, $data = null,$noprocess=false) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0");
		$header = array("Accept-Charset: utf-8");
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		//curl_setopt($curl, CURLOPT_SSLVERSION, 3);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header); /* * *$header 必须是一个数组** */
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		if (!empty($data)) {
		    curl_setopt($curl, CURLOPT_POST, 1);
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		if($noprocess) return $output;
		$errorno = curl_errno($curl);
		if ($errorno) {
		    return array('curl' => false, 'errorno' => $errorno);
		} else {
		    $res = json_decode($output, 1);
		    if ($res['errcode']) {
				return array('errcode' => $res['errcode'], 'errmsg' => $res['errmsg']);
		    } else {
				return $res;
		    }
		}
		curl_close($curl);
    }

    public function wxHttpsRequest($url, $data = null) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)) {
		    curl_setopt($curl, CURLOPT_POST, 1);
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		$errorno = curl_errno($curl);
		curl_close($curl);
		if ($errorno) {
		    return array('curl' => false, 'errorno' => $errorno);
		} else {
		    $res = json_decode($output, 1);
		    if ($res['errcode']) {
				return array('errcode' => $res['errcode'], 'errmsg' => $res['errmsg']);
		    } else {
				return $res;
		    }
		}
    }
}
