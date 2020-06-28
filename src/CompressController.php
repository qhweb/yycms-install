<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://qhweb.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: qhweb <qhweb@foxmail.com>
// +----------------------------------------------------------------------

namespace think\qhweb;
use think\Request;
class CompressController
{
    private $allow_imgext = ['png','jpg','bmp','jpeg','gif'];
    private $allow_fileext = ['js','css'];

    public function Compressimg($img = "")
    {
        $request = Request::instance();
        $imgPath = explode('$$$',base64_decode($img));
        $imgPathInfo = pathinfo($imgPath[0]);
        $extension = $imgPathInfo['extension'];

        if(in_array($extension,$this->allow_imgext)){
            $root_path = realpath($request->root());
            $fileWidth = $imgPath[1];
            $file_path = $root_path .  $imgPath[0]; //目标目标路径 /var/www/http/file/abc.jpg.w320.jpg
            $percent = 1;  #原图压缩，不缩放，但体积大大降低

            //正则获取需要放缩的图片大小，格式：/file/abc.jpg.w320.jpg
            if ( file_exists ( $file_path )){
                $zommImg = new \think\qhweb\Compress($file_path,$percent,$fileWidth);
                $zommImg->compressImg();
            }else{
                _404();
            }
        }
    }
    /**
     * 注册新样式
     * @param $file 文件地址
     * @param $text 输出类型
     * @param $fun 压缩函数
     * @return $this
     */
    public function getResource($file=''){
        $formurl = $_SERVER['HTTP_REFERER'];
        $times = date('Y-m-d H:i:s',time());
        $errstr = "/*\n* 非法访问\n* IP：".request()->ip()."\n* 时间：".$times."\n* 状态:已记录\n*/\n";
        $content = "/**\n* Created by yyinfo.com on ". $times .".\n*/\n";
        $PathInfo = pathinfo($file);
        $extension = $PathInfo['extension'];
        //检测来源，禁止站外访问
        if($formurl == ''){
            //return response($errstr, 200, ['Content-Length' =>strlen($errstr)])->contentType("text/".$extension);
        }

        if(!empty($file)){
            $request = Request::instance();
            $cacheData = \think\Cache::get($PathInfo['filename']);

            if($cacheData['data']){
                return response($cacheData['data'], 200, ['Content-Length' => strlen($cacheData['data'])])->contentType('text/'.$extension);
            }else{
                foreach ($cacheData['path'] as $key=>$val){
                    $root_path = realpath($request->root());
                    $realPath = $root_path . $val;
                    if (file_exists($realPath)) {
                       $basedir = dirname($val).'/';
                        $filecontent = file_get_contents($realPath);

                        //替换CSS样式中的图片路径为绝对路径
                        if(strtolower($extension) == 'css'){
                            preg_match_all("/url\(([\s\S]*?)\)/i", $filecontent, $arrCss);
                            if($arrCss){
                                foreach ($arrCss[1] as $k => $v) {
                                    $nessrc = $basedir.str_replace(['\"','\''], ['',''], $v);
                                    $filecontent = str_replace($arrCss[0][$k], 'url("'.$nessrc.'")', $filecontent);
                                }
                            }
                            preg_match_all("/@import ['|\"|\s]([\s\S]*?)['|\"|\s]/i", $filecontent, $arrImport);
                            if($arrImport){
                                foreach ($arrImport[1] as $k => $v) {
                                    $filecontent = str_replace($v, $basedir.$v, $filecontent);
                                }
                            }
                        }
                        //替换JS文件中thinkphp的Url标签
                        if(strtolower($extension) == 'js'){
                            //正则url标签
                            preg_match_all("/{:url\(['|\"]([\s\S]*?)['|\"](.*?)\)}/i", $filecontent, $arr);

                            foreach ($arr[1] as $k => $v) {
                                $filecontent = str_replace($arr[0][$k], url($v), $filecontent);
                            }
                        }

                        $filecontent = "/*".$val."*/\n".$this->compress($filecontent, $extension);
                        $content .= $filecontent;
                    }
                }
                $cacheData['data'] = $content;
                if( ! \think\Config::get('app_debug')){
                    \think\Cache::set($PathInfo['filename'],$cacheData);
                }
                return response($content, 200, ['Content-Length' => strlen($content)])->contentType('text/'.$extension);
            }
        }
    }
    /**
     * 压缩css样式
     * @param $buffer  内容
     * @param $type  压缩类型
     * @return mixed
     */
    protected function compress($buffer,$type) {
        if($type == 'css'){
            /* remove comments */
            $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
            /* remove tabs, spaces, newlines, etc. */
            $buffer = str_replace(array("", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
        }else if ($type == 'js'){
            $JsPacker = new \think\qhweb\JsPacker($buffer, 'Numeric', true, false);
            $buffer = $JsPacker->pack();
        }else{

        }
        return $buffer;
    }
}