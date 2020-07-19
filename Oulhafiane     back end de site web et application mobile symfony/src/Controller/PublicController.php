<?php

namespace App\Controller;

use App\Entity\MsgContactUs;
use App\Service\FormHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class PublicController extends AbstractController
{
    private $form;

    public function __construct(FormHandler $form)
    {
        $this->form = $form;
    }

    public function doNothing($object)
    {
        return false;
    }

    public function setOwner($msg)
    {
        return false;
    }

    /**
     * @Route("/api/public/contact-us", name="contact_us", methods={"POST"})
     */
    public function contactUsAction(Request $request)
    {
        return $this->form->validate($request, MsgContactUs::class, array($this, 'setOwner'), array($this, 'doNothing'), ['new-msg'], ['new-msg']);
    }
}
