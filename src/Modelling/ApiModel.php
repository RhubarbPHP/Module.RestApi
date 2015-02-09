<?php

namespace Rhubarb\Crown\RestApi\Modelling;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Boolean;
use Rhubarb\Stem\Repositories\MySql\Schema\Index;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\DateTime;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * A simple model class that extends the schema to include columns that track modifications and deletions
 */
abstract class ApiModel extends Model
{
	protected function ExtendSchema(ModelSchema $schema)
	{
		parent::ExtendSchema($schema);

		$schema->AddColumn(
			new DateTime( "DateModified" ),
			new DateTime( "DateCreated" ),
			new Boolean( "Deleted" )
		);

		if ( $schema instanceof MySqlSchema )
		{
			$schema->AddIndex( new Index( "DateModified", Index::INDEX ) );
			$schema->AddIndex( new Index( "Deleted", Index::INDEX ) );
		}
	}

	/**
	 * Replaces the standard delete by flagging the entry deleted instead.
	 */
	public function Delete()
	{
		$this->Deleted = true;
		$this->Save();

		$this->OnDeleted();
	}

	protected function BeforeSave()
	{
		parent::BeforeSave();

		$this->DateModified = "now";

		if ( $this->IsNewRecord() )
		{
			$this->DateCreated = "now";
		}
	}


}