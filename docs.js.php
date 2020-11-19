<script type="text/javascript">
//-----------------------------
//	Programmer	: Shabnam.Jafarkhani
//	Date		: 2015.06
//-----------------------------

class AccDocs {
	TabID = '<?= $_REQUEST["ExtTabID"] ?>';
	address_prefix = "<?= $js_prefix_address ?>";

	AddAccess = <?= $accessObj->AddFlag ? "true" : "false" ?>;
	EditAccess = <?= $accessObj->EditFlag ? "true" : "false" ?>;
	RemoveAccess = <?= $accessObj->RemoveFlag ? "true" : "false" ?>;

	CycleIsOpen = true;
		
	get(elementID){
		return findChild(this.TabID, elementID);
	}
	
	constructor() {
		this.form = this.get("mainForm");

		this.makeInfoWindow();
		this.makeDetailWindow();
		//--------------------------------------------------------------------------
		
		this.checkTafsiliCombo = new Ext.form.ComboBox({
			store: new Ext.data.Store({
				fields:["TafsiliID","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.controller.php?task=GetAllTafsilis&TafsiliType=<?= TAFSILITYPE_PERSON ?>',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			emptyText:'انتخاب تفصیلی ...',
			allowBlank : false,
			valueField : "TafsiliID",
			displayField : "TafsiliDesc"
		});
		
		this.accountCombo = new Ext.form.ComboBox({
			store: new Ext.data.Store({
				fields:["AccountID","AccountDesc","StartNo","EndNo","StartNo2","EndNo2"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'doc.controller.php?task=SelectAccounts',
					reader: {
						root: 'rows',
						totalProperty: 'totalCount'
					}
				},
				autoLoad : true
			}),
			emptyText:'انتخاب حساب ....',
			queryMode : 'local',
			allowBlank : false,
			valueField : "AccountID",
			displayField : "AccountDesc"
		});
		
		this.ChequeStatusCombo = new Ext.form.ComboBox({
			store: new Ext.data.Store({
				autoLoad : true,
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.controller.php?task=SelectChequeStatuses',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['InfoID','InfoDesc']
			}),
			displayField: 'InfoDesc',
			valueField : "InfoID",
			queryMode: "local"
		});

		this.accountTafsiliCombo = new Ext.form.ComboBox({
			store: new Ext.data.Store({
				fields:["TafsiliID","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.controller.php?task=GetAllTafsilis&TafsiliType=<?= TAFSILITYPE_ACCOUNTTYPE ?>',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			emptyText:'انتخاب تفصیلی ...',
			allowBlank : false,
			valueField : "TafsiliID",
			displayField : "TafsiliDesc"
		});
		
		//--------------------------------------------------------------------------
		
		this.mainTab = new Ext.TabPanel({
			renderTo: this.get("div_tab"),
			activeTab: 0,		
			plain: true,
			defaults :{
				autoScroll: true,
				bodyPadding: 10
			},
			items: [{
				title: 'ردیف های سند',
				contentEl : this.get("tabitem_rows")
			},{
				title : "چک های سند",
				listeners :{
					activate : function(){
						var hrecord = AccDocsObject.grid.getStore().getAt(0);
						if(!hrecord)
							return;
						if(!AccDocsObject.checkGrid.rendered)
						{
							AccDocsObject.checkGrid.getStore().proxy.extraParams = {
								DocID : hrecord.data.DocID
							};
							this.add(AccDocsObject.checkGrid);
							return;
						}
						if(AccDocsObject.grid.getStore().proxy.extraParams.DocID != hrecord.data.DocID)
						{
							AccDocsObject.checkGrid.getStore().proxy.extraParams = {
								DocID : hrecord.data.DocID
							};
							AccDocsObject.checkGrid.getStore().load();
						}
					}
				}
			}]
		});
				
		this.summaryFS = new Ext.panel.Panel({
			renderTo : this.get("fs_summary"),
			width : 780,
			height : 40,
			frame : true,
			style : "font-weight:bold",
			layout :{
				type : "table",
				columns : 3
			},
			items :[{
					xtype : "displayfield",
					fieldLabel :"جمع بدهکار",
					labelWidth : 100,
					itemId : "cmp_bd",
					width : 250
				},{
					xtype : "displayfield",
					fieldLabel : "جمع بستانکار",
					labelWidth : 100,
					itemId : "cmp_bs",
					width : 250
				},{
					xtype : "container",
					html : '<div align="center" style="height: 26px;font-family: b titr;font-size: 14px;background-color:#FFB8C9;border : 1px solid red">سند تراز نمی باشد</div>',
					itemId : "cmp_balance",
					width : 200
				}]
		}); 

		//--------------------------------------------------------------------------
		
		this.ParamsStore = new Ext.data.Store({
			fields:["DocType","ParamID","ParamDesc","ParamType", "ParamValues"],
			proxy: {
				type: 'jsonp',
				url: this.address_prefix + 'doc.controller.php?task=selectCostParams',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		});
	}

	operationhMenu = (e) => {
		
		var record = null;
		if(AccDocsObject.grid.getStore().count() > 0) 
		{    
			record = AccDocsObject.grid.getStore().getAt(0);
		}        
		var op_menu = new Ext.menu.Menu();
		if(this.CycleIsOpen)
		{
			if(this.AddAccess)
			{
				op_menu.add({text: 'ایجاد سند',iconCls: 'add', 
					handler : function(){ return AccDocsObject.AddDoc(); }})
				op_menu.add({text: 'کپی سند',iconCls: 'copy', 
					handler : function(){ return AccDocsObject.CopyDoc(1); }})
				op_menu.add({text: 'کپی وارونه سند',iconCls: 'copy', 
					handler : function(){ return AccDocsObject.CopyDoc(2); }})
				op_menu.add({text: 'اجرای رویداد',iconCls: 'process', 
					handler : function(){ return AccDocsObject.ExeEvent(); }})
			}
			
			if(record != null) 
			{
				if(record.data.StatusID == "<?= ACC_STEPID_RAW ?>" || (record.data.StepID == "1" && record.data.ActionType == "REJECT"))
				{
					if(this.EditAccess)
					{
						op_menu.add({text: 'ویرایش سند',iconCls: 'edit', 
							handler : function(){ return AccDocsObject.EditDoc(); } });

						op_menu.add({text: 'ارسال سند',iconCls: 'refresh',
							handler : function(){ return AccDocsObject.StartFlow(); }});
					}
					if(this.RemoveAccess /*&& this.grid.getStore().currentPage == this.grid.getStore().totalCount*/)
						op_menu.add({text: 'حذف سند',iconCls: 'remove', 
							handler : function(){ return AccDocsObject.RemoveDoc(); } });
				}
				if(record.data.StatusID == "0")
				{
					op_menu.add({text: 'برگشت فرم',iconCls: 'return',
					handler : function(){ return AccDocsObject.ReturnStartFlow(); }});
				}
			}
			op_menu.add({text: 'ارسال گروهی اسناد',iconCls: 'tick', 
				handler : function(){ return AccDocsObject.BeforeGroupStartFlow(); } });

		}
		if(record != null)           
		{
			op_menu.add({text: 'چاپ سند',iconCls: 'print', 
				handler : function(){ return AccDocsObject.PrintDoc(); } });
			
			op_menu.add({text: 'مدارک سند',iconCls: 'attach', 
			handler : function(){ return AccDocsObject.Documents('accdoc'); }});
			
			op_menu.add({text: 'سابقه سند',iconCls: 'history', 
				handler : function(){ return AccDocsObject.ShowHistory(); } });
			
		}
		op_menu.showAt([e.getEl().getX()-60, e.getEl().getY()+20]);
	}
	
	makeInfoWindow = () => {
		
		this.docWin = new Ext.window.Window({
			title: 'مشخصات سند',
			modal : true,
			width: 400,
			closeAction : "hide",
			items : new Ext.form.Panel({
				plain: true,
				border: 0,
				bodyPadding: 5,

				fieldDefaults: {
					labelWidth: 140
				},
				layout: {
					type: 'vbox',
					align: 'stretch'
				},
				items : [{
						xtype : "numberfield",
						fieldLabel: "شماره سند",
						name : "LocalNo",
						itemId : "LocalNo",
						hideTrigger : true
					},{
						xtype : "shdatefield",
						format: 'Y/m/d',
						width : 60,
						value : "<?= DateModules::shNow() ?>",
						fieldLabel: "تاریخ سند",
						name : "DocDate",
						allowBlank : false
					},{
						xtype : "combo",
						store : new Ext.data.Store({
							proxy:{
								type: 'jsonp',
								url: '/framework/baseInfo/baseInfo.controller.php?task=SelectBranches',
								reader: {root: 'rows',totalProperty: 'totalCount'}
							},
							fields :  ["BranchID", "BranchName"],
							autoLoad : true
						}),
						queryMode : "local",
						displayField: 'BranchName',
						valueField : "BranchID",
						name : "BranchID",
						fieldLabel : "شعبه سند",
						allowBlank : false
					},{
						xtype : "combo",
						store : new Ext.data.Store({
							proxy:{
								type: 'jsonp',
								url: this.address_prefix + 'doc.controller.php?task=GetSubjects',
								reader: {root: 'rows',totalProperty: 'totalCount'}
							},
							fields :  ["InfoID", "InfoDesc"]
						}),
						displayField: 'InfoDesc',
						valueField : "InfoID",
						name : "SubjectID",
						fieldLabel : "موضوع سند"
					},{
						xtype : "textarea",
						fieldLabel: "توضیحات",
						name : "description"
					},{
						xtype : "hidden",
						name : "DocID"
					}
				],
				buttons : [
					{
						text : "ذخیره",
						iconCls : "save",
						handler : function(){ AccDocsObject.SaveDoc();	}
					},
					{
						text : "انصراف",
						iconCls : "undo",
						handler : function(){
							AccDocsObject.docWin.hide();
						}
					}
				]
			})
		});

	}
	
	makeDetailWindow = () => {
		
		this.detailWin = new Ext.window.Window({
			title: 'ایجاد ردیف سند',
			modal : true,
			width: 630,
			closeAction : "hide",
			items : new Ext.form.Panel({
				plain: true,
				border: 0,
				bodyPadding: 5,
				fieldDefaults: {
					labelWidth: 100
				},
				layout: {
					type: 'table',
					columns : 2,
					align: 'stretch'
				},
				items : [{
						xtype : "combo",
						width : 610,
						fieldLabel : "کد حساب",
						colspan : 2,
						store: new Ext.data.Store({
							fields:["CostID","CostCode","CostDesc", 
								"TafsiliType1","TafsiliType2","TafsiliType3",
								"ParamID1","ParamID2","ParamID3",
								"ParamType1","ParamType2","ParamType3",
								"ParamDesc1","ParamDesc2","ParamDesc3",{
								name : "fullDesc",
								convert : function(value,record){
									return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
								}				
							}],
							proxy: {
								type: 'jsonp',
								url: this.address_prefix + '../baseinfo/baseinfo.controller.php?task=SelectCostCode',
								reader: {root: 'rows',totalProperty: 'totalCount'}
							},
							pageSize : 25
						}),
						pageSize : 25,
						typeAhead: false,
						name : "CostID",
						valueField : "CostID",
						displayField : "fullDesc",
						listConfig: {
							loadingText: 'در حال جستجو...',
							emptyText: 'فاقد اطلاعات'
						},
						listeners :{
							select : function(combo,records){
								AccDocsObject.SelectCostIDHandler(records[0], null);
							}
						}
					},{
						xtype : "combo",
						width : 350,
						colspan: 2,
						fieldLabel : "تفصیلی",
						store: new Ext.data.Store({
							fields:["TafsiliID","TafsiliCode","TafsiliDesc",{
								name : "fullDesc",
								convert : function(v,r){
									return "[" + r.data.TafsiliCode + "]" + r.data.TafsiliDesc;
								}
							}],
							proxy: {
								type: 'jsonp',
								url: this.address_prefix + '../baseinfo/baseinfo.controller.php?task=GetAllTafsilis',
								reader: {root: 'rows',totalProperty: 'totalCount'}
							},
							listeners : {
								beforeload : function(store){
									if(!store.proxy.extraParams.TafsiliType)
									{
										/*group = AccDocsObject.detailWin.down("[name=TafsiliType]").getValue();
										if(group == "")
											return false;
										this.proxy.extraParams["TafsiliType"] = group;*/
									}
								}							
							}
						}),
						typeAhead: false,
						pageSize : 10,
						name : "TafsiliID",
						valueField : "TafsiliID",
						displayField : "fullDesc"
					},{
						xtype : "combo",
						colspan: 2,
						fieldLabel : "تفصیلی 2",
						width : 350,
						store: new Ext.data.Store({
							fields:["TafsiliID","TafsiliCode","TafsiliDesc",{
								name : "fullDesc",
								convert : function(v,r){
									return "[" + r.data.TafsiliCode + "]" + r.data.TafsiliDesc;
								}
							}],
							proxy: {
								type: 'jsonp',
								url: this.address_prefix + '../baseinfo/baseinfo.controller.php?task=GetAllTafsilis',
								reader: {root: 'rows',totalProperty: 'totalCount'}
							},
							listeners : {
								beforeload : function(store){
									if(!store.proxy.extraParams.TafsiliType)
									{
										/*group = AccDocsObject.detailWin.down("[name=TafsiliType2]").getValue();
										if(group == "")
											return false;
										this.proxy.extraParams["TafsiliType"] = group;*/
									}
								}
							}
						}),
						typeAhead: false,
						pageSize : 10,
						name : "TafsiliID2",
						valueField : "TafsiliID",
						displayField : "fullDesc"
					},{
						xtype : "combo",
						colspan: 2,
						fieldLabel : "تفصیلی 3",
						width : 350,
						store: new Ext.data.Store({
							fields:["TafsiliID","TafsiliCode","TafsiliDesc",{
								name : "fullDesc",
								convert : function(v,r){
									return "[" + r.data.TafsiliCode + "]" + r.data.TafsiliDesc;
								}
							}],
							proxy: {
								type: 'jsonp',
								url: this.address_prefix + '../baseinfo/baseinfo.controller.php?task=GetAllTafsilis',
								reader: {root: 'rows',totalProperty: 'totalCount'}
							},
							listeners : {
								beforeload : function(store){
									if(!store.proxy.extraParams.TafsiliType)
									{
										/*group = AccDocsObject.detailWin.down("[name=TafsiliType2]").getValue();
										if(group == "")
											return false;
										this.proxy.extraParams["TafsiliType"] = group;*/
									}
								}
							}
						}),
						typeAhead: false,
						pageSize : 10,
						name : "TafsiliID3",
						valueField : "TafsiliID",
						displayField : "fullDesc"
					},{
						xtype : "currencyfield",
						fieldLabel : "مبلغ بدهکار",
						name : "DebtorAmount",
						hideTrigger : true
					},{
						xtype : "currencyfield",
						fieldLabel : "مبلغ بستانکار",
						name : "CreditorAmount",
						hideTrigger : true
					},{
						xtype : "textfield",
						fieldLabel : "شرح",
						name : "details",
						colspan : 2,
						width : 610
					},{
						xtype : "fieldset",
						colspan : 2,
						title : "آیتم های اطلاعاتی مربوط به کد حساب",
						itemId : "ParamsFS",
						layout : "column",
						columns : 2
					},{
						xtype : "hidden",
						name : "ItemID"
					}
				],
				buttons : [
					{
						text : "ذخیره",
						iconCls : "save",
						handler : function(){ AccDocsObject.SaveItem();	}
					},
					{
						text : "انصراف",
						iconCls : "undo",
						handler : function(){
							AccDocsObject.detailWin.hide();
						}
					}
				]
			})
		});
		Ext.getCmp(this.TabID).add(this.detailWin);
	}

	SelectCostIDHandler = (record, valRecord) => {
		
		if(record.data.TafsiliType1 != null)
		{
			combo = this.detailWin.down("[name=TafsiliID]");
			combo.getStore().proxy.extraParams["TafsiliType"] = record.data.TafsiliType1;
			if(valRecord == null)
			{
				combo.setValue();
				combo.getStore().load();
			}
			else
			{
				combo.getStore().load({	params : { TafsiliID : valRecord.data.TafsiliID} });
			}
		}
		if(record.data.TafsiliType2 != null)
		{
			combo = this.detailWin.down("[name=TafsiliID2]");
			combo.getStore().proxy.extraParams["TafsiliType"] = record.data.TafsiliType2;
			if(valRecord == null)
			{
				combo.setValue();
				combo.getStore().load();
			}
			else
			{
				combo.getStore().load({	params : { TafsiliID : valRecord.data.TafsiliID2} });
			}
		}
		if(record.data.TafsiliType3 != null)
		{
			combo = this.detailWin.down("[name=TafsiliID3]");
			combo.getStore().proxy.extraParams["TafsiliType"] = record.data.TafsiliType3;
			if(valRecord == null)
			{
				combo.setValue();
				combo.getStore().load();
			}
			else
			{
				combo.getStore().load({	params : { TafsiliID : valRecord.data.TafsiliID3} });
			}
		}
		
		var ParamsFS = this.detailWin.down('form').getComponent("ParamsFS");
		ParamsFS.removeAll();
		for(i=1; i<=3; i++)
		{
			if(record.data["ParamType" + i] == "combo")
			{
				ParamsFS.add({
					xtype : "combo",
					itemId : "Cmp_param" + i,
					name : "param" + i,
					fieldLabel : record.data["ParamDesc" + i],
					store : new Ext.data.Store({
						fields:["id","title"],
						proxy: {
							type: 'jsonp',
							url: this.address_prefix + 'doc.controller.php?task=selectParamItems&ParamID=' +
								record.data["ParamID" + i],
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						autoLoad: true
					}),
					queryMode : "local",
					valueField : "id",
					displayField : "title"
				});							
			}
			else if(record.data["ParamType" + i] != null)
			{
				ParamsFS.add({
					itemId : "Cmp_param" + i,
					xtype : record.data["ParamType" + i],
					name : "param" + i,
					fieldLabel : record.data["ParamDesc" + i],
					hideTrigger : (record.data["paramType" + i] == "numberfield" || 
						record.data["paramType" + i] == "currencyfield" ? true : false)
				});			
			}
		}
	}

	static docRender(v,p,record){

		SubjectDesc = record.data.SubjectDesc == null ? "" : record.data.SubjectDesc;
		description = record.data.description == null ? "" : record.data.description;
		
		return "<table class='docInfo' width=100%>"+
			"<tr>"+
				"<td width=25%>شماره سند : <span class='blueText'>" + record.data.LocalNo + "</td>" +
				"<td width=25%>تاریخ سند : <span class='blueText'>" + MiladiToShamsi(record.data.DocDate) + "</td>" +
				"<td width=25%>نوع سند : <span class='blueText'>" + record.data.DocTypeDesc + "</td>" +
				"<td width=25%>ثبت کننده سند : <span class='blueText'>" + record.data.regPerson + "</td>" +
			"</tr>" + 
			"<tr>" +			
				"<td>شعبه : <span class='blueText'>" + record.data.BranchName + "</td>" +
				"<td>موضوع : <span class='blueText'>" + SubjectDesc + "</td>" +
				"<td colspan=3>رویداد : <span class='blueText' colspan=4>[ " + (record.data.EventID == undefined ? "" : record.data.EventID) + " ] " + record.data.EventTitle + "</td>" +
			"</tr>" +
			"<tr>" +			
				"<td colspan=5>توضیحات : <span class='blueText' colspan=4>" + description + "</td>" +
			"</tr>" +
			"</table>";
	}
	
	afterHeaderLoad = (store) => {
		if(store.getAt(0))
		{
			AccDocsObject.itemGrid.show();
			AccDocsObject.showDetail(store.getAt(0));
			/*AccDocsObject.checkTafsiliCombo.getStore().load({
				params :{ DocID : store.getAt(0).data.DocID}
			});
			AccDocsObject.accountCombo.getStore().load({
				params :{ DocID : store.getAt(0).data.DocID}
			});*/
		}
		else
			AccDocsObject.itemGrid.hide();
	}

	AddDoc = () => {
		this.docWin.down("form").getForm().reset();
		this.docWin.show();
		this.docWin.center();
		
		mask = new Ext.LoadMask(this.docWin, {msg:'در حال بارگذاری ...'});
		mask.show()
		
		Ext.Ajax.request({
			url : this.address_prefix + "doc.controller.php?task=GetLastLocalNo",
			method : "POST",
			params:{
				x: 1
			},

			success : function(response){
				AccDocsObject.docWin.down("[name=LocalNo]").setValue(response.responseText);
				mask.hide();
			}
		});
	}

	CopyDoc = (mode) => {
		var record = this.grid.getStore().getAt(0);
		if(!record)
			return;
		mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show()
		
		Ext.Ajax.request({
			url : this.address_prefix + "doc.controller.php?task=CopyDoc",
			method : "POST",
			params:{
				DocID : record.data.DocID,
				mode : mode
			},

			success : function(response){
				AccDocsObject.grid.getStore().loadPage(AccDocsObject.grid.getStore().totalCount+1);
				mask.hide();
			}
		});
	}

	BeforeGroupStartFlow = () => {
		if(!this.totalConfirmWin)
		{
			this.totalConfirmWin = new Ext.window.Window({
				title: 'ارسال گروهی اسناد',
				modal : true,
				width: 400,
				closeAction : "hide",
				items : new Ext.form.Panel({
					plain: true,
					border: 0,
					bodyPadding: 5,

					fieldDefaults: {
						labelWidth: 140
					},
					layout: {
						type: 'vbox',
						align: 'stretch'
					},
					items : [{
							xtype : "shdatefield",
							fieldLabel: "تاریخ سند از",
							name : "FromDate"
						},{
							xtype : "shdatefield",
							fieldLabel: "تا تاریخ",
							name : "ToDate"
						},{
							xtype : "numberfield",
							fieldLabel: "شماره سند از",
							name : "FromNo",
							hideTrigger : true
						},{
							xtype : "numberfield",
							fieldLabel: "تا شماره",
							name : "ToNo",
							hideTrigger : true
						}],
					buttons : [
						{
							text : "ارسال گروهی اسناد",
							iconCls : "tick",
							handler : function(){ AccDocsObject.GroupStartFlow();	}
						},{
							text : "انصراف",
							iconCls : "undo",
							handler : function(){
								AccDocsObject.totalConfirmWin.hide();
							}
						}
					]
				})
			});
		}
		this.totalConfirmWin.down("form").getForm().reset();
		this.totalConfirmWin.show();
		this.totalConfirmWin.center();
	}

	GroupStartFlow = () => {
		
		mask = new Ext.LoadMask(this.totalConfirmWin, {msg:'در حال ارسال اسناد ...'});
		mask.show();
			
		this.totalConfirmWin.down('form').getForm().submit({
			clientValidation: true,
			url: AccDocsObject.address_prefix + 'doc.controller.php?task=GroupStartFlow',
			method : "POST",

			success : function(form,action){
				mask.hide();
				AccDocsObject.totalConfirmWin.hide();
				AccDocsObject.grid.getStore().load();
				Ext.MessageBox.alert("","عملیات مورد نظر با موفقیت انجام گردید");
			},
			failure : function(form,action)
			{
				mask.hide();
				if(action.result.data != "")
					Ext.MessageBox.alert("Error",action.result.data);
				else
					Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
			}
		});
	}

	EditDoc = () => {
		AccDocsObject.docWin.down("form").getForm().reset();
		this.docWin.show();
		this.docWin.center();

		var record = this.grid.getStore().getAt(0);
		record.data.DocDate = MiladiToShamsi(record.data.DocDate);
		this.docWin.down("form").loadRecord(record);

	}

	SaveDoc = () => {
		
		mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();
			
		this.docWin.down('form').getForm().submit({
			clientValidation: true,
			url: AccDocsObject.address_prefix + 'doc.controller.php?task=saveDoc',
			method : "POST",

			success : function(form,action){
				mask.hide();
				AccDocsObject.docWin.hide();
				AccDocsObject.grid.getStore().proxy.extraParams["query"] = "";
				if(AccDocsObject.docWin.down("[name=DocID]").getValue() != "")
					AccDocsObject.grid.getStore().load();
				else
					AccDocsObject.grid.getStore().loadPage(AccDocsObject.grid.getStore().totalCount+1);
			},
			failure : function(form,action)
			{
				mask.hide();
				if(action.result.data != "")
					Ext.MessageBox.alert("Error",action.result.data);
				else
					Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
			}
		});
	}

	RemoveDoc = () => {
		var record = this.grid.getStore().getAt(0);

		if(record.data.DocStatus == "DELETED")
		{
			Ext.MessageBox.alert("","این سند قبلا حذف شده است");
			return;
		}
		Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
			
			if(btn == "no")
				return;
			me = AccDocsObject;
			
			mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف...'});
			mask.show();


			Ext.Ajax.request({
				url: me.address_prefix + 'doc.controller.php?task=removeDoc',
				params:{
					DocID: record.data.DocID
				},
				method: 'POST',

				success: function(response){
					mask.hide();
					result = Ext.decode(response.responseText);
					if(!result.success)
					{
						Ext.MessageBox.alert("Error", result.data);
						return;
					}
					var currentPage = AccDocsObject.grid.getStore().currentPage;
					var totalPages = AccDocsObject.grid.getStore().totalCount;

					if(currentPage != totalPages)
						AccDocsObject.grid.getStore().loadPage(currentPage);
					else if(totalPages == 0)
						AccDocsObject.grid.getStore().loadPage(1);
					else
						AccDocsObject.grid.getStore().loadPage(totalPages-1);
				},
				failure: function(){}
			});
		});
	}

	StartFlow = () => {
		var record = this.grid.getStore().getAt(0);

		var r = this.itemGrid.getStore().getProxy().getReader().jsonData;
		r = r.message.split(',');
		
		if(r[0] != r[1])
		{
			Ext.MessageBox.alert("","به دلیل تراز نبودن سند قادر به ارسال سند نمی باشید");
			return
		}
		
		if(r[0]*1 == 0)
		{
			Ext.MessageBox.alert("","سند فاقد مبلغ می باشد");
			return
		}
		Ext.MessageBox.confirm("","آیا مایل به ارسال سند می باشید؟", function(btn){
			if(btn == "no")
				return;
			
			me = AccDocsObject;
			mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال تایید سند ...'});
			mask.show();

			Ext.Ajax.request({
				url: '/office/workflow/wfm.controller.php',
				method: "POST",
				params: {
					task: "StartFlow",
					FlowID : me.FlowID,
					ObjectID : record.data.DocID
				},
				method: 'POST',

				success: function(response){
					result = Ext.decode(response.responseText);
					mask.hide();
					if(result.success)
						AccDocsObject.grid.getStore().load();
					else
						Ext.MessageBox.alert("Error", 
							result.data == "" ? "عملیات مورد نظر با شکست مواجه شد" : result.data);
				},
				failure: function(){}
			});
		});
	}

	ReturnStartFlow = () => {
		
		Ext.MessageBox.confirm("","آیا مایل به برگشت فرم می باشید؟",function(btn){
			
			if(btn == "no")
				return;
			
			me = AccDocsObject;
			var record = me.grid.getStore().getAt(0);
		
			mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
			mask.show();

			Ext.Ajax.request({
				url: '/office/workflow/wfm.controller.php',
				method: "POST",
				params: {
					task: "ReturnStartFlow",
					FlowID : me.FlowID,
					ObjectID : record.data.DocID
				},
				success: function(response){
					mask.hide();
					AccDocsObject.grid.getStore().load();
				}
			});
		});
	}

	archiveDoc = () => {
		var record = this.grid.getStore().getAt(0);

		mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال قطعی کردن سند ...'});
		mask.show();

		Ext.Ajax.request({
			url: this.address_prefix + 'doc.controller.php?task=archive',
			params:{
				DocID: record.data.DocID
			},
			method: 'POST',

			success: function(response){
				mask.hide();
				AccDocsObject.grid.getStore().load();
			},
			failure: function(){}
		});
	}

	PrintDoc = () => {
		window.open(this.address_prefix + "print_doc.php?DocID=" + 
			this.grid.getStore().getAt(0).data.DocID);
	}

	SearchDoc = () => {

		Ext.Ajax.request({
			url : this.address_prefix + 'doc.controller.php?task=GetSearchCount',
			method : 'POST',
			params : {
				Number : this.grid.down("[itemId=Number]").getValue()
			},
			success : function(response){
				var res = Ext.decode(response.responseText);
				if(res.success)
				{
					var totalCount=AccDocsObject.grid.getStore().totalCount;
					if((res.data*1) != totalCount)                                            
						AccDocsObject.grid.getStore().loadPage(res.data*1+1);
					else
						AccDocsObject.grid.getStore().loadPage(res.data*1);    
				}

			},
			failure : function(){}
		});

	}    

	ShowHistory = () => {

		if(!this.HistoryWin)
		{
			this.HistoryWin = new Ext.window.Window({
				title: 'سابقه تغییرات سند',
				modal : true,
				autoScroll : true,
				width: 700,
				height : 500,
				closeAction : "hide",
				loader : {
					url : this.address_prefix + "history.php",
					scripts : true
				},
				buttons : [{
						text : "بازگشت",
						iconCls : "undo",
						handler : function(){
							this.up('window').hide();
						}
					}]
			});
			Ext.getCmp(this.TabID).add(this.HistoryWin);
		}
		this.HistoryWin.show();
		this.HistoryWin.center();
		this.HistoryWin.loader.load({
			params : {
				DocID : this.grid.getStore().getAt(0).data.DocID
			}
		});
	}

	Documents = (ObjectType) => {

		if(!this.documentWin)
		{
			this.documentWin = new Ext.window.Window({
				width : 920, 
				height : 440,
				modal : true,
				bodyStyle : "background-color:white;padding: 0 10px 0 10px",
				closeAction : "hide",
				loader : {
					url : "../../office/dms/documents.php",
					scripts : true
				},
				buttons :[{
					text : "بازگشت",
					iconCls : "undo",
					handler : function(){this.up('window').hide();}
				}]
			});
			Ext.getCmp(this.TabID).add(this.documentWin);
		}

		this.documentWin.show();
		this.documentWin.center();
		
		var record = this.grid.getStore().getAt(0);
		this.documentWin.loader.load({
			scripts : true,
			params : {
				ExtTabID : this.documentWin.getEl().id,
				ObjectType : ObjectType,
				ObjectID : record.data.DocID
			}
		});
	}

	//.........................................................

	static check_deleteRender = (v,p,r) => {
		
		var record = AccDocsObject.grid.getStore().getAt(0);
		if(record.data.StatusID == "<?= ACC_STEPID_RAW ?>" || (record.data.StepID == "1" && record.data.ActionType == "REJECT"))
			return  "<div title='حذف اطلاعات' class='remove' onclick='AccDocsObject.check_remove();' " +
				"style='background-repeat:no-repeat;background-position:center;" +
				"cursor:pointer;height:16'></div>";
		return "";
	}

	check_Add = () => {
		
		var record = this.grid.getStore().getAt(0);
		if(!(record.data.StatusID == "<?= ACC_STEPID_RAW ?>" || (record.data.StepID == "1" && record.data.ActionType == "REJECT")))
		{
			Ext.MessageBox.alert("امکان صدور چک نمی باشد");
			return;
		}
			
		mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال صدور چک ...'});
		mask.show();

		Ext.Ajax.request({
			url: this.address_prefix + 'doc.controller.php?task=CreatePayCheque',
			params:{
				DocID: record.data.DocID
			},
			method: 'POST',

			success: function(response){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(!result.success)
					Ext.MessageBox.alert("خطا", result.data == "" ? "عملیات مورد نظر با شکست مواجه شد" : result.data);
				else
					AccDocsObject.checkGrid.getStore().load();
			},
			failure: function(){}
		});	
			
		return;
	}

	check_Save = (store,record) => {
		
		mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: this.address_prefix + 'doc.controller.php?task=saveChecks',
			method: 'POST',
			params: {
				record : Ext.encode(record.data)
			},
			form : this.get("checkForm"),

			success: function(response){
				mask.hide();
				var st = Ext.decode(response.responseText);
				if(st.success)
				{
					AccDocsObject.checkGrid.getStore().load();
				}
				else
				{
					if(st.data == "duplicate")
						alert("شماره چک وارد شده تکراری می باشد.");
					else
						alert(st.data);
				}
			},
			failure: function(){}
		});
	}

	check_remove = () => {
		
		Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
			
			if(btn == "no")
				return;
			
			me = AccDocsObject;
			var record = me.checkGrid.getSelectionModel().getLastSelected();

			mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخيره سازي...'});
			mask.show();


			Ext.Ajax.request({
				url: me.address_prefix + 'doc.controller.php?task=removeChecks',
				params:{
					DocChequeID: record.data.DocChequeID
				},
				method: 'POST',

				success: function(response){
					mask.hide();
					result = Ext.decode(response.responseText);
					if(!result.success)
					{
						Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
						return;
					}
					AccDocsObject.checkGrid.getStore().load();
				},
				failure: function(){}
			});
		});
	}

	static beforeCheckEdit = (editor,e) => {
		
		var record = AccDocsObject.grid.getStore().getAt(0);
		if(!(record.data.StatusID == "<?= ACC_STEPID_RAW ?>" || (record.data.StepID == "1" && record.data.ActionType == "REJECT")))
			return false;
	}

	printCheck = () => {
		
		var record = this.checkGrid.getSelectionModel().getLastSelected();
		if(!record)
		{
			Ext.MessageBox.alert("Error", "ردیف چک مربوطه را انتخاب کنید");
			return;
		}
		window.open(this.address_prefix + "../baseinfo/checkBuilder/PrintCheck.php?DocChequeID=" 
				+ record.data.DocChequeID);
	}
	
};


</script>
