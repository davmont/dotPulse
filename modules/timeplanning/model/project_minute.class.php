<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

class ProjectMinute {
	
	private $id=NULL; 
    private $description=NULL; 
    private $isResource=NULL; 
	private $isEffort=NULL; 
	private $isDuration=NULL; 
	private $isSize=NULL; 
    private $date=NULL; 
	private $members=NULL;
	
	public function getId(){
		return $this->id;
	}
	
	public function getMembers(){
		return $this->members;
	}
	
	public function getDate(){
		return $this->date;
	}
	
	public function getDescription(){
		return $this->description;
	}

	// Minutes were written with a rich text editor (TinyMCE) before dotPulse 3.1.1 and
	// are still stored as HTML; they are edited as plain text. These two convert.
	public static function fromPlainText($text){
		$text = str_replace(array("\r\n", "\r"), "\n", trim((string)$text));
		return $text === '' ? '' : '<p>' . str_replace("\n", '<br />', htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) . '</p>';
	}

	public static function plainText($description){
		$description = (string)$description;
		if ($description === strip_tags($description)) {
			return $description;
		}
		$text = preg_replace('#<(script|style)\b.*?</\1\s*>#is', '', $description);
		$text = preg_replace('#<br\s*/?>|</(p|div|li|h[1-6]|tr|blockquote)>#i', "\n", $text);
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
		$text = str_replace("\xC2\xA0", ' ', $text);
		return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace("/[ \t]+\n/", "\n", $text)));
	}
	
	public function isEffort(){
		return $this->isEffort;
	}
	
	public function isDuration(){
		return $this->isDuration;
	}
	
	public function isResource(){
		return $this->isResource;
	}
	
	public function isSize(){
		return $this->isSize;
	}
	
	function __construct()	{
	}
	
	function store($description,$date,$project_id,$id,$isEffort,$isDuration,$isResource,$isSize,$membersIds) {
		$id = (int)$id; // used in SQL below
		$q = new DBQuery();
		$q->addTable('project_minutes');
		if ($id!="-1"){
			$q->addUpdate('description', $description);
			$q->addUpdate('minute_date', $date);
			$q->addUpdate('isEffort', $isEffort);
			$q->addUpdate('isDuration', $isDuration);
			$q->addUpdate('isResource', $isResource);
			$q->addUpdate('isSize', $isSize);
			$q->addWhere('id =' . (int)$id);
		}else{
			$q->addInsert('description', $description);
			$q->addInsert('minute_date', $date);
			$q->addInsert('project_id', $project_id);
			$q->addInsert('isEffort', $isEffort);
			$q->addInsert('isDuration', $isDuration);
			$q->addInsert('isResource', $isResource);
			$q->addInsert('isSize', $isSize);
		}
		$q->exec();
		if($id==-1){
			$qId = new DBQuery();
			$qId->addQuery('max(id)');
			$qId->addTable('project_minutes');
			$results = db_loadHashList($qId->prepare(true), 'id');
			foreach ($results as $id => $data) {
				$id=$data[0];
			}
		}
		$this->setMembers($membersIds,$id);
	}
	
	function setMembers($membersIds,$id){
		global $db;
		$this->deleteMembersRelations($id);
		$db->StartTrans();
		foreach($membersIds as $memberId){
			if($memberId!=""){
				$q = new DBQuery();
				$q->addTable('task_minute_members');
				$q->addInsert('task_minute_id', $id);
				$q->addInsert('user_id', $memberId);
				$q->exec();
			}
		}
		$db->CompleteTrans();
	}
	
	function deleteMembersRelations($id){
		$id = (int)$id; // used in SQL below
		$q = new DBQuery();
		$q->setDelete('task_minute_members');
		$q->addWhere('task_minute_id =' . (int)$id);
		$result=true;
		if (!$q->exec()){
			$result=false;
		}
		return $result;
	}
	
	function load($minute_id) {
		$minute_id = (int)$minute_id; // used in SQL below
		$q = new DBQuery;
		$q->addQuery("tm.description, tm.minute_date,tm.id,tm.isEffort,tm.isSize,tm.isDuration,tm.isResource");
		$q->addTable('project_minutes', 'tm');
		$q->addWhere('id =' . (int)$minute_id);
		$results = db_loadHashList($q->prepare(true), 'id');
		$hasMinute=false;
		// Defaults when the minute does not exist (a new one).
		$id = $description = $date = '';
		$isEffort = $isSize = $isDuration = $isResource = 0;
		foreach ($results as $id => $data) {
			$description=$data[0];
			$date=$data[1];
			$id=$data[2];
			$dateObj=new DateTime($date);
			$date=$dateObj->format('d/m/Y');
			$isEffort=$data[3];
			$isSize=$data[4];
			$isDuration=$data[5];
			$isResource=$data[6];
		}
		$this->id=$id;
		$this->description=$description;
		$this->date=$date;
		$this->isResource=$isResource;
		$this->isDuration=$isDuration;
		$this->isSize=$isSize;
		$this->isEffort=$isEffort;
		
		$q = new DBQuery();
		$q->addTable('task_minute_members','tm');
		$q->addQuery("tm.user_id");
		$q->addWhere('task_minute_id =' . (int)$minute_id);
		$results = db_loadHashList($q->prepare(true), 'user_id');
		$this->members=array();
		foreach ($results as $user_id => $data) {
			$key=$data[0]."";
			$this->members[$key]=$key;
		}
	}

	function delete($id){
		$id = (int)$id; // used in SQL below
		$q = new DBQuery();
		$q->setDelete('project_minutes');
		$q->addWhere('id =' . (int)$id);
		$result=true;
		if (!$q->exec()){
			$result=false;
		}
		return $result;
	}
	
	
	
}
