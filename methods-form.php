<?php
error_reporting(0); 
$folder = 'files/';
if (isset($_POST["submit"]) and $_POST["submit"] != "") {
	$theID = $_POST["theID"];
	$valid_exts = array('csv', 'sql');
	$theFileName = basename( $_FILES['uploadLogs']['name']);
	$ext = strtolower(pathinfo($_FILES['uploadLogs']['name'], PATHINFO_EXTENSION));
	(in_array($ext, $valid_exts))?$validFile=true:$validFile=false;	
	if($validFile){
		$target = $folder;
		$target = $target . $theFileName;
		move_uploaded_file($_FILES['uploadLogs']['tmp_name'], $target);
		$fields = "BIOID,DATETIME";
		$toImport = importFiles($theFileName,1,$theID,$fields);
		$alert = '<div class="success">'.$toImport.'</div>';
	} else {
		$alert = "The file you select is not supported, please check and try again!";
	}
}
?>
<div class="col-md-12 col-sm-12 col-xs-12">
<div class="x_panel<?php if(isset($_GET['id'])&&!$theID)echo ' lock'?>">
<div class="x_title">
<h2>Create Payroll <small>create payroll forms</small></h2>
<ul class="nav navbar-right panel_toolbox">
  <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
  </li>
  <li class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
    <ul class="dropdown-menu" role="menu">
      <li><a href="#">Settings 1</a>
      </li>
      <li><a href="#">Settings 2</a>
      </li>
    </ul>
  </li>
  <li><a class="close-link"><i class="fa fa-close"></i></a>
  </li>
</ul>
<div class="clearfix"></div>
</div>
<div class="x_content no-padding no-margin">
<form action="<?php echo URL.'/methods'.queryURL('')?>" enctype="multipart/form-data" method="post" id="payrollForm" name="payrollForm" data-parsley-validate class="form-horizontal form-label-left">
<?php
$popupType='logBox';
if($theID){
	$formType = getMetaValue($thisType,array('id'=>$theID),'type');
	$formTitle = getMetaValue($thisType,array('id'=>$theID),'title');
}
$payrollTitle = new inputElements('text','title','title','e.g. May 2017 (Week 2)','');
$payrollPeriod = new inputElements('text','datePeriod','datePeriod','e.g. 2017/05/08 - 2017/05/12','');

