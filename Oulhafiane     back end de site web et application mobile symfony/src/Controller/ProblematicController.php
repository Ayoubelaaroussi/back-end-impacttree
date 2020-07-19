<?php

namespace App\Controller;

use App\Service\CurrentUser;
use App\Service\FormHandler;
use App\Entity\Problematic;
use App\Entity\Comment;
use App\Entity\Notification;
use App\Entity\Vote;
use App\Helper\UploadedBase64EncodedFile;
use App\Helper\Base64EncodedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

class ProblematicController extends AbstractController
{
    private $validator;
    private $cr;
    private $imagineCacheManager;
    private $form;
    private $em;
    private $serializer;

    public function __construct(ValidatorInterface $validator, CurrentUser $cr, CacheManager $cacheManager, FormHandler $form, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->cr = $cr;
        $this->imagineCacheManager = $cacheManager;
        $this->form = $form;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    public function doNothing($object)
    {
        return false;
    }

    public function setOwner($problematic)
    {
        $problematic->setOwner($this->cr->getCurrentUser($this));
        $photos = $problematic->getPhotos();
        if (null === $photos)
            return True;
        foreach ($photos as $photo) {
            $file = new UploadedBase64EncodedFile(new Base64EncodedFile($photo->getFile()));
            $photo->setFile($file);
            $photo->setProblematic($problematic);
            $photo->setLink($file->getClientOriginalName());
            $this->imagineCacheManager->getBrowserPath($photo->getLink(), 'photo_thumb');
            $this->imagineCacheManager->getBrowserPath($photo->getLink(), 'photo_scale_down');
        }
        $violations = $this->validator->validate($problematic, null, ['new-photo']);
        $message = '';
        foreach ($violations as $violation) {
            $message .= $violation->getPropertyPath() . ': ' . $violation->getMessage() . ' ';
        }
        if (count($violations) !== 0)
            throw new HttpException(406, $message);
        return True;
    }

    public function updatePhotos($problematic)
    {
        $photos = $problematic->getPhotos();
        if (null === $photos || null === $photos[0])
            return True;
        if (null !== $photos[0]->getId())
            return True;
        foreach ($photos as $photo) {
            $file = new UploadedBase64EncodedFile(new Base64EncodedFile($photo->getFile()));
            $photo->setFile($file);
            $photo->setProblematic($problematic);
            $photo->setLink($file->getClientOriginalName());
            $this->imagineCacheManager->getBrowserPath($photo->getLink(), 'photo_thumb');
            $this->imagineCacheManager->getBrowserPath($photo->getLink(), 'photo_scale_down');
        }
        $violations = $this->validator->validate($problematic, null, ['new-photo']);
        $message = '';
        foreach ($violations as $violation) {
            $message .= $violation->getPropertyPath() . ': ' . $violation->getMessage() . ' ';
        }
        if (count($violations) !== 0)
            throw new HttpException(406, $message);
        return True;
    }

    private function pushNotifications($users, $type, $reference, $message)
    {
        foreach ($users as $user) {
            $notification = new Notification();
            $notification->setType($type);
            $notification->setOwner($user);
            $notification->setReference($reference);
            $notification->setMessage($message);

            try {
                $this->em->persist($notification);
            } catch (\Exception $ex) {}
        }
        try {
            $this->em->flush();
        } catch (\Exception $ex) {
            throw new HttpException(400, $ex->getMessage());
        }
    }

    public function notifyFollowers($problematic)
    {
        $current = $this->cr->getCurrentUser($this);
        $this->pushNotifications($current->getFollowers(), 0, $problematic->getId(), $current->getFirstName()." ".$current->getLastName()." has added a new problematic.");
    }

    private function checkRoleAndId(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (null !== $data && array_key_exists('id', $data))
            throw $this->createAccessDeniedException();

        $this->denyAccessUnlessGranted('ROLE_SEARCHER');
        return;
    }

    /**
     * @Route("/api/problematic/{id}", name="update_problematic", methods={"PATCH"}, requirements={"id"="\d+"})
     */
    public function UpdateProblematicAction(Request $request, $id)
    {
        $this->checkRoleAndId($request);

        $problematic = $this->em->getRepository(problematic::class)->find($id);
        if (null === $problematic)
            throw new HttpException(404, "Problematic not found.");
        
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $problematic->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $data = json_decode($request->getContent(), true);
        if (array_key_exists("photos", $data)) {
            $photos = $problematic->getPhotos();
            foreach($photos as $photo) {
                $problematic->removePhoto($photo);
            }
        }
        
        return $this->form->update($request, Problematic::class, array($this, 'updatePhotos'), array($this, 'doNothing'), ['update-problematic'], ['update-problematic'], $id);
    }

    /**
     * @Route("/api/problematic/new", name="new_problematic", methods={"POST"})
     */
    public function newProblematicAction(Request $request)
    {
        $this->checkRoleAndId($request);

        return $this->form->validate($request, Problematic::class, array($this, 'setOwner'), array($this, 'doNothing'), ['new-problematic'], ['new-problematic'], array($this, 'notifyFollowers'));
    }

    /**
     * @Route("/api/problematic/{id}", name="specific_problematic", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function SpecificProblematicAction($id)
    {
        $problematic = $this->em->getRepository(problematic::class)->find($id);
        if (null === $problematic)
            throw new HttpException(404, "Problematic not found.");
        $data = $this->serializer->serialize($problematic, 'json', SerializationContext::create()->setGroups(array('list-problematics', 'specific-problematic')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/problematic/{id}", name="delete_problematic", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function DeleteProblematicAction(Request $request, $id)
    {
        $this->checkRoleAndId($request);

        $problematic = $this->em->getRepository(problematic::class)->find($id);
        if (null === $problematic)
            throw new HttpException(404, "Problematic not found.");
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $problematic->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $photos = $problematic->getPhotos();
        foreach($photos as $photo) {
            $problematic->removePhoto($photo);
        }

        try{
            $this->em->persist($problematic);
            $this->em->remove($problematic);
            $this->em->flush();
        } catch (\Exception $ex) {
            throw new HttpException(400, $ex->getMessage());
        }

        return new JsonResponse([
            'code' => 200,
            'message' => "Problematic deleted successfully.",
        ], 200);
    }

    /**
     * @Route("/api/problematic/all", name="all_problematic", methods={"GET"})
     */
    public function getAllProblematicAction(Request $request)
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 12);
        $orderBy = $request->query->get('orderBy', null);
        $order = $request->query->get('order', null);
        $pager = $this->em->getRepository(Problematic::class)->findProblematic($page, $limit, $orderBy, $order);
        $results = $pager->getCurrentPageResults();
        $nbPages = $pager->getNbPages();
        $currentPage = $pager->getCurrentPage();
        $maxPerPage = $pager->getMaxPerPage();
        $itemsCount = $pager->count();
        $problematics = array();
        foreach ($results as $result) {
            $problematics[] = $result;
        }
        $data = array('nbPages' => $nbPages, 'currentPage' => $currentPage, 'maxPerPage' => $maxPerPage, 'itemsCount' => $itemsCount, 'problematics' => $problematics);
        $data = $this->serializer->serialize($data, 'json', SerializationContext::create()->setGroups(array('list-problematics')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/public/problematic/filter", name="filtred_problematic", methods={"PATCH"})
     */
    public function getFiltredProblematicAction(Request $request)
    {
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 12);
        $orderBy = $request->query->get('orderBy', null);
        $order = $request->query->get('order', null);
        $data = json_decode($request->getContent(), true);
        $searchers = null;
        $categories = null;
        $subCategories = null;
        $keywords = null;
        if (isset($data['searchers']) && is_array($data['searchers']) && !empty($data['searchers']))
            $searchers = $data['searchers'];
        if (isset($data['categories']) && is_array($data['categories']) && !empty($data['categories']))
            $categories = $data['categories'];
        if (isset($data['subCategories']) && is_array($data['subCategories']) && !empty($data['subCategories']))
            $subCategories = $data['subCategories'];
        if (isset($data['keywords']) && is_array($data['keywords']) && !empty($data['keywords']))
            $keywords = $data['keywords'];
        $pager = $this->em->getRepository(Problematic::class)->filterProblematic($page, $limit, $orderBy, $order, $searchers, $categories, $subCategories, $keywords);
        $results = $pager->getCurrentPageResults();
        $nbPages = $pager->getNbPages();
        $currentPage = $pager->getCurrentPage();
        $maxPerPage = $pager->getMaxPerPage();
        $itemsCount = $pager->count();
        $problematics = array();
        foreach ($results as $result) {
            $problematics[] = $result;
        }
        $data = array('nbPages' => $nbPages, 'currentPage' => $currentPage, 'maxPerPage' => $maxPerPage, 'itemsCount' => $itemsCount, 'problematics' => $problematics);
        $data = $this->serializer->serialize($data, 'json', SerializationContext::create()->setGroups(array('list-problematics')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/comment/{id}", name="delete_comment", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function DeleteCommentAction(Request $request, $id)
    {
        $this->checkRoleAndId($request);

        $comment = $this->em->getRepository(Comment::class)->find($id);
        if (null === $comment)
            throw new HttpException(404, "Comment not found.");
        $current = $this->cr->getCurrentUser($this);
        if ($current !== $comment->getOwner())
            throw new HttpException(401, "You are not the owner.");

        try{
            $this->em->remove($comment);
            $this->em->flush();
        } catch (\Exception $ex) {
            throw new HttpException(400, $ex->getMessage());
        }

        return new JsonResponse([
            'code' => 200,
            'message' => "Comment deleted successfully.",
        ], 200);
    }

    /**
     * @Route("/api/comment/{id}", name="update_comment", methods={"PATCH"}, requirements={"id"="\d+"})
     */
    public function updateCommentAction(Request $request, $id)
    {
        $this->checkRoleAndId($request);

        $code = 401;
        $message = "Unauthorized";
        $extras = NULL;

        $comment = $this->em->getRepository(Comment::class)->find($id);
        if (null === $comment)
            throw new HttpException(404, "Problematic not found.");

        $current = $this->cr->getCurrentUser($this);
        if ($current !== $comment->getOwner())
            throw new HttpException(401, "You are not the owner.");

        $data = json_decode($request->getContent(), true);
        if (null !== $data && !array_key_exists('text', $data))
            throw new HttpException(406, 'Field \'text\' not found.');
        $comment->setText($data['text']);
        $violations = $this->validator->validate($comment, null, "update-comment");
        if (count($violations) !== 0) {
            foreach ($violations as $violation) {
                $extras[$violation->getPropertyPath()] = $violation->getMessage();
            }
        } else {
            try {
                $this->em->persist($comment);
                $this->em->flush();
                $extras['id'] = $comment->getId();
            } catch (\Exception $ex) {
                throw new HttpException(400, $ex->getMessage());
            }
            $code = 201;
            $message = "Comment updated successfully";
        }
        
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'extras' => $extras
        ], $code);
    }

    /**
     * @Route("/api/problematic/{id}/comment", name="new_comment", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function newCommentAction(Request $request, $id)
    {
        $this->checkRoleAndId($request);

        $code = 401;
        $message = "Unauthorized";
        $extras = NULL;

        $problematic = $this->em->getRepository(Problematic::class)->find($id);
        if (null === $problematic)
            throw new HttpException(404, "Problematic not found.");

        $data = json_decode($request->getContent(), true);
        if (null !== $data && !array_key_exists('text', $data))
            throw new HttpException(406, 'Field \'text\' not found.');
        $comment = new Comment();
        $comment->setOwner($this->cr->getCurrentUser($this));
        $comment->setProblematic($problematic);
        $comment->setText($data['text']);
        $violations = $this->validator->validate($comment, null, "new-comment");
        if (count($violations) !== 0) {
            foreach ($violations as $violation) {
                $extras[$violation->getPropertyPath()] = $violation->getMessage();
            }
        } else {
            try {
                $this->em->persist($comment);
                $this->em->flush();
                $extras['id'] = $comment->getId();
            } catch (\Exception $ex) {
                throw new HttpException(400, $ex->getMessage());
            }
            $code = 201;
            $message = "Comment added successfully";
        }
        
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'extras' => $extras
        ], $code);
    }

    private function deleteVote($vote)
    {
        $code = 401;
        $message = "Unauthorized";

        if (null !== $vote) {
            try {
                $this->em->remove($vote);
                $this->em->flush();
                $code = 202;
                $message = "Vote deleted successfully";
            } catch (\Exception $ex) {
                throw new HttpException(400, $ex->getMessage());
            }
        } else {
            throw new HttpException(404, "Vote not found.");
        }

        return new JsonResponse([
            'code' => $code,
            'message' => $message
        ], $code);
    }

    /**
     * @Route("/api/problematic/{id}/comment", name="all_problematic_comments", methods={"PATCH", "GET"}, requirements={"id"="\d+"})
     */
    public function getAllProblematicCommentsAction(Request $request, $id)
    {
        $current = $this->cr->getCurrentUser($this);
        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);
        $problematic = $this->em->getRepository(Problematic::class)->find($id);
        if (null === $problematic)
            throw new HttpException(404, "Problematic not found.");
        $pager = $this->em->getRepository(Comment::class)->findComments($page, $limit, $problematic, $current);
        $results = $pager->getCurrentPageResults();
        $nbPages = $pager->getNbPages();
        $currentPage = $pager->getCurrentPage();
        $maxPerPage = $pager->getMaxPerPage();
        $itemsCount = $pager->count();
        $comments = array();
        foreach ($results as $result) {
            $comments[] = $result;
        }
        $data = array('nbPages' => $nbPages, 'currentPage' => $currentPage, 'maxPerPage' => $maxPerPage, 'itemsCount' => $itemsCount, 'comments' => $comments);
        $data = $this->serializer->serialize($data, 'json', SerializationContext::create()->setGroups(array('list-comments')));
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/comment/{id}/vote", name="new_vote_comment", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function newVoteCommentAction(Request $request, $id)
    {
        $this->checkRoleAndId($request);

        $code = 401;
        $message = "Unauthorized";
        $extras = NULL;
        $group = 'new-vote';
        $successMessage = "Vote added successfully";

        $comment = $this->em->getRepository(Comment::class)->find($id);
        if (null === $comment)
            throw new HttpException(404, "Comment not found.");

        $data = json_decode($request->getContent(), true);
        if (null !== $data && !array_key_exists('good', $data))
            throw new HttpException(406, 'Field \'good\' not found.');
        $vote = $this->em->getRepository(Vote::class)->findOneBy(["voter" => $this->cr->getCurrentUser($this), "comment" => $comment]);

        if (null !== $vote) {
            if ($vote->getGood() === $data['good']) {
                return $this->deleteVote($vote);
            }
            $vote->setGood($data['good']);
            $group = 'old-vote';
            $successMessage = "Vote updated successfully";
        } else {
            $vote = new Vote();
            $vote->setVoter($this->cr->getCurrentUser($this));
            $vote->setComment($comment);
            $vote->setGood($data['good']);
        }

        $violations = $this->validator->validate($vote, null, $group);
        if (count($violations) !== 0) {
            foreach ($violations as $violation) {
                $extras[$violation->getPropertyPath()] = $violation->getMessage();
            }
        } else {
            try {
                $this->em->persist($vote);
                $this->em->flush();
                $extras['id'] = $vote->getId();
                $code = 201;
                $message = $successMessage;
            } catch (\Exception $ex) {
                throw new HttpException(400, $ex->getMessage());
            }
        }
        
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'extras' => $extras
        ], $code);
    }

