<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Entity\User;
use App\Entity\Status;

class PingController extends Controller
{
    /**
     * @return Response
     * @throws ServiceUnavailableHttpException if alarm system is disabled
     */
    public function ping() : Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setLastPingAt(new \DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        /** @var \App\Repository\StatusRepository $statusRepository */
        $statusRepository = $this->getDoctrine()->getRepository(Status::class);
        $status = $statusRepository->getOrCreate();

        if (!$status->isEnabled()) {
            throw new ServiceUnavailableHttpException(60, 'Alarm is disabled');
        }

        return new Response();
    }
}
