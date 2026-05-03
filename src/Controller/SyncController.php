<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\Sync;
use App\Entity\SyncMessage;
use App\Entity\User;
use App\Entity\Application;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncController extends AbstractController
{
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PEOPLE DISCOVERY — /connect
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/connect', name: 'app_connect')]
    public function connect(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $search = trim($request->query->get('q', ''));
        $tab = $request->query->get('tab', 'discover');

        // Get all syncs involving current user
        $allSyncs = $em->getRepository(Sync::class)->createQueryBuilder('s')
            ->where('s.sender = :user OR s.receiver = :user')
            ->setParameter('user', $user)
            ->getQuery()->getResult();

        // Build maps
        $connectedIds = [];
        $pendingSentIds = [];
        $pendingReceived = [];
        $connections = [];

        foreach ($allSyncs as $sync) {
            $other = $sync->getOtherUser($user);
            if (!$other) continue;
            $oid = $other->getId();

            if ($sync->getStatus() === 'ACCEPTED') {
                $connectedIds[$oid] = true;
                $connections[] = ['sync' => $sync, 'user' => $other];
            } elseif ($sync->getStatus() === 'PENDING') {
                if ($sync->getSender() === $user) {
                    $pendingSentIds[$oid] = true;
                } else {
                    $pendingReceived[] = ['sync' => $sync, 'user' => $other];
                }
            }
        }

        // Discover: all users with completed profiles, excluding self and already connected/pending
        $excludeIds = array_merge(array_keys($connectedIds), array_keys($pendingSentIds), [$user->getId()]);
        foreach ($pendingReceived as $pr) { $excludeIds[] = $pr['user']->getId(); }

        $qb = $em->createQueryBuilder()
            ->select('u', 'p')
            ->from(User::class, 'u')
            ->join('u.profile', 'p')
            ->where('u.id NOT IN (:exclude)')
            ->setParameter('exclude', $excludeIds ?: [0]);

        if ($search) {
            $qb->andWhere('p.firstName LIKE :q OR p.lastName LIKE :q OR p.professionalTitle LIKE :q OR p.location LIKE :q OR u.email LIKE :q')
               ->setParameter('q', "%{$search}%");
        }

        $discoverUsers = $qb->orderBy('u.id', 'DESC')->setMaxResults(30)->getQuery()->getResult();

        // Unread message counts per sync
        $unreadCounts = [];
        if ($connections) {
            $syncIds = array_map(fn($c) => $c['sync']->getId(), $connections);
            $unreadRows = $em->createQueryBuilder()
                ->select('IDENTITY(sm.sync) as syncId, COUNT(sm.id) as cnt')
                ->from(SyncMessage::class, 'sm')
                ->where('sm.sync IN (:ids)')
                ->andWhere('sm.sender != :me')
                ->andWhere('sm.isRead = false')
                ->setParameter('ids', $syncIds)
                ->setParameter('me', $user)
                ->groupBy('sm.sync')
                ->getQuery()->getResult();
            foreach ($unreadRows as $r) { $unreadCounts[$r['syncId']] = (int)$r['cnt']; }
        }

        // Last message per connection
        $lastMessages = [];
        foreach ($connections as $c) {
            $lastMsg = $em->getRepository(SyncMessage::class)->findOneBy(
                ['sync' => $c['sync']], ['createdAt' => 'DESC']
            );
            $lastMessages[$c['sync']->getId()] = $lastMsg;
        }

        return $this->render('front/connect.html.twig', [
            'tab' => $tab,
            'search' => $search,
            'discoverUsers' => $discoverUsers,
            'connections' => $connections,
            'pendingReceived' => $pendingReceived,
            'pendingSentIds' => $pendingSentIds,
            'connectedIds' => $connectedIds,
            'unreadCounts' => $unreadCounts,
            'lastMessages' => $lastMessages,
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  CONNECTION REQUESTS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/connect/{id}/request', name: 'app_sync_request', methods: ['POST'])]
    public function sendRequest(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $target = $em->getRepository(User::class)->find($id);
        if (!$target || $target === $user) {
            return $this->jsonOrRedirect($request, 'Invalid user', 'app_connect');
        }

        // Check if sync already exists
        $existing = $em->getRepository(Sync::class)->createQueryBuilder('s')
            ->where('(s.sender = :a AND s.receiver = :b) OR (s.sender = :b AND s.receiver = :a)')
            ->setParameter('a', $user)->setParameter('b', $target)
            ->getQuery()->getOneOrNullResult();

        if ($existing) {
            return $this->jsonOrRedirect($request, 'Already connected or pending', 'app_connect');
        }

        $reason = $request->request->get('reason', 'NETWORK');

        $sync = new Sync();
        $sync->setSender($user);
        $sync->setReceiver($target);
        $sync->setReason($reason);
        $sync->setStatus('PENDING');
        $em->persist($sync);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['ok' => true, 'status' => 'PENDING']);
        }
        $this->addFlash('success', 'Demande de connexion envoyée !');
        return $this->redirectToRoute('app_connect');
    }

    #[Route('/connect/{id}/accept', name: 'app_sync_accept', methods: ['POST'])]
    public function acceptRequest(Sync $sync, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($sync->getReceiver() !== $user || $sync->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException();
        }
        $sync->setStatus('ACCEPTED');
        $sync->setAcceptedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Connexion acceptée !');
        return $this->redirectToRoute('app_connect', ['tab' => 'requests']);
    }

    #[Route('/connect/{id}/decline', name: 'app_sync_decline', methods: ['POST'])]
    public function declineRequest(Sync $sync, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($sync->getReceiver() !== $user || $sync->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException();
        }
        $em->remove($sync);
        $em->flush();

        $this->addFlash('success', 'Demande refusée.');
        return $this->redirectToRoute('app_connect', ['tab' => 'requests']);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PUBLIC PROFILE
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/user/{id}', name: 'app_user_profile', requirements: ['id' => '\d+'])]
    public function userProfile(int $id, EntityManagerInterface $em): Response
    {
        $target = $em->getRepository(User::class)->find($id);
        if (!$target) { throw $this->createNotFoundException(); }

        $user = $this->getUser();
        $syncStatus = null;
        $syncObj = null;

        if ($user && $user !== $target) {
            $sync = $em->getRepository(Sync::class)->createQueryBuilder('s')
                ->where('(s.sender = :a AND s.receiver = :b) OR (s.sender = :b AND s.receiver = :a)')
                ->setParameter('a', $user)->setParameter('b', $target)
                ->getQuery()->getOneOrNullResult();

            if ($sync) {
                $syncStatus = $sync->getStatus();
                $syncObj = $sync;
            }
        }

        return $this->render('front/user_profile.html.twig', [
            'targetUser' => $target,
            'syncStatus' => $syncStatus,
            'syncObj' => $syncObj,
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  MESSAGING HUB — /messages
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/messages', name: 'app_messages')]
    public function messages(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Get all accepted syncs (conversations)
        $syncs = $em->getRepository(Sync::class)->createQueryBuilder('s')
            ->where('(s.sender = :user OR s.receiver = :user) AND s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'ACCEPTED')
            ->orderBy('s.acceptedAt', 'DESC')
            ->getQuery()->getResult();

        // Build conversation list with last message + unread count
        $conversations = [];
        foreach ($syncs as $sync) {
            $other = $sync->getOtherUser($user);
            $lastMsg = $em->getRepository(SyncMessage::class)->findOneBy(
                ['sync' => $sync], ['createdAt' => 'DESC']
            );
            $unread = $em->getRepository(SyncMessage::class)->count([
                'sync' => $sync, 'sender' => $other, 'isRead' => false
            ]);
            $conversations[] = [
                'sync' => $sync,
                'user' => $other,
                'lastMessage' => $lastMsg,
                'unread' => $unread,
            ];
        }

        // Sort by last message date (most recent first)
        usort($conversations, function($a, $b) {
            $aDate = $a['lastMessage'] ? $a['lastMessage']->getCreatedAt() : $a['sync']->getAcceptedAt();
            $bDate = $b['lastMessage'] ? $b['lastMessage']->getCreatedAt() : $b['sync']->getAcceptedAt();
            return $bDate <=> $aDate;
        });

        // Active chat
        $activeSync = null;
        $activeMessages = [];
        $activeOther = null;
        $activeSyncId = $request->query->get('chat');

        if ($activeSyncId) {
            $activeSync = $em->getRepository(Sync::class)->find($activeSyncId);
            if ($activeSync && ($activeSync->getSender() === $user || $activeSync->getReceiver() === $user)) {
                $activeOther = $activeSync->getOtherUser($user);
                $activeMessages = $em->getRepository(SyncMessage::class)->findBy(
                    ['sync' => $activeSync], ['createdAt' => 'ASC']
                );
                // Mark as read
                $em->createQueryBuilder()
                    ->update(SyncMessage::class, 'sm')
                    ->set('sm.isRead', 'true')
                    ->where('sm.sync = :sync AND sm.sender != :me AND sm.isRead = false')
                    ->setParameter('sync', $activeSync)
                    ->setParameter('me', $user)
                    ->getQuery()->execute();
            }
        } elseif (!empty($conversations)) {
            // Auto-open first conversation
            return $this->redirectToRoute('app_messages', ['chat' => $conversations[0]['sync']->getId()]);
        }

        return $this->render('front/messages.html.twig', [
            'conversations' => $conversations,
            'activeSync' => $activeSync,
            'activeMessages' => $activeMessages,
            'activeOther' => $activeOther,
        ]);
    }

    #[Route('/messages/{id}/send', name: 'app_message_send', methods: ['POST'])]
    public function sendMessage(Sync $sync, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($sync->getSender() !== $user && $sync->getReceiver() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('message', ''));
        if ($content) {
            $msg = new SyncMessage();
            $msg->setSync($sync);
            $msg->setSender($user);
            $msg->setMessage($content);
            $em->persist($msg);
            $em->flush();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['ok' => true]);
        }
        return $this->redirectToRoute('app_messages', ['chat' => $sync->getId()]);
    }

    #[Route('/messages/{id}/poll', name: 'app_messages_poll', methods: ['GET'])]
    public function poll(Sync $sync, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if ($sync->getSender() !== $user && $sync->getReceiver() !== $user) {
            return new JsonResponse(['error' => 'forbidden'], 403);
        }

        $afterId = (int)$request->query->get('after', 0);

        $qb = $em->getRepository(SyncMessage::class)->createQueryBuilder('sm')
            ->where('sm.sync = :sync')
            ->setParameter('sync', $sync)
            ->orderBy('sm.createdAt', 'ASC');

        if ($afterId > 0) {
            $qb->andWhere('sm.id > :after')->setParameter('after', $afterId);
        }

        $messages = $qb->getQuery()->getResult();

        // Mark incoming as read
        foreach ($messages as $m) {
            if ($m->getSender() !== $user && !$m->isRead()) {
                $m->setIsRead(true);
            }
        }
        $em->flush();

        $data = [];
        foreach ($messages as $m) {
            $data[] = [
                'id' => $m->getId(),
                'message' => $m->getMessage(),
                'isMine' => $m->getSender() === $user,
                'senderName' => $m->getSender()->getProfile() ? $m->getSender()->getProfile()->getFullName() : $m->getSender()->getEmail(),
                'time' => $m->getCreatedAt()->format('H:i'),
                'date' => $m->getCreatedAt()->format('Y-m-d'),
            ];
        }

        return new JsonResponse($data);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  LEGACY — keep old routes working
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/my-circle', name: 'app_my_circle')]
    public function circle(): Response
    {
        return $this->redirectToRoute('app_connect', ['tab' => 'connections']);
    }

    #[Route('/chat/{id}', name: 'app_chat', requirements: ['id' => '\d+'])]
    public function chat(Sync $sync): Response
    {
        return $this->redirectToRoute('app_messages', ['chat' => $sync->getId()]);
    }

    #[Route('/chat/{id}/send', name: 'app_chat_send', methods: ['POST'])]
    public function chatSendLegacy(Sync $sync, Request $request, EntityManagerInterface $em): Response
    {
        return $this->sendMessage($sync, $request, $em);
    }

    // ── Helper ──
    private function jsonOrRedirect(Request $request, string $msg, string $route): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => $msg], 400);
        }
        $this->addFlash('danger', $msg);
        return $this->redirectToRoute($route);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  CHAT WIDGET API — /api/chat/contacts
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/api/chat/contacts', name: 'api_chat_contacts', methods: ['GET'])]
    public function chatContacts(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse([], 401);

        $syncs = $em->getRepository(Sync::class)->createQueryBuilder('s')
            ->where('(s.sender = :user OR s.receiver = :user) AND s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'ACCEPTED')
            ->orderBy('s.acceptedAt', 'DESC')
            ->getQuery()->getResult();

        $contacts = [];
        foreach ($syncs as $sync) {
            $other = $sync->getOtherUser($user);
            if (!$other) continue;

            $lastMsg = $em->getRepository(SyncMessage::class)->findOneBy(
                ['sync' => $sync], ['createdAt' => 'DESC']
            );
            $unread = $em->getRepository(SyncMessage::class)->count([
                'sync' => $sync, 'sender' => $other, 'isRead' => false
            ]);

            $profile = $other->getProfile();
            $name = ($profile && $profile->getFirstName())
                ? $profile->getFirstName() . ' ' . $profile->getLastName()
                : explode('@', $other->getEmail())[0];
            $avatar = ($profile && $profile->getProfilePicturePath()) ? $profile->getProfilePicturePath() : '';
            $initial = strtoupper(substr($other->getEmail(), 0, 1));

            $contacts[] = [
                'syncId' => $sync->getId(),
                'userId' => $other->getId(),
                'name' => $name,
                'avatar' => $avatar,
                'initial' => $initial,
                'lastMsg' => $lastMsg ? (mb_strlen($lastMsg->getMessage()) > 40 ? mb_substr($lastMsg->getMessage(), 0, 40) . '…' : $lastMsg->getMessage()) : null,
                'lastTime' => $lastMsg ? $lastMsg->getCreatedAt()->format('H:i') : null,
                'unread' => $unread,
            ];
        }

        // Sort: conversations with unread first, then by last message time
        usort($contacts, function($a, $b) {
            if ($a['unread'] > 0 && $b['unread'] === 0) return -1;
            if ($b['unread'] > 0 && $a['unread'] === 0) return 1;
            return 0;
        });

        return new JsonResponse($contacts);
    }
}
