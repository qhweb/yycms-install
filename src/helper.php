<?php


// \think\facade\Route::get('thumbnail/[:img]', "\\think\\qhweb\\CompressController@Compressimg");
// \think\facade\Route::get('resource/[:file]', "\\think\\qhweb\\CompressController@getResource");
// \think\facade\Route::rule('yyadmin/[:controller]/[:action]/', "\\think\\qhweb\\controller\\YYAdmin@autoLoad");
$install_root_path = dirname(dirname(dirname(dirname(__DIR__))));
$install_app_path = $install_root_path . DIRECTORY_SEPARATOR . 'app';
// 检测程序安装
if (!is_dir($install_root_path . "/data")) {
    mkdir($install_root_path . "/data");
}

if (!file_exists($install_root_path . "./data/copyInstall.lock")) {
    copyDir(__DIR__ . "/install", $install_root_path . "/public/install");
    $flag = @touch($install_root_path . './data/copyInstall.lock');
}
if(file_exists($install_root_path . "./data/install.lock") && file_exists($install_root_path . "/public/install")){
    deleteDir($install_root_path . "/public/install");
}
if (!file_exists($install_root_path . "./data/install.lock")) {
    echo ('<script>location.href="/install";</script>');
    exit;
}
/**
 * 获取顶级域名
 */
function getTopDomainhuo($str='.?'){
    $host=$_SERVER['HTTP_HOST'];
    $matchstr="[^\.]+\.(?:(".$str.")|\w{2}|((".$str.")\.\w{2}))$";
    @preg_match("/".$matchstr."/ies",$host,$matchs);
    if($matchs){
      $domain=$matchs['0'];
    }else{
      $domain=$host;
    }
    return $domain;
}
/**
 * 检测授权
 */
function checkCmsAuthorize()
{
  $topdomain=getTopDomainhuo();
  $client_check='http://domain.qhxckj.com/api?key=client_check&domain='.$_SERVER['HTTP_HOST'].'&topdomain='.$topdomain;
  $check_info=file_get_contents($client_check);
  $check_info = json_decode($check_info,true);
  if(isset($check_info['message']) && $check_info['message']){
    echo '<font color=red>' . $check_info['message'] . '</font>';
    die;
  }
  if(isset($check_info['code']) && $check_info['code']!=0){
      echo '<font color=red>' . $check_info['error'] . '</font>';
      die;
  }
  unset($topdomain);
}
// checkCmsAuthorize();
//读取版本号
function getversion(){
    $versionFile = dirname(dirname(dirname(dirname(__DIR__)))).DIRECTORY_SEPARATOR.'.version';
    $version_arr = [];
    $curent_version = @file( $versionFile );
    if($curent_version) foreach ($curent_version as $val){
        list($k,$v)=explode('=',$val);
        $version_arr[$k]=$v;
    }
    return $version_arr;
}
/**
 * 获取拼音
 * @param $string
 * @param string $encoding
 */
function Pinyin($string, $encoding = 'utf-8'){
    return \yycms\Pinyin::getPinyin($string, $encoding);
}

/**
 * 获取拼音缩写
 * @param $string
 * @param string $encoding
 */
function ShortPinyin($string, $encoding = 'utf-8'){
    return \yycms\Pinyin::getShortPinyin($string, $encoding);
}

/**
 * 关键字提取方法
 * @param $title string  进行分词的标题
 * @param $content string  进行分词的内容
 * @param $loadInit bool  初始化类时是否直接加载词典，选是载入速度较慢，但解析较快；选否载入较快，但解析较慢
 * @return string 得到的关键词
 */

