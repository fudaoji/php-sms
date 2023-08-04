<?php

namespace Dao\Sms\Driver;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendBatchSmsRequest;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;

class Acloud
{
    private $accessKeyId;
    private $accessKeySecret;
    private $client;
    private $error;
    private $runtime;

    /**
     * 初始化
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @author fudaoji<fdj@kuryun.cn>
     */
    public function __construct($accessKeyId = '', $accessKeySecret = '', $options = []) {
        $config = new Config([
            // 必填，您的 AccessKey ID
            "accessKeyId" => $accessKeyId,
            // 必填，您的 AccessKey Secret
            "accessKeySecret" => $accessKeySecret
        ]);
        // Endpoint 请参考 https://api.aliyun.com/product/Dysmsapi
        $config->endpoint = $options['endpoint'] ?? "dysmsapi.aliyuncs.com";
        $this->client = new Dysmsapi($config);

        $this->runtime = new RuntimeOptions();
        isset($options['runtime_maxidleconns']) && $this->runtime->maxIdleConns   = $options['runtime_maxidleconns'];
        isset($options['runtime_connecttimeout']) && $this->runtime->connectTimeout = $options['runtime_connecttimeout'];
        isset($options['runtime_readtimeout']) && $this->runtime->readTimeout    = $options['runtime_readtimeout'];
    }

    /**
     * 发送短信
     * @param string|array $mobile
     * @param array $content
     * @return mixed
     * @author Jason<dcq@kuryun.cn>
     */
    public function send($mobile='', $content=[]) {
        try {
            if(is_string($mobile)){
                $mobile = explode(',', $mobile);
            }
            if(count($mobile) <= 1){
                $mobile = $mobile[0];
                //初始化SendSmsRequest实例用于设置发送短信的参数
                $request = new SendSmsRequest();
                //必填，设置短信接收号码
                $request->phoneNumbers = $mobile;
                //必填，设置签名名称，应严格按"签名名称"填写
                $request->signName = $content['sign_name'] ?? '';
                //必填，设置模板CODE，应严格按"模板CODE"填写
                $request->templateCode = $content['template_code'] ?? '';
                //可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
                if(! empty($content['template_param'])){
                    $request->templateParam = json_encode($content['template_param'], JSON_UNESCAPED_UNICODE);
                }
                // 复制代码运行请自行打印 API 的返回值
                $response = $this->client->sendSmsWithOptions($request, $this->runtime);
            }else{
                $mobile = json_encode($mobile);
                $sign_name = json_encode($content['sign_name'], JSON_UNESCAPED_UNICODE);
                //初始化SendSmsRequest实例用于设置发送短信的参数
                $request = new SendBatchSmsRequest();
                //必填，设置短信接收号码
                $request->phoneNumberJson = $mobile;
                //必填，设置签名名称，应严格按"签名名称"填写
                $request->signNameJson = $sign_name;
                //必填，设置模板CODE，应严格按"模板CODE"填写
                $request->templateCode = $content['template_code'] ?? '';
                //可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
                if(! empty($content['template_param'])){
                    $request->templateParamJson =  json_encode($content['template_param'], JSON_UNESCAPED_UNICODE);
                }
                // 复制代码运行请自行打印 API 的返回值
                $response = $this->client->sendBatchSmsWithOptions($request, $this->runtime);
            }

            $resp = $response->toMap();
            if(isset($resp['body']['Code']) && $resp['body']['Code']==='OK'){
                return true;
            }else{
                $this->error = $resp['body']['Message'];
            }
        } catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            $this->error = $error->message;
            return false;
        }
    }

    /**
     * 错误码对照表
     * @param null $code
     * @author Jason<dcq@kuryun.cn>
     */
    private function setError($code = null){
        $list = [
            'OK'                                => '请求成功',
            'isp.RAM_PERMISSION_DENY'           => 'RAM权限DENY',
            'isv.OUT_OF_SERVICE'                => '业务停机',
            'isv.PRODUCT_UN_SUBSCRIPT'          => '未开通云通信产品的阿里云客户',
            'isv.PRODUCT_UNSUBSCRIBE'           => '产品未开通',
            'isv.ACCOUNT_NOT_EXISTS'            => '账户不存在',
            'isv.ACCOUNT_ABNORMAL'              => '账户异常',
            'isv.SMS_TEMPLATE_ILLEGAL'          => '短信模板不合法',
            'isv.SMS_SIGNATURE_ILLEGAL'         => '短信签名不合法',
            'isv.INVALID_PARAMETERS'            => '参数异常',
            'isp.SYSTEM_ERROR'                  => '系统错误',
            'isv.MOBILE_NUMBER_ILLEGAL'         => '非法手机号',
            'isv.MOBILE_COUNT_OVER_LIMIT'       => '手机号码数量超过限制',
            'isv.TEMPLATE_MISSING_PARAMETERS'   => '模板缺少变量',
            'isv.BUSINESS_LIMIT_CONTROL'        => '业务限流',
            'isv.INVALID_JSON_PARAM'            => 'JSON参数不合法，只接受字符串值',
            'isv.BLACK_KEY_CONTROL_LIMIT'       => '黑名单管控',
            'isv.PARAM_LENGTH_LIMIT'            => '参数超出长度限制',
            'isv.PARAM_NOT_SUPPORT_URL'         => '不支持URL',
            'isv.AMOUNT_NOT_ENOUGH'             => '账户余额不足',
        ];
        $this->error = isset($list[$code]) ? $list[$code] : '未知错误';
    }

    /**
     * 返回错误信息
     * @return mixed
     * @author Jason<dcq@kuryun.cn>
     */
    public function getError(){
        return $this->error;
    }
}
