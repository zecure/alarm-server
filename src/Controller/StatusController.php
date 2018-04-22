<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Status;
use App\Form\StatusType;
use App\Form\ApiStatusType;
use App\Entity\User;

class StatusController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/status")
     */
    public function status(Request $request) : Response
    {
        /** @var \App\Repository\StatusRepository $statusRepository */
        $statusRepository = $this->getDoctrine()->getRepository(Status::class);
        $status = $statusRepository->getOrCreate();

        $form = $this->createForm(StatusType::class, $status);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                throw new BadRequestHttpException('Invalid form');
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($status);
            $em->flush();
        }

        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $alarmUsers = $userRepository->findByRole('ROLE_ALARM');

        return $this->render('status/show.html.twig', [
            'status' => $status,
            'alarmUsers' => $alarmUsers,
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/api/status")
     */
    public function apiStatus(Request $request) : Response
    {
        /** @var \App\Repository\StatusRepository $statusRepository */
        $statusRepository = $this->getDoctrine()->getRepository(Status::class);
        $status = $statusRepository->getOrCreate();

        $form = $this->createForm(ApiStatusType::class, $status);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            throw new BadRequestHttpException('Form not found');
        }

        if (!$form->isValid()) {
            throw new BadRequestHttpException('Invalid form');
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($status);
        $em->flush();

        return new Response();
    }
}
