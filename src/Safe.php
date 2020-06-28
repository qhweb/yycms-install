<?php

namespace yycms;

/**
 * 安全检查
 */
class Safe
{
  /**
   * HOST访问限制 支持 IP(单IP,多IP,*通配符,IP段) 域名(单域名,多域名,*通配符)
   * 根据判断实现IP地址 白名单黑名单
   * @param unknown $host 当前host 127.0.0.2
   * @param unknown $list 允许的host列表 127.0.0.*,192.168.1.1,192.168.1.70,127.1.1.33-127.1.1.100
   * @return boolean
   */
  protected function in_host($host, $list) {
      $list = ',' . $list . ',';
      $is_in = false;
      // 1.判断最简单的情况
      $is_in = strpos ( $list, ',' . $host . ',' ) === false ? false : true;
      // 2.判断通配符情况
      if (! $is_in && strpos ( $list, '*' ) !== false) {
          $hosts = array ();
          $hosts = explode ( '.', $host );
          // 组装每个 * 通配符的情况
          foreach ( $hosts as $k1 => $v1 ) {
              $host_now = '';
              foreach ( $hosts as $k2 => $v2 ) {
                  $host_now .= ($k2 == $k1 ? '*' : $v2) . '.';
              }
              // 组装好后进行判断
              if (strpos ( $list, ',' . substr ( $host_now, 0, - 1 ) . ',' ) !== false) {
                  $is_in = true;
                  break;
              }
          }
      }
      // 3.判断IP段限制
      if (! $is_in && strpos ( $list, '-' ) !== false) {
          $lists = explode ( ',', trim ( $list, ',' ) );
          $host_long = ip2long ( $host );
          foreach ( $lists as $k => $v ) {
              if (strpos ( $v, '-' ) !== false) {
                  list ( $host1, $host2 ) = explode ( '-', $v );
                  if ($host_long >= ip2long ( $host1 ) && $host_long <= ip2long ( $host2 )) {
                      $is_in = true;
                      break;
                  }
              }
          }
      }
      return $is_in;
  }
  /**
   * 取得IP
   *
   * @return string 字符串类型的返回结果
   */
  public function getIp(){
      if (@$_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP']!='unknown') {
          $ip = $_SERVER['HTTP_CLIENT_IP'];
      } elseif (@$_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR']!='unknown') {
          $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      } else {
          $ip = $_SERVER['REMOTE_ADDR'];
      }
      return preg_match('/^\d[\d.]+\d$/', $ip) ? $ip : '';
  }
  public function checkip($allowIp = []){
      $ip = $this->getIp();
      return $this->in_host($ip,implode(',',$allowIp));
  }
  /**
   * 敏感词过滤
   * @param $str 需要过滤的字符
   */
  public function wordFiter($str){
      if (empty($str)) return '';
      $wordFiter = cache('safe_words');
      $words_preg = [];
      $replace_preg = [];
      if (!$wordFiter){
          $words = db('safe_word')->where('status=1')->select();
          foreach ($words as $k => $v){
              $words_preg[] = '/'.$v['title'].'/';
              $replace_preg[] = $v['replace'];
          }
          $wordFiter = ['words'=>$words_preg,'replace'=>$replace_preg];
          cache('safe_words',$wordFiter);
      }
  //        print_r($wordFiter);exit;
      $str = preg_replace($wordFiter['words'], $wordFiter['replace'], $str);
      return $str;
  }
}