?>
<input type="hidden" name="action" id="action" value="<?php echo $thisType?>">
<input type="hidden" name="theID" id="theID" value="<?php if($theID)echo $theID?>">
<input type="hidden" name="theAction" id="theAction" value="<?php ($theID)?print 'update':print 'create';?>">
<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Generate / Type <span class="required">*</span></label>
    <div class="selectCol col-md-6 col-sm-6 col-xs-12<?php if($theID)echo ' lock'?>">
      <select id="type" name="type" class="select2_single type form-control" tabindex="-1">
		  <?php $getLogType = getMetaValue('codebook',array('meta_key'=>'log_type'),'id');if(!$formType)$formType=1;echo selectMetaOptions('codebook',$getLogType,$formType,'','');?>
      </select>
    </div>
	</div>
            
  <div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name">Payroll Title <span class="required">*</span></label>
    <div class="col-md-6 col-sm-6 col-xs-12 edit">
      <input type="<?php echo $payrollTitle->type?>" name="<?php echo $payrollTitle->name?>" id="<?php echo $payrollTitle->id?>" placeholder="<?php echo $payrollTitle->placeholder?>" value="<?php if($theID)echo $formTitle?>" required class="form-control col-md-7 col-xs-12">
    </div>
  </div>
    
  <div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Date Period <span class="required">*</span></label>
    <div class="col-md-6 col-sm-6 col-xs-12 has-feedback">
    <input value="<?php if($theID)echo timeDateFormat(getMetaValue($thisType,array('id'=>$theID),'start'),'validDate').' - '.timeDateFormat(getMetaValue($thisType,array('id'=>$theID),'end'),'validDate')?>" type="<?php echo $payrollPeriod->type?>" id="<?php echo $payrollPeriod->id?>" name="<?php echo $payrollPeriod->name?>" placeholder="Reservation Date" class="form-control">
      <span class="fa fa-calendar form-control-feedback right no-padding" aria-hidden="true"></span>
    </div>
    </div>
  
  
  <div class="ln_solid"></div>
  <div class="form-group no-margin">
    <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3 hasBox">
      <!--<button type="button" class="btn btn-primary">Cancel</button>-->
      <button type="button" class="btn btn-success" id="actionBtn" onClick="createOption('payrollForm')"><?php ($theID)?print 'Update':print 'Submit';?></button>
      <button title="1" type="button" id="createLogs" name="createLogs" onclick="getPopup(this,'payroll_logs','logStaff')" value="<?php echo $theID?>" class="btn btn-primary popBtn <?php if(!$theID)echo 'hide'?>" data-toggle="modal" data-target=".<?php echo $popupType?>"><i data-toggle="tooltip" data-placement="bottom" data-original-title="Create Logs: Staffs Log In/Out" class="fa fa-calendar-o"></i></button>
     <button title="6" type="button" id="createAdjustments" name="createAdjustments" onclick="getPopup(this,'payroll_logs','adjustmentStaff')" value="<?php echo $theID?>" class="btn btn-primary popBtn <?php if(!$theID)echo 'hide'?>" data-toggle="modal" data-target=".<?php echo $popupType?>"><i data-toggle="tooltip" data-placement="bottom" data-original-title="Create Adjustments: Overtime" class="fa fa-clock-o"></i></button>
      <button title="7" type="button" id="createAdjustments" name="createAdjustments" onclick="getPopup(this,'payroll_logs','adjustmentStaff')" value="<?php echo $theID?>" class="btn btn-primary popBtn <?php if(!$theID)echo 'hide'?>" data-toggle="modal" data-target=".<?php echo $popupType?>"><i data-toggle="tooltip" data-placement="bottom" data-original-title="Create Adjustments: Routes" class="fa fa-truck"></i></button>
      
    <div id="importBox" class="editor btn-group<?php if(!$theID||$theID&&$formType>1)echo ' hide'?>" data-role="editor-toolbar" data-target="#editor">
        <a class="btn uploadBtn hide" title="Insert picture (or just drag & drop)" id="pictureBtn"><i class="fa fa-table"></i><span>Select a file...</span></a>
        <input type="file" id="uploadLogs" name="uploadLogs" class="" />
        <input name="submit" type="submit" id="submitFile" value="Upload File" class="btnUpload"><span class="hide glyphicon glyphicon-floppy-open" aria-hidden="true"></span>
        
    </div>
    </div>
  </div>
  <div class="ln_solid"></div>
    <div id="alerts"></div>
                  <div id="editor" class="hide editor-wrapper"></div>
</form>
</div>
<table id="<?php echo $dataTableID;?>" width="100%" class="<?php echo $thisView?> table table-striped table-bordered">
  <thead>
    <tr><th>Name</th><th>Position</th><th class="alignCenter">Salary Rate</th><th class="alignCenter">Work Days</th><th class="alignCenter">Routes/OT</th><th class="alignCenter">Late/Deductions</th><th class="alignCenter">Salary Amount</th><th></th></tr>
  </thead>
  <tbody>
  <?php
  if($theID){
      $listTable='payroll_logs';
      $getStaffs=getMetaValue($listTable,array('recordID'=>$theID,'sorting'=>' order by staffID desc'),'id');//,'type'=>$formType
      if($getStaffs){
          $arrayStaffs=explode(',',$getStaffs);
          foreach($arrayStaffs as $listID){
			  $toSummary=false;
			  $staffID=getMetaValue($listTable,array('id'=>$listID),'staffID');
			  
			  if($customID!=$staffID)$toSummary=true;
			  $customID=$staffID;
             echo getListings($listTable,$listID,'logDetails',$toSummary);
          }
      }
  }
?>
  </tbody>
</table>
</div>
</div>
