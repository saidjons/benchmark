<?php

namespace Ubiquity\controllers\auth;

use Ubiquity\controllers\auth\traits\Auth2FATrait;
use Ubiquity\controllers\auth\traits\AuthAccountCreationTrait;
use Ubiquity\controllers\auth\traits\AuthAccountRecoveryTrait;
use Ubiquity\utils\http\USession;
use Ubiquity\utils\http\URequest;
use Ubiquity\utils\flash\FlashMessage;
use Ubiquity\controllers\Controller;
use Ubiquity\utils\http\UResponse;
use Ubiquity\utils\base\UString;
use Ubiquity\controllers\Startup;
use Ajax\service\Javascript;
use Ubiquity\utils\http\UCookie;
use Ubiquity\controllers\semantic\InsertJqueryTrait;
use Ajax\semantic\html\collections\form\HtmlForm;
use Ajax\php\ubiquity\JsUtils;

/**
 * Controller Auth
 *
 * @property \Ajax\php\ubiquity\JsUtils $jquery
 */
abstract class AuthController extends Controller {
	use AuthControllerCoreTrait,AuthControllerVariablesTrait,AuthControllerOverrideTrait,InsertJqueryTrait,Auth2FATrait,AuthAccountCreationTrait,AuthAccountRecoveryTrait;

	/**
	 *
	 * @var AuthFiles
	 */
	protected $authFiles;
	protected $_controller;
	protected $_action;
	protected $_actionParams;
	protected $_noAccessMsg;
	protected $_loginCaption;
	protected $_attemptsSessionKey = '_attempts';
	protected $_controllerInstance;
	protected $_compileJS = true;
	protected $_invalid=false;

	public function __construct($instance = null) {
		parent::__construct ();
		$this->_insertJquerySemantic ();
		$this->_controller = Startup::getController ();
		$this->_action = Startup::getAction ();
		$this->_actionParams = Startup::getActionParams ();
		$this->_noAccessMsg = new FlashMessage ( 'You are not authorized to access the page <b>{url}</b> !', 'Forbidden access', 'error', 'warning circle' );
		$this->_loginCaption = 'Log in';
		$this->_controllerInstance = $instance;
		if (isset ( $instance )){
			Startup::injectDependences ( $instance );
		}
		if($this->useAjax() && !URequest::isAjax()) {
			$this->_addAjaxBehavior($instance->jquery??$this->jquery);
		}
	}

