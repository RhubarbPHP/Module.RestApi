<?php

namespace Rhubarb\Crown\RestApi\Presenters;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Leaf\Presenters\Forms\Form;
use Rhubarb\Crown\Integration\Http\HttpRequest;
use Rhubarb\Crown\RestApi\Clients\RestHttpRequest;
use Rhubarb\Crown\RestApi\Clients\TokenAuthenticatedRestClient;

class TestHarnessPresenter extends Form
{
	private $_lastResponse = "";

	protected function ConfigureView()
	{
		parent::ConfigureView();

		$this->view->AttachEventHandler( "SubmitRequest", function()
		{
			$client = new TokenAuthenticatedRestClient(
				$this->model->ApiUrl,
				$this->model->Username,
				$this->model->Password,
				"/tokens"
			);

			$request = new RestHttpRequest( $this->model->Uri, $this->model->Method, $this->model->RequestPayload );

			if ( $this->model->Since )
			{
				$since = new RhubarbDateTime( $this->model->Since );

				$request->AddHeader( "If-Modified-Since", $since->format( "r" ) );
			}

			$this->_lastResponse = $client->MakeRequest( $request );
		});
	}

	protected function CreateView()
	{
		return new TestHarnessView();
	}

	protected function ApplyModelToView()
	{
		parent::ApplyModelToView();

		$this->view->SetResponse( $this->_lastResponse );
	}


}