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

            $alarm->setFile($this->getParameter('upload_directory') . '/' . $fileName);

            $em = $this->getDoctrine()->getManager();
            $alarm->setCreatedBy('no user system yet'); // TODO
            $em->persist($alarm);
            $em->flush();

            if ($this->getParameter('mailer_to')) {
                $message = (new \Swift_Message('Alarm'))
                    ->setFrom($this->getParameter('mailer_from'))
                    ->setTo($this->getParameter('mailer_to'))
                    ->setBody(
                        $this->renderView(
                        'alarm/email.txt.twig',
                            ['alarm' => $alarm]
                        ),
                        'text/plain'
                    );

                $mailer->send($message);
            }
        }

        return $this->render('alarm/upload.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
