<?php

namespace Rhubarb\Crown\RestApi\UrlHandlers;

use Rhubarb\Crown\RestApi\Resources\RestResource;

class RestApiRootHandler extends RestResourceHandler
{
    public function SetUrl($url)
    {
        parent::SetUrl($url);

        foreach( $this->_childUrlHandlers as $childHandler )
        {
            if ( $childHandler instanceof RestCollectionHandler || $childHandler instanceof RestResourceHandler )
            {
                // Register this handler to make sure it's url is known
                RestResource::RegisterCanonicalResourceUrl( $childHandler->GetRestResourceClassName(), $url.$childHandler->GetUrl() );
            }
        }
    }
} 