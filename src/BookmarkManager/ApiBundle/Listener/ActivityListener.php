<?php
namespace BookmarkManager\ApiBundle\Listener;

use Doctrine\ORM\EntityManager;
use FOS\OAuthServerBundle\Tests\Propel\Token;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use BookmarkManager\ApiBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;

class ActivityListener
{
    protected $context;
    protected $em;
    private $container;
    private $router;

    public function __construct(TokenStorage $tokenStorage, EntityManager $manager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $manager;
    }

    /**
     * Update the user "lastActivity" on each request
     * @param FilterControllerEvent $event
     */
    public function onCoreController(FilterControllerEvent $event)
    {
        // Request is a MASTER_REQUEST: ignore sub-requests such as render()
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }
        if ($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();

            // Update the user only if there is no activity in the last 2 minutes for better performances.
            $delay = new \DateTime();
            $delay->setTimestamp(strtotime('2 minutes ago'));
            if ($user instanceof User && $user->getLastActivity() < $delay) {
                $user->setIsActiveNow();
                $this->em->flush($user);
            }
        }
    }


    public function onKernelRequest(GetResponseEvent $event)
    {

    }
}