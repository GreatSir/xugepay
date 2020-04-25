<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

//生成随机字符串
if(!function_exists('getRandomStr')){
    function getRandomStr(){
        $str = '';
        $str_pol = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyl';
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}
//发送请求操作仅供参考,不为最佳实践
function curlRequest($url,$params){
    $ch = curl_init();
    $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
    curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//如果不加验证,就设false,商户自行处理
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    $output = curl_exec($ch);
    curl_close($ch);
    return  $output;
}


//验签
function payValidSign($array){
    if("SUCCESS"==$array["retcode"]){
        $signRsp = strtolower($array["sign"]);
        $array["sign"] = "";
        $sign =  strtolower(AppUtil::SignArray($array, AppConfig::APPKEY));
        if($sign==$signRsp){
            return TRUE;
        }
        else {
            echo "验签失败:".$signRsp."--".$sign;
        }
    }
    else{
        echo $array["retmsg"];
    }

    return FALSE;
}
