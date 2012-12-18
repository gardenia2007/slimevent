<?php
/**
 * SEHomeClass
 *
 * @package   Slimevent
 **/

/**
 * Action for Home
 */

class SEHome extends SECommon{

	function __construct()
	{
		parent::__construct();
	}

	function test()
	{
	}


	function feedback(){
	}

	function show_feedback(){
		echo "feedback";
	}

	function find_by($key, $word){
		$data = array(':a' => $word);
		$note = "您正在查看";
		switch($key)
		{
			case 'keyword':
				$note .= "活动信息中包含关键词<strong>{$word}</strong>的活动";
				$con = "title LIKE :a OR label LIKE :b OR introduction LIKE :c ";
				$word = '%'.$word.'%';
				$data = array(':a'=>$word, ':b'=>$word, ':c'=>$word);
				break;
			case 'category_id':
			case 'category':
				$con = "category_id = :a ";
				$c = Category::get_name($word);
				$note .= "类别为<strong>{$c}</strong>的活动";
				break;
			case 'organizer_id':
			case 'organizer':
				$con = "organizer_id = :a ";
				$info = Account::get_user($word);
				$note .= "由<strong>{$info['nickname']}</strong>主办(发起)的活动";
				break;
			case 'region':
				$con = "region = :a ";
				$region = F3::get("REGION");
				$note .= "在<strong>{$region[$word]}</strong>举办的活动";
				break;
			case 'time_status':
				$now = time();
				if($word == F3::get('EVENT_NOT_BEGIN'))  //尚未开始
					$con = "`begin_time` > $now ";
				else if($word == F3::get('EVENT_IS_RUNNING'))   //进行中
					$con = "`begin_time` < $now AND `end_time` > $now";
				else  //已结束
					$con = "`end_time` < $now ";
				$data = array();
				$note .= "在<strong>{$word}</strong>举办的活动";
				break;
			case '':
				$con = "eid = :a ";
				break;
			default:
				$con = "eid = :a ";
		}
		return array('con'=>$con,'array'=>$data, 'note'=>$note);
	}

	function find(){
		$key = F3::get("GET.key");
		$word = F3::get("GET.word");

		$data = $this->find_by($key, $word);
		$url = "find/by?key={$key}&word={$word}";


		//Code::dump($query);
		$event = new SEEvent();
		$event->show_by($url, $data['con'], $data['array'], 'events');

		$event->show_by("", '`event`.`status` = :e ORDER BY RAND() DESC',
			array(':e' => F3::get("EVENT_PASSED_STATUS")), 'guess_events', 5);

		F3::set('note', $data['note']);
		echo Template::serve('find/result.html');
		//Code::dump(F3::get('events'));
	}

	function show_find(){
		$category = Category::get_all();
		F3::set("category", $category);
		echo Template::serve('find/find.html');
	}


	function run()
	{
		$event = new SEEvent();

		$event->show_by("", '', array(), 'hot_events', 4);

		foreach(F3::get("INDEX_BLOCK") as $b){
			$event->show_by("", $b['con'].' = :c AND `event`.`status` = :e',
				array(':c' => $b['value'], ':e' => F3::get("EVENT_PASSED_STATUS")), 'event.'.$b["name"], 4);
		}

		$event->show_by("", '`event`.`status` = :e ORDER BY RAND() DESC',
			array(':e' => F3::get("EVENT_PASSED_STATUS")), 'guess_events', 5);

		$event->show_by("", '`event`.`status` = :e ORDER BY `post_time` DESC',
			array(':e' => F3::get("EVENT_PASSED_STATUS")), 'newst_events', 5);
		
		echo Template::serve('index.html');
	}

	function my()
	{
		$gay = new SECommon();
		$gay->my();
	}


	function show_login()
	{	
		switch(F3::get('GET.auth'))
		{
			case F3::get('CAS_AUTH'):
				$name = CAS::login();
				$pwd = F3::get('DEFAULT_PWD');
				if(Account::exists($name) === false)  //首次通过CAS登录
				{
					$group = F3::get('STUDENT_GROUP');
					$nickname = "S".$name;
					Admin::add_user($name, $pwd, $group, $nickname);
				}
				break;
			case F3::get('CLUB_AUTH'):
				echo Template::serve('club/login.html');
				return;
			default:
				F3::reroute('/');
		}

		Account::login($name, $pwd);
		F3::reroute('/');
	}

	function login()
	{
		$user_name = F3::get('POST.user_name');
		$user_pwd = F3::get('POST.user_pwd');

		$user = Account::login($user_name, $user_pwd); 

		if($user === false)
			F3::reroute('/club/login/?show_msg=1');  
		else 
			F3::reroute('/');
	}

	function logout()
	{
		//			CAS::logout("http://www.baidu.com");
		//if(Account::is_login() !== false)
		//{
			//if(Account::the_user_group() == F3::get('STUDENT_GROUP'))
				//echo "bb";
			//}

		Account::logout();
		F3::reroute('/');
	}

	function get_praise_info()
	{
		$uid = trim(F3::get("POST.uid"));
		$eid = trim(F3::get("POST.eid"));
		$num = count(PraiseList::get_praise_user($eid));

		if($uid == "")  //当前属于没有登录
			echo "推一下($num)";
		else if($uid == Account::the_user_id())  //合法登录用户
		{
			if(PraiseList::is_user_praise_event($uid, $eid) === true)
				echo "已推($num)";
			else
				echo "推一下($num)";
		}
		else
			echo "非法登录";
	}

	function get_join_info()
	{
		$uid = trim(F3::get("POST.uid"));
		$eid = trim(F3::get("POST.eid"));
		$num = count(JoinList::get_join_user($eid));

		if($uid == "")  //当前属于没有登录
			echo "我要报名($num)";
		else if($uid == Account::the_user_id())  //合法登录用户
		{
			if(JoinList::is_user_join_event($uid, $eid) === true)
				echo "取消报名($num)";
			else
				echo "我要报名($num)";
		}
		else
			echo "非法登录";
	}

};

?>