    /**
     * @Route("/api/problematic/{id}/vote", name="new_vote", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function newVoteAction(Request $request, $id)
    {
        $this->checkRoleAndId($request);

        $code = 401;
        $message = "Unauthorized";
        $extras = NULL;
        $group = 'new-vote';
        $successMessage = "Vote added successfully";

        $problematic = $this->em->getRepository(Problematic::class)->find($id);
        if (null === $problematic)
            throw new HttpException(404, "Problematic not found.");

        $data = json_decode($request->getContent(), true);
        if (null !== $data && !array_key_exists('good', $data))
            throw new HttpException(406, 'Field \'good\' not found.');
        $vote = $this->em->getRepository(Vote::class)->findOneBy(["voter" => $this->cr->getCurrentUser($this), "problematic" => $problematic]);

        if (null !== $vote) {
            if ($vote->getGood() === $data['good']) {
                return $this->deleteVote($vote);
            }
            $vote->setGood($data['good']);
            $group = 'old-vote';
            $successMessage = "Vote updated successfully";
        } else {
            $vote = new Vote();
            $vote->setVoter($this->cr->getCurrentUser($this));
            $vote->setProblematic($problematic);
            $vote->setGood($data['good']);
        }

        $violations = $this->validator->validate($vote, null, $group);
        if (count($violations) !== 0) {
            foreach ($violations as $violation) {
                $extras[$violation->getPropertyPath()] = $violation->getMessage();
            }
        } else {
            try {
                $this->em->persist($vote);
                $this->em->flush();
                $extras['id'] = $vote->getId();
                $code = 201;
                $message = $successMessage;
            } catch (\Exception $ex) {
                throw new HttpException(400, $ex->getMessage());
            }
        }
        
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'extras' => $extras
        ], $code);
    }

    /**
     * @Route("/api/problematic/{id}/count", name="all_problematic_votes", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getAllProblematicVotesAction(Request $request, $id)
    {
        $problematic = $this->em->getRepository(Problematic::class)->find($id);
        if (null === $problematic)
            throw new HttpException(404, "Problematic not found.");
        $results = $this->em->getRepository(Comment::class)->getCount($problematic);
        if (null !== $results['1'])
            $extras['countComments'] = $results['1'] + 0;
        else
            $extras['countComments'] = 0;
        $resultsGood = $this->em->getRepository(Vote::class)->getCountGood($problematic);
        $resultsNotGood = $this->em->getRepository(Vote::class)->getCountNotGood($problematic);
        if (null !== $resultsGood['1'] && null !== $resultsNotGood['1'])
            $extras['countVotes'] = $resultsGood['1'] - $resultsNotGood['1'];
        else
            $extras['countVotes'] = 0;

        return new JsonResponse([
            'code' => 200,
            'message' => 'Success',
            'extras' => $extras
        ], 200);
    }

    /**
     * @Route("/api/current/problematic/{id}/vote", name="current_problematic_vote", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getCurrentProblematicVoteAction(Request $request, $id)
    {
        $code = 200;
        $message = "You don't have any vote in this problematic";
        $extras = NULL;

        $problematic = $this->em->getRepository(Problematic::class)->find($id);
        if (null === $problematic)
            throw new HttpException(404, "Problematic not found.");
        $vote = $this->em->getRepository(Vote::class)->findOneBy(["voter" => $this->cr->getCurrentUser($this), "problematic" => $problematic]);
        if (null !== $vote) {
            $extras['vote'] = $vote->getGood();
            $message = 'Success';
        }

        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'extras' => $extras
        ], $code);
    }
}
