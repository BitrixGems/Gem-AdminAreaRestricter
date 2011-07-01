<?php
class BitrixGem_AdminAreaRestricter extends BaseBitrixGem{

	protected $aGemInfo = array(
		'GEM'			=> 'AdminAreaRestricter',
		'AUTHOR'		=> 'Владимир Савенков',
		'AUTHOR_LINK'	=> 'http://bitrixgems.ru/',
		'DATE'			=> '20.02.2011',
		'VERSION'		=> '0.1',
		'NAME' 			=> 'Запрет доступа в админку',
		'DESCRIPTION' 	=> "Запрет доступа в админку для всех групп пользователей, кроме указанных в настройках. Необходимо это например для закрытия доступа редакторам сайта  при обновлении функционала на сайте или обновлениям платформы и созданию бэкапов в рабочее время.",
		'REQUIREMENTS'	=> '',
		'REQUIRED_MIN_MODULE_VERSION' => '1.1.0',
	);

	public function checkRequirements(){
		$oModule = CModule::CreateModuleObject('iv.bitrixgems');
		if( $oModule->MODULE_VERSION < '1.1.0' ) throw new Exception('Для работы гема необходима 1.1.0 версия модуля. У вас установлена версия '.$oModule->MODULE_VERSION.'. Пожалуйста, установите обновление.');
	}

	protected function getDefaultOptions(){
		return array(
			'enabled' => array(
				'name' => 'Ограничение доступа включено',
				'type' => 'checkbox',
				'value' => 'N',
				'options' => array('Y' => 'Включено'),
			),
			'errorText' => array(
				'name' => 'Текст выводимого сообщения',
				'type' => 'textarea',
				'value' => 'Администратор сайта временно запретил доступ к административному разделу.',
			),
			'allowedUserGroups' => array(
				'name' => 'Доступ разрешен следующим группам пользователей',
				'type' => 'select|usergroup',
				'multiple' => true,
				'value'=> array(),
			),
		);
	}

	public function event_main_OnBeforeProlog_denyAccess(){
		if( defined('ADMIN_SECTION') ){
			$aOptions = $this->getOptions();
			global $APPLICATION, $DB, $USER;
			$aUG = $USER->GetUserGroupArray();
			$aUGIntersection = array_intersect( $aUG, $aOptions['allowedUserGroups'] );
			$bDenyAccess = empty( $aUGIntersection );
			if( $USER->IsAdmin() || !$USER->IsAuthorized() ) $bDenyAccess = false;

			if( ($aOptions['enabled'] == "Y") ){

				if( $bDenyAccess ){
					require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php" );
					$USER->CanDoOperation('ololo'); // Просто триггерим инициализацию массива $_SESSION["OPERATIONS"] на всякий пожарный :)
					$aPrevSession = $_SESSION["OPERATIONS"];
					$_SESSION["OPERATIONS"] = array();
					$adminPage->bInit = true;
					require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php" );

					CAdminMessage::ShowMessage(array( 'TYPE'=>'ERROR','MESSAGE' => $aOptions['errorText'], 'HTML' => true ));
					$_SESSION["OPERATIONS"] = $aPrevSession;
					require( $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php" );
					die();
				}else{
					//Я не наркоман, просто другого варианта с красивым выводом своего сообщения я не нашел, а заморачиваться с JS уж очень лень ((
					global $adminChain;
					//var_dump($adminChain);
					$adminChain = new CiVAdminAreaRestricterGem_AdminChain_Decorator( $adminChain );
				}


			}

		}
	}

	public function event_main_OnProlog_showWarning(){
		if( defined('ADMIN_SECTION') ){
			$aOptions = $this->getOptions();
			global $APPLICATION, $DB, $USER;
			$aUG = $USER->GetUserGroupArray();
			$aUGIntersection = array_intersect( $aUG, $aOptions['allowedUserGroups'] );
			$bDenyAccess = empty( $aUGIntersection );
			if( $USER->IsAdmin() || !$USER->IsAuthorized() ) $bDenyAccess = false;
			if( ($aOptions['enabled'] == "Y") ){

				if( !$bDenyAccess ){					
					//Я не наркоман, просто другого варианта с красивым выводом своего сообщения я не нашел ((
					global $adminChain;
					$adminChain = new CiVAdminAreaRestricterGem_AdminChain_Decorator( $adminChain );
				}


			}

		}
	}
	
	public function needAdminPage(){
		return true;
	}

}

class CiVAdminAreaRestricterGem_AdminChain_Decorator{

	protected $oAdminChain;

	public function __construct( $oAdminChain ){
		$this->oAdminChain = $oAdminChain;
	}

	public function Show(){
		$mResult = $this->oAdminChain->Show();
		echo '<div style="margin-left:10px">';
		CAdminMessage::ShowMessage(
			array(
				'TYPE'=>'ERROR',
				'MESSAGE' => 'ВНИМАНИЕ! ВКЛЮЧЕНО ОГРАНИЧЕНИЕ ДОСТУПА К АДМИНКЕ!<br />Для просмотра параметров ограничения или для отключения - <a href="bitrixgems_simpleresponder.php?gem=AdminAreaRestricter">пройдите на страницу гема AdminAreaRestricter</a>.',
				'HTML' => true
			)
		);
		echo '</div>';
		return $mResult;
	}

	public function __call($method, $args) {
		return call_user_func_array(
			array($this->oAdminChain, $method),
			$args
		);
	}

	public function __get($sParamName) {
		return $this->oAdminChain->{$sParamName};
	}

	public function __set($sParamName, $mValue) {
		return $this->oAdminChain->{$sParamName} = $mValue;
	}
}
?>
