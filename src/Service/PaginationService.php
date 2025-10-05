<?php

namespace App\Service;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginationService
{
    private PaginatorInterface $paginator;
    private RequestStack $requestStack;

    public function __construct(PaginatorInterface $paginator, RequestStack $requestStack)
    {
        $this->paginator = $paginator;
        $this->requestStack = $requestStack;
    }

    /**
     * Paginer une requête Doctrine ou un tableaukkkk
     *dddd
     * @param mixed $target QueryBuilder, Query ou array
     * @param int|null $page
     * @param int|null $limit
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function paginate($target, ?int $page = null, ?int $limit = null)
    {
        $request = $this->requestStack->getCurrentRequest();

        $page = $page ?? (int) $request->query->get('page', 1);
        $limit = $limit ?? (int) $request->query->get('limit', 10);

        return $this->paginator->paginate($target, $page, $limit);
    }
}
