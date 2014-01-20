<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Sebastian Michaelsen <michaelsen@t3seo.de>, t3seo.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *
 *
 * @package commentsplus
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *
 */
class Tx_Commentsplus_Controller_CommentController extends Tx_Commentsplus_MVC_Controller_ActionController {

	/**
	 * @var tslib_cObj
	 */
	protected $contentObject;

	/**
	 * @var Tx_Extbase_Service_TypoScriptService
	 * @inject
	 */
	protected $typoScriptService;

	/**
	 *
	 */
	public function initializeAction() {
		$this->contentObject = $GLOBALS['TSFE']->cObj;
	}

	/**
	 * action list
	 *
	 * @param Tx_Commentsplus_Domain_Model_Comment $newComment
	 * @return void
	 * @dontvalidate $newComment
	 */
	public function listAction(Tx_Commentsplus_Domain_Model_Comment $newComment = NULL) {
		$commentedObject = $this->contentObject->cObjGetSingle('COA', $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->settings['commentedObject']));
		$comments = $this->commentRepository->findByCommentedObject($commentedObject);
		$this->view->assign('comments', $comments);
		$this->view->assign('newComment', $newComment);
		$this->view->assign('errors', $this->errorContainer->getErrors());
	}

	/**
	 * action create
	 *
	 * @param Tx_Commentsplus_Domain_Model_Comment $newComment
	 * @return void
	 */
	public function createAction(Tx_Commentsplus_Domain_Model_Comment $newComment) {
		$commentedObject = $this->contentObject->cObjGetSingle('COA', $this->typoScriptService->convertPlainArrayToTypoScriptArray($this->settings['commentedObject']));
		$this->reputationSystem->setSettings($this->settings);
		$this->notificationService->setSettings($this->settings);
		$newComment->setTime(new DateTime());
		$newComment->setCommentedObject($commentedObject);
		$newComment->setApproved($this->reputationSystem->determineApprovedStatus($newComment));
		$newComment->_setProperty('_languageUid', intval($GLOBALS['TSFE']->sys_language_uid));
		if ($this->settings['saveIP']) {
			$newComment->setIp($_SERVER['REMOTE_ADDR']);
		}
		$this->notificationService->notify($newComment);
		if ($newComment->getApproved() == Tx_Commentsplus_Domain_Model_Comment::APPROVAL_STATUS_NOTAPPROVED) {
			$this->addFlashMessage('moderation', t3lib_FlashMessage::INFO);
		}
		$this->commentRepository->add($newComment);
		$this->redirectWithQueryString('list');
	}

	/**
	 * @param string $actionName
	 * @param null $controllerName
	 * @param null $extensionName
	 * @param array $arguments
	 * @param null $pageUid
	 * @param int $delay
	 * @param int $statusCode
	 * @param bool $addQueryString
	 * @throws Tx_Extbase_MVC_Exception_UnsupportedRequestType
	 */
	protected function redirectWithQueryString($actionName, $controllerName = NULL, $extensionName = NULL, array $arguments = NULL, $pageUid = NULL, $delay = 0, $statusCode = 303, $addQueryString = TRUE) {
		if (!$this->request instanceof Tx_Extbase_MVC_Web_Request) throw new Tx_Extbase_MVC_Exception_UnsupportedRequestType('redirect() only supports web requests.', 1220539734);

		if ($controllerName === NULL) {
			$controllerName = $this->request->getControllerName();
		}

		$uri = $this->uriBuilder
			->reset()
			->setTargetPageUid($pageUid)
			->setCreateAbsoluteUri(TRUE)
			->setAbsoluteUriScheme(t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https' : NULL)
			->setAddQueryString($addQueryString)
			->setArgumentsToBeExcludedFromQueryString(array(
				'tx_commentsplus_comments[action]',
				'tx_commentsplus_comments[controller]',
			))
			->uriFor($actionName, $arguments, $controllerName, $extensionName);
		$this->redirectToUri($uri, $delay, $statusCode);
	}

}

?>