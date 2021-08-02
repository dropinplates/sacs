<?php
/**
* @author	Amatz Fox - ZERO32
* @since	September 21, 2018
* @type		Update Procedures
*/

$tableFields = $methods->getTableFields(['table'=>$object,'exclude'=>['date','user','status'],'schema'=>Info::DB_SYSTEMS]);
$stmtData2 = ['schema'=>Info::DB_SYSTEMS,'table'=>$object,'arguments'=>['alias'=>$alias],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>$tableFields];
$getObjectValue = $methods->selectDB($stmtData2);
$objectValueID = key($getObjectValue);

//var_dump($getSystemValue,$getObjectValue,$objectValueID);
?>
<div class="container">
    <div class="col-sm-12">
		<div id="pageElement">
			<div class="x_title">
				<h2><?php echo $getMetaCode[$view]->meta_value.' '.$object.' <span class="subTitle">|| '.$object.' settings and configuration page</span>'?> </h2>
			</div>
			<form name="systemElements" id="<?php echo $object.'_'.$getObjectValue[$objectValueID]->alias?>" data-toggle="validator" class="form-label-left input_mask" novalidate>
				<div class="x_panel no-padding no-border">
					<div class="x_title">
						<h2>
							<?php echo $getObjectValue[$objectValueID]->name.'<span class="subTitle" style="text-transform:none;">|| ['.$getObjectValue[$objectValueID]->alias.'] : '.$getObjectValue[$objectValueID]->description.'</span>'?>
						</h2>
						<ul class="nav navbar-right panel_toolbox"><li class="right"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul>
					</div>
					<div class="fieldGroup x_content no-padding">
						<input type="hidden" name="action" id="action" value="autoSave" />
						<input type="hidden" name="table" id="table" value="<?php echo $object?>" />
						<input type="hidden" name="id" id="id" value="<?php echo $objectValueID?>" />
						<input type="hidden" name="field" id="field" value="value" />
						<textarea id="textarea" name="value" style="width:100%;height:320px"><?php echo $getObjectValue[$objectValueID]->value?></textarea>
					</div>
				</div>
			</form>
			<div class="submitBottomBox">
				<div class="col-md-12 col-sm-12 col-xs-12 no-padding">
					<button type="button" class="btn btn-success capitalize no-border no-margin" id="<?php echo $object.'_'.$getObjectValue[$objectValueID]->alias?>" name="systemElements" onclick="submitData(this)"><?php echo 'Update '.$object?> </button>
				</div>
			</div>
		</div>
	</div>
</div>
<script language="Javascript" type="text/javascript" src="<?php echo Info::URL?>/js/text-editor/edit_area_full.js"></script>
<script language="Javascript" type="text/javascript">
editAreaLoader.init({
	id: "textarea",	// id of the textarea to transform	
	start_highlight: true,	// if start with highlight
	font_family: "monospace, Consolas, DejaVu Sans Mono, arial, verdana",
	font_size: "7.6",
	line_height: "inherit",
	allow_resize: "both",
	allow_toggle: true,
	word_wrap: false,
	language: "en",
	min_height: 424,
	syntax: "php",	
	replace_tab_by_spaces: "3"
});

function changeText(me){
	textValue = $(me).val();
	parentName = $(me).attr("inputname");
	$("textarea[name="+parentName+"]").text(textValue);
}
		
function submitData(me){
    formName = me.name;
    pathName = me.id;
    extraParam = '&user=<?php echo $_SESSION['userID']?>';
    //formData = $("form#"+action).serialize()+extraParam;
	formData = $("form[name="+formName+"]").serialize()+extraParam;
    //alert(formData);
    jQuery.ajax({
    url: "storage.php",
    data:formData,
    type: "POST",
    success:function(data){
		console.log(data);
        //$('#alert').html(data.alert).fadeIn(400).delay(3000).fadeOut(400);
		
		// NOTIFICATION ALERT START
		result = 'ERROR FOUND';
		result_type = 'error';
		message = 'Please contact your system administrator!';
		hide_alert = false;
		if(data.result){ // NO ERROR
			result = data.result;
			result_type = result;
			message = data.message;
			hide_alert = true;
		}
		if(data.result == "error"){
			hide_alert = false;
			message = data.message;
		}
		
		new PNotify({
			title: 'Action: '+result,
			text: message,
			type: result_type,
			hide: hide_alert,
			styling: 'bootstrap3',
			delay: 3200,
			addclass:"notify-success"
		});
		// NOTIFICATION ALERT END
		
        //$('input#theID').val(data.id);
        //$('input#data').val(data.data);
    },
        error:function (){}
    });
}
</script>
