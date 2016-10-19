<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: account.app.php 634 2013-04-03 06:02:53Z coolmoo $
*/
class userAdmincp{
    function __construct() {
        $this->appid    = iCMS_APP_USER;
        $this->uid      = (int)$_GET['id'];
        $this->groupApp = admincp::app('groups',0);
    }
    function do_update(){
        $data = admincp::fields($_GET['iDT']);
        $data && iDB::update('user',$data,array('uid'=>$this->uid));
        iPHP::success('操作成功!','js:1');
    }
    function do_add(){
        if($this->uid) {
            $rs = iDB::row("SELECT * FROM `#iCMS@__user` WHERE `uid`='$this->uid' LIMIT 1;");
            if($rs){
                $userdata = iDB::row("SELECT * FROM `#iCMS@__user_data` WHERE `uid`='$this->uid' LIMIT 1;");
            }
        }
        include admincp::view("user.add");
    }
    function do_login(){
        if($this->uid) {
            $user = iDB::row("SELECT * FROM `#iCMS@__user` WHERE `uid`='$this->uid' LIMIT 1;",ARRAY_A);
            iPHP::app('user.class','static');
            user::set_cookie($user['username'],$user['password'],$user);
            $url = iPHP::router(array('uid:home',$this->uid));
            iPHP::gotourl($url);
        }
    }
    function do_iCMS(){
        //iPHP::app('user.class','static');
        $sql = "WHERE 1=1";
        $pid = $_GET['pid'];
        if($_GET['keywords']) {
            $sql.=" AND CONCAT(username,nickname) REGEXP '{$_GET['keywords']}'";
        }

        $_GET['gid'] && $sql.=" AND `gid`='{$_GET['gid']}'";
        if(isset($_GET['status']) && $_GET['status']!==''){
            $sql.=" AND `status`='{$_GET['status']}'";
        }
        $_GET['regip'] && $sql.=" AND `regip`='{$_GET['regip']}'";
        $_GET['loginip'] && $sql.=" AND `lastloginip`='{$_GET['loginip']}'";

        if(isset($_GET['pid']) && $pid!='-1'){
            $uri_array['pid'] = $pid;
            if($_GET['pid']==0){
                $sql.= " AND `pid`=''";
            }else{
                iPHP::import(iPHP_APP_CORE .'/iMAP.class.php');
                map::init('prop',$this->appid);
                $map_where = map::where($pid);
            }
        }

        if($map_where){
            $map_sql = iCMS::map_sql($map_where);
            $sql     = ",({$map_sql}) map {$sql} AND `uid` = map.`iid`";
        }
        $orderby    = $_GET['orderby']?$_GET['orderby']:"uid DESC";
        $maxperpage = $_GET['perpage']>0?(int)$_GET['perpage']:20;
        $total      = iPHP::total(false,"SELECT count(*) FROM `#iCMS@__user` {$sql}","G");
        iPHP::pagenav($total,$maxperpage,"个用户");
        $limit  = 'LIMIT '.iPHP::$offset.','.$maxperpage;
        if($map_sql||iPHP::$offset){
            $ids_array = iDB::all("
                SELECT `uid` FROM `#iCMS@__user` {$sql}
                ORDER BY {$orderby} {$limit}
            ");
            //iDB::debug(1);
            $ids   = iCMS::get_ids($ids_array,'uid');
            $ids   = $ids?$ids:'0';
            $sql   = "WHERE `uid` IN({$ids})";
            $limit = '';
        }
        $rs     = iDB::all("SELECT * FROM `#iCMS@__user` {$sql} ORDER BY {$orderby} {$limit}");
        $_count = count($rs);
        $propArray = admincp::getProp("pid",null,'array');
        include admincp::view("user.manage");
    }
    function do_save(){
        $uid      = (int)$_POST['uid'];
        $pid      = implode(',', (array)$_POST['pid']);
        $_pid     = iS::escapeStr($_POST['_pid']);
        $user     = $_POST['user'];
        $userdata = $_POST['userdata'];
        $username = $user['username'];
        $nickname = $user['nickname'];
        $password = $user['password'];
        unset($user['password']);

        $username OR iPHP::alert('账号不能为空');
        preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i",$username) OR iPHP::alert('该账号格式不对');
        $nickname OR iPHP::alert('昵称不能为空');

        $user['regdate']       = iPHP::str2time($user['regdate']);
        $user['lastlogintime'] = iPHP::str2time($user['lastlogintime']);
        $user['pid']           = $pid;

        iPHP::import(iPHP_APP_CORE .'/iMAP.class.php');

       if(empty($uid)) {
            $password OR iPHP::alert('密码不能为空');
            $user['password'] = md5($password);
            iDB::value("SELECT `uid` FROM `#iCMS@__user` where `username` ='$username' LIMIT 1") && iPHP::alert('该账号已经存在');
            iDB::value("SELECT `uid` FROM `#iCMS@__user` where `nickname` ='$nickname' LIMIT 1") && iPHP::alert('该昵称已经存在');
            $uid = iDB::insert('user',$user);
            map::init('prop',iCMS_APP_USER);
            $pid && map::add($pid,$uid);
            $msg = "账号添加完成!";
        }else {
            iDB::value("SELECT `uid` FROM `#iCMS@__user` where `username` ='$username' AND `uid` !='$uid' LIMIT 1") && iPHP::alert('该账号已经存在');
            iDB::value("SELECT `uid` FROM `#iCMS@__user` where `nickname` ='$nickname' AND `uid` !='$uid' LIMIT 1") && iPHP::alert('该昵称已经存在');
            $password && $user['password'] = md5($password);
            iDB::update('user', $user, array('uid'=>$uid));
            map::init('prop',iCMS_APP_USER);
            map::diff($pid,$_pid,$uid);
            if(iDB::value("SELECT `uid` FROM `#iCMS@__user_data` where `uid`='$uid' LIMIT 1")){
                iDB::update('user_data', $userdata, array('uid'=>$uid));
            }else{
                $userdata['uid'] = $uid;
                iDB::insert('user_data',$userdata);
            }
            $msg = "账号修改完成!";
        }
        iPHP::success($msg,'url:'.APP_URI);
    }
    function do_batch(){
    	$idA	= (array)$_POST['id'];
    	$idA OR iPHP::alert("请选择要操作的用户");
    	$ids	= implode(',',(array)$_POST['id']);
    	$batch	= $_POST['batch'];
    	switch($batch){
            case 'prop':
                iPHP::import(iPHP_APP_CORE .'/iMAP.class.php');
                map::init('prop',iCMS_APP_USER);

                $pid = implode(',', (array)$_POST['pid']);
                foreach((array)$_POST['id'] AS $id) {
                    $_pid = iDB::value("SELECT `pid` FROM `#iCMS@__user` where `uid`='$id' LIMIT 1");
                    iDB::update('user',compact('pid'),array('uid'=>$id));
                    map::diff($pid,$_pid,$id);
                }
                iPHP::success('用户属性设置完成!','js:1');

            break;
    		case 'dels':
                iPHP::$break = false;
	    		foreach($idA AS $id){
	    			$this->do_del($id,false);
	    		}
                iPHP::$break = true;
				iPHP::success('用户全部删除完成!','js:1');
    		break;
		}
	}
    function do_del($uid = null,$dialog=true){
    	$uid===null && $uid=$this->uid;
		$uid OR iPHP::alert('请选择要删除的用户');
		iDB::query("DELETE FROM `#iCMS@__user` WHERE `uid` = '$uid'");
        iDB::query("DELETE FROM `#iCMS@__prop_map` WHERE `iid` = '$uid' AND `appid` = '".iCMS_APP_USER."' ;");

		$dialog && iPHP::success('用户删除完成','js:parent.$("#tr'.$uid.'").remove();');
    }
}
