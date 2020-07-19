<?php

namespace App\Controller;

use App\Service\FormHandler;
use App\Entity\User;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class RegistrationController extends AbstractController
{
    private $encoder;
	private $validator;
	private $form;
	private $em;
	private $JWTencoder;
	private $mailer;

	public function __construct(UserPasswordEncoderInterface $encoder, ValidatorInterface $validator, FormHandler $form, EntityManagerInterface $em, JWTEncoderInterface $JWTencoder, \Swift_Mailer $mailer)
	{
		$this->encoder = $encoder;
		$this->validator = $validator;
		$this->form = $form;
		$this->em = $em;
		$this->JWTencoder = $JWTencoder;
		$this->mailer = $mailer;
	}

	public function doNothing($object)
    {
        return false;
    }

	public function setPassword($user)
	{
		$user->setPassword(
			$this->encoder->encodePassword(
				$user,
				$user->getPlainPassword()
			)
        );
        $user->eraseCredentials();

		return False;
	}

	/**
	 * @Route("/api/register", name="register_user", methods={"POST"})
	 */
	public function registerAction(Request $request)
	{
		$this->form->checkId($request);

		return $this->form->validate($request, User::class, array($this, 'setPassword'), array($this, 'doNothing'), ['new-user'], ['new-user']);
	}

	/**
	 * @Route("/api/forgotPassword", name="forgot_password", methods={"POST"})
	 */
	public function forgotPasswordAction(Request $request)
	{
		$data = json_decode($request->getContent(), true);
		if (null !== $data && array_key_exists('id', $data))
			throw new HttpException(406, 'Field \'id\' not acceptable.');
		if (!array_key_exists('username', $data))
			throw new HttpException(406, 'Field \'username\' not found.');

		$username = $data['username'];
		$user = $this->em->getRepository(User::class)->findOneBy(['email' => $username]);
        if (null !== $user) {
			try {
				$recoveryToken = Uuid::uuid4()->toString();
				$user->setRecoveryToken($recoveryToken);
				$this->em->persist($user);
				$this->em->flush();
				$exp = $exp = new \DateTime();
				$exp->add(new \DateInterval('PT1H'));
				$jwtToken = $this->JWTencoder->encode(['email' => $user->getEmail(), 'token' => $recoveryToken, 'exp' => $exp->getTimestamp()]);
				
				$url = $this->getParameter('front_url');
				$from = $this->getParameter('from_email');
				$message = (new \Swift_Message('Resetting your password'))
				->setFrom($from)
				->setTo($user->getEmail())
				->setBody(
					$this->renderView(
						'emails/resetPassword.html.twig',
						[
							'name' => $user->getFirstName() ." ". $user->getLastName(),
							'confirmationUrl' => $url."auth/login?recover=".$jwtToken
						]
					),
					'text/html'
				);
				$this->mailer->send($message);
			} catch (\Exception $ex) {
				throw new HttpException(500, $ex->getMessage());
			}
		}

		return new JsonResponse([
            'code' => 200,
            'message' => "If you are registered, an email will be sent to you.",
        ], 200);
	}

	/**
	 * @Route("/api/changePassword", name="change_password", methods={"POST"})
	 */
	public function changePasswordAction(Request $request)
	{
		$data = json_decode($request->getContent(), true);
		if (null !== $data && array_key_exists('id', $data))
			throw new HttpException(406, 'Field \'id\' not acceptable.');
			if (!array_key_exists('token', $data))
			throw new HttpException(406, 'Field \'token\' not found.');
		if (!array_key_exists('password', $data))
			throw new HttpException(406, 'Field \'password\' not found.');

		$tokenDecoded = $this->JWTencoder->decode($data['token']);
		if (!array_key_exists('email', $tokenDecoded))
			throw new HttpException(406, 'Token invalid.');
		if (!array_key_exists('token', $tokenDecoded))
			throw new HttpException(406, 'Token invalid.');
		if (!array_key_exists('exp', $tokenDecoded))
			throw new HttpException(406, 'Token invalid.');
		
		/**** remember to check if the expiration time is ok ****/

		$user = $this->em->getRepository(User::class)->findOneBy(['email' => $tokenDecoded['email']]);
		if (null === $user)
			throw new HttpException(406, 'Token invalid.');
		
		if ($user->getRecoveryToken() !== $tokenDecoded['token'])
			throw new HttpException(406, 'Token invalid.');

		$user->setPlainPassword($data['password']);
		$user->setRecoveryToken(null);
		$this->setPassword($user);
		try {
			$this->em->persist($user);
			$this->em->flush();
		} catch (\Exception $ex) {
			throw new HttpException(500, $ex->getMessage());
		}

		return new JsonResponse([
            'code' => 200,
            'message' => "Password changed successfully.",
        ], 200);
	}
}
