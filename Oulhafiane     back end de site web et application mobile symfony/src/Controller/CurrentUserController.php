<?php

namespace App\Controller;

use App\Service\CurrentUser;
use App\Entity\Customer;
use App\Entity\Notification;
use App\Entity\SearcherApplications;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Doctrine\ORM\EntityManagerInterface;

class CurrentUserController extends AbstractController
{
	private $cr;
	private $serializer;
	private $em;

	public function __construct(CurrentUser $cr, SerializerInterface $serializer, EntityManagerInterface $em)
	{
		$this->cr = $cr;
		$this->serializer = $serializer;
		$this->em = $em;
	}

	/**
     * @Route("/api/notification/{id}/seen", name="notification_seen", methods={"PATCH"}, requirements={"id"="\d+"})
     */
	public function notificationSeenAction($id)
	{
		$current = $this->cr->getCurrentUser($this);
		$notification = $this->em->getRepository(Notification::class)->find($id);
		if (null === $notification)
			throw new HttpException(404, "Notification not found.");
		if ($current !== $notification->getOwner())
			throw new HttpException(401, "You are not the owner of this notification.");
		$notification->setSeen(true);
		try {
			$this->em->persist($notification);
			$this->em->flush();
		} catch (\Exception $ex) {
			throw new HttpException(400, $ex->getMessage());
		}

		return new JsonResponse([
			'code' => 200,
			'message' => "Notification status changed successfully.",
		], 201);
	}

	/**
     * @Route("/api/current/notifications", name="all_notifications", methods={"GET"})
     */
    public function getAllNotificationsAction(Request $request)
    {
		$current = $this->cr->getCurrentUser($this);
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 12);
        $pager = $this->em->getRepository(Notification::class)->findNotifications($page, $limit, $current);
        $results = $pager->getCurrentPageResults();
        $nbPages = $pager->getNbPages();
        $currentPage = $pager->getCurrentPage();
        $maxPerPage = $pager->getMaxPerPage();
        $itemsCount = $pager->count();
        $notifications = array();
        foreach ($results as $result) {
            $notifications[] = $result;
        }
        $data = array('nbPages' => $nbPages, 'currentPage' => $currentPage, 'maxPerPage' => $maxPerPage, 'itemsCount' => $itemsCount, 'notifications' => $notifications);
        $data = $this->serializer->serialize($data, 'json', SerializationContext::create()->setGroups(array('notifications')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/current/infos", name="current_user_infos", methods={"GET"})
     */
    public function currentUserInfosAction()
    {
		$current = $this->cr->getCurrentUser($this);	
		$data = $this->serializer->serialize($current, 'json', SerializationContext::create()->setGroups(array('infos')));
		$response = new Response($data, 200);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	private function persistApplication($application) {
		$extras = NULL;

		try {
			$this->em->persist($application);
			$this->em->flush();
			$extras['id'] = $application->getId();
		} catch (\Exception $ex) {
			throw new HttpException(400, $ex->getMessage());
		}

		return new JsonResponse([
			'code' => 201,
			'message' => "Application created successfully.",
			'extras' => $extras
		], 201);
	}

	/**
     * @Route("/api/current/applySearcher", name="apply_searcher", methods={"PATCH"})
     */
    public function applySearcherAction()
    {
		$code = 401;
		$message = "You are already a searcher or an admin.";
		$extras = NULL;

		$current = $this->cr->getCurrentUser($this);
		if ($current instanceof Customer) {
			$oldApplication = $current->getSearcherApplication();
			if (null === $oldApplication) {
				$application = new SearcherApplications();
				$application->setUser($current);
				return $this->persistApplication($application);
			} else if (null === $oldApplication->getStatus()) {
					$message = "You have already an application open, please wait for an response from an administrator.";
					$extras['id'] = $oldApplication->getId();
			} else {
				$creationDate = $oldApplication->getCreationDate();
				$diff = date_diff(new \DateTime(), $creationDate)->format('%a');
				if ($diff < 7) {
					throw new HttpException(401, "You muse wait 7 days to apply again.");
				} else {
					$oldApplication->setCreationDate(new \DateTime());
					$oldApplication->setAcceptedBy(NULL);
					$oldApplication->setStatus(NULL);
					return $this->persistApplication($oldApplication);
				}
			}
		}

		return new JsonResponse([
			'code' => $code,
			'message' => $message,
			'extras' => $extras
		], $code);
    }
}
