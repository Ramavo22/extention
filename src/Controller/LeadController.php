<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LeadController extends AbstractController
{
   private HttpClientInterface $client;

   public function __construct(HttpClientInterface $client)
   {
      $this->client = $client;
   }

   #[Route('/lead', name: 'app_lead')]
   public function index(Request $request): Response
   {
      try {
         $jwt = $request->getSession()->get('jwt');

         if (!$jwt) {
            return $this->redirectToRoute('app_login');
         }

         $response = $this->client->request('GET', 'http://crm-backend:8080/api/lead', [
             'headers' => [
                 'Authorization' => 'Bearer ' . $jwt,
                 'Accept' => 'application/json',
             ],
         ]);

         if ($response->getStatusCode() === 200) {
            $data = $response->toArray();
            $leads = $data['data']['leads'] ?? [];

            return $this->render('lead/index.html.twig', [
                'leads' => $leads,
            ]);
         }

         if ($response->getStatusCode() === 401) {
            $request->getSession()->remove('jwt');
            return $this->redirectToRoute('app_login', [
                '_fragment' => 'unauthorized',
            ]);
         }

         // Pour toute autre erreur (ex: 403, 404, 500...)
         return $this->render('error/custom_error.html.twig', [
             'code' => $response->getStatusCode(),
             'message' => $response->getContent(false),
         ]);

      } catch (\Exception $e) {
         // Gestion des erreurs (ex: serveur indisponible)
         return $this->render('error/custom_error.html.twig', [
             'code' => 500,
             'message' => 'Erreur serveur : ' . $e->getMessage(),
         ]);
      }
   }


   #[Route('/lead/delete/{id}', name: 'lead_delete', methods: ['GET'])]
   public function delete(int $id, Request $request): Response
   {
      try {
         $jwt = $request->getSession()->get('jwt');

         if (!$jwt) {
            return $this->redirectToRoute('app_login');
         }

         // Envoi de la requête de suppression au service Spring
         $response = $this->client->request('GET', 'http://crm-backend:8080/api/lead/delete/' . $id, [
             'headers' => [
                 'Authorization' => 'Bearer ' . $jwt,
                 'Accept' => 'application/json',
             ],
         ]);

         // Vérifier la réponse du backend Spring
         if ($response->getStatusCode() === 200) {
            // Réponse réussie, suppression réussie
            $data = $response->toArray();
            $this->addFlash('success', 'lead deleted successfully.');

            // Rediriger vers la page des tickets
            return $this->redirectToRoute('app_lead');
         }

         if ($response->getStatusCode() === 404) {
            // Ticket non trouvé
            $data = $response->toArray();
            $this->addFlash('error', $data['message']);

            // Rediriger vers la page des tickets
            return $this->redirectToRoute('app_lead');
         }

         if ($response->getStatusCode() === 500) {
            // Erreur interne du serveur
            $data = $response->toArray();
            $this->addFlash('error', $data['message']);

            // Rediriger vers la page des tickets
            return $this->redirectToRoute('app_lead');
         }

         // Pour toute autre erreur (ex: 403, 401, etc.)
         $this->addFlash('error', 'Une erreur est survenue, veuillez réessayer.');

         return $this->redirectToRoute('app_lead');

      } catch (\Exception $e) {
         // Gestion des exceptions (problème réseau, service Spring inaccessible, etc.)
         $this->addFlash('error', 'Erreur serveur : ' . $e->getMessage());

         return $this->redirectToRoute('app_lead');
      }
   }


   #[Route('lead/update-expense', name: 'lead_update_expense', methods: ['POST'])]
   public function updateLeadExpense(Request $request, HttpClientInterface $client): Response
   {
      try {
         // Vérifier que le JWT est présent dans la session
         $jwt = $request->getSession()->get('jwt');
         if (!$jwt) {
            return $this->redirectToRoute('app_login');
         }

         // Récupérer les données envoyées par le formulaire
         $id = $request->request->get('leadId');
         $newExpense = $request->request->get('newExpense');

         // Vérifier que les données sont bien présentes
         if (!$id || !$newExpense) {
            $this->addFlash('error', 'Données invalides.');
            return $this->redirectToRoute('lead_list');
         }

         // Envoyer la requête à l'API Spring Boot
         $response = $client->request('PUT', 'http://crm-backend:8080/api/lead/update', [
             'headers' => [
                 'Authorization' => 'Bearer ' . $jwt,
                 'Content-Type' => 'application/json',
             ],
             'json' => [
                 'id' => (int) $id,
                 'depense' => (float) $newExpense,
             ],
         ]);

         // Vérifier la réponse de Spring Boot
         $responseData = $response->toArray();

         if ($response->getStatusCode() === 200 && isset($responseData['success']) && $responseData['success']) {
            $this->addFlash('success', 'Dépense mise à jour avec succès !');
         } else {
            $this->addFlash('error', $responseData['message'] ?? 'Erreur lors de la mise à jour.');
         }

      } catch (\Exception $e) {
         $this->addFlash('error', 'Erreur serveur : ' . $e->getMessage());
      }

      return $this->redirectToRoute('app_lead');
   }


}
