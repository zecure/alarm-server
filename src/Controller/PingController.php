<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;

class PingController extends Controller
{
    /**
     * @return Response
     */
    public function ping() : Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setLastPingAt(new \DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return new Response();
    }
}
