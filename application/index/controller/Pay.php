<?php
/**
 * User: XuLongCai
 * Date: 2020/4/25
 * Time: 4:54 PM
 */

namespace app\index\controller;


use app\index\utils\pay\AppConfig;
use app\index\utils\pay\AppUtil;
use think\Controller;
use think\facade\Log;

class Pay extends Controller
{
    /**
     * 数据返回统一格式
     * @param string $code 响应码
     * @param string $msg 提示信息
     * @param array $data 数据
     * @return \think\response\Json
     */
    protected function returnJson($code = '000', $msg = '', $data = [])
    {
        $arr['code'] = (string)$code;
        $arr['msg']  = $msg;
        $arr['data'] = $data;

        return json($arr);
    }

    public function getPayParams(){
        $params = [];
        $post_data = $this->request->post('data');
        $data = json_decode($post_data,true);
        $total = $data['total_price']*100;

        $params["cusid"] = AppConfig::CUSID;
        $params["appid"] = AppConfig::APPID;
        $params["version"] = AppConfig::APIVERSION;
        $params["trxamt"] = $total;
        $params["reqsn"] = $data['order_id'];//订单号,自行生成
        $params["paytype"] = "W06";
        $params["randomstr"] = getRandomStr();
        $params["body"] = "测试";
        $params["acct"] = $data['openid'];
        $params["notify_url"] = "https://app.loopyun.com/payNotice";
        $params["sub_appid"] = AppConfig::WX_APPID;
        $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名

        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL . "/pay";
        $rsp = curlRequest($url, $paramsStr);
//        echo "请求返回:".$rsp;
//        echo "<br/>";
        $rspArray = json_decode($rsp, true);
        if($this->payValidSign($rspArray)){
            //验证通过，返回参数
            if($rspArray['trxstatus']=='0000'){
                $response = [];
                $response['app_id'] = AppConfig::JS_APPID;
                $response['data'] = $rspArray['payinfo'];
                $response['status']=0;
                $response['sign'] = $this->getZhiChiSign(json_decode($rspArray['payinfo'],true));
                $response['sign_type'] = 'MD5';
                return json($response);
            }else{
                Log::info($rspArray['trxstatus']);
            }
        }else{
            Log::info("验证未通过");
        }
    }


    private function getZhiChiSign($data)
    {
        if (is_string($data)) {
            $str = $data;
        } else {
            ksort($data);
            $str = '';
            foreach ($data as $key => $value) {
                $str .= "&{$key}={$value}";
            }
            $str = substr($str, 1); // 去除掉开头的 &
        }
        return strtolower(md5($str.'yidusen111'));// md5_key 为后台设置的md5加密key
    }
    private function payValidSign($array){
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
    public function payNotice(){
        $params = array();
        foreach($_POST as $key=>$val) {//动态遍历获取所有收到的参数,此步非常关键,因为收银宝以后可能会加字段,动态获取可以兼容由于收银宝加字段而引起的签名异常
            $params[$key] = $val;
        }
        if(count($params)<1){//如果参数为空,则不进行处理
            echo "error";
            exit();
        }
        if(AppUtil::ValidSign($params, AppConfig::APPKEY)){//验签成功
            //此处进行业务逻辑处理
            $zhichiPayNoticeUrl = 'https://www.jisuapp.cn/index.php/pay/Notify/ThirdApiPaymentCallback';
            $sign_data = [
                'order_id'=>$params['cusorderid'],
                'transaction_id'=>$params['chnltrxid'],
                'order_type'=>'1'
            ];
            $noticeData = [];
            $noticeData['app_id'] = AppConfig::JS_APPID;
            $noticeData['data'] = json_encode($sign_data,JSON_UNESCAPED_UNICODE);
            $noticeData['sign'] = $this->getZhiChiSign($sign_data);
            $noticeData['sign_type'] = 'MD5';
            $noticeStr = AppUtil::ToUrlParams($noticeData);
            $noticeRes = curlRequest($zhichiPayNoticeUrl,$noticeStr);
            Log::info($noticeRes);
            echo "success";
        }
        else{
            echo "erro";
        }
    }

    public function refundNotice(){
        $params = [];
        $post_data = $this->request->post('data');
        $data = json_decode($post_data,true);
        $params['cusid'] = AppConfig::CUSID;
        $params['appid'] = AppConfig::APPID;
        $params['version'] = AppConfig::APIVERSION;
        $params['trxamt'] = $data['refund_price']*100;
        $params['reqsn'] = $data['refund_order_id'];
        $params['randomstr'] = getRandomStr();
        $params['sign'] = AppUtil::SignArray($params,AppConfig::APPKEY);
        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL . "/refund";
        $rsp = curlRequest($url, $paramsStr);
        Log::info($rsp);
        $rspArray = json_decode($rsp, true);
        if($this->payValidSign($rspArray)){
            //验证通过
            if($rspArray['trxstatus']=='0000'){
                $response = [];
                $response['app_id'] = AppConfig::JS_APPID;
                $response['data'] = json_encode([
                    'refund_id'=>$rspArray['trxid']
                ],JSON_UNESCAPED_UNICODE);
                $response['status']=0;
                $response['sign'] = $this->getZhiChiSign([
                    'refund_id'=>$rspArray['trxid']
                ]);
                $response['sign_type'] = 'MD5';
                return json($response);
            }

        }

    }

    public function refundSearch(){

    }
}