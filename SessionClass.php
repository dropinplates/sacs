<?php

class Session{
	
	public $userInfo;
	
    public function createSession(){
        session_start();
    }

    public function setSession($userID){
		$usersFields = $methods->getTableFields(['table'=>'users','exclude'=>['user','date'],'schema'=>Info::DB_SYSTEMS]);
		$stmtUsers = ['schema'=>Info::DB_SYSTEMS,'table'=>'users','arguments'=>['username'=>$logInUser,'password'=>$logInPass],'pdoFetch'=>PDO::FETCH_KEY_PAIR,'fields'=>['id','username']]; //,'option_name'=>$meta[$optionMeta]
		$getUsers = $methods->selectDB($stmtUsers);
		//$this->userInfo = $userInfo;
		$infoKey = "";
		foreach($this->userInfo as $key => $value){
			if($key == 'id'){
				$infoKey = "userID";
			}else{
				$infoKey = $key;
			}
			 $_SESSION[$infoKey] = $value;
		}
       
		$_SESSION["tokens"] = explode(',',$_SESSION["tokens"]);
		
        //$_SESSION["roleValue"] = $userCodebookValue[0]['meta_value'];

        // $selector = base64_encode(random_bytes(8));
        // $token = bin2hex(random_bytes(32));

        // $cookieValue = $selector.':'.base64_encode($token);
        // $hashedToken = hash('sha256', $token);

        // $timestamp = time() + (86400 * 14);

        // setcookie('authToken', $cookieValue, $timestamp, NULL, NULL, NULL, true);

        // $connection = new Connection;
        // $db = $connection->openConnection();

        // $stmt = $db->query("INSERT INTO logins (login_selector, login_token, login_userId, login_expires) VALUES ('$selector', '$hashedToken', '$userId', '$timestamp')");
    }

    public function relogUser($userID){
        $_SESSION['userID'] = $userID;
    }

    public function isLogged(){
        if(isset($_SESSION['userID'])){
            return true;
        }else{
            return false;
        }
    }

    public function logOut(){
        // $connection = new Connection;
        // $db = $connection->openConnection();

        // list($selector, $token) = explode(':', $_COOKIE['authToken']);

        // $stmt = $db->prepare('DELETE FROM logins WHERE login_selector = :login_selector');
        // $stmt->bindValue(':login_selector', $selector);

        // $stmt->execute();

        // $stmt = $db->prepare('DELETE FROM logins WHERE login_userId = :login_userId');
        // $stmt->bindValue(':login_userId', $_SESSION['userId']);

        // $stmt->execute();
		
		foreach($_SESSION as $sessionKey => $sessionValue){
			unset($_SESSION[$sessionKey]);
		}

        //setcookie('authToken', '', 1);
        //unset($_COOKIE['authToken']);
    }

    public function getId(){
        return $_SESSION['userId'];
    }
}

?>