<?php

//-----------------------------
//	Developer	: Shabnam.Jafarkhani
//	Date		: 2015.06
//-----------------------------

namespace App\models;

use Core\PdoDataAccess;
use Core\ExceptionHandler;
use Core\CurrencyModule;

use App\models\BasicInfo;

class ACC_docs extends PdoDataAccess {

	public $DocID;
	public $CycleID;
	public $BranchID;
	public $LocalNo;
	public $DocDate;
	public $RegDate;
	public $DocStatus;
	public $StatusID;
	public $DocType;
	public $SubjectID;
	public $description;
	public $regPersonID;
	public $EventID;
	
	public $_EventTitle;

	function __construct($DocID = "", $pdo = null) {

		$this->DT_DocID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_CycleID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_BranchID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_LocalNo = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_DocDate = DataMember::CreateDMA(InputValidation::Pattern_Date);
		$this->DT_RegDate = DataMember::CreateDMA(InputValidation::Pattern_Date);
		$this->DT_DocStatus = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_StatusID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_DocType = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_SubjectID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_description = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNumSafe);
		$this->DT_regPersonID = DataMember::CreateDMA(DataMember::Pattern_Num);
		$this->DT_EventID = DataMember::CreateDMA(DataMember::Pattern_Num);

		if ($DocID != "")
			parent::FillObject($this, "select d.*,ifnull(e.EventTitle,'') as _EventTitle 
				from ACC_docs d
				left join COM_events e using(EventID)
				where DocID=?", array($DocID), $pdo);
	}

	static function GetAll($where = "", $whereParam = array()) {

		$query = "select sd.*, 
				bch.BranchName,
				concat_ws(' ',fname,lname,CompanyName) as regPerson, 
				b.InfoDesc SubjectDesc,b2.InfoDesc DocTypeDesc,
				fs.StepID,
				ifnull(e.EventTitle,'') EventTitle,
				fr.ActionType
			
			from ACC_docs sd
			left join COM_events e using(EventID)
			left join BSC_branches bch using(BranchID)
			left join BaseInfo b on(b.TypeID=73 AND b.InfoID=SubjectID)
			left join BaseInfo b2 on(b2.TypeID=9 AND b2.InfoID=DocType)
			left join BSC_persons p on(regPersonID=PersonID)
			left join WFM_FlowSteps fs on(fs.FlowID=".FLOWID_ACCDOC." AND fs.StepID=sd.StatusID)
			left join WFM_FlowRows fr on(fr.IsLastRow='YES' AND fr.FlowID=fs.FlowID AND fr.StepRowID=fs.StepRowID AND fr.ObjectID=sd.DocID)
		";

		$query .= ($where != "") ? " where " . $where : "";

		return parent::runquery_fetchMode($query, $whereParam);
	}

