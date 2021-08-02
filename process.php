<?php require 'header.php';?>
        <!-- page content -->
        <div class="right_col" role="main">

          <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Account Information <small>Client/Member information sheet</small></h2>
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
    <form class="form-horizontal form-label-left">
    	<div class="form-group">
        	<div class="col-md-1 col-sm-1 col-xs-10 form-input has-feedback">
                <select class="namePrefix form-control" tabindex="-1">
                    <option></option>
                    <option value="thisValue">Mr.</option>
                    <option value="thisValue">Mrs.</option>
                    <option value="thisValue">Ms.</option>
                </select>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-12 form-input has-feedback">
            <input type="text" class="form-control" id="inputSuccess3" placeholder="Last Name">
            <span class="fa fa-user form-control-feedback right" aria-hidden="true"></span>
            </div>
            
            <div class="col-md-4 col-sm-4 col-xs-12 form-input has-feedback">
            <input type="text" class="form-control" id="inputSuccess3" placeholder="First Name">
            <span class="fa fa-user form-control-feedback right" aria-hidden="true"></span>

           </div>
            
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
            <input type="text" class="form-control" id="inputSuccess3" placeholder="Middle Name">
            <span class="fa fa-user form-control-feedback right" aria-hidden="true"></span>
            </div>
        </div>
        <div class="ln_solid"></div>
        <div class="form-group">
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
            <input type="text" class="form-control" id="inputSuccess3" placeholder="Client ID">
            <span class="fa fa-user form-control-feedback right" aria-hidden="true"></span>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
            <input type="text" class="form-control" id="inputSuccess3" placeholder="Reference ID">
            <span class="fa fa-user form-control-feedback right" aria-hidden="true"></span>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
                <input type="text" class="form-control has-feedback-right" id="single_cal3" placeholder="Date Opened" aria-describedby="inputSuccess2Status3">
                <span class="fa fa-calendar form-control-feedback right" aria-hidden="true"></span>
                <span id="inputSuccess2Status3" class="sr-only">(success)</span>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
                <select class="department form-control" tabindex="-1">
                    <option></option>
                    <option value="thisValue">Head Office</option>
                    <option value="thisValue">Branch Office</option>
                    <option value="thisValue">Finance Department</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
            <input type="text" class="form-control" id="inputSuccess3" placeholder="Account Name">
            <span class="fa fa-user form-control-feedback right" aria-hidden="true"></span>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
                <select class="accountType form-control" tabindex="-1">
                    <option></option>
                    <option value="thisValue">Head Office</option>
                    <option value="thisValue">Branch Office</option>
                    <option value="thisValue">Finance Department</option>

               </select>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
                <select class="clientType form-control" tabindex="-1">
                    <option></option>
                    <option value="thisValue">Head Office</option>
                    <option value="thisValue">Branch Office</option>
                    <option value="thisValue">Finance Department</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
                <select class="clientStatus form-control" tabindex="-1">
                    <option></option>
                    <option value="thisValue">Head Office</option>
                    <option value="thisValue">Branch Office</option>
                    <option value="thisValue">Finance Department</option>
                </select>
            </div>
                
        </div>
        <h4 class="boxHeader">Personal Information</h4>
        <div class="form-group">
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
            <input type="text" class="form-control" id="inputSuccess3" placeholder="Gender">
            <span class="fa fa-user form-control-feedback right" aria-hidden="true"></span>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
                <input type="text" placeholder="Date of Birth (mm/dd/yyyy)" class="form-control" data-inputmask="'mask': '99/99/9999'">
                <span class="fa fa-user form-control-feedback right" aria-hidden="true"></span>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">

               <select class="civilStatus form-control" tabindex="-1">
                    <option></option>
                    <option value="thisValue">Head Office</option>
                    <option value="thisValue">Branch Office</option>
                    <option value="thisValue">Finance Department</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-3 col-xs-12 form-input has-feedback">
                <select class="nationality form-control" tabindex="-1">
                    <option></option>
                    <option value="thisValue">Head Office</option>
                    <option value="thisValue">Branch Office</option>
                    <option value="thisValue">Finance Department</option>
                </select>
            </div>
                
        </div>
        
    
    </form>
                  </div>
                </div>
              </div>

          </div>
          
        </div>
        <!-- /page content -->
      </div>
      <?php include 'footer.php';?>
    </div>
  </body>
</html>