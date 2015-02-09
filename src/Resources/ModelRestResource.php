<?php

namespace Rhubarb\Crown\RestApi\Resources;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Relationships\ManyToMany;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Crown\RestApi\Exceptions\RestImplementationException;
use Rhubarb\Crown\RestApi\Exceptions\RestRequestPayloadValidationException;
use Rhubarb\Crown\RestApi\Exceptions\UpdateException;
use Rhubarb\Crown\RestApi\UrlHandlers\RestHandler;

/**
 * An ApiResource that wraps a business model and provides some of the heavy lifting.
 *
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
abstract class ModelRestResource extends RestResource
{
	private static $_modelToResourceMapping = [];

	public function __construct( $resourceIdentifier = null, $parentResource = null )
	{
		parent::__construct( $resourceIdentifier, $parentResource );
	}

	public static function RegisterModelToResourceMapping( $modelName, $resourceClassName )
	{
		self::$_modelToResourceMapping[ $modelName ] = $resourceClassName;
	}

	public static function GetRestResourceForModel( Model $model )
	{
		$modelName = $model->GetModelName();

		if ( !isset( self::$_modelToResourceMapping[ $modelName ] ) )
		{
			throw new RestImplementationException( "The model $modelName does not have an associated rest resource." );
		}

		$class = self::$_modelToResourceMapping[ $modelName ];

		$resource = new $class();
		$resource->SetModel( $model );

		return $resource;
	}

	public static function GetRestResourceForModelName( $modelName )
	{
		if ( !isset( self::$_modelToResourceMapping[ $modelName ] ) )
		{
			return false;
		}

		$class = self::$_modelToResourceMapping[ $modelName ];

		$resource = new $class();

		return $resource;
	}

	public static function ClearRestResources()
	{
		self::$_modelToResourceMapping = [];
	}

    protected function CreateModelCollection()
    {
        // If we have a parent resource we will look to see if we can exploit a relationship
        // to use as our starting collection. This will ensure we only serve the correct
        // resources
        if ( $this->_parentResource instanceof ModelRestResource )
        {
            // See there is a relationship between these two models that can be exploited
            $parentModelName = $this->_parentResource->GetModelName();
            $relationships = SolutionSchema::GetAllRelationshipsForModel( $parentModelName );

            // Our model name
            $modelName = $this->GetModelName();

            foreach( $relationships as $relationship )
            {
				if ( $relationship instanceof ManyToMany )
				{
					if ( $relationship->GetRightModelName() == $modelName )
					{
						return $relationship->FetchFor( $this->_parentResource->GetModel() );
					}
				}

                if ( $relationship instanceof OneToMany )
                {
                    if ( $relationship->GetTargetModelName() == $modelName )
                    {
                        return $relationship->FetchFor( $this->_parentResource->GetModel() );
                    }
                }
            }
        }

        return new Collection( $this->GetModelName() );
    }

    protected function GetModelCollection()
    {
        $collection = $this->CreateModelCollection();

        $this->FilterModelCollectionForSecurity( $collection );

        return $collection;
    }

	public function GetCollection()
	{
		return new ModelRestCollection( $this, $this->_parentResource, $this->GetModelCollection() );
	}

	protected function GetModelAsResource( $columns )
	{
		$model = $this->GetModel();

		$publicData = $model->ExportData();

		$extract = [];

		$relationships = null;

		foreach( $columns as $label => $column )
		{
			$apiLabel = ( is_numeric( $label ) ) ? $column : $label;

			if ( isset( $publicData[ $column ] ) )
			{
				$extract[ $apiLabel ] = $publicData[ $column ];
			}
			else
			{
				if ( $relationships == null )
				{
					$relationships = SolutionSchema::GetAllRelationshipsForModel( $model->GetModelName() );
				}

				// Look for resource modifiers after the column name
				$modifier = "";
				$urlSuffix = false;
				$relatedField = false;

				if ( stripos( $column, ":" ) !== false )
				{
					$parts = explode( ":", $column );
					$column = $parts[0];

					if ( is_numeric( $label ) )
					{
						$apiLabel = $column;
					}

					$modifier = strtolower( $parts[1] );

					if ( sizeof( $parts ) > 2 )
					{
						$urlSuffix = $parts[2];
					}
				}
				else if ( stripos( $column, "." ) !== false )
				{
					$parts = explode( ".", $column, 2 );
					$column = $parts[0];
					$relatedField = $parts[1];

					if ( is_numeric( $label ) )
					{
						$apiLabel = $parts[1];
					}
				}

				if ( isset( $relationships[ $column ] ) )
				{
					$navigationValue = $model[ $column ];
					$navigationResource = false;

					if ( $navigationValue instanceof Model )
					{
						if ( $relatedField )
						{
							eval( '$extract[ $apiLabel ] = $navigationValue->'.str_replace( ".", "->", $relatedField ).';' );
							continue;
						}

						$navigationResource = self::GetRestResourceForModel( $navigationValue );

						if ( $navigationResource === false )
						{
							throw new RestImplementationException( print_r( $navigationValue, true ) );
							continue;
						}
					}

					if ( $navigationValue instanceof Collection )
					{
						$navigationResource = self::GetRestResourceForModelName( SolutionSchema::GetModelNameFromClass( $navigationValue->GetModelClassName() ) );

						if ( $navigationResource === false )
						{
							continue;
						}

						$navigationResource = $navigationResource->GetCollection();
						$navigationResource->SetModelCollection( $navigationValue );
					}

					if ( $navigationResource )
					{
						switch( $modifier )
						{
							case "summary":
								$extract[ $apiLabel ] = $navigationResource->Summary();
								break;
							case "link":
								$link = $navigationResource->Link();

								if ( $urlSuffix != "" )
								{
									$ourHref = $this->GetHref( $_SERVER[ "SCRIPT_NAME" ] );

									// Override the href with this appendage instead.
									$link->href = $ourHref.$urlSuffix;
								}

								$extract[ $apiLabel ] = $link;

								break;
							default:
								$extract[ $apiLabel ] = $navigationResource->Get();
								break;
						}
					}
				}
			}
		}

		return $extract;
	}

	public function Summary( RestHandler $handler = null )
	{
		$resource = parent::Get( $handler );

		$data = $this->GetModelAsResource( $this->GetSummaryColumns() );

		foreach( $data as $key => $value )
		{
			$resource->$key = $value;
		}

		return $resource;
	}

	public function Get( RestHandler $handler = null )
	{
		$resource = parent::Get( $handler );

		$data = $this->GetModelAsResource( $this->GetColumns() );

		foreach( $data as $key => $value )
		{
			$resource->$key = $value;
		}

		return $resource;
	}

	public function Head( RestHandler $handler = null )
	{
		$resource = parent::Get( $handler );

        if ( !isset( $resource->resource ) )
        {
            $resource->resource = new \stdClass();
        }

		$data = $this->GetModelAsResource( $this->GetHeadColumns() );

		foreach( $data as $key => $value )
		{
			$resource->resource->$key = $value;
		}

		return $resource;
	}

    /**
     * Override to control the columns returned in HEAD requests
     *
     * @return string[]
     */
    protected function GetSummaryColumns()
	{
		$model = $this->GetModel();
		return [ $model->GetLabelColumnName() ];
	}

    /**
     * Override to control the columns returned in GET requests
     *
     * @return string[]
     */
    protected function GetColumns()
	{
		$model = $this->GetModel();

		return $model->PublicPropertyList;
	}

	private $_model = false;

	/**
	 * Get's the Model object used to populate the REST resource
	 *
	 * This is public as it is sometimes required by child handlers to check security etc.
	 *
	 * @throws \Rhubarb\Crown\RestApi\Exceptions\RestImplementationException
	 * @return Collection|null
	 */
	public function GetModel()
	{
		if ( $this->_model === false )
		{
			$this->_model = SolutionSchema::GetModel( $this->GetModelName(), $this->_id );
		}

		if ( !$this->_model )
		{
			throw new RestImplementationException( "There is no matching resource for this url" );
		}

		return $this->_model;
	}

    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public abstract function GetModelName();

	/**
	 * Sets the model that should be used for the operations of this resource.
	 *
	 * This is normally only used by collections for efficiency (to avoid constructing additional objects)
	 *
	 * @param Model $model
	 */
	public function SetModel( Model $model )
	{
		$this->_model = $model;

		$this->SetID( $model->UniqueIdentifier );
	}

	/**
	 * Override to respond to the event of a new model being created through a POST
	 *
	 * @param $model
	 * @param $restResource
	 */
	public function AfterModelCreated( $model, $restResource )
	{

	}

	/**
	 * Override to response to the event of a model being updated through a PUT
	 *
	 * @param $model
	 * @param $restResource
	 */
	protected function BeforeModelUpdated( $model, $restResource )
	{

	}

	public function Put( $restResource, RestHandler $handler = null )
	{
		try
		{
			$model = $this->GetModel();
			$model->ImportData( $restResource );

			$this->BeforeModelUpdated( $model, $restResource );

			$model->Save();

			return true;
		}
		catch( RecordNotFoundException $er )
		{
			throw new UpdateException( "That record could not be found." );
		}
		catch( \Exception $er )
		{
			throw new UpdateException( $er->getMessage() );
		}
	}

	public function Delete( RestHandler $handler = null )
	{
		try
		{
			$model = $this->GetModel();
			$model->Delete();

			return true;
		}
		catch( \Exception $er )
		{
			return false;
		}
	}

	/**
	 * Override to filter a model collection to apply any necessary filters only when this is the specific resource being fetched
	 *
	 * The default handling applies the same filters as FilterModelCollectionContainer, so don't call the parent implementation unless you want that.
	 *
	 * @param Collection $collection
	 */
	public function FilterModelResourceCollection( Collection $collection )
	{
		$this->FilterModelCollectionContainer( $collection );
	}

	/**
	 * Override to filter a model collection to apply any necessary filters only when this is a REST parent of the specific resource being fetched
	 *
	 * @param Collection $collection
	 */
	public function FilterModelCollectionContainer( Collection $collection )
	{
	}

	public function FilterModelCollectionForModifiedSince( Collection $collection, RhubarbDateTime $since )
	{
		throw new RestImplementationException( "A collection filtered by modified date was requested however this resource does not support it." );
	}

    /**
     * Override to filter a model collection generated by a ModelRestCollection
     *
     * Normally used by root collections to filter based on authentication permissions.
     *
     * @param Collection $collection
     */
	public function FilterModelCollectionForSecurity( Collection $collection )
    {

    }
}