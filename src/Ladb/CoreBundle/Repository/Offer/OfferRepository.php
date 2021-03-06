<?php

namespace Ladb\CoreBundle\Repository\Offer;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ladb\CoreBundle\Repository\AbstractEntityRepository;
use Ladb\CoreBundle\Entity\Offer\Offer;
use Ladb\CoreBundle\Entity\Core\User;

class OfferRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ) );
	}

	/////

	public function findOneByIdJoinedOnUser($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->where('o.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneByIdJoinedOnOptimized($id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'uav', 'mp', 'bbs', 'tgs' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->innerJoin('u.avatar', 'uav')
			->leftJoin('o.mainPicture', 'mp')
			->leftJoin('o.bodyBlocks', 'bbs')
			->leftJoin('o.tags', 'tgs')
			->where('o.id = :id')
			->setParameter('id', $id)
		;

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneFirstByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->andWhere('o.user = :user')
			->orderBy('o.id', 'ASC')
			->setParameter('user', $user)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneLastByUser(User $user) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->andWhere('o.user = :user')
			->orderBy('o.id', 'DESC')
			->setParameter('user', $user)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOnePreviousByUserAndId(User $user, $id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->andWhere('o.user = :user')
			->andWhere('o.id < :id')
			->orderBy('o.id', 'DESC')
			->setParameter('user', $user)
			->setParameter('id', $id)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	public function findOneNextByUserAndId(User $user, $id) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->andWhere('o.user = :user')
			->andWhere('o.id > :id')
			->orderBy('o.id', 'ASC')
			->setParameter('user', $user)
			->setParameter('id', $id)
			->setMaxResults(1);

		try {
			return $queryBuilder->getQuery()->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByIds(array $ids) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where($queryBuilder->expr()->in('o.id', $ids))
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	private function _applyCommonFilter(&$queryBuilder, $filter) {
		if ('popular-views' == $filter) {
			$queryBuilder
				->addOrderBy('o.viewCount', 'DESC')
			;
		} else if ('popular-likes' == $filter) {
			$queryBuilder
				->addOrderBy('o.likeCount', 'DESC')
			;
		} else if ('popular-comments' == $filter) {
			$queryBuilder
				->addOrderBy('o.commentCount', 'DESC')
			;
		}
		$queryBuilder
			->addOrderBy('o.changedAt', 'DESC')
		;
	}

	public function findPagined($offset, $limit, $filter = 'recent') {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('o.isDraft = false')
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

	public function findPaginedByUser(User $user, $offset, $limit, $filter = 'recent', $includeDrafts = false) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'o', 'u', 'mp' ))
			->from($this->getEntityName(), 'o')
			->innerJoin('o.user', 'u')
			->leftJoin('o.mainPicture', 'mp')
			->where('u = :user')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;

		if ('draft' == $filter && $includeDrafts) {
			$queryBuilder
				->andWhere('o.isDraft = true')
			;
		} else if (!$includeDrafts) {
			$queryBuilder
				->andWhere('o.isDraft = false')
			;
		}

		$this->_applyCommonFilter($queryBuilder, $filter);

		return new Paginator($queryBuilder->getQuery());
	}

}