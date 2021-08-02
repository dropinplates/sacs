<?php
$sessionTokens = [];
if(isset($_SESSION["tokens"])) $sessionTokens = $_SESSION["tokens"];

$unitParams = (isset($_GET["unit"]) && $_GET["unit"] != "") ? "&unit=".$_GET["unit"] : "";

$getPathActivities = $_SESSION["path_activity"];
?>

<div class="col-md-3 left_col menu_fixed">
  <div class="left_col scroll-view">
    <div class="navbar nav_title">
      <a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=membership'?>" class="site_title"><img src="<?php echo Info::URL?>/images/sacs-name-logo.png" class="mCS_img_loaded"><span class="hide"><?php echo $infoSystems["title"]?></span></a>
    </div>

    <div class="clearfix"></div>

    <!-- menu profile quick info -->
    <div class="profile">
      <div class="profile_pic"<?php // if($sessionAvatar)echo ' style="background-image:url('.URL.'/profiles/80x80_'.$sessionAvatar.')"'?>>
      </div>
      <div class="profile_info">
        <span>Welcome!</span>
        <h2>
		<?php
		echo $_SESSION['firstname'];//$_SESSION["username"];
		$unitName = "unit";
		if($_SESSION["userrole"] == 3) $unitName = "unit_code";
		?>
		</h2>
        <span class="small"><?php echo $_SESSION["position"].': '.$_SESSION['codebook']['unit'][$_SESSION[$unitName]]->meta_value;//$_SESSION["roleValue"]?></span>
      </div>
    </div>
    <!-- /menu profile quick info -->
    <!-- sidebar menu -->
    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
      <div class="menu_section">
		<?php
		$getTokens = (isset($_SESSION["path_activity"][1])) ? array_column($_SESSION["path_activity"][1], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
        <ul class="nav side-menu">
              <li id="menu-membership"><a><i class="fa fa-slideshare"></i> <span>Membership</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-membership_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=membership'?>">Create Membership</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=membership'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-membership_lists"><a class="parent">Membership Applications<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=membership'?>">Application Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[1] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=membership&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
        </ul>
		<?php
		}
		$getTokens = (isset($_SESSION["path_activity"][2])) ? array_column($_SESSION["path_activity"][2], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-savings"><a><i class="fa fa-credit-card"></i> <span>Savings Account</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-savings_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=savings'?>">Create Savings</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=savings'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-savings_lists"><a class="parent">Savings Applications<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=savings'?>">Account Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[2] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=savings&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php
		}
		$getTokens = (isset($_SESSION["path_activity"][3])) ? array_column($_SESSION["path_activity"][3], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-deposits"><a><i class="fa fa-download"></i> <span>Deposits</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-deposits_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=deposits'?>">Create Deposits</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=deposits'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-deposits_lists"><a class="parent">Deposits Transactions<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=deposits'?>">Transaction Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[3] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=deposits&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php 
		}
		$getTokens = (isset($_SESSION["path_activity"][3])) ? array_column($_SESSION["path_activity"][4], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-withdrawal"><a><i class="fa fa-upload"></i> <span>Withdrawal</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-withdrawal_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=withdrawal'?>">Create Withdrawal</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=withdrawal'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-withdrawal_lists"><a class="parent">Withdrawal Transactions<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=withdrawal'?>">Transaction Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[4] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=withdrawal&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php
		}
		$getTokens = (isset($_SESSION["path_activity"][5])) ? array_column($_SESSION["path_activity"][5], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-cbu"><a><i class="fa fa-cubes"></i> <span>Capital Build-Up</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-cbu_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=cbu'?>">Add Capital Build-Up</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=cbu'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-cbu_lists"><a class="parent">Capital Build-Up Transactions<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=cbu'?>">Transaction Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[5] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=cbu&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php 
		}
		$getTokens = (isset($_SESSION["path_activity"][6])) ? array_column($_SESSION["path_activity"][6], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-share_capital"><a><i class="fa fa-cube"></i> <span>Share Capital</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-share_capital_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=share_capital'?>">Create Share Capital</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=share_capital'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-share_capital_lists"><a class="parent">Share Capital Transactions<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=share_capital'?>">Transaction Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[6] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=share_capital&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php
		}
		$getTokens = (isset($_SESSION["path_activity"][7])) ? array_column($_SESSION["path_activity"][7], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-loans"><a><i class="fa fa-stack-overflow"></i> <span>Loan Application</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-loans_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=loans'?>">Apply/Create Loan</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=loans'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-loans_lists"><a class="parent">Loan Applications<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=loans'?>">Transaction Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[7] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=loans&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							$loanMenuLists .= "<li id='menu-cancelled'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=loans&activity=cancelled'>Cancelled/Disapproved</a></li>";
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php
		}
		$getTokens = (isset($_SESSION["path_activity"][8])) ? array_column($_SESSION["path_activity"][8], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-loans_payment"><a><i class="fa fa-database"></i> <span>Loans Payment</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-loans_payment_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=loans_payment'?>">Create Payments</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=loans_payment'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-loans_payment_lists"><a class="parent">Loans Payments<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=loans_payment'?>">Transaction Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[8] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=loans_payment&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php
		}
		$getTokens = (isset($_SESSION["path_activity"][9])) ? array_column($_SESSION["path_activity"][9], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-clients"><a><i class="fa fa-users"></i> <span>Clients/Supplier</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-clients_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=clients'?>">Create Clients</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=clients'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-clients_lists"><a class="parent">Clients/Supplier Listings<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=clients'?>">Client Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[9] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=clients&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php
		}
		$getTokens = (isset($_SESSION["path_activity"][10])) ? array_column($_SESSION["path_activity"][10], 'tokens') : [];
		$checkTokens = array_intersect($sessionTokens,$getTokens);
		if($checkTokens){
		?>
		<ul class="nav side-menu">
			<li id="menu-cash"><a><i class="fa fa-money"></i> <span>Cash Transactions</span> <span class="fa fa-chevron-down"></span></a>
                  <ul class="nav child_menu">
                      <li id="menu-cash_create" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=cash'?>">Create Cash Transaction</a> <a href="<?php echo Info::URL.'/methods?module_type=admin&view=posts&alias=cash'?>"><i class="fa fa-plus-circle"></i></a></li>
                      <li id="menu-cash_lists"><a class="parent">Cash Transaction Listings<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<li id="menu-view_all"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=lists&alias=cash'?>">Transaction Listings</a></li>
							<?php
							$loanMenuLists = "";
							foreach($getPathActivities[10] as $activityID => $thisActivity){
								if(in_array($thisActivity['tokens'],$sessionTokens)){
									$loanMenuLists .= "<li id='menu-{$thisActivity['alias']}'><a href='".Info::URL."/methods?module_type=admin&view=lists&alias=cash&activity={$thisActivity['alias']}'>{$thisActivity['name']}</a></li>";
								}
							}
							echo $loanMenuLists;
							?>
                        </ul>
                      </li>
                  </ul>
              </li>
		</ul>
		<?php } ?>
		<?php //if($_SESSION["userID"] < 2){?>
		<ul class="nav side-menu">
			<li id="menu-accounting"><a><i class="fa fa-book"></i> <span>Accounting</span> <span class="fa fa-chevron-down"></span></a>
				<ul class="nav child_menu">
					<li id="menu-charts"><a href="<?php echo Info::URL.'/methods?module_type=accounting&view=charts'?>">Chart of Accounts</a></li>
					<li id="menu-tree"><a href="<?php echo Info::URL.'/methods?module_type=accounting&view=tree'?>">Accounting Charts Tree</a></li>
					<li class="divider"></li>
					<li id="menu-journals-entry" class="btnCreate"><a href="<?php echo Info::URL.'/methods?module_type=accounting&view=journals&type=entry'?>">Journal Entry</a> <a href="<?php echo Info::URL.'/methods?module_type=accounting&view=journals&type=entry'?>"><i class="fa fa-plus-circle"></i></a></li>
					<li id="menu-journals-lists"><a href="<?php echo Info::URL.'/methods?module_type=accounting&view=journals&type=lists'?>">General Journal</a></li>
					<li class="divider"></li>
					<li id="menu-statements-trial_balance"><a href="<?php echo Info::URL.'/methods?module_type=accounting&view=statements&type=trial_balance'?>">Trial Balance</a></li>
					<li id="menu-statements-income_statement"><a href="<?php echo Info::URL.'/methods?module_type=accounting&view=statements&type=income_statement'?>">Income Statement</a></li>
					<li id="menu-statements-balance_sheet"><a href="<?php echo Info::URL.'/methods?module_type=accounting&view=statements&type=balance_sheet'?>">Balance Sheet</a></li>
				</ul>
			</li>
		</ul>
		<?php //} ?>
		<ul class="nav side-menu">
			<li id="menu-reports"><a><i class="fa fa-bar-chart"></i> <span>Data and Reports</span> <span class="fa fa-chevron-down"></span></a>
			  <ul class="nav child_menu">
			  <?php
			  $reportsPage = $_SESSION['codebook']['reports'];
			  foreach($reportsPage as $reportPageDetails){
				if($reportPageDetails->meta_option == "list_members") echo "<li class='divider'></li>";
				echo "<li id='menu-{$reportPageDetails->meta_option}'><a href='".Info::URL."/methods?module_type=reports&view={$reportPageDetails->meta_option}{$unitParams}'>{$reportPageDetails->meta_value}</a></li>";
			  }
			  ?>
				</ul>
			  </li>
			</li>
		</ul>
		<ul class="nav side-menu">
			<li id="menu-admin"<?php //if($_SESSION["userID"]<3)echo ' class="active"'?>><a><i class="fa fa-dropbox"></i> <span>Administration</span> <span class="fa fa-chevron-down"></span></a>
			  <ul class="nav child_menu"<?php //if($_SESSION["userID"]<3)echo ' style="display:block"'?>>
			  
			<?php if(false) { // BRANCH MANAGER AND CLERK ONLY 	in_array($_SESSION["userrole"],[4,5]) ?>
				 <li><a class="parent">Sync Members<span class="label label-success pull-right uppercase" onclick="processMeta('memberMasterlist',<?php echo $_SESSION['unit']?>)"><i class="fa fa-cloud-download"></i>Import Record</span></a>
				 <li id="menu-settings_pn"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=settings_pn'?>">Branch Information</a></li>
			<?php }
				if(in_array(28,$sessionTokens)) echo "<li id='menu-generate'><a href='".Info::URL."/methods?module_type=admin&view=generate'>Generate/Indexed Records</a></li>";
				if(in_array(39,$sessionTokens)) echo "<li id='menu-generate'><a href='".Info::URL."/methods?module_type=admin&view=amortization_calculator'>Loan Amortization Calculator</a></li>";
			?>
				<li id="menu-users"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=users'?>">User and Profile</a></li>
				<?php if($_SESSION["userrole"] < 2){?>
				<li id="menu-system_settings"><a class="parent">Data Information<span class="fa fa-chevron-down"></span></a>
					<ul class="nav child_menu">
						<li id="menu-system_settings" class="hide"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=system_settings'?>">Credit Rating</a></li>
						<li id="menu-module_policy"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=module_policy'?>">Module Policy</a></li>
						<li id="menu-settings_lam" class="hide"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=settings_lam'?>">Loan Application Memorandum</a></li>
						<li id="menu-settings_pn" class="hide"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=settings_pn'?>">Promissory Notes</a></li>
						<li id="menu-settings_auth_debit" class="hide"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=settings_auth_debit'?>">Authority To Debit</a></li>
						<li id="menu-settings_auth_credit" class="hide"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=settings_auth_credit'?>">Authority To Credit</a></li>
						<li id="menu-settings_share_capital"><a href="<?php echo Info::URL.'/methods?module_type=admin&view=settings_share_capital'?>">Certificate Share Capital</a></li>
					  </ul>
				</li>
				<?php } ?>
				<li id="menu-import"><a class="parent">Import Bulk Data<span class="fa fa-chevron-down"></span></a>
					<ul class="nav child_menu">
						<li id='menu-import-charts'><a href='<?php echo Info::URL?>/methods?module_type=admin&view=import&type=charts'>Accounting Charts</a></li>
						<li id='menu-import-membership'><a href='<?php echo Info::URL?>/methods?module_type=admin&view=import&type=membership'>Membership</a></li>
						<li id='menu-import-loans'><a href='<?php echo Info::URL?>/methods?module_type=admin&view=import&type=loans'>Loan Applications</a></li>
						<li id='menu-import-loans_payment'><a href='<?php echo Info::URL?>/methods?module_type=admin&view=import&type=loans_payment'>Loans Payment</a></li>
						<li id='menu-import-share_capital'><a href='<?php echo Info::URL?>/methods?module_type=admin&view=import&type=share_capital'>Share Capital</a></li>
						<li id='menu-import-cbu'><a href='<?php echo Info::URL?>/methods?module_type=admin&view=import&type=cbu'>Capital Build-Up</a></li>
					  </ul>
				</li>
			<?php
			if(in_array(10,$sessionTokens) && $_SESSION["userrole"] <= 2) echo "<li id='menu-settings-options'><a href='".Info::URL."/methods?module_type=admin&view=settings&type=options'>Option Settings</a></li>";
			if(in_array(11,$sessionTokens) && $_SESSION["userrole"] <= 2) echo "<li id='menu-info'><a href='".Info::URL."/methods?module_type=admin&view=info'>General Settings</a></li>";
			?>
				</ul>
			  </li>
		</ul>
        
	  <?php if($_SESSION["userID"] < 2){?>
        <ul class="nav side-menu">
          <li id="menu-systems"><a><i class="fa fa-cogs"></i> <span>System Settings</span> <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
			<?php if($_SESSION["userrole"] < 2){?>
            <li id="menu-systems_options"><a class="parent">Fields and Objects<span class="fa fa-chevron-down"></span></a>
              <ul class="nav child_menu top">
                <li id="menu-procedure"><a href="<?php echo Info::URL.'/methods?module_type=systems&view=options&object=procedure'?>">Procedure</a></li>
				<li id="menu-codemeta"><a href="<?php echo Info::URL.'/methods?module_type=systems&view=options&object=codefields'?>">CodeBook Fields</a></li>
                <li id="menu-fields"><a href="<?php echo Info::URL.'/methods?module_type=systems&view=options&object=fields'?>">Fields</a></li>
                <li id="menu-groups"><a href="<?php echo Info::URL.'/methods?module_type=systems&view=options&object=groups'?>">Groups</a></li>
              </ul>
            </li>
            <li id="menu-options2"><a class="parent">Workflow Design<span class="fa fa-chevron-down"></span></a>
              <ul class="nav child_menu">
				<li id="menu-path"><a href="<?php echo Info::URL.'/methods?module_type=systems&view=options&object=path'?>">Path Map</a></li>
				<li id="menu-tokens"><a href="<?php echo Info::URL.'/methods?module_type=systems&view=options&object=tokens'?>">Tokens</a></li>
                <li id="menu-activity"><a href="<?php echo Info::URL.'/methods?module_type=systems&view=options&object=activity'?>">Activity</a></li>
				<li id="menu-workflow"><a href="<?php echo Info::URL.'/methods?module_type=systems&view=options&object=workflow'?>">Workflow</a></li>
              </ul>
            </li>
             <li id="menu-options3" class="hide"><a class="parent">Users Access and Roles<span class="fa fa-chevron-down"></span></a>
              <ul class="nav child_menu">
                <li><a href="<?php echo Info::URL.'/methods?module_type=admin&view=users'?>">Users</a></li>
              </ul>
            </li>
			<?php } ?>
            </ul>
          </li>
	  <?php } ?>
		  </ul>
      </div>
    </div>
    <!-- /sidebar menu -->

    <!-- /menu footer buttons -->
    <div class="sidebar-footer hidden-small">
      <a data-toggle="tooltip" data-placement="top" title="Settings">
        <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
      </a>
      <a data-toggle="tooltip" data-placement="top" title="FullScreen">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
      </a>
      <a data-toggle="tooltip" data-placement="top" title="Lock">
        <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
      </a>
      <a href="<?php echo Info::URL.'/logout.php'?>" data-toggle="tooltip" data-placement="top" title="Logout">
        <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
      </a>
    </div>
    <!-- /menu footer buttons -->
  </div>
