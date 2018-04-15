<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Entity\Status;
use App\Form\StatusType;

class StatusController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function status(Request $request) : Response
    {
        /** @var \App\Repository\StatusRepository $statusRepository */
        $statusRepository = $this->getDoctrine()->getRepository(Status::class);
        $status = $statusRepository->getOrCreate();

        $form = $this->createForm(StatusType::class, $status);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('alarm/status.html.twig', [
                'form' => $form->createView()
            ]);
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
