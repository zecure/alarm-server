<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Entity\Alarm;
use App\Form\AlarmType;
use App\Entity\User;
use App\Entity\Status;

class UploadController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function upload(Request $request) : Response
    {
        /** @var \App\Repository\StatusRepository $statusRepository */
        $statusRepository = $this->getDoctrine()->getRepository(Status::class);
        $status = $statusRepository->getOrCreate();

        if (!$status->isEnabled()) {
            throw new ServiceUnavailableHttpException(60, 'Alarm is disabled');
        }

        $alarm = new Alarm();
        $form = $this->createForm(AlarmType::class, $alarm);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('alarm/upload.html.twig', [
                'form' => $form->createView()
            ]);
        }

        if (!$form->isValid()) {
            throw new BadRequestHttpException('Invalid form');
        }

        /** @var \App\Repository\AlarmRepository $alarmRepository */
        $alarmRepository = $this->getDoctrine()->getRepository(Alarm::class);
        if ($alarmRepository->hasTooMany($this->getUser(), $this->getParameter('alarm_threshold'))) {
            throw new TooManyRequestsHttpException();
        }

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        $file = $alarm->getFile();
        $fileName = hash('sha256', random_bytes(16)) . '.' . $file->guessExtension();

        $file->move(
            $this->getParameter('upload_directory'),
            $fileName
        );
        $newPath = $this->getParameter('upload_directory') . '/' . $fileName;

        $alarm->setFile($fileName);
        $alarm->setFileName($file->getClientOriginalName());
        $alarm->setCreatedBy($this->getUser()->getUsername());

        $em = $this->getDoctrine()->getManager();
        $em->persist($alarm);
        $em->flush();

        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        $adminUsers = $userRepository->findAdmins();

        foreach ($adminUsers as $adminUser) {
            $this->notify($adminUser, $alarm, $newPath);
        }

        return new Response();
    }

    /**
     * @param User $adminUser
     * @param Alarm $alarm
     * @param string $newPath
     */
    private function notify(User $adminUser, Alarm $alarm, string $newPath)
    {
        /** @var \Swift_Mailer $mailer */
        $mailer = $this->get('mailer');
        $message = (new \Swift_Message($this->getParameter('mail_subject_alarm')))
            ->setFrom($this->getParameter('mailer_from'))
            ->setTo($adminUser->getEmail())
            ->setBody(
                $this->renderView(
                    'alarm/email.txt.twig',
                    ['alarm' => $alarm]
                ),
                'text/plain'
            )
            ->attach(\Swift_Attachment::fromPath($newPath))
        ;

        $mailer->send($message);
    }
}