</div>

<!-- top navigation -->
<div class="top_nav">

  <h2 class="pageTitle"><?php echo sprintf('%s <span class="subTitle">|| %s</span>', Info::value('systems')['name'], Info::value('systems')['title'])?></h2>
  <h2 class="pageTitle"></h2>
  <div class="nav_menu">
    <nav>
      <div class="nav toggle">
        <a id="menu_toggle"><i class="fa fa-bars"></i></a>
      </div>

      <ul class="nav navbar-nav navbar-right">
        <li class="">
          <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            <div class="profile_pic top"<?php //if($sessionAvatar)echo ' style="background-image:url('.Info::URL.'/profiles/80x80_'.$sessionAvatar.')"'?>></div>
            <span class="nameUser"><?php echo $_SESSION["displayname"]?><span><?php echo $_SESSION["codebook"]["role"][$_SESSION["userrole"]]->meta_value?></span></span>
            <span class=" fa fa-angle-down"></span>
          </a>
          <ul class="dropdown-menu dropdown-usermenu pull-right">
            <li><a href="<?php echo Info::URL.'/methods?module_type=admin&view=users'?>"> Profile</a></li>
			<?php if($_SESSION["userrole"] < 3){ ?><li><a href="<?php echo Info::URL.'/methods?module_type=admin&view=settings&type=options'?>"> Settings</a></li><?php } ?>
            <li class="hide">
              <a href="javascript:;">
                <span class="badge bg-red pull-right">50%</span>
                <span>Settings</span>
              </a>
            </li>
            <li class="hide"><a href="javascript:;">Help</a></li>
            <li><a href="<?php echo Info::URL.'/logout.php'?>"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
          </ul>
        </li>
		<?php if(false){?>
        <li role="presentation" class="dropdown">
          <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-envelope"></i>
            <?php if($getUnreadMessage){ echo '<span class="badge bg-green">'.count($getUnreadMessage).'</span>';}?>
          </a>
          <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
            <?php
            if($getUnreadMessage){
              foreach($getUnreadMessage as $msgDetail){
                //$thisMessage = $getMessages->listings(['id'=>$msgDetail['message_id'],'parent_id'=>$msgDetail['message_id']],getTableFields('messages',['content','status']));
                $thisMessage = $getMessages->listings(['id'=>$msgDetail['message_id']],getTableFields('messages',['content','status']));
                $thisSender = $getUsers->listings(['id'=>$thisMessage[0]['user']],['firstname','lastname']);
                ?>
                <li>
                  <a>
                    <span class="image"><img src="images/img.jpg" alt="Profile Image" /></span>
                        <span>
                          <span><?php echo $thisSender[0]['firstname'].' '.$thisSender[0]['lastname']?></span>
                          <span class="time"><?php echo timeDateFormat($msgDetail['date'],'date')?></span>
                        </span>
                    <span class="message"><?php echo $thisMessage[0]['subject']?></span>
                  </a>
                </li>
              <?php } } ?>
            <li>
              <div class="text-center">
                <a href="<?php echo Info::URL?>/methods?module_type=admin&view=messages"><strong>See All Messages</strong><i class="fa fa-angle-right"></i></a>
              </div>
            </li>
          </ul>
        </li>
		<?php
		}
		
		$getNotifications = $methods->notificationList();
		if($getNotifications['total'] > 0){
		?>
        <li role="presentation" class="dropdown">
          <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-exclamation-triangle"></i>
            <span class="badge bg-orange"><?php echo $getNotifications['total'];?></span>
          </a>
          <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
            <?php echo $getNotifications['lists']; ?>
          </ul>
        </li>
		<?php } ?>

      </ul>
    </nav>
  </div>
</div>
<!-- /top navigation -->
