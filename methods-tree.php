<?php
//$getPath = new getMetaValue(['schema'=>DB_SYSTEMS,'table'=>$object]);
//$thisPath = $getPath->listings(['status>'=>1,'alias'=>$alias,'statement'=>'order by name asc'],getTableFields($object,['date','user']));

$stmtPath = ['schema'=>Info::DB_SYSTEMS,'table'=>$object,'arguments'=>['alias'=>$params['alias'],'status >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name','description','groups']];
$thisPath = $methods->selectDB($stmtPath);

//$getForm = new formTree(['schema'=>Info::DB_SYSTEMS,'table'=>$object,'type'=>'treeView']);
//$theForm = $getForm->formBox($thisPath[$params['alias']]->groups);

$stmtProcedure = ['schema'=>Info::DB_SYSTEMS,'table'=>'procedure','arguments'=>['id>'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name']];
$getProcedure = $methods->selectDB($stmtProcedure);

//$pathDataTable = getTableFields('path_1_loan_details',['id','date']); // TABLE FIELDS ON AUTO RECORDS
//$checkTable = substr('path_1_loan_details', 0, 4);

//$generatePath = generateTablePath('path','',$alias,['loan_details','loan_details_test']);

// $getGroupFields = array_merge(['alias'],$methods->getTableFields(['table'=>'groups','exclude'=>['alias','date','status','user'],'schema'=>Info::DB_SYSTEMS]));
// $stmtGroupFields = ['schema'=>Info::DB_SYSTEMS,'table'=>'groups','arguments'=>['alias'=>'loan_application','status'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>$getGroupFields];
// $postGroupFields = $methods->selectDB($stmtGroupFields);
// $postGroupElements = $postGroupFields[$params['alias']]->elements;

// $getPostElements = $methods->setGroupElements(["elements"=>$postGroupElements,"pathID"=>1]);
// $getElementForm = $methods->elementForm($getPostElements);
//$methods->getElementType = "1";

//$methods->Params["pathID"] = 2;
$stmtFieldTypes = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook','arguments'=>['meta_key'=>'field_type'],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['meta_id','meta_option','meta_value']];
$getFieldTypes = $methods->selectDB($stmtFieldTypes);

$getElementTree = $methods->elementTree($params['alias']);
//$elementProcedures = array_column($getElementTree['meta']['procedure'], 'procedure');
//$elementFields = array_column($getElementTree['meta']['group']['loan_details'], 'field');
$arrayElementGroups = $getElementTree['meta']['group'];
$elementGroupFields = [];
foreach($arrayElementGroups as $groupName => $groupFields){
	$elementGroupFields = array_merge($elementGroupFields,array_column($getElementTree['meta']['group'][$groupName], 'field'));
}
$stmtPathFields = ['schema'=>Info::DB_SYSTEMS,'table'=>'fields','arguments'=>['id>'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'AND alias IN ("'.implode('","',$elementGroupFields).'") ORDER BY id ASC','fields'=>['alias','id','name','field_type']];
$getPathFields = $methods->selectDB($stmtPathFields);

$elementTreeData = [];
$cnt = 0;
$cntParent = 1;
$arrayElementTree = $getElementTree['elements'];

foreach($arrayElementTree as $key => $value){
	$thisEementTree = [];
	if(count($value) > 1){ // GROUPS
		$parentKey = "group";
		$id = $_SESSION['groups'][$key]->id;
		$text = $_SESSION['groups'][$key]->name;
		$tags = [2];
	}else{
		$parentKey = key($value);
		$id = $getProcedure[$key]->id;
		$text = $getProcedure[$key]->name;
		$tags = [0];
	}
	$thisEementTree = [
		"id" => $id,
		"name" => $key,
		"type" => $parentKey,
		"object" => $parentKey,
		"text" => $text,
		"href" => "#{$key}",
		"tags" => $tags
	];
	if(count($value) > 1){ // GROUP
		$thisElementChild = [];
		$cntSub = 1;
		$tags = [0];
		foreach($value as $elementValue){ // GETTING THE FIELDS ONLY 2nd DEGREE ARRAYS
			$elementKey = key($elementValue);
			$thisElementChild[] = [
				"id" => (isset($getPathFields[$elementValue[$elementKey]]->id)) ? $getPathFields[$elementValue[$elementKey]]->id : $elementValue[$elementKey],//(string)$cntSub,
				"name" => $elementValue[$elementKey],
				"type" => ($elementKey == 'field') ? $getFieldTypes[$getPathFields[$elementValue[$elementKey]]->field_type]->meta_option : $elementKey,
				"object" => $elementKey,
				"text" => ($elementKey == 'field') ? $getPathFields[$elementValue[$elementKey]]->name : $elementValue[$elementKey],
				"href" => "#{$elementValue[$elementKey]}",
				"tags" => $tags
			];
			$cntSub++;
		}
		$thisEementTree["nodes"] = $thisElementChild;
	}
	$elementTreeData[] = $thisEementTree;
	$cnt++; $cntParent++;
}
//$dataLoanID = $dataLoanNum = $checkElements['loan_details']['loan_id'];
$jsonDataTree = json_encode($elementTreeData,true);
//var_dump($thisPath);
?>
<?php /*?><pre><?php print_r($jsonDataTree);?></pre><?php */?>
<div class="container">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_title">
            <h2>Path Map Elements Tree <small>Objects and Fields: procedures, groups and fields</small></h2>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="x_panel">
            <div class="x_title">
                <h2><span class="subTitle"><?php echo $object?> Name:</span> <?php echo $thisPath[$params['alias']]->name?></h2>
                <ul class="nav navbar-right panel_toolbox"><li class="right"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul>
            </div>
            <div class="x_content">
                <p><?php echo $thisPath[$params['alias']]->description?></p>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="x_panel">
            <div class="x_title boxWrapper">
                <h2>Object and Fields</h2>
            </div>
			<input type="hidden" name="action" id="action" value="create<?php echo $thisView?>" />
			<input type="hidden" name="table" id="table" value="<?php echo $thisView?>" />
			<input type="hidden" name="status" id="status" value="1" />
            <div id="treeview3" class=""></div>
            <div class="ln_solid"></div>
			<div class="left">
				<button type="submit" class="btn btn-success" onclick="generatePath()">Generate Path Elements</button>
			</div>
        </div>
        <div class="tree" id="alert"></div>
    </div>
</div>
<script src="<?php echo Info::URL?>/js/bootstrap-treeview.js"></script>

<script type="text/javascript">

function generatePath(){
   paramObject = [];
    $('.list-group li[object="group"]').each(function() {
         paramKey = [];comma = ""; elements = "";
        groupName = $(this).attr('name');
        $('input#'+groupName).each(function() {
            inputValue = $(this).val();
            inputName = $(this).attr('name');
           // paramKey['group'] = [groupName];
           //elements.push(inputName);
		   elements += comma+inputName;//+":"+inputValue;
		//elements[cnt] = theID:theItem};
        //elements[theItem] = theID;
            comma = ',';

        });
      // elementValues = JSON.stringify(elements);
       //paramObject[groupName] = elements;//JSON.stringify(elements);
       paramObject.push(groupName+"="+elements);
    });
    paramObjectValue = JSON.stringify(paramObject);
    formData = {"action":"generatePath","path_name":"<?php echo $alias?>","path_id":<?php echo $thisPath[$params['alias']]->id?>,"path_group":paramObjectValue}
	theValue = 'alias="Amatz Fox",description="Quick Brown"';
	//formData = JSON.parse(theMeta);
	//formData['value'] = theValue;
	//console.log(formData);
	//alert(formData['group'][0]['elements']);
    jQuery.ajax({
    url: "storage.php",
    data:formData,
    type: "POST",
    success:function(data){
		console.log(data);
		
        $('#alert').css("display","block").html(data.sql_statement);
    },
    error:function (){}
    });
}

function generateField(tableName,fieldName){

    formData = {"action":"generateField","pathName":"<?php echo $alias?>","tableName":tableName,"fieldName":fieldName}

	console.log(formData);
	//alert(formData['group'][0]['elements']);
    jQuery.ajax({
    url: "storage.php",
    data:formData,
    type: "POST",
    success:function(data){

        $('#alert').html(data);
    },
    error:function (){}
    });
}

$(function() {
var defaultData = <?php echo $jsonDataTree;//echo "[{$theForm}]";?>;

var json = '[' +
    '{' +
    '"text": "Parent 1",' +
    '"nodes": [' +
        '{' +
        '"text": "Child 1",' +
        '"nodes": [' +
            '{' +
            '"text": "Grandchild 1"' +
            '},' +
            '{' +
            '"text": "Grandchild 2"' +
            '}' +
        ']' +
        '},' +
        '{' +
        '"text": "Child 2"' +
        '}' +
    ']' +
    '},' +
    '{' +
    '"text": "Parent 2"' +
    '},' +
    '{' +
    '"text": "Parent 3"' +
    '},' +
    '{' +
    '"text": "Parent 4"' +
    '},' +
    '{' +
    '"text": "Parent 5"' +
    '}' +
']';

$('#treeview3').treeview({
    levels: 999,
    data: defaultData
});

});
</script>
