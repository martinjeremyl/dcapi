<?php

namespace App\Controller;

use App\Entity\User;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Mime\Email;

class AuthenticationController extends AbstractFOSRestController
{
    /**
     * @Post("/api/register", name="register")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $em = $this->getDoctrine()->getManager();
        $content = json_decode($request->getContent());
        $user = new User();
        $user->setNom($content->nom);
        $user->setPrenom($content->prenom);
        $user->setEmail($content->email);
        $user->setPassword($passwordEncoder->encodePassword($user, $content->password));
        $user->setRoles(['ROLE_CLIENT']);
        $em->persist($user);
        $em->flush();
        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'code' => 201,
            'item' => $user,
        ]);
    }

    /**
     * @Put("/api/user/update-main-informations", name="update-main-informations")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateMainInformationsAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if ($request->files->get('avatar') instanceof UploadedFile) {
            $file = $request->files->get('avatar');
            $fileId = uniqid();
            $destination = $this->getParameter('kernel.project_dir').'/public/uploads/avatar';
            $file->move($destination, uniqid());
            $user->setAvatar($destination.'/'.$fileId);
        }
        $user->setCouleur($request->get('couleur'));
        $user->setInstagram($request->get('instagram'));
        $user->setFacebook($request->get('facebook'));
        $user->setYoutube($request->get('youtube'));
        $user->setSoundcloud($request->get('soundcloud'));
        $user->setSpotify($request->get('spotify'));
        $user->setPortable($request->get('portable'));
        $user->setAdresse($request->get('adresse'));
        $user->setSite($request->get('site'));
        $em->flush();
        return $this->json(['message' => 'Utilisateur modifié !', 'status' => 200, 'item' => $user]);
    }

    /**
     * @Post("/api/user/avatar", name="update-avatar")
     */
    public function updateAvatarAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if ($request->files->get('avatar') instanceof UploadedFile) {
            $file = $request->files->get('avatar');
            $newFileName = uniqid().'.'.$file->guessExtension();
            $destination = $this->getParameter('kernel.project_dir').'/public/uploads/avatar';

            $file->move($destination, $newFileName);
            $user->setAvatar($newFileName);
        }
        $em->flush();
    }

    /**
     * @Post("/api/user/reset-password", name="update-avatar")
     * @param Request $request
     * @param MailerInterface $mailer
     */
    public function resetPasswordAction(Request $request, MailerInterface $mailer) {
        $em = $this->getDoctrine()->getManager();
        $content = json_decode($request->getContent());
        $user = $em->getRepository(User::class)->findOneByEmail($content->email);
        $email = (new Email())
            ->from('dcystems.noreply@gmail.com')
            ->to($user->getEmail())
            ->priority(Email::PRIORITY_HIGH)
            ->subject('Demande de réinitialisation pour votre mot de passe')
            ->html('Bonjour, pour modifier votre mot de passe merci de cliquer sur le lien suivant : <a href="http://localhost:4200/reset-password">réinitialiser mon mot de passe</a>')
        ;
        $mailer->send($email);
        return $this->json(['message' => 'Mail de réinitialisation envoyé !', 'status' => 200, 'item' => $user]);
    }
}
