<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'app_connect_google')]
    public function connect(): Response
    {
        $clientId = $this->getParameter('app.google_client_id');
        $redirectUri = $this->getParameter('app.google_redirect_uri');

        $url = 'https://accounts.google.com/o/oauth2/v2/auth'
            . '?client_id=' . urlencode($clientId)
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&response_type=code'
            . '&scope=' . urlencode('openid email profile')
            . '&access_type=offline'
            . '&prompt=consent';

        return $this->redirect($url);
    }

    #[Route('/connect/google/check', name: 'app_connect_google_check')]
    public function check(
        Request $request,
        EntityManagerInterface $em,
        HttpClientInterface $httpClient
    ): Response {
        $code = $request->query->get('code');
        $error = $request->query->get('error');

        if ($error || !$code) {
            $this->addFlash('danger', 'Authentification Google annulée.');
            return $this->redirectToRoute('app_login');
        }

        $clientId = $this->getParameter('app.google_client_id');
        $clientSecret = $this->getParameter('app.google_client_secret');
        $redirectUri = $this->getParameter('app.google_redirect_uri');

        try {
            // Exchange code for access token
            $tokenResponse = $httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $tokenData = $tokenResponse->toArray();
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                throw new \RuntimeException('No access token received.');
            }

            // Fetch user info
            $userInfoResponse = $httpClient->request('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);

            $userInfo = $userInfoResponse->toArray();
            $googleId = $userInfo['id'] ?? null;
            $email = $userInfo['email'] ?? null;

            if (!$googleId || !$email) {
                throw new \RuntimeException('Could not retrieve Google user info.');
            }

            // Find or create user
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setRole('CANDIDATE');
                $user->setAuthProvider('GOOGLE');
                $user->setProviderId($googleId);
                $user->setActive(true);
                $user->setEmailVerified(true); // Google emails are pre-verified
                $user->setPassword(null); // No local password for OAuth users

                // Create profile
                $profile = new Profile();
                $profile->setUser($user);
                $profile->setProfileCompleted(false);

                // Set name from Google if available
                if (isset($userInfo['given_name'])) {
                    $profile->setFirstName($userInfo['given_name']);
                }
                if (isset($userInfo['family_name'])) {
                    $profile->setLastName($userInfo['family_name']);
                }

                $em->persist($user);
                $em->persist($profile);
                $em->flush();
            } else {
                // Existing user — update Google provider info if needed
                if ($user->getAuthProvider() === 'LOCAL') {
                    // Link Google to existing local account
                    $user->setAuthProvider('GOOGLE');
                    $user->setProviderId($googleId);
                    $user->setEmailVerified(true);
                    $em->flush();
                }
            }

            // Log the user in programmatically
            // Symfony's security token approach
            $token = new \Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken(
                $user, 'main', $user->getRoles()
            );
            $this->container->get('security.token_storage')->setToken($token);
            $request->getSession()->set('_security_main', serialize($token));

            // Reset failed attempts on successful Google auth
            $user->setFailedAttempts(0);
            $user->setActive(true);
            $em->flush();

            // Redirect based on role
            if (in_array($user->getRole(), ['ADMIN', 'HR'])) {
                return $this->redirectToRoute('admin_dashboard');
            }

            return $this->redirectToRoute('app_home');

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur d\'authentification Google: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}
