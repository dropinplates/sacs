<?php
//$this->method->schema = "projectzero";

$reportingTypes = ['unit','division','payment_mode','loan_purpose','loan_types','membership_type','interest_type','reports','cash_option','dd_type','admin'];//,'admin','role','gender','status','civil_status','field_type'
if($_SESSION['userID'] == 1){
	$superAdminFields = ['admin']; //,'accounting_code'
	foreach($superAdminFields as $setField){
		$reportingTypes[] = $setField;
	}
}

$fields = ['id','meta_id','meta_key','meta_option','meta_value'];

$stmtCodebook = ['schema'=>Info::DB_SYSTEMS,'table'=>'codebook','arguments'=>['id >'=>1],'pdoFetch'=>PDO::FETCH_GROUP | PDO::FETCH_CLASS,'extra'=>'AND meta_key IN ("'.implode('","',$reportingTypes).'") ORDER BY CAST(meta_id AS DECIMAL) ASC','fields'=>['meta_key','id','meta_id','meta_option','meta_value']];
$getCodebook = $methods->selectDB($stmtCodebook);

$stmtFields = ['schema'=>Info::DB_SYSTEMS,'table'=>'fields AS tblFields','arguments'=>['tblFields.status'=>1,'tblFields.codebook_id >'=>1],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'extra'=>'AND tblFields.alias IN ("'.implode('","',$reportingTypes).'") OR tblFields.id = tblFields.codebook_id ORDER BY tblFields.id ASC','fields'=>['tblFields.alias','(SELECT alias FROM '.Info::PREFIX_SCHEMA.Info::DB_SYSTEMS.'.fields WHERE id = tblFields.codebook_id)']];
$getFields = $methods->selectDB($stmtFields);

$methods->tblCol = ['meta_id'=>['Meta ID','14%'],'meta_option'=>['Meta Alias','38%'],'meta_value'=>['Meta Name/Title','42%']];

if($_SESSION['userrole'] >= 3){ // PAGE AUTHENTICATION
	$getPageToken = $methods->getValueDB(['table'=>'tokens','alias'=>$methods->Params['pageName'],'schema'=>Info::DB_SYSTEMS]);
	if($getPageToken['id']){ // IF USER NOT ADMIN AND NOT AUTHORIZE
		if(!in_array($getPageToken['id'], $_SESSION["tokens"])) throw new \Exception($methods->errorPage('403'), 1);
	}
}
if($methods->authError) throw new \Exception($methods->errorPage('405'), 1);
//var_dump($getFields);
?>
<div class="row">
    <div class="container">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_title">
                <h2>Reporting Format</h2><span class="subTitle">|| Double-click fields to edit. System will Auto-Save once data has been updated.</span>
            </div>
            <div id="log"><?php /*<pre> //var_dump($_SESSION["unit_area"]); //$getDataQueue[$theID]->unit; ,$_SESSION["codebook"]["unit"]</pre>*/?></div>
            <div id="pageElement" class="row boxFlex boxPadding">
                <div class="masonry-grid">
				
                    <?php
                    foreach($getCodebook as $metaType => $metaValues){
                        $tbody = "";
                        $popUpBtn = '<button type="button" id="'.$metaType.'Btn" name="'.$metaType.'" onclick="getPopup(this,\'codebook\',\''.$action.'\')" value="0" class="btn" data-toggle="modal" data-target=".'.$popupType.'"><i class="fa fa-plus"></i></button>';
                    ?>
						<div class="no-padding half masonry-column">
                            <div class="x_panel elementBox no-padding">
                                <div class="x_title">
                                    <h2 class="capitalize"><?php echo str_replace("_"," ",$metaType)?></h2>
                                    <ul class="nav navbar-right panel_toolbox"><li class="right"><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul>
                                </div>
                                <div class="x_content no-padding">
                                    <table id="table-<?php echo $metaType?>" width="100%" class="table codebook-list">
                                        <thead>
                                            <tr class="default"><th width="14%" class="alignCenter">Meta ID</th><th width="32%" class="alignLeft">Meta Alias</th><th width="36%" class="alignLeft">Meta Name/Title</th><th width="16%" class="no-padding no-sort actionBtn"><?php echo $popUpBtn?></th></tr>
                                        </thead>
                                        <tbody id="<?php echo $metaType?>Listings">
                                        <?php
											//$fieldsCodeMeta = $methods->getTableFields(['table'=>'codemeta','exclude'=>['active'],'schema'=>Info::DB_SYSTEMS]);
											$fieldsCodeMeta = array_merge(['meta_value'],$methods->getTableFields(['table'=>'codemeta','exclude'=>['active'],'schema'=>Info::DB_SYSTEMS]));
											$keyParent = (isset($getFields[$metaType])) ? $getFields[$metaType] : "";
											$stmtCodeMeta = ['schema'=>Info::DB_SYSTEMS,'table'=>'codemeta','arguments'=>['key_value'=>$metaType,'key_parent'=>$keyParent],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>$fieldsCodeMeta];
											$getCodeMeta = $methods->selectDB($stmtCodeMeta);
											//var_dump($getCodeMeta);
                                            $methods->parseType = true;
                                            foreach($metaValues as $metaField => $metaValue){
                                                $dataParams = ["metaType"=>$metaType,"metaValue"=>$metaValue,"getFields"=>$getFields,"getCodeMeta"=>$getCodeMeta];
                                                $tbody .= $methods->getTableListings($dataParams);
                                                //$tbody .= "<tr id='meta_".$metaValue->meta_option."' class='editable_meta'>{$optionTbl}</tr>";
                                            }
                                        echo $tbody;
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    <?php
                    }




                    ?>
					
                </div>
            </div>
        </div>

    </div>

</div>
<script>
    $('.editable').dblclick(function(e) {
        $(this).attr('readonly',false);
    });
	
	$("a.collapse-link").on('click',function () {
		$('.masonry-grid').masonry({
				itemSelector: '.masonry-grid .masonry-column',
				columnWidth: 160
			});
	});
    $(window).ready(function() {

        var m = new Masonry($('.masonry-grid').get()[0], {itemSelector: ".masonry-grid .masonry-column"});


//        webshims.setOptions('forms-ext', { // INPUT NUMBER FORMAT CURRENCY
//            replaceUI: 'auto',
//            types: 'number'
//        });
//        webshims.polyfill('forms forms-ext');

//        $('#datePeriod').daterangepicker({
//            singleDatePicker: false,
//            singleClasses: "datePeriod"
//        }, function(start, end, label) {
//            //console.log(start.toISOString(), end.toISOString(), label);
//        });
    });
</script>
<script type="text/javascript" src="<?php echo Info::URL?>/js/masonry.pkgd.min.js"></script>