	public function index() {
		if (($nbAttempsMax = $this->attemptsNumber ()) !== null) {
			$nb = USession::getTmp ( $this->_attemptsSessionKey, $nbAttempsMax );
			if ($nb <= 0) {
				$this->badLogin ();
				return;
			}
		}
		if($this->useAjax()){
			$this->_addFrmAjaxBehavior('frm-login');
		}
		$vData=[ 'action' => $this->getBaseUrl () . '/connect','loginInputName' => $this->_getLoginInputName (),'loginLabel' => $this->loginLabel (),'passwordInputName' => $this->_getPasswordInputName (),'passwordLabel' => $this->passwordLabel (),'rememberCaption' => $this->rememberCaption () ];
		$this->addAccountCreationViewData($vData,true);
		$this->authLoadView ( $this->_getFiles ()->getViewIndex (), $vData );
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Ubiquity\controllers\Controller::isValid()
	 */
	public final function isValid($action) {
		return true;
	}

	/**
	 * Action called when the user does not have access rights to a requested resource
	 *
	 * @param array|string $urlParts
	 */
	public function noAccess($urlParts) {
		if (! \is_array ( $urlParts )) {
			$urlParts = \explode ( '.', $urlParts );
		}
		USession::set ( 'urlParts', $urlParts );
		$fMessage = $this->_noAccessMsg;
		$this->noAccessMessage ( $fMessage );
		$message = $this->fMessage ( $fMessage->parseContent ( [ 'url' => \implode ( '/', $urlParts ) ] ) );
		
		if (URequest::isAjax ()) {
			$this->jquery->get ( $this->_getBaseRoute () . '/info/f', '#_userInfo', [ 'historize' => false,'jqueryDone' => 'replaceWith','hasLoader' => false,'attr' => '' ] );
			$this->jquery->compile ( $this->view );
		}
		$vData=[ '_message' => $message,'authURL' => $this->getBaseUrl (),'bodySelector' => $this->_getBodySelector (),'_loginCaption' => $this->_loginCaption ];
		$this->addAccountCreationViewData($vData);
		$this->authLoadView ( $this->_getFiles ()->getViewNoAccess (), $vData);
	}

	/**
	 * Override to implement the complete connection procedure
	 *
	 * @post
	 */
	#[\Ubiquity\attributes\items\router\Post]
	public function connect() {
		if (URequest::isPost ()) {
			if ($connected = $this->_connect ()) {
				if (isset ( $_POST ['ck-remember'] )) {
					$this->rememberMe ( $connected );
				}
				if (USession::exists ( $this->_attemptsSessionKey )) {
					USession::delete ( $this->_attemptsSessionKey );
				}
				if($this->has2FA($connected)){
					$this->initializeAuth();
					USession::set($this->_getUserSessionKey().'-2FA', $connected);
					$this->send2FACode();
					$this->confirm();
					$this->finalizeAuth();
				}else{
					$this->onConnect ( $connected );
				}
			} else {
				$this->_invalid=true;
				$this->initializeAuth();
				$this->onBadCreditentials ();
				$this->finalizeAuth();
			}
		}
	}

	/**
	 * Default Action for invalid creditentials
	 *
	 * @noRoute()
	 */
	#[\Ubiquity\attributes\items\router\NoRoute]
	public function badLogin() {
		$fMessage = new FlashMessage ( 'Invalid creditentials!', 'Connection problem', 'warning', 'warning circle' );
		$this->badLoginMessage ( $fMessage );
		$attemptsMessage = '';
		if (($nbAttempsMax = $this->attemptsNumber ()) !== null) {
			$nb = USession::getTmp ( $this->_attemptsSessionKey, $nbAttempsMax );
			$nb --;
			if ($nb < 0) {
				$nb = 0;
			}
			if ($nb == 0) {
				$fAttemptsNumberMessage = $this->noAttempts ();
			} else {
				$fAttemptsNumberMessage = new FlashMessage ( '<i class="ui warning icon"></i> You still have {_attemptsCount} attempts to log in.', null, 'bottom attached warning', '' );
			}
			USession::setTmp ( $this->_attemptsSessionKey, $nb, $this->attemptsTimeout () );
			$this->attemptsNumberMessage ( $fAttemptsNumberMessage, $nb );
			$fAttemptsNumberMessage->parseContent ( [ '_attemptsCount' => $nb,'_timer' => '<span id="timer"></span>' ] );
			$attemptsMessage = $this->fMessage ( $fAttemptsNumberMessage, 'timeout-message' );
			$fMessage->addType ( "attached" );
		}
		$message = $this->fMessage ( $fMessage, 'bad-login' ) . $attemptsMessage;
		$this->authLoadView ( $this->_getFiles ()->getViewNoAccess (), [ '_message' => $message,'authURL' => $this->getBaseUrl (),'bodySelector' => $this->_getBodySelector (),'_loginCaption' => $this->_loginCaption ] );
	}
	
	/**
	 * Logout action
	 * Terminate the session and display a logout message
	 */
	public function terminate() {
		USession::terminate ();
		$fMessage = new FlashMessage ( 'You have been properly disconnected!', 'Logout', 'success', 'checkmark' );
		$this->terminateMessage ( $fMessage );
		$message = $this->fMessage ( $fMessage );
		$this->authLoadView ( $this->_getFiles ()->getViewNoAccess (), [ '_message' => $message,'authURL' => $this->getBaseUrl (),'bodySelector' => $this->_getBodySelector (),'_loginCaption' => $this->_loginCaption ] );
	}

	public function _disConnected() {
		$fMessage = new FlashMessage ( 'You have been disconnected from the application!', 'Logout', '', 'sign out' );
		$this->disconnectedMessage ( $fMessage );
		$message = $this->fMessage ( $fMessage );
		$this->jquery->getOnClick ( '._signin', $this->getBaseUrl (), $this->_getBodySelector (), [ 'stopPropagation' => false,'preventDefault' => false ] );
		$this->jquery->execOn ( 'click', '._close', "window.open(window.location,'_self').close();" );
		return $this->jquery->renderView ( $this->_getFiles ()->getViewDisconnected (), [ "_title" => 'Session ended','_message' => $message ], true );
	}

	/**
	 * Action displaying the logged user information
	 * if _displayInfoAsString returns true, use _infoUser var in views to display user info
	 *
	 * @param null|boolean $force
	 * @return string|null
	 * @throws \Exception
	 */
	public function info($force = null) {
		if (isset ( $force )) {
			$displayInfoAsString = $force === true;
		} else {
			$displayInfoAsString = $this->_displayInfoAsString ();
		}
		return $this->loadView ( $this->_getFiles ()->getViewInfo (), [ 'connected' => USession::get ( $this->_getUserSessionKey () ),'authURL' => $this->getBaseUrl (),'bodySelector' => $this->_getBodySelector () ], $displayInfoAsString );
	}
	
	public function checkConnection() {
		UResponse::asJSON ();
		echo \json_encode(['valid'=> $this->_isValidUser ()]);
	}

	/**
	 * Sets the default noAccess message
	 * Default : "You are not authorized to access the page <b>{url}</b> !"
	 *
	 * @param string $content
	 * @param string $title
	 * @param string $type
	 * @param string $icon
	 */
	public function _setNoAccessMsg($content, $title = NULL, $type = NULL, $icon = null) {
		$this->_noAccessMsg->setValues ( $content, $title, $type, $icon );
	}

	/**
	 *
	 * @param string $_loginCaption
	 */
	public function _setLoginCaption($_loginCaption) {
		$this->_loginCaption = $_loginCaption;
	}

	/**
	 * Auto connect the user
	 */
	public function _autoConnect() {
		$cookie = $this->getCookieUser ();
		if (isset ( $cookie )) {
			$user = $this->fromCookie ( $cookie );
			if (isset ( $user )) {
				USession::set ( $this->_getUserSessionKey (), $user );
			}
		}
	}

	/**
	 * Deletes the cookie for auto connection and returns to index
	 */
	public function forgetConnection() {
		UCookie::delete ( $this->_getUserSessionKey () );
		$this->index ();
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Ubiquity\controllers\ControllerBase::finalize()
	 */
	public function finalize() {
		if (! UResponse::isJSON ()) {
			if(Startup::getAction()!=='connect') {
				$this->finalizeAuth();
			}
			$this->jquery->execAtLast ( "if($('#_userInfo').length){\$('#_userInfo').replaceWith(" . \preg_replace ( "/$\R?^/m", "", Javascript::prep_element ( $this->info () ) ) . ");}" );
			if ($this->_compileJS) {
				echo $this->jquery->compile ();
			}
		}
	}

	protected function finalizeAuth() {
		if (!URequest::isAjax()) {
			$this->loadView('@activeTheme/main/vFooter.html');
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \Ubiquity\controllers\ControllerBase::initialize()
	 */
	public function initialize() {
		if(Startup::getAction()!=='connect') {
			$this->initializeAuth();
		}
	}

	protected function initializeAuth() {
		if (!URequest::isAjax()) {
			$this->loadView('@activeTheme/main/vHeader.html');
		}
	}

	/**
	 *
	 * @param string $url
	 */
	public function _forward($url, $initialize = null, $finalize = null) {
		if (! isset ( $initialize )) {
			$initialize = (! isset ( $this->_controllerInstance ) || URequest::isAjax ());
		}
		if (! isset ( $finalize )) {
			$finalize = $initialize;
		}
		Startup::forward ( $url, $initialize, $finalize );
	}
	
	public function _addAjaxBehavior(JsUtils $jquery=null,$ajaxParameters=['hasLoader'=>'$(this).children(".button")','historize'=>false,'listenerOn'=>'body']){
		$jquery??=$this->jquery;
		$jquery->getHref('.ajax[data-target]','', $ajaxParameters);
		$jquery->postFormAction('.ui.form',$this->_getBodySelector(),$ajaxParameters);
	}

	public function _addFrmAjaxBehavior($id):HtmlForm{
		$frm=$this->jquery->semantic()->htmlForm($id);
		$frm->addExtraFieldRule($this->_getLoginInputName(),'empty');
		$frm->addExtraFieldRule($this->_getPasswordInputName(),'empty');
		$frm->setValidationParams(['inline'=>true,'on'=>'blur']);
		return $frm;
	}
	public function _init(){
		
	}
}
