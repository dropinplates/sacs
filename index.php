<?php
session_start();
include 'functions-methods.php';
$params = "";
$methods = new Method($params);
$getSystemInfo = $methods->Systems("info");
$infoSystems = Info::value('systems');
$thisAlert='';
if(count($_POST)>0) {
    $params = $_POST;
    $logInUser = $params["username"];
    $logInPass = $methods->getEncrypt($params["password"]);

	$stmtUsers = ['schema'=>Info::DB_SYSTEMS,'table'=>'users','arguments'=>['username'=>$logInUser,'password'=>$logInPass],'pdoFetch'=>PDO::FETCH_UNIQUE | PDO::FETCH_CLASS,'fields'=>['id','status','role']]; //,'option_name'=>$meta[$optionMeta]
	$getUsers = $methods->selectDB($stmtUsers);
	$userID = key($getUsers);
	$userValues = $getUsers[$userID];
    if($userValues){
        $logUserID = $userID;
        $statusLogUserID = $userValues->status;
        if($logUserID&&$statusLogUserID>=1) { //&& getEncrypt('check')
            $thisAlert = 'success';
            $_SESSION["userID"] = $logUserID;
            $_SESSION["userrole"] = (int)$userValues->role;
            $theIPaddress = $_SERVER['REMOTE_ADDR'];
            //add_log($_SESSION["userID"],'has been logged in','IP/SERVER: '.$theIPaddress);
        }elseif($statusLogUserID<1){ //!getEncrypt('check')
            $thisAlert = "<div class='bgOrange'>Your access has been disabled!<br />Please contact system administrator.</div>";
        }else{
            $thisAlert = "<div class='bgRed'>Invalid Username or Password!</div>";
        }
    }
}
if(isset($_SESSION["userID"])) {
    //include "functions-methods.php";
	$methods->sessionAuth($_SESSION["userID"]);
    $params = $_SESSION;
    //$methods = new Method($params);
    if(true): //$sessionUserRole>2
        header("Location:".Info::URL."/methods?module_type=reports&view=list_members");
        // $logsMeta='user';
        // $arrayOptions=array('action'=>'login');
        // $logsOptions = setMetaArray($arrayOptions,'');
        //$logsID = insertMetaValue("logs","(user,meta,options,ipaddress,date)","('".$_SESSION["userID"]."','".$logsMeta."','".$logsOptions."','".getIpAddress()."','".getTime('datetime')."')");
    else:
        header("Location:".Info::URL."/methods?module_type=admin&view=dashboard");
    endif;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?php echo Info::URL?>/favicon.png">
    <title><?php echo $getSystemInfo['companyname'];?></title>
    <?php echo $methods->headerJS();?>
    <!-- Bootstrap -->
</head>

<body class="login">

<div class="login_wrapper">
    <div class="navbar nav_title">
        <a href="index.html" class="site_title"><span class="iconLogo"><img src="images/icon-sacs.png" class="mCS_img_loaded"></span><span class="hide">ProjectZero</span><span><sub class="small hide">ZERO32 iNTERACTIVE</sub></span></a>
    </div>
    <div class="animate form login_form">
        <section class="login_content">
            <form method="post" action="<?php echo Info::URL.'/index.php'?>" class="">
                <h1>Employees Multi-Purpose Cooperative</h1>
                <div>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Username" required />
                </div>
                <div>
                    <input type="password" class="form-control" placeholder="Password" name="password" id="password" required />
                </div>
                <div>
                    <input type="submit" class="btn btn-default submit" value="Log in" />
                    <a class="reset_pass" href="#">Lost your password?</a>
                </div>

                <div class="clearfix"></div>
                <div class="query"><?php echo $thisAlert?></div>
                <div class="separator">
                    <p class="change_link">New to site?
                        <a href="#signup" class="to_register"> Create Account </a>
                    </p>
                </div>
            </form>
        </section>
    </div>

    <div id="register" class="animate form registration_form">
        <section class="login_content">
            <form>
                <h1>Create Account</h1>
                <div>
                    <input type="text" class="form-control" placeholder="Username" required />
                </div>
                <div>
                    <input type="email" class="form-control" placeholder="Email" required />
                </div>
                <div>
                    <input type="password" class="form-control" placeholder="Password" required />
                </div>
                <div>
                    <a class="btn btn-default submit" href="index.html">Submit</a>
                </div>

                <div class="clearfix"></div>

                <div class="separator">
                    <p class="change_link">Already a member ?
                        <a href="#signin" class="to_register"> Log in </a>
                    </p>

                </div>
            </form>
        </section>
    </div>
    <div class="developerAds">
		<?php echo sprintf('<p>%s - %s | %s © Copyright %s. All Rights Reserved. Privacy and Terms</p>', $infoSystems['name'], $infoSystems['title'], $infoSystems['owner_name'], $infoSystems['copyright'])?>
    </div>
</div>
</body>
</html>