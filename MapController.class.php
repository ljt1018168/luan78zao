<?php
 
/**
 * @author duyuan 
 */
namespace Admin\Controller; 
class MapController {
 private static $_instance;
 
 const REQ_GET = 1;
 const REQ_POST = 2;
 
 /**
  * 单例模式
  * @return map
  */
 public static function instance()
 {
  if (!self::$_instance instanceof self)
  {
   self::$_instance = new self;
  }
  return self::$_instance;
 }
 
 /**
  * 执行CURL请求
  * @author: xialei<xialeistudio@gmail.com>
  * @param $url
  * @param array $params
  * @param bool $encode
  * @param int $method
  * @return mixed
  */
 private function async($url, $params = array(), $encode = true, $method = self::REQ_GET)
 {
  $ch = curl_init();
  if ($method == self::REQ_GET)
  {
   $url = $url . '?' . http_build_query($params);
   $url = $encode ? $url : urldecode($url);
   curl_setopt($ch, CURLOPT_URL, $url);
  }
  else
  {
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_POST, true);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  }
  curl_setopt($ch, CURLOPT_REFERER, '*.vipdo.cn*');
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $resp = curl_exec($ch);
  curl_close($ch);
  return $resp;
 }
 
 /**
  * ip定位
  * @param string $ip
  * @return array
  * @throws Exception
  */
 public function locationByIP($ip)
 {
  //检查是否合法IP
  if (!filter_var($ip, FILTER_VALIDATE_IP))
  {
   throw new Exception('ip地址不合法');
  }
  $params = array(
    'ak' => 'G0bry7U610XA0GOkzW54lGS1n6vHUTKB',
    'ip' => $ip,
    'coor' => 'bd09ll'//百度地图GPS坐标
  );
  $api = 'http://api.map.baidu.com/location/ip';
  $resp = $this->async($api, $params);
  $data = json_decode($resp, true);
//   dump($data);die;
//   array(3) {
//   ["address"] => string(34) "CN|广东|东莞|None|CHINANET|0|0"
//   ["content"] => array(3) {
//     ["address"] => string(18) "广东省东莞市"
//     ["address_detail"] => array(6) {
//       ["city"] => string(9) "东莞市"
//       ["city_code"] => int(119)
//       ["district"] => string(0) ""
//       ["province"] => string(9) "广东省"
//       ["street"] => string(0) ""
//       ["street_number"] => string(0) ""
//     }
//     ["point"] => array(2) {
//       ["x"] => string(12) "113.76343399"
//       ["y"] => string(11) "23.04302382"
//     }
//   }
//   ["status"] => int(0)
// }
  //有错误
  if ($data['status'] != 0)
  {
   throw new Exception($data['message']);
  }
  //返回地址信息
  return array(
    'address' => $data['content']['address'],
    'province' => $data['content']['address_detail']['province'],
    'city' => $data['content']['address_detail']['city'],
    'district' => $data['content']['address_detail']['district'],
    'street' => $data['content']['address_detail']['street'],
    'street_number' => $data['content']['address_detail']['street_number'],
    'city_code' => $data['content']['address_detail']['city_code'],
    'lng' => $data['content']['point']['x'],
    'lat' => $data['content']['point']['y']
  );
 }
 
 
 /**
  * GPS定位
  * @param $lng
  * @param $lat
  * @return array
  * @throws Exception
  */
 public function locationByGPS($lng, $lat)
 {
  $params = array(
    'coordtype' => 'wgs84ll',
    'location' => $lat . ',' . $lng,
    'ak' => 'G0bry7U610XA0GOkzW54lGS1n6vHUTKB',
    'output' => 'json',
    'pois' => 0
  );
  $resp = $this->async('http://api.map.baidu.com/geocoder/v2/', $params, false);
  $data = json_decode($resp, true);
  if ($data['status'] != 0)
  {
   throw new Exception($data['message']);
  }
  return array(
    'address' => $data['result']['formatted_address'],
    'province' => $data['result']['addressComponent']['province'],
    'city' => $data['result']['addressComponent']['city'],
    'street' => $data['result']['addressComponent']['street'],
    'street_number' => $data['result']['addressComponent']['street_number'],
    'city_code'=>$data['result']['cityCode'],
    'lng'=>$data['result']['location']['lng'],
    'lat'=>$data['result']['location']['lat']
  );
 }



}