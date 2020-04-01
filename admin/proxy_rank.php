<?php
//源码由旺旺:ecshop2012所有 未经允许禁止倒卖 一经发现停止任何服务
define('IN_ECS', true);
require dirname(__FILE__) . '/includes/init.php';
$exc = new exchange($ecs->table('user_rank'), $db, 'rank_id', 'rank_name');
$exc_user = new exchange($ecs->table('users'), $db, 'user_rank', 'user_rank');

 if($_REQUEST['act'] == 'list'){
    $ranks = array();
    $ranks = $db->getAll('SELECT * FROM ' . $ecs->table('user_proxy_rank'));
    $smarty->assign('menu_select', array('action' => '08_members', 'current' => 'user_affiliate_rank'));
    $smarty->assign('action_link', array('text' => '添加代理等级', 'href' => 'proxy_rank.php?act=proxy_rank_add'));
    $smarty->assign('user_ranks', $ranks);
    $smarty->assign('ur_here', '会员代理等级');
    $smarty->assign('full_page', 1);
    $smarty->display('proxy_rank_list.dwt');
}
else if($_REQUEST['act'] == 'proxy_rank_add'){
    $id=intval($_REQUEST['id']);
	if(!empty($id)){
        $info = $db->getrow('SELECT * FROM ' . $ecs->table('user_proxy_rank').' where id= '.$id);
        $smarty->assign('ur_here', '编辑代理等级');
	}else{
        $smarty->assign('ur_here', '添加代理等级');
	}
    $smarty->assign('form_action', 'proxy_add_rank');
    $smarty->assign('info', $info);
    $smarty->assign('action_link', array('text' => '推荐代理列表', 'href' => 'proxy_rank.php?act=list'));
    $smarty->display('proxy_rank_add.dwt');
}
else if($_REQUEST['act'] == 'proxy_add_rank'){
   if(empty($_REQUEST['proxy_rank_name'])) sys_msg('名称不能为空！', 1);
   if(empty($_REQUEST['proxy_achievement'])) sys_msg('业绩不能为空！', 1);
   if(empty($_REQUEST['proxy_profit'])) sys_msg('提成不能为空！', 1);


   $proxy_rank_name=trim($_REQUEST['proxy_rank_name']);
   $proxy_achievement=$_REQUEST['proxy_achievement']?intval($_REQUEST['proxy_achievement']):0;
   $proxy_profit=number_format($_REQUEST['proxy_profit'],2);
   $id=$_REQUEST['id']?intval($_REQUEST['id']):'';
   if(!empty($id)){
   	   $sql='update '.$ecs->table('user_proxy_rank').' set proxy_rank_name=\''.$proxy_rank_name.'\', proxy_achievement='.$proxy_achievement.',proxy_profit='.$proxy_profit.' where id='.$id;
       $action_name='返回编辑列表';
   }else{
       $sql='insert into '.$ecs->table('user_proxy_rank').'(proxy_rank_name,proxy_achievement,proxy_profit) values(\''.$proxy_rank_name.'\','.$proxy_achievement.','.$proxy_profit.')';
       $action_name='继续添加';
   }

    $db->query($sql);
    $lnk[] = array('text' => '返回推荐代理列表', 'href' => 'proxy_rank.php?act=list');
    $lnk[] = array('text' => $action_name, 'href' => 'proxy_rank.php?act=rank_add&id='.$id);
    sys_msg('操作成功！', 0, $lnk);
}else if($_REQUEST['act'] =="del_rank"){
    $id=$_REQUEST['id']?intval($_REQUEST['id']):'';
	if(!empty($id)){
        $sql='delete from '.$ecs->table('user_proxy_rank').' where id='.$id;
        $db->query($sql);
	}
    $url = 'proxy_rank.php?act=list';
    ecs_header('Location: ' . $url . "\n");
    exit();
}
else if ($_REQUEST['act'] == 'query') {
	$ranks = array();
	$ranks = $db->getAll('SELECT * FROM ' . $ecs->table('user_rank'));
	$smarty->assign('rank_count', count($ranks));
	$smarty->assign('user_ranks', $ranks);
	make_json_result($smarty->fetch('user_rank.dwt'));
}


?>
