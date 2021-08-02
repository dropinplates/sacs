<?php
/**
* @author	Amatz Fox - ZERO32
* @since	September 21, 2018
* @type		Drag and Drop Group, Fields and Procedures
*/

$cntField = 0; $cntGroup = $cntProcedure = 0;
$objElement = $dropdownListings = '';

$getFieldLists = $getGroupLists = $getProcedureLists = [];

$stmtFields = ['schema'=>Info::DB_SYSTEMS,'table'=>'fields','arguments'=>['id >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name']];
$getFields = $methods->selectDB($stmtFields);

$stmtGroups = ['schema'=>Info::DB_SYSTEMS,'table'=>'groups','arguments'=>['id >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name','elements']];
$getGroups = $methods->selectDB($stmtGroups);

$stmtProcedure = ['schema'=>Info::DB_SYSTEMS,'table'=>'procedure','arguments'=>['id >'=>1],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'extra'=>'ORDER BY id ASC','fields'=>['alias','id','name']];
$getProcedure = $methods->selectDB($stmtProcedure);

$groupElements = $getGroups[$params['alias']]->elements;
$arrayGroupElements = explode(",",$groupElements);
$objectElements = [];
foreach($arrayGroupElements as $groupElement){
    $getElement = explode(":",$groupElement);
    $objectType = $getElement[0];
    $objectAlias = str_replace($getElement[0].'-', '', $getElement[1]);
    $objectElements[$objectType][] = $objectAlias;
    switch($objectType){
        case "field":
            $objectID = $getFields[$objectAlias]->id;
            $objectName = $getFields[$objectAlias]->name;
            break;
        case "group":
            $objectID = $getGroups[$objectAlias]->id;
            $objectName = $getGroups[$objectAlias]->name;
            break;
        case "procedure":
            $objectID = $getProcedure[$objectAlias]->id;
            $objectName = $getProcedure[$objectAlias]->name;
            break;
		default: // CUSTOM
            $objectID = $getFields[$objectAlias]->id;
            $objectName = $getFields[$objectAlias]->name;
            break;
    }

    $objElement .= "<div id='{$getElement[1]}' class='item x_panel box-{$getElement[0]}' draggable='true' item='{$getElement[0]}'><span>{$objectName} [{$objectID}]</span><a class='close-link right'><i class='fa fa-close'></i></a></div>";
}
$objectElementField = (!EMPTY($objectElements['field'])) ? $objectElements['field'] : [];
if(sizeof($objectElementField) > 0){
    $objectFieldLists = array_diff(array_keys($getFields),$objectElementField);
    $getFieldLists = $objectFieldLists;
}else{
    $getFieldLists = array_keys($getFields);
}

if(!EMPTY($objectElements['group']) && sizeof($objectElements['group']) > 0){
    $objectGroupLists = array_diff(array_keys($getGroups),$objectElements['group']);
    $getGroupLists = $objectGroupLists;
}else{
    $getGroupLists = array_keys($getGroups);
}

if(!EMPTY($objectElements['procedure']) && sizeof($objectElements['procedure']) > 0){
    $objectProcedureLists = array_diff(array_keys($getProcedure),$objectElements['procedure']);
    $getProcedureLists = $objectProcedureLists;
}else{
    $getProcedureLists = array_keys($getProcedure);
}

foreach($getFieldLists as $fieldAlias){
    $fieldAliasValue[$fieldAlias] = $getFields[$fieldAlias]->name." [".$getFields[$fieldAlias]->id."]";
}

foreach($getGroupLists as $groupAlias){
    $groupAliasValue[$groupAlias] = $getGroups[$groupAlias]->name." [".$getGroups[$groupAlias]->id."]";
    $dropdownListings .= '<li><a href="'.Info::URL.'/methods?module_type='.$module_type.'&view='.$view.'&object='.$object.'&alias='.$groupAlias.'">'.$getGroups[$groupAlias]->name.' ['.$getGroups[$groupAlias]->id.']</a></li>';
}

