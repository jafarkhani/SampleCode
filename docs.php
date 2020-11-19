<?php
//-----------------------------
//	Programmer	: Shabnam.Jafarkhani
//	Date		: 2015.06
//-----------------------------

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../App/header.php';

use Core\sadaf_datagrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "doc.data.php?task=selectDocs","div_dg");

$dg->addColumn("کد سند","DocID","",true);
$dg->addColumn("","BranchID","",true);
$dg->addColumn("","DocStatus","",true);
$dg->addColumn("شعبه سند","BranchName","",true);
$dg->addColumn("تاریخ سند","DocDate","",true);
$dg->addColumn("تاریخ ثبت","RegDate","",true);
$dg->addColumn("توضیحات","description","",true);
$dg->addColumn("کد سند","LocalNo","",true);
$dg->addColumn("نوع سند","DocType","",true);


$dg->addColumn("ثبت کننده سند","regPerson","",true);
$dg->addColumn("","SubjectDesc","",true);
$dg->addColumn("","SubjectID","",true);
$dg->addColumn("","DocTypeDesc","",true);

$dg->addColumn("", "FlowID", "", true);
$dg->addColumn("", "StatusID", "", true);
$dg->addColumn("", "StepID", "", true);
$dg->addColumn("", "ActionType", "", true);

$dg->addColumn("", "EventID", "", true);
$dg->addColumn("", "EventTitle", "", true);

$col = $dg->addColumn("اطلاعات سند","DocID");
$col->renderer = "AccDocs.docRender";

$dg->addButton('HeaderBtn', 'عملیات', 'list', 'function(e){ return AccDocsObject.operationhMenu(e); }');

$dg->title = "سند های حسابداری";
$dg->width = 780;
$dg->DefaultSortField = "LocalNo";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "DocID";
$dg->emptyTextOfHiddenColumns = true;
$dg->hideHeaders = true;
$dg->pageSize = 1;
$dg->disableChangePageSize = true;
$dg->PageSizeChange = false;
$dg->EnableRowNumber = false;
$dg->hideHeaders = true;
$dg->EnableSearch = false;
$dg->ExcelButton = false;
//$dg->collapsible = true;
$grid = $dg->makeGrid_returnObjects();

//---------------------------------------------------
$dgh = new sadaf_datagrid("dg",$js_prefix_address."doc.data.php?task=selectCheques","div_dg");

$dgh->addColumn("","DocID","",true);
$dgh->addColumn("","AccountDesc","",true);
$dgh->addColumn("","TafsiliDesc","",true);
$dgh->addColumn("","StatusTitle","",true);
$dgh->addColumn("","AccountTafsiliDesc","",true);

$col = $dgh->addColumn("کد","DocChequeID","",true);
$col->width = 50;

$col = $dgh->addColumn("نوع حساب", "AccountTafsiliID");
$col->renderer = "function(v,p,r){return r.data.AccountTafsiliDesc;}";
$col->editor = "AccDocsObject.accountTafsiliCombo";
$col->width = 80;

$col = $dgh->addColumn("حساب", "AccountID");
$col->renderer = "function(v,p,r){return r.data.AccountDesc;}";
$col->editor = "AccDocsObject.accountCombo";
$col->width = 150;

$col = $dgh->addColumn("شماره چک", "CheckNo");
$col->editor = ColumnEditor::TextField(true, "cmp_CheckNo");
$col->width = 100;

$col = $dgh->addColumn("تاریخ چک", "CheckDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 90;

$col = $dgh->addColumn("مبلغ", "amount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField();
$col->width = 200;

$col = $dgh->addColumn("در وجه", "TafsiliID");
$col->renderer = "function(v,p,r){return r.data.TafsiliDesc;}";
$col->editor = "AccDocsObject.checkTafsiliCombo";

$col = $dgh->addColumn("بابت", "description");
$col->editor = ColumnEditor::TextField(true);

$col = $dgh->addColumn("وضعیت", "CheckStatus");
$col->editor = "AccDocsObject.ChequeStatusCombo";
$col->renderer = "function(v,p,r){return r.data.StatusTitle}";
$col->width = 80;

if($accessObj->RemoveFlag)
{
	$col = $dgh->addColumn("حذف", "", "string");
	$col->renderer = "AccDocsObject.check_deleteRender";
	$col->width = 50;
	$col->align = "center";
}
if($accessObj->AddFlag)
{
	$dgh->addButton = true;
	$dgh->addHandler = "function(v,p,r){ return AccDocsObject.check_Add(v,p,r);}";
}

$dgh->enableRowEdit = true ;
$dgh->rowEditOkHandler = "function(v,p,r){ return AccDocsObject.check_Save(v,p,r);}";

$dgh->addButton("", "چاپ چک", "print", "function(){ return AccDocsObject.printCheck();}");

$dgh->addColumn("", "CheckStatus","",true);
$dgh->addColumn("", "PrintPage1","",true);
$dgh->addColumn("", "PrintPage2","",true);

$dgh->DefaultSortField = "DocChequeID";
$dgh->autoExpandColumn = "description";
$dgh->emptyTextOfHiddenColumns = true;
$dgh->DefaultSortDir = "ASC";
$dgh->height = 315;
$dgh->EnableSearch = false;
$dgh->EnablePaging = false;
$dgh->width = 1000;
$checksgrid = $dgh->makeGrid_returnObjects();

//-----------------------------------------

require_once 'docs.js.php';
?>
<script>

var AccDocsObject = new AccDocs();

AccDocsObject.grid = <?= $grid ?>;
AccDocsObject.grid.getView().getRowClass = function(record, index)
{
	if(record.data.StepID == "1" && record.data.ActionType == "REJECT")
		return "pinkRow";
	if(record.data.StatusID == "<?= ACC_STEPID_RAW ?>")
		return "";
	if(record.data.StatusID == "<?= ACC_STEPID_CONFIRM ?>")
		return "yellowRow";
	
	return "greenRow";
}

AccDocsObject.grid.getStore().on("load", AccDocsObject.afterHeaderLoad);	
AccDocsObject.grid.getStore().currentPage = <?= $docsCount ?>;
AccDocsObject.grid.render(AccDocsObject.get("div_dg"));

//...................................................
AccDocsObject.checkGrid = <?= $checksgrid ?>;
AccDocsObject.checkGrid.plugins[0].on("beforeedit", AccDocs.beforeCheckEdit);
AccDocsObject.checkGrid.plugins[0].on("beforeedit", function(editor,e){
	if(e.record.data.CheckStatus == '<?= INCOMECHEQUE_VOSUL ?>')
		return false;
	if(!e.record.data.DocChequeID)
		return AccDocsObject.AddAccess;
	return AccDocsObject.EditAccess;
});
//...................................................
</script>

<center>
<form id="mainForm">
	<br><div id="div_dg"></div>
	<br>
	<div id="div_tab" >
		<div id="tabitem_rows">
			<div style="margin-left:10px;margin-right: 10px" id="div_detail_dg"></div>
		</div>
	</div>	
</form>
<div id="fs_summary"></div>
<div id="div_checksWin" class="x-hidden"></div>
</center>
