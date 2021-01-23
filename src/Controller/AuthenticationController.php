<?php

namespace App\Controller;

use App\Entity\User;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Serializer\SerializerInterface;

class AuthenticationController extends AbstractFOSRestController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
       $this->serializer = $serializer;
    }

    /**
     * @Get("/api/user/public-informations/{slug}", name="publicInformations")
     */
    public function publicInformationsAction(Request $request, string $slug) {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findOneBySlug($slug);
        return $this->json([
            'message' => 'Utilisateur récupéré avec succès',
            'code' => 200,
            'item' => $user,
        ]);    }
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
        $content = json_decode($request->getContent());
        $user = $this->getUser();
        $user->setCouleur($content->couleur);
        $user->setInstagram($content->instagram);
        $user->setFacebook($content->facebook);
        $user->setYoutube($content->youtube);
        $user->setSoundcloud($content->soundcloud);
        $user->setSpotify($content->spotify);
        $user->setPortable($content->telephone);
        $user->setAdresse($content->adresse);
        $user->setSite($content->site);
        $user->setHasCompletedInformations(true);
        $em->flush();
        return $this->json(['message' => 'Utilisateur modifié !', 'status' => 200, 'item' => $user]);
    }

    /**
     * @Post("/api/user/avatar", name="update-avatar")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateAvatarAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
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
        return $this->json(['message' => 'Avatar modifié !', 'status' => 200, 'item' => $user]);
    }

    /**
     * @Post("/api/user/reset-password", name="reset-password")
     * @param Request $request
     * @param MailerInterface $mailer
     */
    public function resetPasswordAction(Request $request, MailerInterface $mailer): \Symfony\Component\HttpFoundation\JsonResponse
    {
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

    /**
     * @Get("/api/user", name="get-user")
     */
    public function getUserInformationsAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $user = $this->getUser();
            return $this->json(['message' => 'Utilisateur récupéré !', 'status' => 200, 'item' => $user]);
    }

    /**
     * @Get("/api/user/avatar", name="get-user-avatar")
     */
    public function getUserAvatarAction(Request $request): BinaryFileResponse
    {
        $user = $this->getUser();
        $filePath = $this->getParameter('kernel.project_dir').'/public/uploads/avatar/' . $user->getAvatar();
        $response = new BinaryFileResponse($filePath);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $user->getAvatar(),
            iconv('UTF-8', 'ASCII//TRANSLIT', $user->getAvatar())
        );
        return $response;
    }
}
