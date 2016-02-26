<?php

namespace ArsThanea\KunstmaanExtraBundle\Twig;

use ArsThanea\KunstmaanExtraBundle\ContentType\PageContentTypeInterface;
use ArsThanea\KunstmaanExtraBundle\SiteTree\SiteTreeService;
use Kunstmaan\NodeBundle\Entity\HasNodeInterface;

class SiteTreeTwigExtension extends \Twig_Extension
{
    /**
     * @var PageContentTypeInterface
     */
    private $contentType;

    /**
     * @var SiteTreeService
     */
    private $siteTree;

    public function __construct(PageContentTypeInterface $contentType, SiteTreeService $siteTree)
    {
        $this->contentType = $contentType;
        $this->siteTree = $siteTree;
    }

    public function getFunctions()
    {
        return [
            'get_page_children' => new \Twig_SimpleFunction('get_page_children', [$this, 'getPageChildren']),
        ];
    }

    public function getPageChildren(HasNodeInterface $page, $ofType = null, array $options = [])
    {
        return $this->siteTree->getChildren($page, $options + [
                'depth' => 0,
                'refName' => $this->getRefNames($ofType),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'site_tree_twig';
    }

    private function getRefNames($ofType)
    {
        if (null === $ofType) {
            return null;
        }

        return array_filter(array_map(function ($name) {
            $contentTypeClass = $this->contentType->getContentTypeClass($name);

            if (!$contentTypeClass) {
                throw new \RuntimeException(sprintf('Class of type "%s" not found', $name));
            }

            return $contentTypeClass;
        }, (array)$ofType));
    }
}
