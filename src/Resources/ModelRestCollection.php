<?php

namespace Rhubarb\Crown\RestApi\Resources;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Crown\RestApi\Exceptions\InsertException;
use Rhubarb\Crown\RestApi\Exceptions\RestRequestPayloadValidationException;
use Rhubarb\Crown\RestApi\UrlHandlers\RestHandler;

/**
 *
 *
 * @package Rhubarb\Crown\RestApi\Resources
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
class ModelRestCollection extends RestCollection
{
	private $_collection = null;

    public function __construct($restResource, RestResource $parentResource = null, Collection $itemsCollection )
    {
        parent::__construct($restResource, $parentResource);

        $this->_collection = $itemsCollection;
    }

    public function SetModelCollection( Collection $collection )
	{
		$this->_collection = $collection;
	}

    /**
     * @return \Rhubarb\Stem\Collections\Collection|null
     */
    public function GetModelCollection()
    {
        return $this->_collection;
    }

	public function ContainsResourceIdentifier($resourceIdentifier)
	{
		$collection = clone $this->GetModelCollection();

		if ( $this->_restResource instanceof ModelRestResource )
		{
			$this->_restResource->FilterModelCollectionContainer( $collection );
		}

		$collection->Filter( new Equals( $collection->GetModelSchema()->uniqueIdentifierColumnName, $resourceIdentifier ) );

		if ( count( $collection ) > 0 )
		{
			return true;
		}

		return false;
	}

	protected function GetItems( $from, $to, RhubarbDateTime $since = null )
	{
		if ( $this->_restResource instanceof ModelRestResource )
		{
			$collection = $this->GetModelCollection();

			$this->_restResource->FilterModelResourceCollection( $collection );

			if ( $since !== null )
			{
				$this->_restResource->FilterModelCollectionForModifiedSince( $collection, $since );
			}

			$pageSize = ( $to - $from ) + 1;
			$collection->SetRange( $from, $pageSize );

			$items = [];

			foreach( $collection as $model )
			{
				$this->_restResource->SetModel( $model );

				$modelStructure = $this->_restResource->Get();
				$items[] = $modelStructure;
			}

			return [ $items, sizeof( $collection ) ];
		}

		return parent::GetItems( $from, $to );
	}


	public function Post( $restResource, RestHandler $handler = null  )
	{
		try
		{
			$newModel = SolutionSchema::GetModel( $this->_restResource->GetModelName() );

			if ( is_array( $restResource ) )
			{
				$newModel->ImportData( $restResource );
			}

			$newModel->Save();

			$this->_restResource->AfterModelCreated( $newModel, $restResource );

			$this->_restResource->SetModel( $newModel );

			return $this->_restResource->Get();
		}
		catch( RecordNotFoundException $er )
		{
			throw new InsertException( "That record could not be found." );
		}
		catch( \Exception $er )
		{
			throw new InsertException( $er->getMessage() );
		}
	}
}