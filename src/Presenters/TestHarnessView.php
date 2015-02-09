<?php

namespace Rhubarb\Crown\RestApi\Presenters;

use Rhubarb\Leaf\Presenters\Controls\Buttons\Button;
use Rhubarb\Leaf\Presenters\Controls\DateTime\Date;
use Rhubarb\Leaf\Presenters\Controls\Selection\DropDown\DropDown;
use Rhubarb\Leaf\Presenters\Controls\Text\TextArea\TextArea;
use Rhubarb\Leaf\Presenters\Controls\Text\TextBox\TextBox;
use Rhubarb\Leaf\Views\HtmlView;

class TestHarnessView extends HtmlView
{
	private $_response;

	public function SetResponse( $response )
	{
		$this->_response = $response;
	}

	public function CreatePresenters()
	{
		parent::CreatePresenters();

		$this->AddPresenters(
			new TextBox( "ApiUrl" ),
			new TextBox( "Uri" ),
			new TextBox( "Username" ),
			new TextBox( "Password" ),
			$method = new DropDown( "Method" ),
			new TextArea( "RequestPayload", 5, 60 ),
			new Date( "Since" ),
			new Button( "Submit", "Submit", function()
			{
				$this->RaiseEvent( "SubmitRequest" );
			})
		);

		$method->SetSelectionItems(
			[
				[ "get" ],
				[ "post" ],
				[ "put" ],
				[ "head" ],
				[ "delete" ]
			]
		);
	}

	protected function PrintViewContent()
	{
		parent::PrintViewContent();

		$this->PrintFieldset(
			"",
			[
				"ApiUrl",
				"Username",
				"Password",
				"Uri",
				"Method",
				"RequestPayload",
				"Since",
				"Submit"
			]
		);

		if ( $this->_response )
		{
			print "<h2>Response</h2>";

			$pretty = json_encode( $this->_response, JSON_PRETTY_PRINT );

			print "<pre>".$pretty."</pre>";
		}
	}


} 