<?php
/**
 * Created by PhpStorm.
 * User: 123456
 * Date: 2018/9/20
 * Time: 11:15
 * @author sunjiaqiang
 * @email 1355049422@qq.com
 */
namespace yycms;
use think\facade\Db;
use think\facade\Config;

class DbAction{

  //SQL字段类型定义
  public $propertyField = [
    'text'      => array('name'=>'varchar','maxlen'=>250,'decimal'=>0),
    'int'       => array('name'=>'int', 'maxlen'=>11, 'decimal'=>0),
    'tinyint'   => array('name'=>'tinyint', 'maxlen'=>4, 'decimal'=>0),
    'textarea'  => array('name'=>'text', 'maxlen'=>0, 'decimal'=>0),
    'number'    => array('name'=>'decimal', 'maxlen'=>10, 'decimal'=>2),
    'datetime'  => array('name'=>'timestamp', 'maxlen'=>0, 'decimal'=>0),
    'select'    => array('name'=>'tinyint', 'maxlen'=>4, 'decimal'=>0),
    'radio'     => array('name'=>'tinyint', 'maxlen'=>4, 'decimal'=>0),
    'checkbox'  => array('name'=>'tinyint', 'maxlen'=>4, 'decimal'=>0),
    'upimage'   => array('name'=>'text', 'maxlen'=>0, 'decimal'=>0),
    'upfile'    => array('name'=>'text', 'maxlen'=>0, 'decimal'=>0),
    'linkpage'  => array('name'=>'varchar','maxlen'=>250,'decimal'=>0),
    'memberid'  => array('name'=>'bigint', 'maxlen'=>20, 'decimal'=>0),
    'userid'    => array('name'=>'bigint', 'maxlen'=>20, 'decimal'=>0),
    'siteid'    => array('name'=>'bigint', 'maxlen'=>20, 'decimal'=>0),
  ];
  /**
   * 数据库前缀
   */
  public function getPrefix()
  {
    return Config::get('database.connections.mysql.prefix');
  }
  //数据库自动类型
  public function getCharset()
  {
    return Config::get('database.connections.mysql.charset');
  }

