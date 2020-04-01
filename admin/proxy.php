<?php
//websc
function get_user_log()
{
    $result = get_filter();

    if ($result === false) {
        $where = ' WHERE 1 ';
        $filter = array();
        $filter['id'] = !empty($_REQUEST['id']) ? $_REQUEST['id'] : '0';
        $where .= ' AND user_id = \'' . $filter['id'] . '\'';
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users_log') . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);
        $sql = 'SELECT log_id,user_id,change_time,change_type,ip_address,change_city,logon_service,admin_id FROM' . $GLOBALS['ecs']->table('users_log') . ($where . '  ORDER BY change_time DESC');
        set_filter($filter, $sql);
    }
    else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
    $arr = array();

    while ($rows = $GLOBALS['db']->fetchRow($res)) {
        if (0 < $rows['change_time']) {
            $rows['change_time'] = local_date('Y-m-d H:i:s', $rows['change_time']);
        }

        if (0 < $rows['admin_id']) {
            $sql = 'SELECT user_name FROM' . $GLOBALS['ecs']->table('admin_user') . ' WHERE user_id = \'' . $rows['admin_id'] . '\'';
            $rows['admin_name'] = $GLOBALS['_LANG']['manage_alt'] . $GLOBALS['db']->getOne($sql);
        }
        else {
            $rows['admin_name'] = $GLOBALS['_LANG']['user_handle'];
        }

        $arr[] = $rows;
    }

    return array('list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

function user_date($result)
{
    if (empty($result)) {
        return i($GLOBALS['_LANG']['not_fuhe_date']);
    }

    $data = i($GLOBALS['_LANG']['user_date_notic'] . "\n");
    $count = count($result);

    for ($i = 0; $i < $count; $i++) {
        if (empty($result[$i]['ru_name'])) {
            $result[$i]['ru_name'] = $GLOBALS['_LANG']['mall_user'];
        }

        $data .= i($result[$i]['user_id']) . ',' . i($result[$i]['user_name']) . ',' . i($result[$i]['ru_name']) . ',' . i($result[$i]['mobile_phone']) . ',' . i($result[$i]['email']) . ',' . i($result[$i]['is_validated']) . ',' . i($result[$i]['user_money']) . ',' . i($result[$i]['frozen_money']) . ',' . i($result[$i]['rank_points']) . ',' . i($result[$i]['pay_points']) . ',' . i($result[$i]['reg_time']) . "\n";
    }

    return $data;
}

function i($strInput)
{
    return iconv('utf-8', 'gb2312', $strInput);
}

function user_list()
{
    $result = get_filter();

    if ($result === false) {
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['rank'] = empty($_REQUEST['rank']) ? 0 : intval($_REQUEST['rank']);
        $filter['pay_points_gt'] = empty($_REQUEST['pay_points_gt']) ? 0 : intval($_REQUEST['pay_points_gt']);
        $filter['pay_points_lt'] = empty($_REQUEST['pay_points_lt']) ? 0 : intval($_REQUEST['pay_points_lt']);
        $filter['mobile_phone'] = empty($_REQUEST['mobile_phone']) ? 0 : addslashes($_REQUEST['mobile_phone']);
        $filter['email'] = empty($_REQUEST['email']) ? 0 : addslashes($_REQUEST['email']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'u.user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $checked = empty($_REQUEST['checkboxes']) ? array() : $_REQUEST['checkboxes'];


        // $proxy_type=0;//初始代理'];
        $proxy_type = empty($_REQUEST['proxy_type']) ? 0: $_REQUEST['proxy_type'];

        $ex_where = ' WHERE 1 ';

        if($proxy_type==0){
            //初始账号
            $ex_where.=' and ISNULL(p_1) ';
        }elseif($proxy_type==1){
            //一级账号
            $ex_where.=' and ISNULL(p_2) and p_1<>0 ';
        }elseif($proxy_type==2){
            //二级账号
            $ex_where.=' and ISNULL(p_3) and p_2<>0  ';
        }elseif($proxy_type==3){
            //三级账号
            $ex_where.=' and ISNULL(p_4) and p_3<>0 ';
        }else{
            $ex_where.='  and p_4<>0 ';
        }

        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';
        $store_where = '';
        $store_search_where = '';

        if ($filter['store_search'] != 0) {
            if ($ru_id == 0) {
                if ($_REQUEST['store_type']) {
                    $store_search_where = 'AND msi.shopNameSuffix = \'' . $_REQUEST['store_type'] . '\'';
                }

                if ($filter['store_search'] == 1) {
                    $ex_where .= ' AND u.user_id = \'' . $filter['merchant_id'] . '\' ';
                }
                else if ($filter['store_search'] == 2) {
                    $store_where .= ' AND msi.rz_shopName LIKE \'%' . mysql_like_quote($filter['store_keyword']) . '%\'';
                }
                else if ($filter['store_search'] == 3) {
                    $store_where .= ' AND msi.shoprz_brandName LIKE \'%' . mysql_like_quote($filter['store_keyword']) . '%\' ' . $store_search_where;
                }

                if (1 < $filter['store_search']) {
                    $ex_where .= ' AND (SELECT msi.user_id FROM ' . $GLOBALS['ecs']->table('merchants_shop_information') . ' as msi ' . (' WHERE msi.user_id = u.user_id ' . $store_where . ') > 0 ');
                }
            }
        }

        if ($filter['keywords']) {
            $ex_where .= ' AND (u.user_name LIKE \'%' . mysql_like_quote($filter['keywords']) . '%\' OR u.nick_name LIKE \'%' . mysql_like_quote($filter['keywords']) . '%\')';
        }

        if ($filter['mobile_phone']) {
            $ex_where .= ' AND u.mobile_phone = \'' . $filter['mobile_phone'] . '\'';
        }

        if ($filter['email']) {
            $ex_where .= ' AND u.email = \'' . $filter['email'] . '\'';
        }

        if ($filter['rank']) {
            $sql = 'SELECT min_points, max_points, special_rank FROM ' . $GLOBALS['ecs']->table('user_rank') . (' WHERE rank_id = \'' . $filter['rank'] . '\'');
            $row = $GLOBALS['db']->getRow($sql);

            if (0 < $row['special_rank']) {
                $ex_where .= ' AND u.user_rank = \'' . $filter['rank'] . '\' ';
            }
            else {
                $ex_where .= ' AND u.rank_points >= ' . intval($row['min_points']) . ' AND u.rank_points < ' . intval($row['max_points']);
            }
        }

        if ($filter['pay_points_gt']) {
            $ex_where .= ' AND u.pay_points < \'' . $filter['pay_points_gt'] . '\' ';
        }

        if ($filter['pay_points_lt']) {
            $ex_where .= ' AND u.pay_points >= \'' . $filter['pay_points_lt'] . '\' ';
        }

        if ($checked) {
            $ex_where .= ' AND u.user_id ' . db_create_in($checked);
        }

        $filter['record_count'] = $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users') . ' AS u ' . $ex_where);
        $filter = page_and_size($filter);

        $filter['page_size']=10;

        $limit = ' LIMIT ' . $filter['start'] . ',' . $filter['page_size'];

        if ($_REQUEST['export'] == 1) {
            $limit = '';
        }

        $sql = 'SELECT p_1, p_2, p_3, p_4,u.user_rank,u.user_id, u.user_name, u.nick_name, u.mobile_phone, u.email, u.is_validated, u.user_money, u.frozen_money, u.rank_points, u.pay_points, u.reg_time,rank_points ' . ' FROM ' . $GLOBALS['ecs']->table('users') . ' AS u ' . $ex_where . ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . $limit;

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    }
    else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $user_list = $GLOBALS['db']->getAll($sql);

    $count = count($user_list);

    for ($i = 0; $i < $count; $i++) {

      $li_user_id   =  $user_list[$i]['user_id'];

      $sql='SELECT user_id  FROM ' . $GLOBALS['ecs']->table('users').' where  locate(\''.$li_user_id.'\',p_string)';
      $team=    $GLOBALS['db']->getall($sql);
      $team_total=  count($team);

      $team_p1=$GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users').' where p_1='.$li_user_id);
      $team_p2=$GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users').' where p_2='.$li_user_id);
      $team_p3=$GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('users').' where p_3='.$li_user_id);

        $user_list[$i]['team_p1'] =$team_p1;
        $user_list[$i]['team_p2'] =$team_p2;
        $user_list[$i]['team_p3'] =$team_p3;
        $user_list[$i]['team_total'] =$team_total;


      //——————————————————当月团队业绩

        $total_fee = 'sum(goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ';
        $son_user_id='(SELECT user_id  FROM ' . $GLOBALS['ecs']->table('users').' where  locate(\''.$li_user_id.'\',p_string))';
        $month_start = strtotime(date("Y-m-01"));//本月时间戳
        $sql = 'SELECT  ' . $total_fee . ' FROM ' . $GLOBALS['ecs']->table('order_info') .' WHERE user_id in ' . $son_user_id .' and shipping_status=2 and confirm_take_time>'.$month_start;
        $team_total_fee=    $GLOBALS['db']->getone($sql);

        $user_list[$i]['team_total_fee']=$team_total_fee;


        if($team_total_fee>0){
            $proxy_rank = $GLOBALS['db']->getrow('SELECT * FROM' . $GLOBALS['ecs']->table('user_proxy_rank') . ' WHERE proxy_achievement <='.$team_total_fee .' order by proxy_achievement desc limit 1');
            if($proxy_rank){
                $user_list[$i]['proxy_rank']=$proxy_rank['proxy_rank_name'];   //当前代理等级
                $user_list[$i]['proxy_profit']=$proxy_rank['proxy_profit'];   //分成比例
                $user_list[$i]['max_profit_money']=$proxy_rank['proxy_profit']/100*$team_total_fee;  //最大分成金额


                //查看下级业绩  抽成金额
                if(  $user_list[$i]['max_profit_money']>0){
                    $user_list[$i]['son_profit']=son_profit($li_user_id,$proxy_type);   //下级分成金额
                }

                $user_list[$i]['real_profit']=$user_list[$i]['max_profit_money']-$user_list[$i]['son_profit'];//当前实际分成

            }
        }

        //——————————————————————————————当月团队业绩



        if ($user_list[$i]['rank_name'] == '') {
            $user_list[$i]['rank_name'] = $GLOBALS['_LANG']['not_rank'];
        }
    }

    $arr = array('user_list' => $user_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}


//下级抽成
function son_profit($user_id, $proxy_type)
{
    $total = 0;
    if ($proxy_type == 3) {
        return $total;
    }


    $sql = 'SELECT user_id  FROM ' . $GLOBALS['ecs']->table('users') . ' where p_1=' . $user_id;
    $team = $GLOBALS['db']->getall($sql);

    foreach ($team as $li) {
        $total_fee = 'sum(goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ';
        $son_user_id = '(SELECT user_id  FROM ' . $GLOBALS['ecs']->table('users') . ' where  locate(\'' . $li['user_id'] . '\',p_string))';
        $month_start = strtotime(date("Y-m-01"));//本月时间戳
        $sql = 'SELECT  ' . $total_fee . ' FROM ' . $GLOBALS['ecs']->table('order_info') . ' WHERE user_id in ' . $son_user_id . ' and shipping_status=2 and confirm_take_time>' . $month_start;
        $team_total_fee = $GLOBALS['db']->getone($sql);

        if($team_total_fee>0){
            $proxy_rank = $GLOBALS['db']->getrow('SELECT * FROM' . $GLOBALS['ecs']->table('user_proxy_rank') . ' WHERE proxy_achievement <='.$team_total_fee .' order by proxy_achievement desc limit 1');
            if($proxy_rank){
                $son_profit_money=$proxy_rank['proxy_profit']/100*$team_total_fee;
                if($son_profit_money>0){
                    $total=$total+$son_profit_money;
                }
            }
        }
    }


    return $total;

}







function user_update($user_id, $args)
{
    if (empty($args) || empty($user_id)) {
        return false;
    }

    return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $args, 'update', 'user_id=\'' . $user_id . '\'');
}

define('IN_ECS', true);
require dirname(__FILE__) . '/includes/init.php';
$adminru = get_admin_ru_id();

if ($adminru['ru_id'] == 0) {
    $smarty->assign('priv_ru', 1);
}
else {
    $smarty->assign('priv_ru', 0);
}

if ($_REQUEST['act'] == 'list') {
    admin_priv('users_manage');



    $proxy_type = empty($_REQUEST['proxy_type']) ? 0: $_REQUEST['proxy_type'];




    $smarty->assign('menu_select', array('action' => '08_members', 'current' => '03_users_list'));
    $sql = 'SELECT rank_id, rank_name, min_points FROM ' . $ecs->table('user_rank') . ' ORDER BY min_points ASC ';
    $rs = $db->query($sql);
    $ranks = array();

    while ($row = $db->FetchRow($rs)) {
        $ranks[$row['rank_id']] = $row['rank_name'];
    }

    $smarty->assign('proxy_type', $proxy_type);
    $smarty->assign('user_ranks', $ranks);
    $smarty->assign('ur_here', $_LANG['03_users_list']);
    $smarty->assign('action_link', array('text' => $_LANG['04_users_add'], 'href' => 'users.php?act=add'));
    $smarty->assign('action_link2', array('text' => $_LANG['12_users_export'], 'href' => 'javascript:download_userlist();'));

    $user_list = user_list();
    $smarty->assign('user_list', $user_list['user_list']);
    $smarty->assign('filter', $user_list['filter']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('page_count', $user_list['page_count']);
    $smarty->assign('full_page', 1);
    $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');
    assign_query_info();
    $smarty->display('proxy_list.dwt');
}


?>
