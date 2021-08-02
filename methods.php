<?php
include 'header.php';
//include 'header-php.php';
?>
<script>
	//$(".right_col").addClass("loading");
	<?php
      $parentMenu = ($parentMenu) ? $parentMenu : $thisType;
      $subMenu = ($subMenu) ? $subMenu : $thisView;
      $currentPage = ($currentPage) ? $currentPage : "";
      $menuSettings = "
      $('#menu-{$parentMenu}').addClass('active');
      $('#menu-{$parentMenu} .nav.child_menu').attr('style','display:block');
      $('#menu-{$parentMenu} .nav.child_menu > #menu-{$subMenu}').addClass('active');
      $('#menu-{$parentMenu} .nav.child_menu > #menu-{$subMenu} > .nav.child_menu').attr('style','display:block');
      $('#menu-{$parentMenu} #menu-{$currentPage}').addClass('current-page');
      ";
	  if(!$currentPage){
		$currentPage = (isset($params['type']))? $params['type'] : "" ;
		$menuSettings .= "$('#menu-{$parentMenu} #menu-{$subMenu}-{$currentPage}').addClass('current-page');";
	  }
      echo $menuSettings;
    ?>
</script>
	<!-- page content -->
	<div class="right_col<?php ($dataTableID) ? print ' listings' : '' ?>" role="main" id="<?php echo $thisType.'_'.$view?>">
		<div class="row">
		<?php
		//var_dump($_SESSION["notifications"]); //$methods->test
		//echo $parentMenu." | ".$subMenu." | ".$currentPage;
			try{
				if($contentPage != "") include $contentPage;
				if($hasPopup) include 'popup-box.php';
			}catch(Exception $e){
				echo $e->getMessage();
			}
		?>
		</div>
	</div>
	<!-- /page content -->
</div>
<?php include 'footer.php';?>
</div>
<?php include 'methods-js.php';?>
  </body>
</html>