function getKeywords($title = "", $content = "",$loadInit = false) {
    if (empty ( $title )) {return array ();}
    $data = $title . $title . $title . $content; // 为了增加title的权重，这里连接3次
    \yycms\SplitWord::$loadInit = $loadInit;  //初始化类时是否直接加载词典，选是载入速度较慢，但解析较快；选否载入较快，但解析较慢
    $pa = new \yycms\SplitWord ( 'utf-8', 'utf-8', false );
    $pa->LoadDict ();  //载入词典
    $pa->SetSource ( $data );  //设置源字符串
    $pa->StartAnalysis ( true );  //是否对结果进行优化
    $tags = $pa->GetFinallyKeywords (4); // 获取文章中的五个关键字
    $tagsArr = explode(",",$tags);
    return $tagsArr;//返回关键字数组
}

/**
 * 加密函数
 * @param $txt
 * @param string $key
 * @return string
 */
function lock_url($txt,$key='qhweb')
{
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    $nh = rand(0,61);
    $ch = $chars[$nh];
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $txt = base64_encode($txt);
    $tmp = '';
    $k = 0;
    for ($i=0; $i<strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($nh+strpos($chars,$txt[$i])+ord($mdKey[$k++]))%65;
        $tmp .= $chars[$j];
    }
    return urlencode($ch.$tmp);
}

/**
 * 解密函数
 * @param $txt
 * @param string $key
 * @return bool|string
 */
function unlock_url($txt,$key='qhweb')
{
    $txt = urldecode($txt);
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    $ch = $txt[0];
    $nh = strpos($chars,$ch);
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $txt = substr($txt,1);
    $tmp = '';
    $k = 0;
    for ($i=0; $i<strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars,$txt[$i])-$nh - ord($mdKey[$k++]);
        while ($j<0) $j+=65;
        $tmp .= $chars[$j];
    }
    return base64_decode($tmp);
}

/**
 * 缩略图生成
 * @param $srcPath 图片原地址
 * @param string $newWidth  缩略图图片的宽，默认200
 */
function ThumbSrc($srcPath,$newWidth='200'){
    if(empty($srcPath)) return '';
    $strInfo = parse_url($srcPath);
    $newSrc =  $strInfo['path'].'$$$'.$newWidth;
    $baseUrl = base64_encode($newSrc);
    return ($strInfo['scheme'] ? $strInfo['scheme'] .'://' . $strInfo['host'] : '') .'/thumbnail/'.$baseUrl;
}

/**
 * 压缩资源列表
 * @param $srcPath 图片原地址
 * @param string $newWidth  缩略图图片的宽，默认200
 */
function setResource($path,$type='css',$dir='/'){
    if(empty($path)) return '';
    if(is_array($path)){
        $cacheName = md5(implode(',',$path));
    }else{
        $cacheName = md5($path);
        $path = explode(',',$path);
    }

    \think\Cache::set($cacheName,['path'=>$path,'data'=>'']);
    if($type == 'css'){
        $linkUrl = '<link type="text/css" rel="stylesheet" charset="utf-8" href="'.$dir.'resource/'.$cacheName.'.'.$type.'"  media="all"/>';
    }elseif ($type == 'js'){
        $linkUrl = '<script type="text/javascript" charset="utf-8" src="'.$dir.'resource/'.$cacheName.'.'.$type.'"></script>';
    }
    return $linkUrl;
}

/**
 * 生成URL
 * @return string
 */

function YYUrl($url='',$param='',$suffix=true,$domaon=false){
    $urlArr = explode('/',$url);
    if(count($urlArr) == 1){
        $path = MODULE .'/' .CONTROLLER.'/'.$url;
    }elseif(count($urlArr) == 2){
        $path = MODULE .'/'.$url;
    }elseif(count($urlArr) == 0){
        $path = MODULE .'/' .CONTROLLER.'/'.ACTION;
    }
    return \think\Url::build($path,$param,$suffix,$domaon);
}
/**
 * 获取token
 * @return string
 */

function setToken(){
    $request = \think\Request::instance();
    return $request->token('__token__');
}

/**
 * 404错误页面
 */
