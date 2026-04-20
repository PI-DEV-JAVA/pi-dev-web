<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/switch-locale/{locale}', name: 'switch_locale')]
    public function switchLocale(string $locale, Request $request): Response
    {
        // Only allow fr and en
        if (!in_array($locale, ['fr', 'en'])) {
            $locale = 'fr';
        }

        // Store in session
        $request->getSession()->set('_locale', $locale);

        // Redirect back to the page user came from
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_home'));
    }
}
