<?php
require 'header.php';
$theID='';
$thisType='payroll';
$popupType='viewStaff';
$action=$popupType;
if(isset($_GET['id']))$theID=getMetaValue($thisType,array('id'=>$_GET['id']),'id');
if(isset($_GET['action'])){
	$popupType='logStaff';
	$action=$_GET['action'];		
}
?>
<!-- Font Awesome -->
<link href="<?php echo URL?>/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
<!-- NProgress -->
<link href="<?php echo URL?>/vendors/nprogress/nprogress.css" rel="stylesheet">
<!-- iCheck -->
<link href="<?php echo URL?>/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
<!-- Datatables -->
<link href="<?php echo URL?>/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
<link href="<?php echo URL?>/vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
<link href="<?php echo URL?>/vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
<link href="<?php echo URL?>/vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
<link href="<?php echo URL?>/vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
<!-- Custom Theme Style -->
        <!-- page content -->
        <div class="right_col" role="main">

          <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel<?php if(isset($_GET['id'])&&!$theID)echo ' lock'?>">
				<?php
				$hasPopup=true;
                switch($action){
					case 'viewStaff':
						include 'options-lists.php';
					break;
					case 'view':
						$hasPopup=false;
						include 'options-lists.php';
					break;
					case 'create':
						include 'options-form.php';
					break;
                }
              if($hasPopup) include 'popup-box.php';
                ?>
                
                </div>
              </div>

          </div>
          
        </div>
        <!-- /page content -->
      </div>
      <?php include 'footer.php';?>
    </div>
<script src="<?php echo URL?>/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
<script src="<?php echo URL?>/vendors/datatables.net-scroller/js/datatables.scroller.min.js"></script>

<script src="<?php echo URL?>/vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js"></script>
<script src="<?php echo URL?>/vendors/google-code-prettify/src/prettify.js"></script>
<script>
function getLink(theID,linkType){
	switch(linkType){
		case 'payroll':
			window.location.href='<?php echo URL;?>/methods?module_type=payroll&action=create&id='+theID;	
		break;
	}
}
function createOption(serialVal) {
	switch(serialVal){
		case 'staffDetails': //update staffs details
			jQuery.ajax({
			url: "storage.php",
			data:$("form#"+serialVal).serialize(),
			type: "POST",
			success:function(data){
				x=$("form#"+serialVal+" #firstname").val();
				if(data.success){
					$('.modal-footer #success').html('Data has been saved!').delay(3000).fadeOut(400);
				}
				
			},
			error:function (){}
			});
		break;
		case 'addLogs': case 'addAdjustments':
			$("#"+serialVal+" #saveLogs").html('Wait!');
			recordID=$("#"+serialVal+" #recordID").val();
			staffID=$("#"+serialVal+" #staffID").val();
			theDate=$("#"+serialVal+" #theDate").val();
			inOut=$("#"+serialVal+" #inOut").val();
			formData = {'action':serialVal,'recordID':recordID,'staffID':staffID,'theDate':theDate,'inOut':inOut};
			jQuery.ajax({
			url: "storage.php",
			data:formData,
			type: "POST",
			success:function(data){
				$("#"+serialVal+" #saveLogs").html('Save');
				if(!data.isExist)$("table#staffListTable tbody").prepend(data.logList);
				//$(dataHTML).html(data);
				//alert(dataID+" | "+data.staffID+" | "+data.type+" | "+data.dateIn+" | "+data.dateOut);
			},
			error:function (){}
			});
			
			
		break;
		case 'payrollForm': //create update payrolls
			jQuery.ajax({
			url: "storage.php",
			data:$("form#"+serialVal).serialize(),
			type: "POST",
			success:function(data){
				dataID = data.success;
				$("#"+serialVal+" #theID").val(dataID);
				dataType=$("#"+serialVal+" #type").val();
				theAction=$("#"+serialVal+" #theAction").val();
				if(theAction=='create'){
					if(dataType<2){
						$("#"+serialVal+" #submitFile").removeClass('hide');
						$("#"+serialVal+" #importBox").removeClass('hide');
					}
					$("#"+serialVal+" #createAdjustments").val(dataID).removeClass('hide');
					$("#"+serialVal+" #createLogs").val(dataID).removeClass('hide');
					$("#"+serialVal+" .selectCol").addClass('lock');
				}else{
					//alert(data.end);	
				}
				$("#"+serialVal+" #theAction").val('update');
				$("#"+serialVal+" #actionBtn").html('Update');
				//$("#"+serialVal+" #"+btnLogs).removeClass('hide');
			},
			error:function (){}
			});
		break;
	}
}

function getPopup(me,theTable,action){
	theID = me.value;
	switch(action){
	case "viewStaff": case "logStaff": case "adjustmentStaff": case "logDetails":
		modalBox = action;
		popupTitle='Staff Information';
		formData = {'action':action,'theTable':theTable,'theID':theID};
		tempHTML='<div class="modal-header iconUser"><h4 class="modal-title">Loading user data, please wait...</h4></div>';
		if(action=='logStaff'||action=='adjustmentStaff'||action=='logDetails'){
			modalBox = 'logBox';
			popupTitle='Staff Logs Details';
			if(action=='adjustmentStaff'){popupTitle='Staff Adjustments: Overtime';}
			if(action=='logDetails')popupTitle='<span class="small">Payroll Title: </span>'+$('#payrollForm #title').val();
			$('.modal.'+modalBox+' .modal-content .modal-footer').hide();
		}
		dataHTML = '.modal.'+modalBox+' .modal-content .modal-body';
		$('.modal.'+modalBox+' .modal-content .modal-header .modal-title').html(popupTitle);
		$(dataHTML).html(tempHTML);
	break;
	
	}
	jQuery.ajax({
	url: "storage.php",
	data:formData,
	type: "POST",
	success:function(data){
		$(dataHTML).html(data);
		switch(action){
			case "viewStaff":
				$('.modal.'+modalBox+' .modal-footer #saveBtn').attr("onclick","createOption('staffDetails')");
				onclick=""
			break;
		}
	},
	error:function (){}
	});
}
  $(document).ready(function() {
	var handleDataTableButtons = function() {
	  if ($("#staffListTable").length) {
		$("#staffListTable").DataTable({
		  dom: "Bfrtip",
		  buttons: [
			{
			  extend: "copy",
			  className: "btn-sm"
			},
			{
			  extend: "csv",
			  className: "btn-sm"
			},
			{
			  extend: "print",
			  className: "btn-sm"
			},
		  ],
		  responsive: true
		  <?php if(!$hasPopup)echo ',"order": [[ 0, "desc" ]]';?>
		});
	  }
	};

	TableManageButtons = function() {
	  "use strict";
	  return {
		init: function() {
		  handleDataTableButtons();
		}
	  };
	}();


	TableManageButtons.init();
	
	
  });
  
   $(".type").select2({
		placeholder: "Select option",
		allowClear: false
	});
	
	
	
</script>
  </body>
</html>