foreach($getProcedureLists as $procedureAlias){
    $fieldProcedureValue[$procedureAlias] = $getProcedure[$procedureAlias]->name." [".$getProcedure[$procedureAlias]->id."]";
}
?>
<script src="<?php echo Info::URL?>/js/angular.min.js"></script>
<div ng-app="dragDrop" ng-controller="DragDropController">
    <div class="container">
        <!--<pre><?php /*var_dump($getGroups);*/?></pre>-->
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_title">
                <h2>Group Form Settings <small>{{ message }}</small></h2>
                <div class="titleDropdown btn-group right">
                    <i class="fa fa-list"></i><button data-toggle="dropdown" class="btn btn-default dropdown-toggle" type="button"> Show Group Listings <span class="caret"></span> </button>
                    <ul class="dropdown-menu"><?php echo $dropdownListings;?></ul>
                </div>
            </div>
        </div>
		<div class="col-md-6 col-sm-6 col-xs-12 fieldGroup">
            <div class="x_panel tile field">
                <div class="x_title">
                    <h2><span class="subTitle">Object Name:</span> Fields</h2>
                    <ul class="nav navbar-right panel_toolbox"><li class="right"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul>
                </div>
                <div class="x_content">
                    <div class="row"  ng-repeat="item in ['<?php echo implode("','",$getFieldLists)?>']"> <!-- FIELD LISTINGS -->
						<div id="field-{{ item }}" class="item x_panel box-field" draggable item="field"><span>{{ item }}</span><a class="close-link right"><i class="fa fa-close"></i></a></div>
					</div>
                </div>
            </div>
            <div class="x_panel tile group">
                <div class="x_title">
                    <h2><span class="subTitle">Object Name:</span> Groups</h2>
                    <ul class="nav navbar-right panel_toolbox"><li class="right"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul>
                </div>
                <div class="x_content">
                    <div class="row" ng-repeat="item in ['<?php echo implode("','",$getGroupLists)?>']"> <!-- GROUP LISTINGS -->
                        <div id="group-{{ item }}" class="item x_panel box-group" draggable item="group"><span>{{ item }}</span><a class="close-link right"><i class="fa fa-close"></i></a></div>
                    </div>
                </div>
            </div>
            <div class="x_panel tile procedure">
                <div class="x_title">
                    <h2><span class="subTitle">Object Name:</span> Procedure</h2>
                    <ul class="nav navbar-right panel_toolbox"><li class="right"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul>
                </div>
                <div class="x_content">
                    <div class="row" ng-repeat="item in ['<?php echo implode("','",$getProcedureLists)?>']"> <!-- PROCEDURE LISTINGS -->
                        <div id="procedure-{{ item }}" class="item x_panel box-procedure" draggable item="procedure"><span>{{ item }}</span><a class="close-link right"><i class="fa fa-close"></i></a></div>
                    </div>
                </div>
            </div>
        </div>
        
		<div class="col-md-6 col-sm-6 col-xs-12 objectBin">
		<form>
			<div class="x_panel tile binBox">
				<div class="x_title">
					<h2><span class="subTitle">Group Name:</span> <?php echo $getGroups[$params['alias']]->name?></h2>
					<ul class="nav navbar-right panel_toolbox"><li class="right"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul>
				</div>
				<div class="x_content">
					<div class="form-group">    
						<div class="row" ng-repeat="bin in ['<?php echo $_GET['alias']?>']"><div id="{{ bin }}" class="bin" bin="bin" droppable drop="handleDrop">
							{{ bin }}
							<div class="ln_solid"></div>
							<?php echo $objElement;?>
							</div>
						</div>
					</div>
					<div class="ln_solid"></div>
					<div class="left">
						<button type="submit" class="btn btn-success" onclick="save(<?php echo $getGroups[$params['alias']]->id?>)"><?php ($getGroups[$params['alias']]->id)? print 'Update':print 'Create'?> Group</button>
					</div>
				</div>
				</div>
			</form>
		</div>
    </div>
</div>    

