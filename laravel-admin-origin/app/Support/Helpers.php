<?php


namespace App\Support;

use App\Libs\HttpClient;
use App\Models\Manager;
use App\Models\ManagerRel;
use App\Models\VillageHouse;
use App\Models\VillageHouseManagerRel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;


class Helpers
{

    /**
     * 根据token获取推客id
     * @param $token
     * @return string
     */
    public static function getUserIdByToken($token)
    {
        $data = Cache::get($token, []);
        return $data ?? '';
    }

    /**
     * 上传文件
     * @param $request
     * @param $name
     * @param string $disk
     * @return bool|string
     */
    public static function uploadFile($file, $disk = 'public', $is_http = false,$is_import=false)
    {

        if (!$file) {
            return '';
        }

        if(is_array($file)){
            $fileArr= array();
            foreach($file as $key=>$v)
            {
                $fileName = date('Y_m_d').md5(rand(1,100000));

                $res = Storage::disk($disk)->put($fileName, $v);
                if (!$res) {
                    return '';
                }
                if ($is_http) {
                    $fileArr[$key] = '/storage/'.$res;
                }
            }
            $fileName = json_encode($fileArr);

        } else {
            //获取文件的扩展名
            $ext = $file->getClientOriginalExtension();

            //获取文件的绝对路径
            $path = $file->getRealPath();

            //定义文件名
            $fileName = date('Y_m_d').'/'.md5(rand(1,100000)).'.'.$ext;
            $res = Storage::disk($disk)->put($fileName, file_get_contents($path));
            if (!$res) {
                return '';
            }
            if ($is_http) {
                $fileName = '/storage/'.$fileName;
            }else{
                $fileName = './storage/'.$fileName;
            }

        }
        if($is_import){
            $ext_arr = array('csv','xlsx','xls');

            if(!in_array($ext,$ext_arr)){
                return "";
            }
        }

        return $fileName;
    }

    /**
     * 小程序获取access_key
     * @return mixed|string
     */
    public static function XcxGetAccessToken()
    {
        $token = Cache::get('xcx_access_token', '');
        if (!empty($token)) {
            return $token;
        }
        $appid = env('WX_APPID');
        $secret = env('WX_SECRET');
        $api = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";

        $res = HttpClient::get($api);
        $res = json_decode($res, true);
        if (isset($res['access_token'])) {
            Cache::add('xcx_access_token', $res['access_token'], $res['expires_in'] - 100);
            return $res['access_token'];
        }

        return '';

    }

    /**
     * 生成小程序二维码
     * @param $manager_id
     * @return bool|string
     */
    public static function XcxGetUnlimited($manager_id)
    {
        $token = self::XcxGetAccessToken();
        $api = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$token}";
        $data = [
            'scene' => $manager_id,
        ];

        $res = HttpClient::post($api, json_encode($data));
        $res_decode = json_encode($res, true);
        if (isset($res_decode['errcode'])) {
            return false;
        }

        $file_name = 'qrcode/'.md5(time().rand(1000, 9999)).'.png';
        file_put_contents($file_name, $res);
        return env('APP_URL').'/'.$file_name;

    }

    /**
     * 给一个秒数，得出一个格式化的字符串
     * @param $second
     * @param string $format
     * @return string
     */
    public static function formatTimeSecondToText($second, $format = 'DHIS')
    {
        $day = floor($second / (60*60*24));
        $second = $second - $day*(60*60*24);
        $hours = floor($second / (60*60));
        $second = $second - $hours*(60*60);
        $minute = floor($second / 60);
        $second = $second - $minute*60;
        switch ($format) {
            case 'DHIS':
                return $day.'天'.$hours.'小时'.$minute.'分钟'.$second.'秒';
            case 'DHI':
                return $day.'天'.$hours.'小时'.$minute.'分钟';
            case 'DH':
                return $day.'天'.$hours.'小时';
            case "HIS":
                return ($day*24)+$hours.'小时'.$minute.'分钟'.$second.'秒';
        }

    }


    /**
     * 验证参数不能为空，如果为空，就返回code错误码信息
     * 需要注意，这里不适用参数为0或者参数为假的情况
     * @param $data
     * @param $request
     * @return array
     */
    public static function checkEmptyParamAndReturnCode($data, $request)
    {
        foreach ($data as $key => $val) {
            if (empty($val)) {
                return ShowArtwork::setCode(ShowArtwork::ERR_PARAMS, '', [], ['参数不能为空'], $request->input());
            }
        }

        return false;
    }
}
