<?php
namespace Jhg\DoctrineUtilsBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\CommonException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

abstract class AbstractAdvancedDoctrineRepository extends EntityRepository
{
	/**
	 * @var String entity alias for use in queries
	 */
	protected $entityAlias = null;
	
	/**
	 * @var array
	 */
	protected $defaultOrderBy = array('id'=>'asc');
	
	
	/**
	 * Verifies that __construct method has configure required properties
	 * @throws CommonException
	 */
	protected function verifyRepositoryConstruction () {
		$calledClassName = get_called_class();
		
		if(!$this->entityAlias) {
			throw new CommonException("The entity repository '$calledClassName' has not an entity alias");
		}
		
		if(strlen($this->entityAlias)>1) {
			throw new CommonException("The entity alias has more than 1 character for '$calledClassName'");
		}
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see \Doctrine\ORM\EntityRepository::findOneBy()
	 */
	public function findOneBy(array $criteria=array(), array $orderBy=null) {
		// verifies repository
		$this->verifyRepositoryConstruction();
		
		// create query builder
		$qb = $this->createQueryBuilder($this->entityAlias);
		
		// configures query
		$this->preProcessQB($qb);
		$this->processOrderBy($qb,$orderBy);
		$this->processCriteria($qb, $criteria);
	
		// limits to first result
		$qb->setMaxResults(1);
	
		return $qb->getQuery()->getSingleResult();
	}
	
	
	/**
	 * @param number $page
	 * @param number $rpp
	 * @param array $criteria
	 * @param array $orderBy
	 * @return ArrayCollection
	 */
	public function findPageBy($page=1,$rpp=10,array $criteria=array(), array $orderBy=array()) {
		// verifies repository
		$this->verifyRepositoryConstruction();
		
		// create query builder
		$qb = $this->createQueryBuilder($this->entityAlias);
		
		// configures query
		$this->preProcessQB($qb);
		$this->processOrderBy($qb,$orderBy);
		$this->processPagination($qb,$page,$rpp);
		$this->processCriteria($qb, $criteria);
	
		// dump SQL for query develop
		// echo $qb->getQuery()->getSQL(); exit();
		
		// execute query and return result
		$query = $qb->getQuery();
		return new ArrayCollection($query->getResult());
	}
	
	
	/**
	 * @param array $criteria
	 * @return integer
	 */
	public function countBy(array $criteria=array()) {
		// verifies repository
		$this->verifyRepositoryConstruction();
		
		// create query builder
		$qb = $this->createQueryBuilder($this->entityAlias);
		
		// configures query
		$this->preProcessQB($qb);
		$this->processCriteria($qb, $criteria);
	
		// add count expression
		$qb->add('select', $qb->expr()->count($this->entityAlias));
	
		return $qb->getQuery()->getSingleScalarResult();
	}
	
	
	/**
	 * @param array $criteria
	 * @param array $orderBy
	 * @return ArrayCollection
	 */
	public function findAllBy(array $criteria=array(), array $orderBy=array('id'=>'asc')) {
		return $this->findPageBy(1,null,$criteria,$orderBy);
	}
	
	
	/**
	 * Preprocesses the query builder
	 * @param QueryBuilder $qb
	 */
	protected function preProcessQB(QueryBuilder $qb) {}
	
	
	/**
	 * Processes the order by array in the query
	 * @param mixed $orderBy
	 * @param QueryBuilder $qb
	 */
	protected function processOrderBy(QueryBuilder $qb,$orderBy) {
		if(!empty($orderBy)) {
			foreach($orderBy as $field=>$order) 
				$qb->addOrderBy("$this->entityAlias.$field",$order);
		}
		else {
			foreach($this->defaultOrderBy as $field=>$order) 
				$qb->addOrderBy("$this->entityAlias.$field",$order);
		}
	}
	
	
	/**
	 * Processes the pagination parameters
	 * @param QueryBuilder $qb
	 * @param integer $page
	 * @param integer $rpp
	 */
	protected function processPagination(QueryBuilder $qb,$page,$rpp) {
		if ($rpp) {
			// set offset
			$qb->setFirstResult(($page-1)*$rpp);
		
			// set rpp
			$qb->setMaxResults($rpp);
		}
	}
	
	
	/**
	 * Processes the search criteria
	 * @param QueryBuilder $qb
	 * @param array $criteria
	 */
	abstract protected function processCriteria(QueryBuilder $qb, array $criteria);
}