	function SaveTrigger($pdo = null) {

		if ($this->LocalNo != "") {
			$dt = PdoDataAccess::runquery("select * from ACC_docs 
			where BranchID=? AND CycleID=? AND LocalNo=?", array($this->BranchID, $this->CycleID, $this->LocalNo), $pdo);

			if (count($dt) > 0) {
				if (empty($this->DocID) || $this->DocID != $dt[0]["DocID"]) {
					ExceptionHandler::PushException("شماره سند تکراری است");
					return false;
				}
			}
			//..................................................	
			$DocDate = $this->DocDate;
			
		}

		return true;
	}

	function Add($pdo = null) {

		$temp = parent::runquery("select * from ACC_cycles where CycleID=?", array($this->CycleID));
		if ($temp[0]["IsClosed"] == "YES") {
			ExceptionHandler::PushException("دوره مالی مربوطه بسته شده است");
			return false;
		}		
		
		$pdo2 = $pdo == null ? PdoDataAccess::getPdoObject() : $pdo;
		if ($pdo == null)
			$pdo2->beginTransaction();

		if ($this->LocalNo == "")
			$this->LocalNo = parent::GetLastID("ACC_docs", "LocalNo", "CycleID=?", array($this->CycleID), $pdo2) + 1;

		if (!$this->SaveTrigger($pdo2)) {
			if ($pdo == null)
				$pdo2->rollBack();
			return false;
		}

		if (!parent::insert("ACC_docs", $this, $pdo2)) {
			if ($pdo == null)
				$pdo2->rollBack();
			return false;
		}

		$this->DocID = parent::InsertID($pdo2);

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->DocID;
		$daObj->TableName = "ACC_docs";
		$daObj->execute($pdo2);

		if ($pdo == null)
			$pdo2->commit();
		return true;
	}

	function Edit($pdo = null) {

		if (!$this->SaveTrigger($pdo))
			return false;

		if (parent::update("ACC_docs", $this, " DocID=:did", array(":did" => $this->DocID), $pdo) === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->DocID;
		$daObj->TableName = "ACC_docs";
		$daObj->execute($pdo);

		return true;
	}

	static function Remove($DocID, $pdo = null) {

		$temp = parent::runquery("select * from ACC_docs join ACC_cycles using(CycleID)
			where DocID=?", array($DocID));
		if (count($temp) == 0)
			return false;

		if ($temp[0]["StatusID"] != ACC_STEPID_RAW) {
			ExceptionHandler::PushException("سند مربوطه تایید شده و قابل حذف نمی باشد");
			return false;
		}
		if ($temp[0]["IsClosed"] == "YES") {
			ExceptionHandler::PushException("دوره مربوطه بسته شده و سند قابل حذف نمی باشد.");
			return false;
		}
		if($temp[0]["EventID"]*1 > 0)
		{
			$eobj = new COM_events($temp[0]["EventID"]);
			if($eobj->IsSystemic == "YES")
			{
				ExceptionHandler::PushException("اسنادی که به صورت اتومات صادر می شوند باید از زیر سیستم مربوطه حذف گردند");
				return false;
			}
		}

		if ($pdo == null) {
			$pdo2 = parent::getPdoObject();
			$pdo2->beginTransaction();
		}
		else
			$pdo2 = $pdo;
		$result = parent::delete("ACC_DocCheques", "DocID=?", array($DocID), $pdo2);
		if ($result === false)
			return false;

		$result = parent::delete("ACC_DocItems", "DocID=?", array($DocID), $pdo2);
		if ($result === false)
			return false;

		$result = parent::delete("ACC_docs", "DocID=?", array($DocID), $pdo2);
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $DocID;
		$daObj->TableName = "ACC_docs";
		$daObj->execute($pdo2);

		if ($pdo == null)
			$pdo2->commit();
		return true;
	}

	static function GetLastLocalNo() {

		$no = parent::GetLastID("ACC_docs", "LocalNo", "CycleID=?", 
				array($_SESSION["accounting"]["CycleID"]));

		return $no + 1;
	}

	static function GetPureRemainOfSaving($PersonID, $BranchID){
		
		//------------- find Tafsili -----------------
		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=" . TAFSILITYPE_PERSON .  
				" AND ObjectID=?", array($PersonID));
		if(count($dt) == 0)
		{
			ExceptionHandler::PushException("تفصیلی فرد مربوطه یافت نشد.");
			return false;
		}
		$TafsiliID = $dt[0]["TafsiliID"];
		
		//------------- find saving remain -----------------
		$dt = PdoDataAccess::runquery("
				select ifnull(sum(CreditorAmount-DebtorAmount),0) amount
				from ACC_DocItems join ACC_docs using(DocID)
				join ACC_cycles using(CycleID)
				where TafsiliType=:tt AND TafsiliID=:t AND BranchID=:b 
					AND CycleYear=:y AND CostID=:cost
				group by TafsiliID", 
			array(
				":y" => substr(DateModules::shNow(),0,4),
				":b" => $BranchID,
				":cost" => COSTID_saving,
				":tt" => TAFSILITYPE_PERSON,
				":t" => $TafsiliID
		));
		$SavingAmount = count($dt) == 0 ? 0 : $dt[0][0];
		
		//------------- minus block accounts -----------------
		$BlockedAmount = ACC_CostBlocks::GetBlockAmount(COSTID_saving, TAFSILITYPE_PERSON, $TafsiliID);

		//------------------------------------------------
		return $SavingAmount*1 - $BlockedAmount*1;
	}

	static function GetRemainOfCost($CostID, $TafsiliID, $TafsiliID2, $Date){
		
		$dt = PdoDataAccess::runquery("
				select ifnull(sum(CreditorAmount-DebtorAmount),0) amount
				from ACC_DocItems join ACC_docs using(DocID)
				where CycleID=:cycle AND CostID=:cost AND TafsiliID=:t AND TafsiliID2=:t2
					AND DocDate <= :date
				group by TafsiliID", 
			array(
				":cycle" => $_SESSION["accounting"]["CycleID"],
				":cost" => $CostID,
				":t" => $TafsiliID,
				":t" => $TafsiliID2,
				":date" => $Date
		));
		return count($dt) == 0 ? 0 : $dt[0][0];
	}
}