<script>
<?php if(isset($_GET['alias']) && $_GET['alias'] != '' && $thisType == 'systems' && $object == 'groups') { ?>

(function() {
    "use strict";
    var app = angular.module('dragDrop', []);

    app.directive('draggable', function() {
        return function(scope, element) {
            // this gives us the native JS object
            var el = element[0];

            el.draggable = true;

            el.addEventListener(
                'dragstart',
                function(e) {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('Text', this.id);
                    this.classList.add('drag');
                    return false;
                },
                false
            );

            el.addEventListener(
                'dragend',
                function(e) {
                    this.classList.remove('drag');
                    return false;
                },
                false
            );
        }
    });

    app.directive('droppable', function() {
        return {
            scope: {
                drop: '&',
                bin: '='
            },
            link: function(scope, element) {
                // again we need the native object
                var el = element[0];

                el.addEventListener(
                    'dragover',
                    function(e) {
                        e.dataTransfer.dropEffect = 'move';
                        // allows us to drop
                        if (e.preventDefault) e.preventDefault();
                        this.classList.add('over');
                        return false;
                    },
                    false
                );

                el.addEventListener(
                    'dragenter',
                    function(e) {
                        this.classList.add('over');
                        return false;
                    },
                    false
                );

                el.addEventListener(
                    'dragleave',
                    function(e) {
                        this.classList.remove('over');
                        return false;
                    },
                    false
                );

                el.addEventListener(
                    'drop',
                    function(e) {
                        // Stops some browsers from redirecting.
                        if (e.stopPropagation) e.stopPropagation();

                        this.classList.remove('over');

                        var binId = this.id;
                        var item = document.getElementById(e.dataTransfer.getData('Text'));
                        this.appendChild(item);
                        // call the passed drop function
                        scope.$apply(function(scope) {
                            var fn = scope.drop();
                            if ('undefined' !== typeof fn) {
                                fn(item.id, binId);
                            }
                        });

                        return false;
                    },
                    false
                );
            }
        }
    });

    app.controller('DragDropController', function($scope) {
        $scope.message = 'Select Objects and drag it to over to right panel';
        $scope.handleDrop = function(item, bin) {
            $scope.message = 'Item ' + item + ' has been dropped into ' + bin;
        }
    });
}());
// DRAG AND DROP ANGULAR

function save(thisID){
    elements = [];
	cnt = 0;comma = '';
    $('#<?php echo $alias?> > .item').each(function() {
		theID = $(this).attr('id');
		theItem = $(this).attr('item');
		elements += comma+theItem+":"+theID;
		//elements[cnt] = theID:theItem};
        //elements[theItem] = theID;
		comma = ',';
    });
	//console.log(elements);
	//elements = [elements];
    //elements.push(elements);
	theMeta = '{"action":"updateMeta","table":"groups","id":"'+thisID+'"}';
	//theValue = "elements='"+elements+"'";
    theValue = '{"elements":"'+elements+'"}';
	//alert(theMeta+' | '+theValue);
	updateMeta(theMeta,theValue);
	
}

$(window).load(function() { // AFTER ALL ELEMENTS LOAD
    //console.log("Time until everything loaded: ", Date.now()-timerStart);
    selectField = <?php echo json_encode($fieldAliasValue, JSON_FORCE_OBJECT);?>;
    $.each(selectField, function( index, value ) {
        <?php //echo "fieldHtml = ".$getFields['first_name']->name.";";?>
        //alert(fieldHtml);
        $('#field-'+index+' > span').html(value);
    });

    selectGroup = <?php echo json_encode($groupAliasValue, JSON_FORCE_OBJECT);?>;
        $.each(selectGroup, function( index, value ) {
            $('#group-'+index+' > span').html(value);
    });
    selectProcedure = <?php echo json_encode($fieldProcedureValue, JSON_FORCE_OBJECT);?>;
        $.each(selectProcedure, function( index, value ) {
            $('#procedure-'+index+' > span').html(value);
    });
});
<?php
} //END OF CREATING GROUP OBJECTS

?>
</script>
