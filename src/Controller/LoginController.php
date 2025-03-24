<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LoginController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/', name: 'app_login', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('login/index.html.twig',[
            'erreur' => null,
            'statut' => null,
        ]);
    }

    #[Route('/', name: 'app_login_post', methods: ['POST'])]
    public function traiterLogin(Request $request): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        try {
            $response = $this->client->request('POST', 'http://crm-backend:8080/api/login', [
                'json' => [
                    'username' => $username,
                    'password' => $password,
                ]
            ]);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $data = $response->toArray();
                $token = $data['token']; // supposons que Spring renvoie un JWT
                $request->getSession()->set('jwt', $token);
                return $this->redirectToRoute('admin_dashboard');
            } else {
                $message = $response->getContent(false);
                return $this->render('login/index.html.twig', [
                    'erreur' => 'Erreur : ' . $message,
                    'statut' => $statusCode,
                ]);
            }
        } catch (\Exception $e) {
            return $this->render('login/index.html.twig', [
                'erreur' => 'Connexion au serveur échouée.',
                'statut' => 'Erreur : ' . $e->getMessage(),
            ]);
        }
    }
}
