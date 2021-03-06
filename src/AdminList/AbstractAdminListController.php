<?php

namespace ArsThanea\KunstmaanExtraBundle\AdminList;

use Kunstmaan\AdminListBundle\Controller\AdminListController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractAdminListController extends AdminListController
{
    const OPERATOR_UP = '<=';
    const OPERATOR_DOWN = '>=';

    private $operatorModifiers = [
        self::OPERATOR_UP => -1,
        self::OPERATOR_DOWN => 1,
    ];

    private $operatorDirection = [
        self::OPERATOR_UP => 'DESC',
        self::OPERATOR_DOWN => 'ASC',
    ];

    abstract public function getAdminListConfigurator();

    public function indexAction(Request $request)
    {
        return parent::doIndexAction($this->getAdminListConfigurator(), $request);
    }

    public function addAction(Request $request)
    {
        return parent::doAddAction($this->getAdminListConfigurator(), null, $request);
    }

    public function editAction(Request $request, $id)
    {
        return parent::doEditAction($this->getAdminListConfigurator(), $id, $request);
    }

    public function viewAction(Request $request, $id)
    {
        return parent::doViewAction($this->getAdminListConfigurator(), $id, $request);
    }

    public function deleteAction(Request $request, $id)
    {
        return parent::doDeleteAction($this->getAdminListConfigurator(), $id, $request);
    }

    public function exportAction(Request $request, $_format)
    {
        return parent::doExportAction($this->getAdminListConfigurator(), $_format, $request);
    }

    public function moveUpAction(Request $request, $id)
    {
        return parent::doMoveUpAction($this->getAdminListConfigurator(), $id, $request);
    }

    public function moveDownAction(Request $request, $id)
    {
        return parent::doMoveDownAction($this->getAdminListConfigurator(), $id, $request);
    }

    protected function moveItem(SortableInterface $configurator, $item, $operator)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $em = $this->getEntityManager();
        $field = $configurator->getSortableField();
        $qb = $configurator->getQueryBuilder(true);

        $otherItem = $qb
            ->andWhere(sprintf('b.%s %s :weight', $field, $operator))
            ->andWhere('b.id != :self')
            ->setParameter('weight', $accessor->getValue($item, $field))
            ->setParameter('self', $accessor->getValue($item, 'id'))
            ->orderBy(sprintf('b.%s', $field), $this->operatorDirection[$operator])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($otherItem) {
            $accessor->setValue($otherItem, $field, $accessor->getValue($item, $field));
            $accessor->setValue($item, $field, $accessor->getValue($item, $field) + $this->operatorModifiers[$operator]);

            $em->persist($otherItem);
            $em->persist($item);
            $em->flush();
        }

        $indexUrl = $configurator->getIndexUrl();

        return $this->redirect(
            $this->generateUrl($indexUrl['path'], isset($indexUrl['params']) ? $indexUrl['params'] : [])
        );
    }
}
