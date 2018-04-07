<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Alarm;
use App\Form\AlarmType;

class UploadController extends Controller
{
    /**
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @return Response
     */
    public function upload(Request $request, \Swift_Mailer $mailer) : Response
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
                )
                ->attach(\Swift_Attachment::fromPath($this->getParameter('upload_directory') . '/' . $fileName))
            ;

            $mailer->send($message);

            return new Response();
        }

        return $this->render('alarm/upload.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
