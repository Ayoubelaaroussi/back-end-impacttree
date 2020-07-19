<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Searcher;
use App\Entity\Customer;
use App\Entity\Admin;
use App\Entity\Photo;
use App\Service\CurrentUser;
use App\Service\FormHandler;
use App\Helper\UploadedBase64EncodedFile;
use App\Helper\Base64EncodedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Doctrine\ORM\EntityManagerInterface;

class UpdateUserController extends AbstractController
{
    private $validator;
    private $cr;
    private $imagineCacheManager;
    private $form;
    private $entityManager;

    public function __construct(ValidatorInterface $validator, CurrentUser $cr, CacheManager $cacheManager, FormHandler $form, EntityManagerInterface $entityManager)
    {
        $this->validator = $validator;
        $this->cr = $cr;
        $this->imagineCacheManager = $cacheManager;
        $this->form = $form;
        $this->entityManager = $entityManager;
    }

    public function doNothing($object)
    {
        return false;
    }

    public function addType($data)
    {
        $current = $this->cr->getCurrentUser($this);
        if ($current instanceof Customer)
            $data['type'] = "customer";
        else if ($current instanceof Searcher)
            $data['type'] = "searcher";
        else if ($current instanceof Admin)
            $data['type'] = "admin";
        else
            throw new HttpException(400, 'Cannot found type of user.');

        return json_encode($data);
    }

    public function checkIdAndEmail(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (null !== $data && array_key_exists('id', $data))
            throw new HttpException(406, 'Field \'id\' not acceptable.');
        if (null !== $data && array_key_exists('email', $data))
            throw new HttpException(406, 'Field \'email\' not acceptable.');
        if (null !== $data && array_key_exists('password', $data))
            throw new HttpException(406, 'Field \'password\' not acceptable.');
        if (null !== $data && array_key_exists('type', $data))
            throw new HttpException(406, 'Field \'type\' not acceptable.');

        return $this->addType($data);
    }

    public function updateUser($infos)
    {
        $current = $this->cr->getCurrentUser($this);
        $current->setFirstName($infos->getFirstName());
        $current->setLastName($infos->getLastName());
        $current->setOrganization($infos->getOrganization());
        $current->setJobTitle($infos->getJobTitle());
        $current->setOrganizationAddress($infos->getOrganizationAddress());
        $current->setOrganizationCity($infos->getOrganizationCity());
        $current->setOrganizationCountry($infos->getOrganizationCountry());
        $current->setPhone($infos->getPhone());
        $current->setBio($infos->getBio());
        if (null !== $current->getDomains()) {
            foreach($current->getDomains() as $domain) {
                $current->removeDomain($domain);
            }
        }
        if (null !== $infos->getDomains()) {
            foreach($infos->getDomains() as $domain) {
                $current->addDomain($domain);
            }
        }
        
        return $current;
    }

    public function updatePhoto($data)
    {
        $code = 401;
        $message = "Unauthorized";
        $extras = NULL;

        $data = json_decode($data, true);
        if (isset($data['photo']) && null !== $data['photo']) {
            $current = $this->cr->getCurrentUser($this);
            $photo = new Photo();
            $file = new UploadedBase64EncodedFile(new Base64EncodedFile($data['photo']));
            $photo->setFile($file);
            $photo->setLink($file->getClientOriginalName());
            $extras['thumnail'] = $this->imagineCacheManager->getBrowserPath($photo->getLink(), 'photo_thumb');
            $extras['link'] = $this->imagineCacheManager->getBrowserPath($photo->getLink(), 'photo_scale_down');
            $current->setPhoto($photo);

            $violations = $this->validator->validate($photo, null, ['new-photo']);
            $message = '';
            foreach ($violations as $violation) {
                $message .= $violation->getPropertyPath() . ': ' . $violation->getMessage() . ' ';
            }
            if (count($violations) !== 0)
                throw new HttpException(406, $message);
    
            try {
                $this->entityManager->persist($photo);
                $this->entityManager->persist($current);
                $this->entityManager->flush();

                $code = 201;
                $message = 'Photo updated successfully';
            } catch (HttpException $ex) {
                $code = $ex->getStatusCode();
                $message = $ex->getMessage();
            } catch (\Exception $ex) {
                $code = 400;
                $message = $ex->getMessage();
            }
        }

        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'extras' => $extras
        ], $code);
    }

    /**
     * @Route("/api/current/update", name="update_user", methods={"POST"})
     */
    public function updateUserAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $data = $this->addType($data);

        return $this->form->updateV1($data, User::class, array($this, 'updateUser'), ['update-user'], ['update-user']);
    }

    /**
     * @Route("/api/current/update_photo", name="update_photo_user", methods={"POST"})
     */
    public function updatePhotoAction(Request $request)
    {
        $data = $this->checkIdAndEmail($request);

        return $this->updatePhoto($data);
    }
}
