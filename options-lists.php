<?php
$arrayLists = '';
$titleHeader = 'Staff Information <small>Employee/Staff information listings</small>';
switch($action){
	case 'viewStaff':
		$theTable='staffs';
		$getLists=getMetaValue($theTable,array('id>'=>2),'id');//,'status'=>1
		if($getLists)$arrayLists=explode(',',$getLists);
		$listHead = '<tr><th>Name</th><th>Bio-ID</th><th>Position</th><th>Office</th><th>Salary</th><th></th></tr>';
	break;
	case 'view':
		$theTable='payroll';
		$popupType='viewPayroll';
		$titleHeader = 'Payroll Listings <small>Lists of all payroll logs</small>';
		$getLists=getMetaValue($theTable,array('start!'=>'0000-00-00','end!'=>'0000-00-00'),'id');
		if($getLists)$arrayLists=explode(',',$getLists);
		$listHead = '<tr><th class="alignCenter">Date</th><th>Title/Description</th><th>Type</th><th class="alignCenter">Period Start</th><th class="alignCenter">Period End</th><th>Logs</th></tr>';
	break;
}
?>
<div class="x_title">
    <h2><?php echo $titleHeader?></h2>
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
  <div class="x_content">
  <!--<p class="text-muted font-13 m-b-30"></p>-->
  <table id="staffListTable" width="100%" class="table table-striped table-bordered">
      <thead>
        <?php echo $listHead?>
      </thead>
      <tbody>
		<?php
        if($arrayLists){
            //$arrayActiveStaffs=array(1,4,5,8,9,10,11,12,14,15,16,18,21,22,24,25,26,27,28,29,30,33,34,35,36,37,38,39,40,42,44,45,46,47,48,49,52,54,55,56,57,58,59,61,65,68,69,70,73,74,76,77,78,81,82,84,89,91,92,93,98,100,101,102,105,107,109,110,111,112,113,115,116,118,121,122,124,125,127,129,130,131,132,133,135,137,139,141,142,144,145,146,149,151,152,153,154,158,159,160,162,163,164,165,166,168,170,171,172,173,175,176,177,179,180,181,182,184,187,188,189,190,191,192,193,195,199,200,201,202,205,206,207,208,209,211,212,213,214,215,216,217,218,220,221,222,223,224,225,226,228,229,232,235,237,238,80,239,241,243,244,245,246,249,251,252,253,254,255,256,257,258,260,261,264,266,267,269,270,271,272,273,274,275,276,278,279,280,282,285,286,287,288,289,290,292,293,294,295,296,297,298,299,301,302,305,307,309,310,311,313,314,315,316,317,318,319,320,321,322,323,324,325,326,327,328,329,330,331,332,333,248,335,336,337,339,340,341,342,343,344,345,346,347,348,349,350,352,353,356,357,358,359,360,361,364);
            //$extractStaffs=array_intersect($arrayLists, $arrayActiveStaffs);
            foreach($arrayLists as $theID){
             echo getListings($theTable,$theID,$popupType);
            }
        }
        ?>
      </tbody>
    </table>
  </div>
