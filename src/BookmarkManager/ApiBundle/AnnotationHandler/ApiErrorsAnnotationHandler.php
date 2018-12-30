<?php

namespace BookmarkManager\ApiBundle\AnnotationHandler;

use Doctrine\ORM\Mapping\Annotation;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

use BookmarkManager\ApiBundle\Annotation\ApiErrors;

class ApiErrorsAnnotationHandler implements HandlerInterface {

    /**
     * Parse route parameters in order to populate ApiDoc.
     *
     * @param \Nelmio\ApiDocBundle\Annotation\ApiDoc $annotation
     * @param array $annotations
     * @param \Symfony\Component\Routing\Route $route
     * @param \ReflectionMethod $method
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        $result = "";

        foreach ($annotations as $annot) {

            // -- get ApiErrors annotation
            if ($annot instanceof ApiErrors) {
                $result = $result . $annot->getErrorsFormattedAsHtmlTable();
            }

        }

        if (strlen($result) > 0) {
            // We add our custom documentation to the documentation part.
            $documentation = $annotation->getDocumentation() . '<br />'
                . '<h4>Api error codes</h4>'
                . '<table>'
                . '<thead>
                    <tr>
                        <th>Code</th>
                        <th>Signification</th>
                    </tr>
                   </thead>
                   <tbody>'
                . $result
                . '</tbody>
                </table>'
                ;

            $annotation->setDocumentation($documentation);
        }
    }
}