<!-- footer content -->

<footer>
	<div id="alert" class="blue bold"></div>
	<div class="pull-right">
		<?php echo sprintf('%s - %s %s © Copyright %s', $infoSystems['name'], $infoSystems['title'], $infoSystems['version'], $infoSystems['copyright'])?> | <a href="http://mantoolph.com" target="_blank">Management Tools Creation</a>. All Rights Reserved.
	</div>
</footer>
<script>
  $(window).ready(function() {
    $("html").removeClass("loading");
    $(".nav.child_menu > li > a:not(.parent)").on('click',function () {
      $("html").addClass("loading");
    });

  });
</script>
<!-- /footer content -->

<?php
echo Method::footerJS();
// DISPLAY IF HAS ERROR
$getError = error_get_last();
if((isset($getError) && count($getError) > 0) && $_SESSION['userrole'] < 2 && !array_search('32', $getError)){
?>
<div class="errorBox"><pre><?php print_r($getError)?></pre></div>
<?php } ?>

