<?php if(!$error404){ ?>
<script src="<?php echo Info::URL?>/vendors/datatables.net/js/jquery.dataTables.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
<script src="<?php echo Info::URL?>/vendors/datatables.net-scroller/js/datatables.scroller.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/validator/validator.js"></script>

<script src="<?php echo Info::URL?>/vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js"></script>
<script src="<?php echo Info::URL?>/vendors/google-code-prettify/src/prettify.js"></script>
<script>
	function saveData(serialVal) { // SIGNATORY SETTINGS
		tokens = "";extraParam = "";
		$('.modal-footer button[type=button]#saveBtn').attr('disabled',true).text('Saving... Please wait!');
		<?php
		if($_SESSION["userrole"] <= 2){
			echo '
				if(serialVal == "createusers"){
					tokens = $("form[name="+serialVal+"] select[name=tokens]").val();extraParam = \'&tokens=\'+tokens;
				}
			';
		}
		?>
		postData = $("form[name="+serialVal+"]").serialize()+extraParam;
		//formData = JSON.stringify(postData);
		splitPostData = postData.split('&');
		postDataTable = splitPostData[1].split('='); // TABLE
		postDataMetaKey = splitPostData[3].split('='); // META_KEY
		//alert(postDataTable+" - "+postDataMetaKey);
		jQuery.ajax({
			url: "storage.php",
			data:postData,
			type: "POST",
			success:function(data){
				console.log(data);
				// $('.modal-footer #success').html('Data has been saved!').fadeIn(400).delay(3000).fadeOut(400);
				$('.modal-footer button[type=button]#saveBtn').attr('disabled',false).text('Update Data');
				if(data.theID > 0){
					$("form[name="+serialVal+"] input[name=theID]").val(data.theID);
				}
				if(data.result_type == "lists" && data.record_type == "create" && data.success > 0){
					switch(data.table){
						case "codebook":
							dataHTML = "table#table-"+postDataMetaKey[1]+" tbody#"+postDataMetaKey[1]+"Listings";
							$(dataHTML).append(data.html);
							var m = new Masonry($('.masonry-grid').get()[0], {itemSelector: ".masonry-grid .masonry-column"});
						break;
						default:
							dataHTML = "table#<?php echo $dataTableID;?> tbody";
							$(dataHTML).prepend(data.html);
						break;
					}
					
				}else{
					// NO LISTING APPEND
				}
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

			},
			error:function (data){
				console.log(data);
				new PNotify({
					title: 'Result: ERROR',
					text: 'Unable to process your request, please check and try again...',
					type: 'error',
					hide: true,
					styling: 'bootstrap3',
					delay: 3200,
					addclass:"notify-success"
				});
			}
		});
	}

	function saveOption(serialVal) { // CREATE CHARTS, JOURNAL ENTRY
		switch(serialVal){
			case "createcharts":
				paramForm = "saveCharts";
			break;
			case "createJournalEntry":
				paramForm = serialVal;
			break;
			default:
				paramForm = "saveRecords";
			break;
		}
		$('.modal-footer button[type=button]#saveBtn').attr('disabled',true).text('Saving... Please wait!');
		extraParam = '&form='+paramForm;
		postData = $("form#"+serialVal).serialize()+extraParam;
		//console.log(postData);
		jQuery.ajax({
			url: "storage.php",
			data:postData,
			type: "POST",
			success:function(data){
				console.log(data);
				if(data.success > 0){
					$.each(data.values, function( index, value ) {
						//alert(index+" - "+value);
						$("td[meta-alias='"+data.meta+"["+data.dataID+"]:"+index+"']").html(value);
					});
					if(data.record_type == "create"){
						$("#"+serialVal+" #theID").val(data.dataID);
					}
					switch(serialVal){
						case "createJournalEntry":
							$("button[name=submitRecords]").text("UPDATE ENTRY");
						break;
						default:
							$('.modal-footer #success').html('Data has been saved!').fadeIn(400).delay(3000).fadeOut(400);
							$('.modal-footer button[type=button]#saveBtn').attr('disabled',false).text('Update Data');
						break;
					}
					
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
					
					
				}
				//$(dataHTML).html(data);
			},
			error:function (data){alert("Unable to process your request, please check and try again...");console.log(data);}
		});
	}
	
	function toggleValue(val,toggleValues) {
		return toggleValues[val];
	}
	
	function autoSave(me){
		proceed = true;
        inputID = $(me).attr('id');

		switch(inputID){
			case "valueBtn":
				toggleValues = { 1: 2, 2: 1 };
				inputValue = me.value;
				getValue = toggleValue(inputValue,toggleValues);
				inputValue = getValue;
				theParams = $(me).attr('params');
				getParams = JSON.parse(theParams);
				getParamID = getParams['id'];
				$('tr#'+getParamID+' td.payment_form span').text(inputValue);
				//alert('tr#'+getParamID+' td.payment_form span');
			break;
			case "statusBtn":
				inputValue = me.value;
				getValue = inputValue;
				getValue ^= 1;
				inputValue = getValue;
			break;
			case "deleteBtn":
				proceed = confirm("Are you sure you want to delete? Click OK to confirm...");
				metaName = $(me).attr('meta');
				inputValue = me.value;
			break;
			default:
				$("html").addClass("loading");
				inputValue = me.value;
			break;
		}
		
		if(proceed){
			inputAction = 'autoSave';
			inputName = me.name;
			theMeta = $(me).attr('params');//'{"action":"updateMeta","table":"codemeta","id":"1"}';
			inputTable = me.title;
			isCompute = $(me).attr('compute');
			formData = JSON.parse(theMeta);
			formData['value'] = inputValue;

			jQuery.ajax({
				url: "storage.php",
				data:formData,
				type: "POST",
				success:function(data){
					//alert(data.table+' - '+data.element+' - '+data.value);
					console.log(data);
					if(inputID == "deleteBtn"){
						$('table tbody#'+metaName+'Listings tr#meta_'+data.id).html('');
						var m = new Masonry($('.masonry-grid').get()[0], {itemSelector: ".masonry-grid .masonry-column"});
					}else{ // DEFAULT PROCESS
						setTimeout(function() {
							if(inputID != "statusBtn"){
								if(isCompute){
									computeTotal(isCompute);
								}
								if(formData.id == ''){
									$(me).attr("meta",data);
								}
								// AUTO ENABLE SUBMIT BUTTON
								checkVerify = $(me).attr('verify');
								if(checkVerify){
									checkFieldElements(checkVerify);
								}
							}else{
								//$('#alert').html(data.alert).fadeIn(400).delay(5000).fadeOut(400);
							}
							$("html").removeClass("loading");
							// AUTO ENABLE SUBMIT BUTTON
						}, 600);
					}
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
				},
				error:function (){}
			});
		}

    }

	function getCheckBoxValues(){
		thisAccess=comma='';
		$('input[type=checkbox]:checked').each(function() {
			theAccess = $(this).val();
			thisAccess = thisAccess + comma + theAccess;
			comma = ',';
		});
		return thisAccess;
	}

	function createOption(serialVal) {
		extraParam = '&user=<?php echo $_SESSION['userID']?>';
		//formData['user'] = <?php echo $_SESSION['userID']?>;
		//if(serialVal=='createUsers'){extraParam += "&accessRights="+getCheckBoxValues();}
		if(serialVal=='createExtras'){extraParam += "&score="+$('button[type=button]#saveBtn').attr('score');}
		$('.modal-footer button[type=button]#saveBtn').attr('disabled',true).text('Saving... Please wait!');
		// $.each(fieldsArray, function( index ) {
		// theValue += comma + index + '="' + $('input[name='+index+theID+']').val()+'"';
		// comma = ',';
		// });
		postData = $("form[name="+serialVal+"]").serialize()+extraParam;
		//alert(postData);
		switch(serialVal){
			case 'optionForm': case 'customReservations': case 'extraPayments':
			jQuery.ajax({
				url: "storage.php",
				data:postData,
				type: "POST",
				success:function(data){
				},
				error:function (){}
			});
			break;
			default: //update staffs details
				//alert($("form#"+serialVal).serialize());
				switch(serialVal){
					case 'createusers': // GET USER TOKENS ARRAY
						tokens = comma = '';
						$('#tokenBox .token_list .inputCheckBox.checked > input').each(function() {
							theValue = $(this).val();
							tokens += comma + theValue;
							comma = ',';
						});
						extraParam += '&tokens='+tokens;
						//alert(extraParam);
						break;
					case 'createmessage':
						recipient = $("form#"+serialVal+" select[name=recipient]").val();
						content = $('form#'+serialVal+' #editor').html();
						extraParam += '&content='+content+'&recipient='+recipient;
						break;
				}
				formData = $("form#"+serialVal).serialize()+extraParam;
				//alert(formData);
				jQuery.ajax({
					url: "storage.php",
					data:formData,
					type: "POST",
					success:function(data){
						//$('.modal-footer #success').html(data.id).fadeIn(400);
						alert(data);
						<?php if($view != 'posts'){ ?>
						if(data.id){//data.success
							$('.modal-footer #success').html('Data has been saved!').fadeIn(400).delay(3000).fadeOut(400);
							switch(serialVal){
								case 'createclients':
									colArray = ["id","memID","memType","firstname","lastname","address","email","contact"];
									break;
								case 'createpath':
									colArray = ["id","type","name","alias","groups","description"];
									break;
								case 'createmessage':
									colArray = '';
									clearMsgBox();
									//alert(data.id);colArray = ["id","username","firstname","lastname","position","description","role","unit"];
									break;
								case 'createusers':
									colArray = ["id","username","firstname","lastname","position","description","role","unit"];
									break;
								case 'createcodemeta':
									colArray = ["id","meta_key","code_name","code_meta"];
									break;
								case 'createtokens': case 'creategroups': case 'createprocedure': case 'createactivity': case 'createworkflow':
									colArray = ["id","name","alias","description"];
								break;
								case 'createfields':
									colArray = ["id","field_type","name","alias","description"];
									break;
								case 'createRooms':
									$('form#'+serialVal+' #price_set').val(data.price_set);
									colArray = ["number","name","this_type","this_bed_bath","this_bed_type","price"];
									$(".customBox input[name=roomID]").val(data.id);
									break;
								case 'createGuests':
									colArray = ["this_name","this_gender","this_contact","this_address"];
									break;
								case 'createUsers':
									colArray = ["this_user","this_name","this_role","this_details","this_phone_email","this_remarks"];
									//alert(data.test);
									break;
								case 'createReservations':
									colArray = ["this_name","this_status","this_dateIn","this_dateOut","this_bed_bath","this_remarks"];
									//alert($('form#'+serialVal+' #status').val());
									$('form#optionForm input[name=optionID],form#customReservations input[name=metaID],form#extraPayments input[name=metaID],input[name=reservationsID]').val(data.id);
									createOption('insertGuest');
									createOption('customReservations');
									createOption('extraPayments');
									$('.x_panel').removeClass('lock');
									if(data.isData=='create'){ //TO DISABLE SERVICE TYPE UPON CREATE RESERVATION
										roomType = $('#createReservations select#service_type').val();
										optionAttr = "[value='6']";
										if(roomType>1){optionAttr = ":not([value='6'])";}
										$('select#room_type').find("option"+optionAttr).attr('disabled',true);
									}

									break;
								case 'saveOptions':
									//alert($("form#"+serialVal).serialize());
									break;
							}
							if(data.isData=='update'){
								$.each(data, function( index, value ) {
									if($.inArray(index, colArray) > -1)$('td#row_'+index+'_'+data.id+' div').html(value);
									//alert( index + ": " + value ); //(> -1) if retain same array
								});
							}else{
								$('table.'+data.dataTableID+' tbody').prepend(data.thisListings);
							}
							//$('.modal-footer #success').html(data.success).fadeIn(400).delay(3000).fadeOut(400);
						}
						<?php
						}elseif($view == 'posts' && $alias == 'loan_application'){ // LOAN APPLICATION
							echo "
								$('input[name=memID]').val(data.memID);
								$('input[name=memType]').val(data.memType);
								$('input[name=contact]').val(data.contact);
								if(serialVal == 'createExtras'){
									switch(data.meta_key){
										case 'willingness':
											$('[name=members_credit_rating_willingness_pay]').val(data.score);
											scoreAdjectival = $('[name=members_credit_rating_willingness_pay] option[value='+data.score+']').text();
											$('[name=members_credit_rating_willingness_pay] + .select2-container .select2-selection__rendered').text(scoreAdjectival);
										break;
										case 'ability':
											dataScore = parseFloat(data.score);
											$('[name=members_credit_rating_ability_pay]').val(dataScore.toFixed(2));
											setLoanGranted(); computeSummary('compute_summary');
										break;
										case 'collateral':
											dataScore = parseFloat(data.score);
											$('[name=members_credit_rating_collateral_pay]').val(dataScore.toFixed(2));
										break;
									}
								}
								if(serialVal == 'createclients' && data.id > 0){
									$('select[name=loan_details_client_id]').prepend('<option selected alias=\"'+data.memID+'\" value=\"'+data.id+'\">'+data.firstname+' '+data.lastname+'</option>');
									$('button#addMember').val(data.id);
								}
							";
						} // $view != 'posts'
						?>
						//$('#success').html(data.score);

						$('form#'+serialVal+' #theID').val(data.id);
						$('form#'+serialVal+' #data').val(data.isData);
						$('.modal-footer button[type=button]#saveBtn').attr('disabled',false).text('Update Data');
					},
					error:function (){}
				});
				break;
			case 'createStaff': case 'insertGuest'://update staffs details //case 'saveOptions':
			jQuery.ajax({
				url: "storage.php",
				data:$("form#"+serialVal).serialize()+extraParam,
				type: "POST",
				success:function(data){
					if(serialVal=='insertGuest'){
						$("form#"+serialVal+" input[name=theID],#createReservations input[name=guestID],#optionForm input[name=optionValue]").val(data.success);
						dataAction = $("#createReservations input[name=data]").val();dataID = $("#createReservations input[name=theID]").val();
						//if(dataAction=='create'){}
						//alert(data.sessionUserID);
						createOption('optionForm');
						$('td#row_this_name_'+dataID).html(data.this_name);
						//alert($('form#createReservations input#theID').val());
						//$('<input>').attr({type: 'hidden',id: 'guestID',name: 'guestID', value: data.success}).appendTo('form#optionForm');
					}else{
						if(data.success){
							$("form#"+serialVal+" input[name=staffID]").val(data.success);
							$('.modal-footer button[type=button]#saveBtn').attr('disabled',false).text('Update Data').val();
						}
					}
					if(serialVal=='saveOptions'){
						meta_key = data.meta_key;
						dataHTML = 'table#optionSettings tbody#'+meta_key+'.'+serialVal;
						$(dataHTML).append(data.optionList);
						$('form#'+serialVal+' input[type=text]').each(function() {
							$(this).val('');
						});
						$('form#'+serialVal+' button#saveLogs').addClass('btnLock');
					}

				},
				error:function (){}
			});
			break;
			<?php if($thisType=='payroll'){?>
			case 'addLogs': case 'addAdjustments':
			$("#"+serialVal+" #saveLogs").html('Wait!');
			routeSet='';
			recordID=$("#"+serialVal+" #recordID").val();
			staffID=$("#"+serialVal+" #staffID").val();
			theDate=$("#"+serialVal+" #theDate").val();
			type=$("#"+serialVal+" #type").val();
			inOut=$("#"+serialVal+" #inOut").val();
			if(serialVal=='addAdjustments'){
				routeSet=$("#"+serialVal+" #routeSet").val();
				if(type>6){
					inOut=$("#"+serialVal+" #adjustmentRoute #inOut").val();
					if(!inOut){inOut=1;}
				}
			}
			//alert(recordID+' = '+staffID+' = '+inOut+' = '+routeSet);
			formData = {'action':serialVal,'recordID':recordID,'staffID':staffID,'theDate':theDate,'inOut':inOut,'type':type,'routeSet':routeSet};
			jQuery.ajax({
				url: "storage.php",
				data:formData,
				type: "POST",
				success:function(data){
					$("#"+serialVal+" #saveLogs").html('Save');
					if(!data.isExist)$("table#<?php echo $dataTableID;?> tbody").prepend(data.logList);
					//$(dataHTML).html(data);
					//alert(dataID+" | "+data.staffID+" | "+data.type+" | "+data.dateIn+" | "+data.dateOut);
				},
				error:function (){}
			});
			break;
			case 'payrollForm': //create update payrolls
				jQuery.ajax({
					url: "storage.php",
					data:$("form#"+serialVal).serialize()+extraParam,
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
			<?php } ?>
		}
	}

	function metaUpdate(me,meta_key){
		proceed=true;inputName=me.name;
		termID = me.value;
		action=me.id;
		thisButton='button[name='+inputName+']';
		if(meta_key!='reservations_meta'){
			$(thisButton).attr('readonly',true);
		}else{
			$(thisButton).addClass('btnLock');
		}
		if(action=='deleteMeta'){
			proceed=confirm("Are you sure you want to delete? Click OK to confirm...");
		}
		//alert($("form#"+meta_key+termID+" input#meta_value").val());
		if(proceed){
			extraParam = "&action="+action+"&termID="+termID+"&meta_key="+meta_key+"&sessionUserID=<?php echo $_SESSION['userID']?>";
			data=$("form#"+meta_key+termID).serialize()+extraParam;
			//alert("form#"+meta_key+termID);
			//manual=$("form#"+meta_key+" #metaOption"+termID+" input[name=meta_value"+termID+"]").val();
			jQuery.ajax({
				url: "storage.php",
				data:data,
				type: "POST",
				success:function(data){
					//alert(meta_key+" | "+termID+" | "+data.success+" | "+action+" | "+data.action);
					switch(meta_key){
						case 'reservations_meta':
							metaForm = 'form#'+meta_key+termID;
							if(data.actionData=='create'){

								$(metaForm+' input#actionData').val('update').attr('name','actionData'+data.success);
								$(metaForm+' button#reservationsMeta').val(data.success).attr('name','reservationsMeta'+data.success);
								$(metaForm+' select#room').attr('name','room'+data.success);
								$(metaForm+' input#roomRate').attr('name','roomRate'+data.success);
								$(metaForm+' input#adult').val(data.adultRate).attr('name','adult'+data.success);
								$(metaForm+' input#child').val(data.childRate).attr('name','child'+data.success);
								roomType = $('#createReservations select#service_type').val();
								if(roomType>1){
									$(metaForm+' input[type=number]#adultQty').attr("disabled",false).attr('name','adultQty'+data.success).attr("onchange","toBlur('reservationsMeta',"+data.success+")");
									$(metaForm+' input[type=number]#childQty').attr("disabled",false).attr('name','childQty'+data.success).attr("onchange","toBlur('reservationsMeta',"+data.success+")");
								}
								//computeAccountSummary();
								$(metaForm).attr('id',meta_key+data.success).attr('name',meta_key+data.success).attr('metaid',data.success).addClass("reservations_meta");
							}
							computeAccountSummary();

							//$(metaForm+' button[name=reservationsMeta'+termID+']').attr('readonly',false);
							break;
						case 'room_price':
							if(data.actionData=='create'){
								metaForm = 'form#'+meta_key+termID;
								$(metaForm+' input#actionData').val('update').attr('name','actionData'+data.success);
								$(metaForm+' button#roomRates').val(data.success).attr('name','roomRates'+data.success);
								$(metaForm+' input#priceTitle').attr('name','priceTitle'+data.success);
								$(metaForm+' input#roomPrice').attr('name','roomPrice'+data.success);
								$(metaForm+' input#roomExtra').attr('name','roomExtra'+data.success);
								$(metaForm).attr('id',meta_key+data.success).attr('name',meta_key+data.success);
								$(thisButton).addClass('btnLock');
							}
							break;
						default:
							if(data.success)$('button[name=saveMeta'+termID+']').addClass('btnLock');
							if(data.success&&data.action=='deleteMeta')$('#list'+data.success).fadeOut(400);
							break;
					}
					//$(thisButton).attr('disabled',false);

				},
				error:function (){}
			});
		}
	}
	
	function getPopup(me,theTable,action){
		inputID = me.id;
		theID = me.value;
		theType = me.title;
		theName = me.name;
		btnText='Create';
		dataTarget = $(me).attr("data-target");
		if(theID>0){btnText='Update ';}
		popupID = action+'_'+theID;
		$('.modal-footer button[type=button]#saveBtn').text(btnText+' Data');
		switch(action){
			default: //case "viewRooms": case 'viewGuests':
				modalBox = action;
				popupTitle=theTable+' Settings';
				formData = {'action':action,'theTable':theTable,'theID':theID,'theName':theName,'sessionUserID':'<?php echo $_SESSION['userID']?>'};
				tempHTML='<div class="modal-header iconUser"><h4 class="modal-title">Loading user data, please wait...</h4></div>';
				dataHTML = '.modal'+dataTarget+' .modal-content .modal-body';
				$('.modal'+dataTarget+' .modal-content .modal-header .modal-title').html(popupTitle);
				$(dataHTML).attr('id',popupID).addClass(theTable).html(tempHTML);
				//formData = JSON.parse(formData);
				//alert(formData['action']);
				break;

			case "viewextras":
				modalBox = 'viewextras';
				optionsBox = 'viewextras';
				popupTitle = 'Credit Investigation';
				inputElementID = document.getElementById(inputID);
				theAlias = inputElementID.getAttribute("alias");
				formData = {'action':action,'theTable':theTable,'meta_key':theType,'alias':theAlias,'dataID':theID,'sessionUserID':'<?php echo $_SESSION['userID']?>'};
				tempHTML='<div class="modal-header iconUser"><h4 class="modal-title">Loading user data, please wait...</h4></div>';
				dataHTML = '.modal'+dataTarget+' .modal-content .modal-body';
				$('.modal'+dataTarget+' .modal-content .modal-header .modal-title').html(popupTitle);
				//$(dataHTML).attr('id',popupID).addClass(theTable).html(tempHTML);
				break;

			case "optionSettings":
				modalBox = 'optionsMeta';popupTitle=$('#'+theID+' label').text();
				formData = {'action':action,'theTable':theTable,'meta_key':theID,'sessionUserID':'<?php echo $_SESSION['userID']?>'};
				tempHTML='<div class="modal-header iconUser"><h4 class="modal-title">Loading user data, please wait...</h4></div>';
				dataHTML = '.modal.'+modalBox+' .modal-content .modal-body';
				$('.modal.'+modalBox+' .modal-content .modal-header .modal-title').html(popupTitle);
				$('#'+theID).height('auto');
				$('#'+theID+' .collapse-link i').removeClass('fa-chevron-down').addClass('fa-chevron-up');

				$('#'+theID+' .x_content').fadeIn(200);
				$(dataHTML).attr('id',popupID).html(tempHTML);
				break;

		}
		$(dataHTML).html("");
		jQuery.ajax({
			url: "storage.php",
			data:formData,
			type: "POST",
			success:function(data){
				//$('.modal .modal-body form').append("<script src='<?php // echo URL;?>/vendors/jquery/dist/jquery.min.js'><\/script><script src='<?php // echo URL;?>/js/convertNumber.js'><\/script>");
				$(dataHTML).html(data);
				switch(action){
					case "viewextras":
						$('.modal.viewextras .modal-footer #saveBtn').attr("onclick","submitData('createExtras',this)").attr("name","submitOption");
						switch(theAlias){
							case 'willingness_pay':
								resultTitle = 'Overall/Attained Score: ';
							break;
							case 'ability_pay':
								resultTitle = 'Ability To Pay Amount: ';
								getScore = $(".modal-footer #attainedScore span#resultScore").text();
								abilityAmount = parseFloat(convertAmount(getScore));
								if(abilityAmount > 0){
									$("[name=members_credit_rating_ability_pay]").val(abilityAmount);
									$("span#ability_pay").text(convertCurrency(abilityAmount.toFixed(2)));
									//computeLoanSummary(this);
								}
							break;
							case 'collateral_pay':
								resultTitle = 'Total Value of Appraised Securities: ';
							break;
						}
						$('.modal-footer #attainedScore span#resultTitle').text(resultTitle);
						buttonID = inputElementID.getAttribute("id");
						if($("#"+buttonID).hasClass("readOnly")){
							$(".modal-footer button[type=button]").hide();
						}
					
						break;
					case 'viewcharts': case 'viewfields': case 'viewgroups': case 'viewtokens': case 'viewpath': case 'viewprocedure': case 'viewclients': case 'viewactivity': case 'viewworkflow':
						optionAction = action.replace("view", "create");
						//alert(optionAction);
						if(action=='viewUsers'){
							<?php //if($_SESSION["userrole"]<2) echo "$('select[name=role]').attr('disabled',false);"?>
						}
						if(theID < 1){
							//$('.modal.'+modalBox+' .modal-footer #saveBtn').attr("name","popupBtnData").addClass("btnLock");
						}else{
							$('.modal.'+modalBox+' .modal-footer #saveBtn').removeClass("btnLock");
						}
						$('.modal.'+modalBox+' .modal-footer #saveBtn').attr("onclick","saveOption('"+optionAction+"')");
					break;
					default:
						//alert(dataHTML);
						optionAction = action.replace("view", "create");
						$('.modal'+dataTarget+' .modal-footer #saveBtn').attr("onclick","saveData('"+optionAction+"')").attr("name","createRecords");
						//$('.modal.'+modalBox+' .modal-footer #saveBtn').attr("onclick","createOption('"+optionAction+"')");
						//$('.modal.'+modalBox+' .modal-content .modal-footer').hide(); //to hide popup footer
						//$(dataHTML+' button#saveLogs').text('Add '+popupTitle);
						break;
				}

			},
			error:function (){}
		});
	}
	
	function printWindow(selector, title) {
	   var divContents = $(selector).html();
	   var $cssLink = $('link');
	   var printWindow = window.open('', '', 'height=' + window.outerHeight * 1 + ', width=' + window.outerWidth  * 1);
	   printWindow.document.write('<html><head><h2><b><title>' + title + '</title></b></h2>');
	   for(var i = 0; i<$cssLink.length; i++) {
		printWindow.document.write($cssLink[i].outerHTML);
	   }
	   printWindow.document.write('</head><body><div class="printElement"><h4 class="uppercase">St. Alphonsus Catholic School Employees Multi-Purpose Cooperative</h4><h3 class="capitalize">'+title+'</h3>');
	   printWindow.document.write(divContents);
	   printWindow.document.write('</div></body></html>');
	   printWindow.document.close();
	   printWindow.onload = function () {
				printWindow.focus();
				setTimeout( function () {
					printWindow.print();
					printWindow.close();
				}, 100);  
			}
	}
	
	function windowLocation(me){ // GO TO CERTAIN PHP FILE
		parameters = "";
		path = $(me).attr("path");
		file = $(me).attr("file");
		params = $(me).attr("params");
		if(params != ""){
			parameters = "?"+params;
		}
		window.location.href = "<?php echo Info::URL?>/"+path+file+parameters;
	}
	
	function loadStorage(me){ // onclick="loadStorage(this);" action="getElementData" block=".modal.viewLogs .modal-content .modal-body" meta="post_logs" value=""
		params = "";
		proceed = false;
		//me.id = $("[name="+me+"]").attr('id');
		thisAttr = true;
		// fox = (false) ? "yeah" : "whatever";
		// alert(fox);
		
		if(thisAttr){
			inputID = me.id;
			name = me.name;
			val = me.value;
			action = $(me).attr("action");
			meta = $(me).attr("meta");
			loadBlock = $(me).attr("block");
			if(meta == "recipient_listings"){ // CASH DISBURSEMENT/RECEIPT
				cash_trans = $(me).attr("cash_trans");
				params = "&cash_trans="+cash_trans;
			}
		}else{
			inputID = $("[name="+me+"]").attr('id');
			name = $("[name="+me+"]").attr('name');
			val = $("[name="+me+"] option[selected]").val();
			action = $(me).attr("action");
			meta = $(me).attr("meta");
			loadBlock = $(me).attr("block");
		}
		switch(meta){
			case "members_list":
				proceed = true;
				name = "comaker_id";
				group = "loan_summary";
				param_value = $('[name=loan_summary_comaker_id]').val();
				params = "&group="+group+"&name="+name+"&value="+param_value;	
				$(loadBlock).html("<span class='placeholder_loading'>Please wait...</span>");
			break;
			case "industry_section":
				proceed = true;
				titleValue = $("[name="+name+"] option[value="+val+"]").text();
				titleValue = titleValue.replace(" ", ":");
				params = "&title="+encodeURIComponent(titleValue);
			break;
			case "journal_entry":
				proceed = true;
				prevLoabBlock = loadBlock;
				loadBlock = loadBlock+" tr#"+val;
				$(loadBlock).addClass(meta);
			break;
			case "loans_listings": case "member_listings": case "savings_listings": case "recipient_listings": // LOANS PAYMENT, SAVINGS, DEPOSITS, CASH TRANSACTION
				fieldName = "members_name";
				if(meta == "recipient_listings"){
					fieldName = "recipients_name";
				}
				keyword = $("[name="+fieldName+"]").val();
				if(meta == "recipient_listings"){ // recipient_listings
					proceed = true;
					if(keyword != ""){
						if(keyword.indexOf(' ') != -1) {
						   segments = keyword.split(' ');
						   keyword = segments[0];
						}
					}
				}else{
					if(keyword != ""){
						proceed = true;
						if(keyword.indexOf(',') != -1) {
						   segments = keyword.split(',');
						   keyword = segments[0];
						}
					}
				}
				
				params = params+"&keyword="+keyword;
				$(loadBlock).html("<span class='placeholder_loading'>Please wait...</span>");
			break;
			default:
				proceed = true;
				$(loadBlock).html("<span class='placeholder_loading'>Please wait...</span>");
			break;
		}
		console.log(loadBlock);
		if(proceed){
			$(loadBlock).load( "storage.php?action="+action+"&meta="+meta+"&value="+val+params);
			switch(meta){
				case "members_list":
					$(loadBlock).attr("onclick","");
				break;
				case "industry_section":
					group = $(me).attr("group");
					$("input#"+inputID).val(val);
				break;
				case "journal_entry":
					newVal = parseFloat(val) + 1;
					$(me).val(newVal);
					$("<tr/>").attr('id',newVal).appendTo(prevLoabBlock);
					$("input[name=entry]").val(val);
					webshims.setOptions('forms-ext', {replaceUI: 'auto', types: 'number'});webshims.polyfill('forms forms-ext');
				break;
			} // END SWITCH
		}else{
			$(loadBlock).html("<span class='placeholder_loading'>Please fill-in keywords to show results and try again...</span>");
		} // END IF PROCEED
	} // END FUNCTION LOADSTORAGE

	function getSelectElements(me,action,table,field){
		//alert(action);
		thisID=me.id;
		rowID=me.title;
		value=me.value;
		thisName=me.name;
		thisBox='box_'+thisName;
		switch(action){
			case 'loadBox':
				reservationsID = $("input[name=reservationsID]").val();
				$('div#'+thisBox).load( "storage.php?action="+action+"&reservationsID="+reservationsID+"&rowID="+rowID+"&field="+field+"&value="+value);
				break;
			case 'loadGuestInfo':
				$('div#'+thisBox).load( "storage.php?action="+action+"&table="+table+"&field="+field+"&value="+value);
				break;
			default:
				$('div#'+thisBox).html('<label for="'+thisBox+'">Please wait...</label>');
				$('div#'+thisBox).load( "storage.php?action="+action+"&table="+table+"&field="+field+"&value="+value);
				break;

		}
		if(action=='loadBox'){

		}else{

		}
	}

	function getLink(theID,linkType){
		switch(linkType){
			case 'payroll':
				window.location.href='<?php echo Info::URL;?>/methods?module_type=payroll&action=create&id='+theID;
				break;
		}
	}

	function toBlur(btnName,termID){
		if(!termID){
			unlock=true;
			$('form#'+btnName+' input[type=text]').each(function() {
				if(!$(this).val()){
					unlock=false;
					return false;
				}
			});
			if(unlock){$('button[name=saveLogs]').removeClass('btnLock');}else{$('button[name=saveLogs]').addClass('btnLock');}
		}
		$('button[name='+btnName+termID+']').removeClass('btnLock');
	}

	$("input.toBlur").on('keypress',function () {
		termID = $(this).attr('termID');
		btnName = $(this).attr('btnBlur');
		toBlur(btnName,termID);
	});

	$("input#statusBtnxxx").on('change',function () {
		toConfirm = true;
		theName = $(this).attr('name');
		theMeta = $(this).attr('params');
		//objMeta = jQuery.parseJSON(theMeta);
		theValue = $(this).val();
		theValue ^= 1;
		$(this).val(theValue);
		// if(theValue<1){
		// toConfirm = confirm('Are you sure you want to disable data?');
		// }
		if(toConfirm){
			theValue = 'status='+theValue;
			updateMeta(theMeta,theValue);
		}
	});

	// $("button#saveMeta").on('click',function () {
	// theID = $(this).val();
	// //selectArray = { meta_value:$('input[name=meta_value'+theID']').val(),meta_option:$('input[name=meta_option'+theID']').val()};
	// selectArray = { meta_value:'ddd',meta_option:'aaa'};
	// $.each(selectArray, function( index, value ) {
	// alert( index + ": " + value );

	// //alert( index + ": " + value ); //(> -1) if retain same array
	// });
	// });

	function updateMe(me){
		comma = theValue = '';
		theID = me.value;
		theMeta = me.title;
		inputName = me.name;
		fieldsArray = { meta_value,meta_option};
		$.each(fieldsArray, function( index ) {
			theValue += comma + index + '="' + $('input[name='+index+theID+']').val()+'"';
			comma = ',';
		});
		//alert(theMeta+" | "+theValue);
		updateMeta(theMeta,theValue);
		$('button[name='+inputName+']').addClass('btnLock');
	}

	//data=$("form#"+meta_key+termID).serialize()
	
	function processMeta(action,id){
		proceed = true;
		loaderClass = "html";
		$(loaderClass).addClass("loading");
		formData = {'action':action,'id':id};
		//formData = JSON.parse(theMeta);
		if(action == "memberMasterlist"){
			proceed = confirm("Exporting and updating your members data is irreversible. Continue?");
		}
		if(proceed){
			jQuery.ajax({
				url: "storage.php",
				data:formData,
				type: "POST",
				success:function(data){
					console.log(data);
					$(loaderClass).removeClass("loading");
					//$('#alert').html(data.alert).fadeIn(400).delay(5000).fadeOut(400);
					
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
				},
				error:function (){}
			});
		}else{
			$(loaderClass).removeClass("loading");
			$('#alert').html("No data/records has been changed.").fadeIn(400).delay(5000).fadeOut(400);
		} // END PROCEED
	}

	function updateMeta(theMeta,theValue){
		//theMeta = {"action":"updateMeta","table":"codemeta","id":"1"}
		//theValue = 'alias="Amatz Fox",description="Quick Brown"';
		formData = JSON.parse(theMeta);
		formData['value'] = JSON.parse(theValue);
		//formData['value'] = theValue;
		jQuery.ajax({
			url: "storage.php",
			data:formData,
			type: "POST",
			success:function(data){
				//alert(data);
				//$(dataHTML).html(data);
			},
			error:function (){}
		});
	}
	
	cntSummary=0;
	$('tr.listFooter input#summaryID').each(function() {
		thisID = $(this).val();
		thisLate = computeTotal('table#methods_payroll #late'+thisID,'');
		thisDays = computeTotal('table#methods_payroll input[name=numDays'+thisID+']','');
		thisAdjustments = computeTotal('table#methods_payroll input[name=numAdjustment'+thisID+']','');
		thisLateAdjustments = computeTotal('table#methods_payroll input[name=lateAmount'+thisID+']','');
		thePerDay = $('input#perDay'+thisID).val();
		salaryDeduction = $('input#salaryDeduction'+thisID).val();
		//getLate= thePerDay / 8 / 60 * thisLate;
		//theLate = getLate.toFixed(2);
		theDeductions = parseFloat(salaryDeduction) + thisLateAdjustments;
		grossPay = thisDays * thePerDay + thisAdjustments - theDeductions;
		//$('#thisLates'+thisID).html('<div>'+thisLate+'</div><div class="small"> mins late</div>'); //SHOWS LATES SUMMARY
		$('#thisAdjmts'+thisID).html('<span>'+convertCurrency(thisAdjustments.toFixed(2))+'</span>');
		$('#thisDeductions'+thisID).html('<span>'+convertCurrency(theDeductions.toFixed(2))+'</span>');
		$('#thisGross'+thisID).html('<span class="bold">'+convertCurrency(grossPay.toFixed(2))+'</span>');
		$('#thisWorks'+thisID).html('<span>'+thisDays+'</span>');
		//alert(thisLate);
		cntSummary++;
	});

	function computeTotal(optionInputTable,action){
		var total = 0; adultValue=childValue=cnt=0;
		if(action=='time'){total =  new Date(); }
		$(optionInputTable).each(function() {
			cnt=cnt+1;
			switch(action) {
				case 'metaTerms':
					metaID = $(this).attr('metaID');
					metaArray = ["adult","child"];
					$.each(metaArray, function( index, value ) {
						if(value){
							metaRate = $('form#reservations_meta'+metaID+' input[name='+value+metaID+']').val();
							metaQty = $('form#reservations_meta'+metaID+' input[name='+value+'Qty'+metaID+']').val();
							metaValue = parseFloat(metaRate) * parseFloat(metaQty);
							total = total + metaValue;
							if(metaQty>0){
								switch(value){
									case 'adult': adultValue = adultValue + metaValue; break;
									case 'child': childValue = childValue + metaValue; break;
								}

							}
						}
					});
					$('#adultAmount .amountBox').html(convertCurrency(adultValue.toFixed(2)));
					$('#childAmount .amountBox').html(convertCurrency(childValue.toFixed(2)));
					$('#amountAdultChild .amountBox').html(convertCurrency(total.toFixed(2)));
					break;
				case 'time':
					//time = '';
					dateValue = new Date($(this).val());
					//total = timeStringToFloat(dateValue) + timeStringToFloat(total);
					//total =  total + tValue.getTime();
					//total.toLocaleString('en-GB').split(' ')[1];
					break;
				case 'selectOption': case 'class': case 'extraRate':
				//selectName = $(this).attr('name');
				if(action=='selectOption'){
					optionValue = $(this).find("option:selected").text();
				}else{
					if(action=='extraRate'){
						if(cnt<2){
							optionValue = $(this).find("option:selected").attr("class");
							floatValue = parseFloat(optionValue);
							total =  total + floatValue;
						}
					}else{
						optionValue = $(this).find("option:selected").attr(action);
					}
				}
				//if(total=='NaN'){total=0;}

				if(optionValue!=''&&action!='extraRate'){
					floatValue = parseFloat(optionValue);
					total =  total + floatValue;
				}
				//alert(selectName);
				//$('select#'+selectName+'Rate').val(floatValue);
				break;
				default:
					floatValue = parseFloat($(this).val());
					total =  total + floatValue;
					break;
			}
		});
		//if(currency){total = total.toFixed(2);}
		return total;
	}

	function convertAmount(val){
		if(val){
			//val = val.replace(',','');
			val = val.replace(/[`~!@#$%^&*()_|+\=?;:'",<>\{\}\[\]\\\/]/gi, '');
			//val = val.replace(/[`~!@#$%^&*()_|+\-=?;:'",<>\{\}\[\]\\\/]/gi, '');
		}else{
			val = 0;
		}
		val = parseFloat(val);
		return val;
	}

	function convertCurrency(val){
		if(val){
			while (/(\d+)(\d{3})/.test(val.toString())){
				val = val.toString().replace(/(\d+)(\d{3})/, '$1'+','+'$2');
			}
		}else{
			val = 0;
		}
		if(val=='NaN'){val=0;}
		//console.log(val);
		return val;
	}

	//$(":input").inputmask();

	// $('#datePeriod').daterangepicker({
		// singleDatePicker: false,
		// singleClasses: "datePeriod"
	// }, function(start, end, label) {
// //console.log(start.toISOString(), end.toISOString(), label);
	// });

	// $(".select2_single").select2({
		// placeholder: $('.select2_single').attr('placeholder'),
		// allowClear: false
	// });

	$('.select2_single').each(function() {
		$(this).select2({
			placeholder: $(this).attr('placeholder'),
			allowClear: false
		});
	});

	<?php if($isDataTable){	?>
	$(document).ready(function() {
		<?php /*?>var table = $('#<?php echo $dataTableID;?>').DataTable();
		var rows = table.rows( '.details' );
		$.each(rows[0], function( index, value ) {
			$('#<?php echo $dataTableID;?> tr:eq('+index+')').addClass( 'ready' );
		});<?php */?>
		
		var handleDataTableButtons = function() {
			if ($("#<?php echo $dataTableID;?>").length) {
				$("#<?php echo $dataTableID;?>").DataTable({
					<?php
					if($isMobile) echo '"aoColumnDefs": [{"aTargets": [2], "visible": false }],';
					if(isset($params['alias']) && $params['alias'] != ""){
						switch($params['alias']){
							case "loan_application":
								echo '"aoColumnDefs": [{"aTargets": [5], "className": "alignRight"},{"aTargets": [6], "className": "alignCenter"}],';
							break;
							case "clients":
								echo '"aoColumnDefs": [{"aTargets": [2], "className": "alignCenter"}],';
							break;
						}
					}else{
						switch($dataTableID){
							case "methods_reports-list_default":
								echo '"aoColumnDefs": [{"aTargets": [0,1,3,4,5,6], "className": "alignCenter"},{"aTargets": [7,8,9,10,11], "className": "alignRight"}],'; // PORTFOLIO(PAR) REPORTS
							break;
							case "methods_reports-list_portfolio":
								echo '"aoColumnDefs": [{"aTargets": [1,3,4], "className": "min-th alignCenter" },{"aTargets": [0,5], "className": "alignCenter"},{"aTargets": [6,7,8,9,10,11,12,13,14], "className": "alignRight"}],'; // PORTFOLIO(PAR) REPORTS
							break;
							case "methods_reports-list_collections":
								echo '"aoColumnDefs": [{"aTargets": [0,1,4,5,6], "className": "alignCenter"},{"aTargets": [7,8,9,10], "className": "alignRight"}],'; // PORTFOLIO(PAR) REPORTS
							break;
							case "methods_reports-list_loan_ledger":
								echo '"aoColumnDefs": [{"aTargets": [0,1,4,5,6,7], "className": "alignCenter"},{"aTargets": [8,9,10,11,12], "className": "alignRight"}],'; // PORTFOLIO(PAR) REPORTS
							break;
							default:
								echo '"aoColumnDefs": [{"aTargets": [0], "className": "alignCenter"}],';
							break;
						}
					}
					?>
					dom: "Bfrtip",
					//"lengthMenu": [[-1], ["All"]],
					"lengthMenu": [[16, -1], [16, "All"]],
					"columnDefs": [ {
						"targets": 'no-sort',
						"orderable": false,
					} ],
					buttons: [
						{
							extend: "copy",
							className: "fa fa-clipboard"
						},
						{
							extend: "csv",
							className: "fa fa-table"
						},
						//{
						//  extend: "excel",
						//  className: "btn-sm"
						//},
						//{
						//extend: "pdfHtml5",
						//className: "btn-sm"
						// },
						{
							extend: "print",
							className: "fa fa-print"
						},
					],
					responsive: true
					<?php
					switch($theView){
						default:
							if(!$hasPopup)echo ',"order": [[ 0, "desc" ]]';
						break;
						case 6: // 6 [ADMIN LIST]
							echo ',"order": [[ 0, "desc" ]]';
						break;
					}

					?>
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
		
		

	}); // END DOCUMENT READY

	<?php
	} // ENDIF $isDataTable
		
	if($isFilterQuery){
		$comma=$jsFilters='';
		//foreach($filterArray as $jsFieldSelect){
		foreach($filterArray as $jsSelectName => $jsSelectValue) {
			$jsFilter = $comma.$jsSelectName;
			$jsFilter = ''.$jsFilter.':"'.$jsSelectValue.'"';
			$comma=',';
			$jsFilters = $jsFilters.$jsFilter;

		}
		?>

		selectArray = { <?php echo $jsFilters?> };
		$.each(selectArray, function( index, value ) {
			$('select[name=filter_'+index+']').select2({
				placeholder: value+'...',
				allowClear: true
			});
		});

		$('#filter_date_in').daterangepicker({
			singleDatePicker: false,
			singleClasses: "filterDateIn"
		}, function(start, end, label) {
			//console.log(start.toISOString(), end.toISOString(), label);
		});
	<?php } // END $isFilterQuery?>
	
$(window).scroll(function(){
	//var sticky = $('#top-bottom.topTitle, #content');
	var titleHeader1 = $('table.scroll thead tr:first-of-type');
	var titleHeader2 = $('table.scroll thead tr:last-of-type');
	//var sidebarSticky = $('.content-box-inside .sidebar');
	scroll = $(window).scrollTop();
	// if (scroll >= 85){
		// //sticky.addClass('fixed');
		// sidebarSticky.addClass('fixed').next().addClass('fixed').next().addClass('fixed');
	// } else {
		// sticky.removeClass('fixed');
		// sidebarSticky.removeClass('fixed').next().removeClass('fixed').next().removeClass('fixed');
	// }

	if (scroll >= 54){
		titleHeader1.addClass('fixed');
		titleHeader2.removeClass('hide');
	} else {
		titleHeader1.removeClass('fixed');
		titleHeader2.addClass('hide');
	}

});

</script>

<?php
	} // END !$error404
	
if($view == 'posts' && $alias == 'loan_application'){ // RESULT SCORE ON EXTRAS
	echo "<script>$('.modal.viewextras .modal-footer').prepend('<div id=\"attainedScore\" class=\"left mid bold\"><span id=\"resultTitle\" class=\"large\" ></span><span id=\"resultScore\" class=\"xlarge\">0.0</span></div>');</script>";
}
?>