  /**
   * 创建数据表
   */
  public function deleteTable($table='',$isSystem=0)
  {
    $tablename = $this->getPrefix() . ($isSystem ? 'ext_'.$table : $table);
    return Db::execute("DROP TABLE IF EXISTS `{$tablename}`");
  }
  /**
   * 创建数据表
   */
  public function createTable($table='',$title='',$isSystem=0)
  {
    //独立模块
    if(!$isSystem){
      $tablename = $this->getPrefix().$table;
      Db::execute("CREATE TABLE `".$tablename."` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID主键',
        `status` tinyint(1) unsigned DEFAULT '1' COMMENT '状态',
        `sort` int(10) unsigned  DEFAULT '0' COMMENT '排序',
        `create_by` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `update_by` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '修改时间',
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET={$this->getCharset()} COMMENT='{$title}'");
    }
    //内容扩展模块
    if($isSystem){
      $tablename = $this->getPrefix().'ext_'.$table;
      Db::execute("CREATE TABLE `".$tablename."` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '内容ID,主键',
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET={$this->getCharset()} COMMENT='{$title}'");
    }

  }
  /**
   * 修改数据表名
   */
  public function updateTable($oldtable='',$table='',$title='',$isSystem=0)
  {
    $oldtable = $this->getPrefix() . ($isSystem ? 'ext_'. $oldtable : $oldtable);
    $table = $this->getPrefix() . ($isSystem ? 'ext_'.$table : $table);
    $sql="ALTER TABLE ".$oldtable." RENAME TO ".$table;
    $statusSql = Db::execute($sql);
    return $statusSql;
  }

  /**
   * 插入字段
   */
	public function createField($data,$table,$system=0){
    //sql字段值定义
    $property = $this->propertyField[$data['type']];
    //传递的自动类型
    if(isset($data['setup']['fieldtype'])){
      $property['name'] = $data['setup']['fieldtype'];
    }
    //传递的字段长度
    if(isset($data['maxlength']) && !empty($data['maxlength'])){
      $property['maxlen'] = $data['maxlength'];
    }
    //传递的小数点位数
    if(isset($data['setup']['decimaldigits']) && !empty($data['setup']['decimaldigits'])){
      $property['decimal'] = $data['setup']['decimaldigits'];
    }
    //判断是否货币类
    if($property['decimal']){
        $property['decimal'] = ','.$property['decimal'];
    }else{
        $property['decimal'] = '';
    }
    //表名格式化加入前缀和判断
		$table = $this->getPrefix() . ($system ? 'ext_'.$table : $table);
    //插入字段
    if($data['type'] == 'datetime'){
      $sql="ALTER TABLE {$table} ADD {$data['field']} {$property['name']}({$property['maxlen']}{$property['decimal']}) DEFAULT 0 COMMENT '{$data['name']}'";
    }else{
      $sql="ALTER TABLE {$table} ADD {$data['field']} {$property['name']}({$property['maxlen']}{$property['decimal']}) DEFAULT NULL COMMENT '{$data['name']}'";
    }
    $statusSql = Db::execute($sql);
		return $statusSql;
	}

  /**
   * 修改字段
   */
	public function updateField($oldfiled,$data,$table,$system=0){
    $data['setup'] = is_array($data['setup']) ? $data['setup'] : unserialize($data['setup']);
    //sql字段值定义
    $property = $this->propertyField[$data['type']];
    //传递的自动类型
    if(isset($data['setup']['fieldtype'])){
      $property['name'] = $data['setup']['fieldtype'];
    }

    //传递的字段长度
    if(isset($data['maxlength']) && !empty($data['maxlength'])){
      $property['maxlen'] = $data['maxlength'];
    }
    //传递的小数点位数
    if(isset($data['setup']['decimaldigits']) && !empty($data['setup']['decimaldigits'])){
      $property['decimal'] = $data['setup']['decimaldigits'];
    }
    //判断是否货币类
    if($property['decimal']){
        $property['decimal'] = ','.$property['decimal'];
    }else{
        $property['decimal'] = '';
    }
    if($property['name'] == 'varchar'){
      $property['maxlen'] = isset($data['maxlength']) && !empty($data['maxlength']) ? $data['maxlength'] : 255;
    }
// e($property);
		$table = $this->getPrefix() . ($system ? 'ext_'.$table : $table);
    $sql="ALTER TABLE {$table} CHANGE {$oldfiled} {$data['field']} {$property['name']}({$property['maxlen']}{$property['decimal']}) DEFAULT NULL COMMENT '{$data['name']}'";
    $statusSql = Db::execute($sql);
		return $statusSql;
	}
  /**
   * 删除字段
   */
  public function deleteField($table,$field,$system=0){
    $table = $this->getPrefix() . ($system ? 'ext_'.$table : $table);
    $statusSql = Db::execute("ALTER TABLE {$table} DROP COLUMN {$field}");
    return $statusSql;
  }
  /**
   * 检查数据表字段是否存在
   */
  public function checkField($table='',$field='',$system=0)
  {
    $tableName = $this->getPrefix() . ($system ? 'ext_'.$table : $table);
    $tableFields = Db::getTableFields($tableName);
    return in_array($field,$tableFields);
  }

  /**
   * 检查数据表是否存在
   */
  public function checkTable($tableName,$isSystem=0)
  {
    //获取数据库所有表名
    $tables = $this->get_dbname();
    //组装表名
    $table = $this->getPrefix() . ($isSystem ? 'ext_'.$tableName : $tableName);
    //判断表名是否已经存在
    return in_array($table,$tables);
  }
  /**
  * 获取全部表
  * @param string $dbName
  * @return array
  */
 public function getAllTable($dbName = '*') {
     $sql = 'SHOW TABLE STATUS';
     return Db::query($sql);
     $tables = array();
 }
  /**
  * 获取全部表
  * @param string $dbName
  * @return array
  */
 private function get_dbname($dbName = '*') {
     $sql = 'SHOW TABLE STATUS';
     $list = Db::query($sql);
     $tables = array();
     foreach ($list as $value)
     {
         $tables[] = $value['Name'];
     }
     return $tables;
 }

 /**
  * 所有字段
  */
 public function getAllFields($table)
 {
   if(!$table) return [];
   return  Db::getFields($table);
 }
 /**
  * 模型数据格式化
  * @$fieldinfo 字段配置信息 Array
  * @ return string
  */
 public function formatFieldData($fieldInfo,$vo=[]){
   foreach ($fieldInfo  as $key => $field) {
     $defaultValue = $vo[$field['field']];
     $field['setup'] = unserialize($field['setup']);
     	switch($field['type']){
        case 'radio':
          $searchArr = isset($field['setup']['options']) ? explode("\n", $field['setup']['options']) : [];
  				if($searchArr){
  					foreach($searchArr as $k=>$v){
  						$varArr = explode('|',$v);
              $keyVal = isset($varArr[1]) ? trim($varArr[1]) : trim($varArr[0]);
  						if($defaultValue == $keyVal){
  							$vo[$field['field']] = $varArr[0];
  						}
  					}
  				}
        break;
        case 'checkbox':
        case 'select':
          $searchArr = isset($field['setup']['options']) ? explode("\n", $field['setup']['options']) : [];
          $valueArr = explode(',',$defaultValue);
          $newValue = [];
          if($searchArr){
            foreach($searchArr as $k=>$v){
              $varArr = explode('|',$v);
              $keyVal = isset($varArr[1]) ? trim($varArr[1]) : trim($varArr[0]);
              if(in_array($keyVal,$valueArr)){
                $newValue[] = $varArr[0];
              }
            }
            $vo[$field['field']] = implode(',',$newValue);
          }
        break;
        case 'upimage':
          $newValue = [];
          if($valueArr){
            $valueArr = explode(',',$defaultValue);
            foreach($valueArr as $k=>$v){
              $newValue[] = '<img src="'.$v.'">';
            }
            $vo[$field['field']] = implode(',',$newValue);
          }
        case 'upfile':
          $newValue = [];
          if($valueArr){
            $valueArr = explode(',',$defaultValue);
            foreach($valueArr as $k=>$v){
              $newValue[] = '<a href="'.$v.'" taregt="_blank">'.$v.'</a>';
            }
            $vo[$field['field']] = implode(',',$newValue);
          }
        break;
        case 'linkpage':
          if($defaultValue){
            $table = $field['setup']['table'];
            $fieldn = $field['setup']['field'];
            $fieldv = isset($field['setup']['value']) ? $field['setup']['value'] : 'id';
            $vo[$field['field']] = Db::table($table)->where([$fieldv=>$defaultValue])->value($fieldn);
          }
        break;
        default:
        break;
      }
   }
   return $vo;
 }
  /**
   * 自动表单生成
   * @$fieldinfo 字段配置信息 Array
   * @ return string
   */
  public function getFieldData($fieldInfo,$vo=[]){
    $fieldInfo['setup'] = unserialize($fieldInfo['setup']);
    $defaultValue = isset( $vo[$fieldInfo['field']] ) &&  !empty($vo[$fieldInfo['field']]) ?  $vo[$fieldInfo['field']] : (isset($fieldInfo['setup']['default']) ? $fieldInfo['setup']['default'] : '');
    $str = '';
    if($fieldInfo['hidden']){
      $str .="<input type=\"hidden\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\">\n";
    }else{
      switch($fieldInfo['type']){
  			//文本框
  			case 'text':
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";
  				$str .="							<input type=\"text\" id=\"".$fieldInfo['field']."\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\" class=\"layui-input\" placeholder=\"请输入".$fieldInfo['name']."\">\n";
  				if(!empty($fieldInfo['tips'])){
  					$str .="						<p class=\"help-block\">".$fieldInfo['tips']."</p>\n";
  				}
  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;

  			//下拉框
  			case 'select':
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";
  				$str .="							<select class=\"layui-select\" name=\"".$fieldInfo['field']."\" id=\"".$fieldInfo['field']."\">\n";
  				$str .="								<option value=\"\">请选择</option>\n";
  				$searchArr = isset($fieldInfo['setup']['options']) ? explode("\n", $fieldInfo['setup']['options']) : [];
  				if($searchArr){
  					foreach($searchArr as $k=>$v){
  						$varArr = explode('|',$v);
              $keyVal = isset($varArr[1]) ? trim($varArr[1]) : trim($varArr[0]);
  						if($defaultValue == $keyVal){
  							$str .= "								<option selected value=\"".$keyVal."\">".$varArr[0]."</option>\n";
  						}else{
  							$str .= "								<option value=\"".$keyVal."\">".$varArr[0]."</option>\n";
  						}
  					}
  				}

  				$str .= "							</select>\n";
          if(!empty($fieldInfo['tips'])){
  					$str .="						<p class=\"help-block\">".$fieldInfo['tips']."</p>\n";
  				}

  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;

  			//单选框
  			case 'radio':
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";

  			     $searchArr = isset($fieldInfo['setup']['options']) ? explode("\n", $fieldInfo['setup']['options']) : [];

  				if($valArr){
  					foreach($valArr as $k=>$v){
  						$varArr = explode('|',$v);
              $keyVal = isset($varArr[1]) ? trim($varArr[1]) : trim($varArr[0]);
  						if($defaultValue == $keyVal){
  							$str .= "							<input name=\"".$fieldInfo['field']."\" value=\"".$keyVal."\" type=\"radio\" checked title=\"".$varArr[0]."\">\n";
  						}else{
  							$str .= "							<input name=\"".$fieldInfo['field']."\" value=\"".$keyVal."\" type=\"radio\" title=\"".$varArr[0]."\">\n";
  						}

  					}
  				}

  				if(!empty($fieldInfo['tips'])){
  					$str .="							<p class=\"help-block\">".$fieldInfo['tips']."</p>\n";
  				}

  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;

  			//复选框
  			case 'checkbox':
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";
  				$searchArr = isset($fieldInfo['setup']['options']) ? explode("\n", $fieldInfo['setup']['options']) : [];

  				if($searchArr){
  					foreach($searchArr as $k=>$v){
  						$varArr = explode('|',$v);
              $keyVal = isset($varArr[1]) ? trim($varArr[1]) : trim($varArr[0]);
  						if(in_array($keyVal,explode(',',$defaultValue))){
  							$str .= "								<input name=\"".$fieldInfo['field']."\" checked value=\"".$keyVal."\" type=\"checkbox\" title=\"".$varArr[0]."\">\n";
  						}else{
  							$str .= "								<input name=\"".$fieldInfo['field']."\" value=\"".$keyVal."\" type=\"checkbox\" title=\"".$varArr[0]."\">\n";
  						}
  					}
  				}

  				if(!empty($fieldInfo['tips'])){
  					$str .="							<p class=\"help-block\">".$fieldInfo['tips']."</p>\n";
  				}

  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;


  			//文本域
  			case 'textarea':
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";
  				$str .="							<textarea id=\"".$fieldInfo['field']."\" name=\"".$fieldInfo['field']."\"  class=\"layui-textarea\" placeholder=\"请输入".$fieldInfo['name']."\">".$defaultValue."</textarea>\n";
  				if(!empty($fieldInfo['tips'])){
  					$str .="							<p class=\"help-block\">".$fieldInfo['tips']."</p>\n";
  				}
  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;

  			//日期选择框
  			case 'datetime':
          $defaultValue = !empty($defaultValue) ? $defaultValue : date('Y-m-d H:i:s');
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";
  				$str .="							<input type=\"text\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\"  placeholder=\"请输入".$fieldInfo['name']."\" class=\"layui-input layer-date\" data-date-input id=\"".$fieldInfo['field']."\">\n";

  				if(!empty($fieldInfo['tips'])){
  					$str .="							<p class=\"help-block\">".$fieldInfo['tips']."</p>\n";
  				}

  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;

  			//图片上传
  			case 'upimage':
          $upload_allowext = !empty($fieldInfo['setup']['upload_allowext']) ? $fieldInfo['setup']['upload_allowext'] : 'jpg,png,gif,jpeg';
          $multiple = 'one';//one或者btn的时候单个文件，其他多个文件
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
          $str .="						<div class=\"layui-input-block\">\n";
  				$str .="						<div class=\"layui-col-md9 layui-col-xs9\">\n";
  				//多图上传
          if($fieldInfo['setup']['upload_allownum'] > 1){
            $str .="							<textarea type=\"hidden\" id=\"".$fieldInfo['field']."\" name=\"".$fieldInfo['field']."\" class=\"layui-input\" placeholder=\"请输入".$fieldInfo['name']."\">".$defaultValue."</textarea>\n";
            $str .="							<div class=\"pic_list\">\n";
    				$str .="							</div>\n";
            $multiple = 'more';
          }else{
            $str .="							<input type=\"hidden\" id=\"".$fieldInfo['field']."\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\" class=\"layui-input\" placeholder=\"请输入".$fieldInfo['name']."\">\n";
          }
  				if(!empty($fieldInfo['tips'])){
  					$str .="					<p class=\"help-block\">".$fieldInfo['tips']."</p>\n";
  				}
  				$str .="						</div>\n";
  				$str .="						<div class=\"layui-col-md3 layui-col-xs3\" style=\"position:relative;left:10px\">\n";
  				$str .="							<button type=\"button\" data-title=\"上传图片\" data-file=\"{$multiple}\" data-type=\"{$upload_allowext}\" data-field=\"".$fieldInfo['field']."\" class=\"layui-btn layui-btn-primary\">上传</button>\n";
  				$str .="						</div>\n";
          $str .="					  </div>\n";
  				$str .="					</div>\n";
  			break;

  			//文件上传
  			case 'upfile':
          $upload_allowext = !empty($fieldInfo['setup']['upload_allowext']) ? $fieldInfo['setup']['upload_allowext'] : 'doc,docx,xls,xlsx,pdf,zip,rar,mp4,mp3,ppt';
          $multiple = 'one';//one或者btn的时候单个文件，其他多个文件
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
          $str .="						<div class=\"layui-input-block\">\n";
  				$str .="						<div class=\"layui-col-md9 layui-col-xs9\">\n";
  				//多文件上传
          if($fieldInfo['setup']['upload_allownum'] > 1){
            $str .="							<textarea type=\"hidden\" id=\"".$fieldInfo['field']."\" name=\"".$fieldInfo['field']."\" class=\"layui-input\" placeholder=\"请输入".$fieldInfo['name']."\">".$defaultValue."</textarea>\n";
            $str .="							<div class=\"file_list\">\n";
    				$str .="							</div>\n";
            $multiple = 'more';
          }else{
            $str .="							<input type=\"text\" id=\"".$fieldInfo['field']."\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\" class=\"layui-input\" placeholder=\"请输入".$fieldInfo['name']."\">\n";
          }
  				if(!empty($fieldInfo['tips'])){
  					$str .="							<span class=\"help-block m-b-none\">".$fieldInfo['tips']."</span>\n";
  				}

  				$str .="						</div>\n";
  				$str .="						<div class=\"layui-col-md3 layui-col-xs3\" style=\"position:relative;left:10px\">\n";
  				$str .="							<button type=\"button\" data-title=\"上传文件\" data-file=\"{$multiple}\" data-type=\"{$upload_allowext}\" data-field=\"".$fieldInfo['field']."\" class=\"layui-btn layui-btn-primary\">上传</button>\n";
  				$str .="						</div>\n";
          $str .="						</div>\n";
  				$str .="					</div>\n";
  			break;

  			//ckeditor编辑器
  			case 'ckeditor':
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";
  				$str .="								<textarea id=\"".$fieldInfo['field']."\" name=\"".$fieldInfo['field']."\" style=\"width: 100%; height:300px;\">".$defaultValue."</textarea>\n";
  				$str .="								<script type=\"text/javascript\">require(['ckeditor'], function () {var editor = window.createEditor('[name=\"{$fieldInfo['field']}\"]');});</script>\n";
  				if(!empty($fieldInfo['tips'])){
  					$str .="							<span class=\"help-block m-b-none\">".$fieldInfo['tips']."</span>\n";
  				}

  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;
        //kindeditor编辑器
  			case 'kindeditor':
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";
  				$str .="								<textarea id=\"".$fieldInfo['field']."\" name=\"".$fieldInfo['field']."\" style=\"width: 100%; height:300px;\">".$defaultValue."</textarea>\n";
  				$str .="								<script type=\"text/javascript\">require(['kdeditor'], function () {var editor = window.createKindEditor('[name=\"{$fieldInfo['field']}\"]');});</script>\n";

  				if(!empty($fieldInfo['tips'])){
  					$str .="							<span class=\"help-block m-b-none\">".$fieldInfo['tips']."</span>\n";
  				}

  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;

  			//货币
  			case 'number':
  				$str .="					<div class=\"layui-form-item\">\n";
  				$str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
  				$str .="						<div class=\"layui-input-block\">\n";
  				$str .="							<input type=\"text\" id=\"".$fieldInfo['field']."\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\" class=\"layui-input\" placeholder=\"请输入".$fieldInfo['name']."\">\n";
  				if(!empty($fieldInfo['tips'])){
  					$str .="							<span class=\"help-block m-b-none\">".$fieldInfo['tips']."</span>\n";
  				}

  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;


  			//联动菜单
  			case 'linkpage':
          $table = $fieldInfo['setup']['table'];
          $field = $fieldInfo['setup']['field'];
          $fieldv = isset($fieldInfo['setup']['value']) ? $fieldInfo['setup']['value'] : 'id';
          $map = isset($fieldInfo['setup']['where']) ? $fieldInfo['setup']['where'] : '';
          $linkData = Db::table($table)->where($map)->select();
          $linkData = $this->getTreeStr($linkData);//树形结构数据
          $str .="					<div class=\"layui-form-item\">\n";
          $str .="						<label class=\"layui-form-label\">".$fieldInfo['name']."</label>\n";
          $str .="						<div class=\"layui-input-block\">\n";
          $str .="							<select lay-ignore name=\"".$fieldInfo['field']."\" class=\"layui-input\" id=\"".$fieldInfo['field']."\">\n";
  				$str .="								<option value=\"\">请选择</option>\n";

  				if($linkData){
  					foreach($linkData as $k=>$v){
  						if($defaultValue == $v[$fieldv]){
  							$str .= "								<option selected value=\"".$v[$fieldv]."\">".$v[$field]."</option>\n";
  						}else{
  							$str .= "								<option value=\"".$v[$fieldv]."\">".$v[$field]."</option>\n";
  						}
  					}
  				}

  				$str .= "							</select>\n";
          if(!empty($fieldInfo['tips'])){
  					$str .="						<p class=\"help-block\">".$fieldInfo['tips']."</p>\n";
  				}

  				$str .="						</div>\n";
  				$str .="					</div>\n";
  			break;
        case 'memberid':
          $defaultValue = !empty($defaultValue) ? $defaultValue : session('memberid');
          $str ="<input type=\"hidden\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\">\n";
        break;
        case 'siteid':
          $defaultValue = !empty($defaultValue) ? $defaultValue : session('siteid');
          $str ="<input type=\"hidden\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\">\n";
        break;
        case 'userid':
          $defaultValue = !empty($defaultValue) ? $defaultValue : session('user.id');
          $str ="<input type=\"hidden\" value=\"".$defaultValue."\" name=\"".$fieldInfo['field']."\">\n";
        break;
  		}
    }
		return $str;
	}

  /**
   * 树形结构缩进符号
   */
  protected function str_repeat($str='--',$num=0){
      $nstr = '';
      for ($i=0;$i<$num;$i++){
          $nstr .= '&nbsp;&nbsp;'.$str;
      }
      return $nstr;
  }
  /**
   * 递归实现无限极分类
   * @param $array 分类数据
   * @param $pid 父ID
   * @param $level 分类级别
   * @return $list 分好类的数组 直接遍历即可 $level可以用来遍历缩进
   */
  protected function getTreeStr($array, $pid =0, $level = 0,$list=array()){
      foreach ($array as $key => $value){
          //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
          if(isset($value['pid'])){
              if ($value['pid'] == $pid){
                  //父节点为根节点的节点,级别为0，也就是第一级
                  $value['level'] = $level;
                  //把数组放到list中
                  $list[] = $value;
                  //把这个节点从数组中移除,减少后续递归消耗
                  unset($array[$key]);
                  //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                  $list = $this->getTreeStr($array, $value['id'], $level+1,$list);
              }
          }else{
              $value['level'] = $level;
              $list[] = $value;
          }
      }
      return $list;
  }
}
