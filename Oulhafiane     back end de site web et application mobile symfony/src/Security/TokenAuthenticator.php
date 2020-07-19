<?php

namespace App\Security;

use App\Entity\SurveyToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $jwtEncoder;

    public function __construct(EntityManagerInterface $em, JWTEncoderInterface $jwtEncoder)
    {
        $this->em = $em;
        $this->jwtEncoder = $jwtEncoder;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        preg_match("/.*\/dataset\/(.*?)\//", $request->getUri(), $matches);
        if (!isset($matches[1]))
            return false;
        return $request->headers->has('X-AUTH-TOKEN');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        preg_match("/.*\/dataset\/(.*?)\//", $request->getUri(), $matches);
        $dataset = $matches[1];
        return [
            'token' => $request->headers->get('X-AUTH-TOKEN'),
            'dataset' => $dataset
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $apiToken = $credentials['token'];

        if (null === $apiToken) {
            return;
        }

        // if a User object, checkCredentials() is called
        $data = $this->jwtEncoder->decode($apiToken);
        if (!array_key_exists('dataset', $credentials))
            return null;
        if (!array_key_exists('dataset', $data))
            return null;
        if (!array_key_exists('survey', $data))
            return null;
        if ($data['dataset'] !== $credentials['dataset'])
            return null;
        return $this->em->getRepository(SurveyToken::class)
             ->findOneBy(['uuid' => $data['survey']]);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // return true to cause authentication success

        /**** remember to check if the expiration time is ok ****/

        if ($user->getDataset()->getUuid() === $credentials["dataset"])
            return true;
        else
            return false;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}