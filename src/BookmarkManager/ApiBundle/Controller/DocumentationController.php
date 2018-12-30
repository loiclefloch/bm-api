<?php

namespace BookmarkManager\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use BookmarkManager\ApiBundle\DependencyInjection\BaseController;

class DocumentationController extends BaseController
{
    /**
     * @Route("/")
     */
    public function showAction()
    {
        return $this->render('ApiBundle:Documentation:index.html.twig');
    }

}