function _404($text=''){
    $text = !empty($text) ? $text : '对不起，您请求的页面不存在、或已被删除、或暂时不可用';
    $head404   = "data:image/png;base64," . base64_encode(file_get_contents(__DIR__ .'/../assets/image/head404.png'));
    $txtbg404   = "data:image/png;base64," . base64_encode(file_get_contents(__DIR__ .'/../assets/image/txtbg404.png'));
    $html='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title>404-对不起！您访问的页面不存在</title>
            <style type="text/css">
            .head404{ width:580px; height:234px; margin:50px auto 0 auto; background:url('.$head404.') no-repeat; }
            .txtbg404{ width:499px; height:169px; margin:10px auto 0 auto; background:url('.$txtbg404.') no-repeat;}
            .txtbg404 .txtbox{ width:390px; position:relative; top:30px; left:60px;color:#eee; font-size:13px;}
            .txtbg404 .txtbox p {margin:5px 0; line-height:18px;}
            .txtbg404 .txtbox .paddingbox { padding-top:15px;}
            .txtbg404 .txtbox p a { color:#eee; text-decoration:none;}
            .txtbg404 .txtbox p a:hover { color:#FC9D1D; text-decoration:underline;}
            </style>
            </head>
            <body bgcolor="#494949">
                <div class="head404"></div>
                <div class="txtbg404">
              <div class="txtbox">
                  <p>'.$text.'</p>
                  <p class="paddingbox">请点击以下链接继续浏览网页</p>
                  <p>》<a style="cursor:pointer" onclick="history.back()">返回上一页面</a></p>
                  <p>》<a href="'.request()->domain().'">返回网站首页</a></p>
                </div>
              </div>
            </body>
            </html>';
    exit($html);
}




  //删除目录（递归删除）
  function delDir($dir){
      //传入文件的路径
      //遍历目录
      $arr = scandir($dir);
      foreach ($arr as $val) {
          if ($val != '.' && $val != '..') {
              //路径链接
              $file = $dir . '/' . $val;
              if (is_dir($file)) {
                  delDir($file);
              } else {
                  unlink($file);
              }
          }
      }
      rmdir($dir);
  }


// 目录复制
function copyDir($dir1, $dir2){
    if(!file_exists($dir1)) return true;
    if (!file_exists($dir2)) {
        $cdir = mkdir($dir2,0777);
    }

    //遍历原目录
    $arr = scandir($dir1);
    foreach ($arr as $val) {
        if ($val != '.' && $val != '..') {
            //原目录拼接
            $sfile = $dir1 . '/' . $val;
            //目的目录拼接
            $dfile = $dir2 . '/' . $val;
            if (is_dir($sfile)) {
                copyDir($sfile, $dfile);
            } else {
                copy($sfile, $dfile);
            }
        }
    }
}


if (!function_exists('moveDir')) {
  // 移动目录
  function moveDir($dir1, $dir2){
      copyDir($dir1, $dir2);
      delDir($dir1);
  }
}



  function getgpc($k, $t = 'GP'){
      $t = strtoupper($t);
      switch ($t) {
          case 'GP' :
              isset($_POST[$k]) ? $var = &$_POST : $var = &$_GET;
              break;
          case 'G':
              $var = &$_GET;
              break;
          case 'P':
              $var = &$_POST;
              break;
          case 'C':
              $var = &$_COOKIE;
              break;
          case 'R':
              $var = &$_REQUEST;
              break;
      }
      return isset($var[$k]) ? $var[$k] : null;
  }


  ////判断目录读写权限
  function testwrite($d)
  {
      if (is_file($d)) {
          if (is_writeable($d)) {
              return true;
          }
          return false;
      } else {
          $tfile = "_test.txt";
          $fp = @fopen($d . "/" . $tfile, "w");
          if (!$fp) {
              return false;
          }
          fclose($fp);
          $rs = @unlink($d . "/" . $tfile);
          if ($rs) {
              return true;
          }
          return false;
      }
  }



  /**
   * 创建文件夹
   */
  function dir_create($path, $mode = 0777){
      if (is_dir($path))
          return TRUE;
      $ftp_enable = 0;
      $path = format_dir_path($path);
      $temp = explode('/', $path);
      $cur_dir = '';
      $max = count($temp) - 1;
      for ($i = 0; $i < $max; $i++) {
          $cur_dir .= $temp[$i] . '/';
          if (@is_dir($cur_dir))
              continue;
          @mkdir($cur_dir, 0777, true);
          @chmod($cur_dir, 0777);
      }
      return is_dir($path);
  }



  /**
   * 获取文件夹路径
   */
  function format_dir_path($path=''){
      if(empty($path)) return '';
      $path = str_replace('\\', '/', $path);
      if (substr($path, -1) != '/')  $path = $path . '/';
      return $path;
  }


  /**
   * 字节格式化
   */
  function filesize_formatted($path){
      $size = filesize($path);
      $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
      $power = $size > 0 ? floor(log($size, 1024)) : 0;
      return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
  }


    /**
     * 文件大小格式化
     * @param integer $size 初始文件大小，单位为byte
     * @return array 格式化后的文件大小和单位数组，单位为byte、KB、MB、GB、TB
     * @example file_size_format(123456789);
     */
    function file_size_format($size = 0, $dec = 2)
    {
        $unit = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        $result['size'] = round($size, $dec);
        $result['unit'] = $unit[$pos];
        return $result['size'] . $result['unit'];

    }

if (!function_exists('mitobyte')) {
    /**
     * Converts a human readable file size value to a number of bytes that it
     * represents. Supports the following modifiers: K, M, G and T.
     * Invalid input is returned unchanged.
     *
     * Example:
     * <code>
     * $config->human2byte(10);          // 10
     * $config->human2byte('10b');       // 10
     * $config->human2byte('10k');       // 10240
     * $config->human2byte('10K');       // 10240
     * $config->human2byte('10kb');      // 10240
     * $config->human2byte('10Kb');      // 10240
     * // and even
     * $config->human2byte('   10 KB '); // 10240
     * </code>
     *
     * @param number|string $value
     * @return number
     */

    function mitobyte($value)
    {
        return preg_replace_callback('/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i', function ($m) {
            switch (strtolower($m[2])) {
                case 't':
                    $m[1] *= 1024;
                case 'g':
                    $m[1] *= 1024;
                case 'm':
                    $m[1] *= 1024;
                case 'k':
                    $m[1] *= 1024;
            }
            return $m[1];
        }, $value);
    }
}
/**
 **
 * 检查连接是否可用
 * @param Link $dbconn 数据库连接
 * @return Boolean
 */

function pdo_ping($dbconn){
    try {
        $dbconn->getAttribute(PDO::ATTR_SERVER_INFO);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'MySQL server has gone away') !== false) {
            return false;
        }


    }
    return true;
}

/**
 * 删除当前目录及其目录下的所有目录和文件
 * @param string $path 待删除的目录
 * @note  $path路径结尾不要有斜杠/(例如:正确[$path='./static/image'],错误[$path='./static/image/'])
 */
function deleteDir($path) {

    if (is_dir($path)) {
        //扫描一个目录内的所有目录和文件并返回数组
        $dirs = scandir($path);

        foreach ($dirs as $dir) {
            //排除目录中的当前目录(.)和上一级目录(..)
            if ($dir != '.' && $dir != '..') {
                //如果是目录则递归子目录，继续操作
                $sonDir = $path.'/'.$dir;
                if (is_dir($sonDir)) {
                    //递归删除
                    deleteDir($sonDir);

                    //目录内的子目录和文件删除后删除空目录
                    @rmdir($sonDir);
                } else {

                    //如果是文件直接删除
                    @unlink($sonDir);
                }
            }
        }
        @rmdir($path);
    }
}
