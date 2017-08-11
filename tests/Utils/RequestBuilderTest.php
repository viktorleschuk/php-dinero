<?php

namespace LasseRafn\Dinero\Tests\Utils;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LasseRafn\Dinero\Builders\Builder;
use LasseRafn\Dinero\Builders\ContactBuilder;
use LasseRafn\Dinero\Exceptions\DineroRequestException;
use LasseRafn\Dinero\Exceptions\DineroServerException;
use LasseRafn\Dinero\Models\Contact;
use LasseRafn\Dinero\Requests\ContactRequestBuilder;
use LasseRafn\Dinero\Tests\TestCase;
use LasseRafn\Dinero\Utils\Request;
use LasseRafn\Dinero\Utils\RequestBuilder;

class RequestBuilderTest extends TestCase
{
	/** @var RequestBuilder */
	private $builder;

	public function setUp()
	{
		$this->builder = new RequestBuilder( new Builder( new Request() ) );

		parent::setUp(); // TODO: Change the autogenerated stub
	}

	/** @test */
	public function can_set_page()
	{
		$this->assertEquals( 0, $this->builder->getPage() );

		$this->builder->page( 2 );

		$this->assertEquals( 2, $this->builder->getPage() );
	}

	/** @test */
	public function can_set_perPage()
	{
		$this->assertEquals( 100, $this->builder->getPerPage() );

		$this->builder->perPage( 50 );

		$this->assertEquals( 50, $this->builder->getPerPage() );
	}

	/** @test */
	public function will_set_per_page_to_1000_if_set_above_because_of_dinero_limit()
	{
		$this->assertEquals( 100, $this->builder->getPerPage() );

		$this->builder->perPage( 5000 );

		$this->assertEquals( 1000, $this->builder->getPerPage() );
	}

	/** @test */
	public function can_select_specific_field()
	{
		$this->assertEquals( null, $this->builder->getSelectedFields() );

		$this->builder->select( 'id' );

		$this->assertEquals( [ 'id' ], $this->builder->getSelectedFields() );
	}

	/** @test */
	public function can_select_many_fields()
	{
		$this->assertEquals( null, $this->builder->getSelectedFields() );

		$this->builder->select( [ 'id', 'name' ] );

		$this->assertEquals( [ 'id', 'name' ], $this->builder->getSelectedFields() );
	}

	/** @test */
	public function can_get_deleted_only()
	{
		$this->assertEquals( 'false', $this->builder->getDeletedOnlyState() );

		$this->builder->deletedOnly();

		$this->assertEquals( 'true', $this->builder->getDeletedOnlyState() );

		$this->builder->notDeletedOnly();

		$this->assertEquals( 'false', $this->builder->getDeletedOnlyState() );
	}

	/** @test */
	public function can_set_changed_since()
	{
		$date = new \DateTime( '2017-01-01' );

		$this->builder->since( $date );

		$this->assertEquals( $date->format( 'Y-m-d' ), $this->builder->getSince() );
	}

	/** @test */
	public function can_find_model()
	{
		$expectedResponse = [
			'ContactGuid'                  => 'eff82399-c387-4cba-b2bb-06ad75849f63',
			'CreatedAt'                    => '2017-08-09T22:01:04.0368735+00:00',
			'UpdatedAt'                    => '2017-08-09T22:01:04.0368735+00:00',
			'DeletedAt'                    => '2017-08-09T22:01:04.0368735+00:00',
			'IsDebitor'                    => true,
			'IsCreditor'                   => true,
			'ExternalReference'            => 'Fx. WebShopID:42',
			'Name'                         => 'John Doe',
			'Street'                       => 'Main road 42',
			'ZipCode'                      => '2100',
			'City'                         => 'Copenhagen',
			'CountryKey'                   => 'DK',
			'Phone'                        => '+45 99 99 99 99',
			'Email'                        => 'test@test.com',
			'Webpage'                      => 'test.com',
			'AttPerson'                    => 'Donald Duck',
			'VatNumber'                    => '12345678',
			'EanNumber'                    => '1111000022223',
			'PaymentConditionType'         => 'Netto',
			'PaymentConditionNumberOfDays' => 8,
			'IsPerson'                     => false
		];

		$mock = new MockHandler( [
			new Response( 200, [], json_encode( $expectedResponse ) )
		] );

		$handler = HandlerStack::create( $mock );

		$builder = new ContactRequestBuilder( new ContactBuilder( new Request( '', '', null, null, [ 'handler' => $handler ] ) ) );

		/** @var Contact $contact */
		$contact = $builder->find( 'eff82399-c387-4cba-b2bb-06ad75849f63' );

		$this->assertSame( $expectedResponse['ContactGuid'], $contact->ContactGuid );
		$this->assertSame( $expectedResponse['CreatedAt'], $contact->CreatedAt );
		$this->assertSame( $expectedResponse['UpdatedAt'], $contact->UpdatedAt );
		$this->assertSame( $expectedResponse['DeletedAt'], $contact->DeletedAt );
		$this->assertSame( $expectedResponse['IsDebitor'], $contact->IsDebitor );
		$this->assertSame( $expectedResponse['IsCreditor'], $contact->IsCreditor );
		$this->assertSame( $expectedResponse['ExternalReference'], $contact->ExternalReference );
		$this->assertSame( $expectedResponse['Name'], $contact->Name );
		$this->assertSame( $expectedResponse['Street'], $contact->Street );
		$this->assertSame( $expectedResponse['ZipCode'], $contact->ZipCode );
		$this->assertSame( $expectedResponse['City'], $contact->City );
		$this->assertSame( $expectedResponse['CountryKey'], $contact->CountryKey );
		$this->assertSame( $expectedResponse['Phone'], $contact->Phone );
		$this->assertSame( $expectedResponse['Email'], $contact->Email );
		$this->assertSame( $expectedResponse['Webpage'], $contact->Webpage );
		$this->assertSame( $expectedResponse['AttPerson'], $contact->AttPerson );
		$this->assertSame( $expectedResponse['VatNumber'], $contact->VatNumber );
		$this->assertSame( $expectedResponse['EanNumber'], $contact->EanNumber );
		$this->assertSame( $expectedResponse['PaymentConditionType'], $contact->PaymentConditionType );
		$this->assertSame( $expectedResponse['PaymentConditionNumberOfDays'], $contact->PaymentConditionNumberOfDays );
		$this->assertSame( $expectedResponse['IsPerson'], $contact->IsPerson );
	}

	/** @test */
	public function can_fail_to_find_model_because_not_found()
	{
		$this->expectException( DineroRequestException::class );

		$mock = new MockHandler( [
			new Response( 404, [], json_encode( [ 'error' => 'Not found.' ] ) )
		] );

		$handler = HandlerStack::create( $mock );

		$builder = new ContactRequestBuilder( new ContactBuilder( new Request( '', '', null, null, [ 'handler' => $handler ] ) ) );

		$builder->find( '123' );
	}

	/** @test */
	public function can_fail_to_find_model_because_of_server_error()
	{
		$this->expectException( DineroServerException::class );

		$mock = new MockHandler( [
			new Response( 503, [], json_encode( [ 'error' => 'Server has drowned..' ] ) )
		] );

		$handler = HandlerStack::create( $mock );

		$builder = new ContactRequestBuilder( new ContactBuilder( new Request( '', '', null, null, [ 'handler' => $handler ] ) ) );

		$builder->find( '123' );
	}
}
