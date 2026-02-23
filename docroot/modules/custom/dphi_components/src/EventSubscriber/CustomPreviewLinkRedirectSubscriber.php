<?php

namespace Drupal\dphi_components\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CustomPreviewLinkRedirectSubscriber implements EventSubscriberInterface {

  protected $currentPath;
  protected $routeMatch;
  protected $urlGenerator;
  protected $currentUser;

  public function __construct(CurrentPathStack $currentPath, RouteMatchInterface $routeMatch, $urlGenerator, AccountProxyInterface $currentUser) {
    $this->currentPath = $currentPath;
    $this->routeMatch = $routeMatch;
    $this->urlGenerator = $urlGenerator;
    $this->currentUser = $currentUser;
  }


  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['checkPreviewAccess', 30],
    ];
  }

  public function checkPreviewAccess(RequestEvent $event) {
      
    $request = $event->getRequest();
    $path = $this->currentPath->getPath();
    
    

    // Check if it's a preview link and user is anonymous
    if (str_contains($path, '/preview-link') && $this->currentUser->isAnonymous()) {
    
      $destination = $path;
      $login_url = Url::fromRoute('user.login', [], ['query' => ['destination' => $destination]])->toString();
      $response = new RedirectResponse($login_url);
      $response->send();
    }
  }
}
