<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Crown\RestApi\Modelling;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\Boolean;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\DateTime;
use Rhubarb\Stem\Repositories\MySql\Schema\Index;
use Rhubarb\Stem\Repositories\MySql\Schema\MySqlSchema;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * A simple model class that extends the schema to include columns that track modifications and deletions
 */
abstract class ApiModel extends Model
{
    protected function extendSchema(ModelSchema $schema)
    {
        parent::extendSchema($schema);

        $schema->addColumn(
            new DateTime("DateModified"),
            new DateTime("DateCreated"),
            new Boolean("Deleted")
        );

        if ($schema instanceof MySqlSchema) {
            $schema->addIndex(new Index("DateModified", Index::INDEX));
            $schema->addIndex(new Index("Deleted", Index::INDEX));
        }
    }

    /**
     * Replaces the standard delete by flagging the entry deleted instead.
     */
    public function delete()
    {
        $this->Deleted = true;
        $this->save();

        $this->onDeleted();
    }

    protected function beforeSave()
    {
        parent::beforeSave();

        $this->DateModified = "now";

        if ($this->isNewRecord()) {
            $this->DateCreated = "now";
        }
    }
}