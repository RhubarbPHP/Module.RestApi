<?php

namespace Rhubarb\RestApi\Resources;

use Rhubarb\Crown\Context;
use Rhubarb\Stem\Models\Model;

class SimpleModelItemRestResource extends ModelItemRestResource
{
    private $model;
    private $columns;
    private $summaryColumns;

    public function __construct(Model $model, $columns, $summaryColumns, $parentResource = null)
    {
        $this->model = $model;
        $this->columns = $columns;
        $this->summaryColumns = $summaryColumns;

        parent::__construct($model->UniqueIdentifier, $parentResource);
    }

    protected function getColumns()
    {
        return $this->columns;
    }

    protected function getSummaryColumns()
    {
        return $this->summaryColumns;
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * Returns the name of the model to use for this resource.
     *
     * @return string
     */
    public function getModelName()
    {
        return $this->model->getModelName();
    }

    /**
     * Calculates the correct and unique href property for this resource.
     *
     * @param string $nonCanonicalUrlTemplate If this resource has no canonical url template then you can supply one instead.
     * @return string
     */
    public function generateHref($nonCanonicalUrlTemplate = "")
    {
        $urlTemplate = RestResource::getCanonicalResourceUrl(get_class($this->parentResource));

        if (!$urlTemplate && $nonCanonicalUrlTemplate !== "") {
            $urlTemplate = $nonCanonicalUrlTemplate;
        }

        if ($urlTemplate) {
            $request = Context::currentRequest();

            $urlStub = (($request->Server("SERVER_PORT") == 443) ? "https://" : "http://") .
                $request->Server("HTTP_HOST");

            if ($this->id && $urlTemplate[strlen($urlTemplate) - 1] != "/") {
                return $urlStub . $urlTemplate . "/" . $this->id;
            }
        }

        return "";
    }
}