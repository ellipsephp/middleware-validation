<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;

use Ellipse\Validation\AbstractValidationMiddleware;
use Ellipse\Validation\ValidatorFactory;
use Ellipse\Validation\Validator;
use Ellipse\Validation\ValidationResult;
use Ellipse\Validation\Exceptions\DataInvalidException;

class TestMiddleware extends AbstractValidationMiddleware
{
    public function getRules(): array
    {
        return [];
    }
}

describe('AbstractValidationMiddleware', function () {

    beforeEach(function () {

        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->handler = Mockery::mock(RequestHandlerInterface::class);

        $this->factory = Mockery::mock(ValidatorFactory::class);

        $this->middleware = Mockery::mock(AbstractValidationMiddleware::class . '[getRules, getLabels, getTemplates]', [
            $this->factory,
        ]);

    });

    afterEach(function () {

        Mockery::close();

    });

    it('should implements MiddlewareInterface', function () {

        expect($this->middleware)->to->be->an->instanceof(MiddlewareInterface::class);

    });

    describe('::create()', function () {

        it('should return a new AbstractValidationMiddleware', function () {

            $test = TestMiddleware::create();

            expect($test)->to->be->an->instanceof(AbstractValidationMiddleware::class);

        });

    });

    describe('::withFactory()', function () {

        it('should return a new AbstractValidationMiddleware', function () {

            $test = TestMiddleware::withFactory($this->factory);

            expect($test)->to->be->an->instanceof(AbstractValidationMiddleware::class);

        });

    });

    describe('->process()', function () {

        beforeEach(function () {

            $file = Mockery::mock(UploadedFileInterface::class);

            $this->post = ['key1' => 'value'];
            $this->files = ['key2' => $file];

            $this->input = ['key1' => 'value', 'key2' => $file];

            $rules = ['key1' => 'required', 'key2' => 'required'];
            $labels = ['key1' => 'field1 name', 'key2' => 'field2 name'];
            $templates = ['key1' => 'field1 template', 'key2' => 'field2 template'];

            $validator1 = Mockery::mock(Validator::class);
            $validator2 = Mockery::mock(Validator::class);

            $this->validator = Mockery::mock(Validator::class);

            $this->result = Mockery::mock(ValidationResult::class);

            $this->request->shouldReceive('getParsedBody')->once()
                ->andReturn($this->post);

            $this->request->shouldReceive('getUploadedFiles')->once()
                ->andReturn($this->files);

            $this->middleware->shouldReceive('getRules')->once()
                ->andReturn($rules);

            $this->middleware->shouldReceive('getLabels')->once()
                ->andReturn($labels);

            $this->middleware->shouldReceive('getTemplates')->once()
                ->andReturn($templates);

            $this->factory->shouldReceive('getValidator')->once()
                ->with($rules)
                ->andReturn($validator1);

            $validator1->shouldReceive('withLabels')->once()
                ->with($labels)
                ->andReturn($validator2);

            $validator2->shouldReceive('withTemplates')->once()
                ->with($templates)
                ->andReturn($this->validator);

        });

        context('when the input pass the validation', function () {

            it('should delegate the processing to the next middleware', function () {

                $this->validator->shouldReceive('validate')->once()
                    ->with($this->input)
                    ->andReturn($this->result);

                $this->result->shouldReceive('passed')->once()->andReturn(true);

                $this->handler->shouldReceive('handle')->once()
                    ->with($this->request)
                    ->andReturn($this->response);

                $test = $this->middleware->process($this->request, $this->handler);

                expect($test)->to->be->equal($this->response);

            });

        });

        context('when the input fails the validation', function () {

            it('should fail with a DataInvalidException', function () {

                $this->validator->shouldReceive('validate')->once()
                    ->with($this->input)
                    ->andReturn($this->result);

                $this->result->shouldReceive('passed')->once()->andReturn(false);

                $this->result->shouldReceive('getMessages')->once()->andReturn([
                    'failed',
                ]);

                expect([$this->middleware, 'process'])->with($this->request, $this->handler)
                    ->to->throw(DataInvalidException::class);

            });

        });

    });

});
