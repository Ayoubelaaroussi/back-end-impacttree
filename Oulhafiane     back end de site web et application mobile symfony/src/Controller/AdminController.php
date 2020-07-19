<?php

namespace App\Controller;

use App\Entity\News;
use App\Entity\Admin;
use App\Entity\SearcherApplications;
use App\Entity\MsgContactUs;
use App\Service\CurrentUser;
use App\Service\FormHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

class AdminController extends AbstractController
{
    private $em;
    private $cr;
    private $serializer;
    private $form;

    public function __construct(EntityManagerInterface $em, CurrentUser $cr, SerializerInterface $serializer, FormHandler $form)
    {
        $this->em = $em;
        $this->cr = $cr;
        $this->serializer = $serializer;
        $this->form = $form;
    }

    public function doNothing($object)
    {
        return false;
    }

    public function setOwner($news)
    {
        $news->setCreator($this->cr->getCurrentUser($this));

        return True;
    }

    private function checkRoleAndId(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (null !== $data && array_key_exists('id', $data))
            throw $this->createAccessDeniedException();

        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return;
    }

    /**
     * @Route("/api/admin/news/new", name="new_news", methods={"POST"})
     */
    public function newNewsAction(Request $request)
    {
        $this->checkRoleAndId($request);

        return $this->form->validate($request, News::class, array($this, 'setOwner'), array($this, 'doNothing'), ['new-news'], ['new-news']);
    }

    /**
     * @Route("/api/admin/msg/{id}", name="mark_as_read_msg", methods={"PATCH"}, requirements={"id"="\d+"})
     */
    public function markAsReadMsg($id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $msg = $this->em->getRepository(MsgContactUs::class)->findOneBy(['id' => $id]);
        if (null === $msg)
            throw new HttpException(404, 'Message not found.');

        try {
            $msg->setSeen(true);
            $this->em->persist($msg);
            $this->em->flush();
        } catch (\Exception $ex) {
            throw new HttpException(500, $ex->getMessage());
        }

        return new JsonResponse([
            'code' => 200,
            'message' => "Message marked as read successfully.",
            'extras' => NULL
        ], 200);
    }

    /**
     * @Route("/api/admin/listMsgContactUs", name="list_msg_contact_us", methods={"GET"})
     */
    public function listMsgContactUsAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 12);
        $seen = $request->query->get('seen', null);
        $pager = $this->em->getRepository(MsgContactUs::class)->findMsgsContactUs($page, $limit, $seen);
        $results = $pager->getCurrentPageResults();
        $nbPages = $pager->getNbPages();
        $currentPage = $pager->getCurrentPage();
        $maxPerPage = $pager->getMaxPerPage();
        $itemsCount = $pager->count();
        $msgs = array();
        foreach ($results as $result) {
            $msgs[] = $result;
        }
        $data = array('nbPages' => $nbPages, 'currentPage' => $currentPage, 'maxPerPage' => $maxPerPage, 'itemsCount' => $itemsCount, 'msgs' => $msgs);
        $data = $this->serializer->serialize($data, 'json', SerializationContext::create()->setGroups(array('list-msgs')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/admin/listApplications", name="list_applications", methods={"GET"})
     */
    public function listApplicationsAction()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $applications = $this->em->getRepository(SearcherApplications::class)->findBy(['status' => null]);
        $data = $this->serializer->serialize($applications, 'json', SerializationContext::create()->setGroups(array('list-applications')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/admin/approveSearcher/{id}", name="approve_searcher", methods={"PATCH"}, requirements={"id"="\d+"})
     */
    public function approveSearcher($id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $application = $this->em->getRepository(SearcherApplications::class)->find($id);
        if (null === $application)
            throw new HttpException(404, "Application not found.");
        if (null !== $application->getStatus())
            throw new HttpException(404, "Application already closed.");
        try{
            $application->setAcceptedBy($current);
            $application->setStatus(true);
            $query = "UPDATE user SET type = 'searcher', roles = '[\"ROLE_SEARCHER\"]' WHERE id = ".$application->getUser()->getId();
            $this->em->getConnection()->exec( $query );
            $this->em->persist($application);
            $this->em->flush();

            return new JsonResponse([
                'code' => 200,
                'message' => "Application approved successfully.",
                'extras' => NULL
            ], 200);
        } catch (\Exception $ex) {
            throw new HttpException(400, $ex->getMessage());
        }
    }

    /**
     * @Route("/api/admin/rejectSearcher/{id}", name="reject_searcher", methods={"PATCH"}, requirements={"id"="\d+"})
     */
    public function rejectSearcher($id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $application = $this->em->getRepository(SearcherApplications::class)->find($id);
        if (null === $application)
            throw new HttpException(404, "Application not found.");
        if (null !== $application->getStatus())
            throw new HttpException(404, "Application already closed.");
        try{
            $application->setAcceptedBy($current);
            $application->setStatus(false);
            $this->em->persist($application);
            $this->em->flush();

            return new JsonResponse([
                'code' => 200,
                'message' => "Application rejected successfully.",
                'extras' => NULL
            ], 200);
        } catch (\Exception $ex) {
            throw new HttpException(400, $ex->getMessage());
        }
    }
}
