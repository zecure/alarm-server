<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Alarm;
use AppBundle\Form\AlarmType;

class AlarmController extends Controller
{
    /**
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @return Response
     * @Route("/upload", name="upload")
     */
    public function uploadAction(Request $request, \Swift_Mailer $mailer)
    {
        $alarm = new Alarm();
        $form = $this->createForm(AlarmType::class, $alarm);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $alarm->getFile();
            $fileName = hash('sha256', random_bytes(16)) . '.' . $file->guessExtension();

            $file->move(
                $this->getParameter('upload_directory'),
                $fileName
            );

            $alarm->setFile($fileName);
            $alarm->setFileName($file->getClientOriginalName());
            $alarm->setCreatedBy($this->getUser()->getUsername());

            $em = $this->getDoctrine()->getManager();
            $em->persist($alarm);
            $em->flush();

            $message = (new \Swift_Message('Alarm'))
                ->setFrom($this->getParameter('mailer_from'))
                ->setTo($this->getUser()->getEmail())
                ->setBody(
                    $this->renderView(
                    'alarm/email.txt.twig',
                        ['alarm' => $alarm]
                    ),
                    'text/plain'
                );

            $mailer->send($message);
        }

        return $this->render('alarm/upload.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
