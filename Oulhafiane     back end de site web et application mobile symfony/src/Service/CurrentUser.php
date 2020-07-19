<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CurrentUser extends AbstractController
{
	public function getCurrentUser($controller)
	{
		if (!$controller->has('security.token_storage')) {
			throw new \LogicException('The Security Bundle is not registered in your application.');
		}
		if (null === $token = $controller->get('security.token_storage')->getToken()) {
			return;
		}
		if (!is_object($user = $token->getUser())) {
			// e.g. anonymous authentication
			return;
		}
		return $user;
	}
}
